<?php

namespace App\Services\Cartrack;

use App\Models\JadwalPerjalanan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Mesin status jadwal perjalanan <-> Cartrack.
 *
 *   terjadwal --(waktu mulai tiba)--> berjalan   : driver di-link ke kendaraan di Cartrack
 *   berjalan  --(tujuan terakhir rute tercapai + grace)--> selesai : driver dilepas (bila tidak ada jadwal aktif lain)
 *   selesai   --> trip ditarik dari Cartrack      : trip masuk daftar validasi
 *
 * Jadwal tidak punya waktu selesai yang ditentukan di muka. Selama berjalan, rute dicek ke Cartrack
 * (paling sering tiap `rute_cek_menit`): tujuan dianggap tercapai bila kendaraan melewati geofence-nya,
 * berurutan. Begitu tujuan terakhir tercapai, waktu itu menjadi waktu selesai. Rute yang tak kunjung
 * terdeteksi ditutup paksa setelah `batas_berjalan_jam`, atau bisa diselesaikan manual.
 *
 * Dipanggil tiap menit oleh `cartrack:jalankan-jadwal`, dan sekali saat jadwal dibuat/diubah
 * agar jadwal yang waktunya sudah lewat langsung diproses.
 */
class JadwalPerjalananService
{
    /** Jarak minimum antar percobaan tarik trip yang gagal (menit). */
    private const JEDA_ULANG_TARIK_MENIT = 10;

    public function __construct(
        private CartrackClient $client,
        private CartrackDirectory $direktori,
        private CartrackTripPuller $puller,
    ) {
    }

    /** Waktu sekarang sebagai waktu lokal "telanjang" (sama dengan cara jadwal & trip disimpan). */
    public static function sekarangLokal(): Carbon
    {
        $zona = config('services.cartrack.zona_lokal', 'Asia/Jakarta');

        return Carbon::parse(Carbon::now($zona)->format('Y-m-d H:i:s'));
    }

    /**
     * Proses semua jadwal yang perlu tindakan.
     *
     * @return array{diproses:int, galat:int}
     */
    public function jalankan(?Carbon $sekarang = null): array
    {
        $sekarang ??= self::sekarangLokal();
        $urutan = [JadwalPerjalanan::STATUS_BERJALAN => 0, JadwalPerjalanan::STATUS_SELESAI => 1, JadwalPerjalanan::STATUS_TERJADWAL => 2];

        $jadwals = JadwalPerjalanan::with('operator')
            ->where(function ($q) {
                $q->whereIn('status', [JadwalPerjalanan::STATUS_TERJADWAL, JadwalPerjalanan::STATUS_BERJALAN])
                    ->orWhere(fn ($q) => $q->where('status', JadwalPerjalanan::STATUS_SELESAI)->whereNull('trip_ditarik_pada'));
            })
            ->get()
            ->sortBy(fn ($j) => $urutan[$j->status] ?? 9);

        $hasil = ['diproses' => 0, 'galat' => 0];
        foreach ($jadwals as $j) {
            try {
                $this->proses($j, $sekarang);
                $hasil['diproses']++;
            } catch (Throwable $e) {
                $hasil['galat']++;
                Log::error("Jadwal perjalanan #{$j->id} gagal diproses: ".$e->getMessage());
            }
        }

        return $hasil;
    }

    /**
     * Satu putaran mesin status untuk satu jadwal.
     */
    public function proses(JadwalPerjalanan $j, ?Carbon $sekarang = null): void
    {
        $sekarang ??= self::sekarangLokal();
        $grace = (int) config('services.cartrack.selesai_grace_menit', 30);
        $lead = (int) config('services.cartrack.link_lead_menit', 0);
        $j->refresh();

        if ($j->status === JadwalPerjalanan::STATUS_TERJADWAL) {
            if ($j->link_status === 'menunggu' && $j->waktu_mulai->copy()->subMinutes($lead)->lte($sekarang)) {
                // Jadwal susulan (dibuat setelah mobil berangkat, atau sistem sempat mati): cek dulu apakah
                // rutenya sudah tuntas. Bila ya, tidak ada driver yang perlu di-link.
                $susulan = $j->waktu_mulai->copy()->addMinutes($grace)->lte($sekarang);
                if ($susulan && $this->periksaRute($j, true)) {
                    $j->update(['status' => JadwalPerjalanan::STATUS_SELESAI, 'link_status' => 'dilewati']);
                } else {
                    $this->kirimLink($j);
                }
            }
        } elseif ($j->status === JadwalPerjalanan::STATUS_BERJALAN) {
            if ($j->waktu_selesai === null) {
                $batasJam = (int) config('services.cartrack.batas_berjalan_jam', 48);
                if ($batasJam > 0 && $j->waktu_mulai->copy()->addHours($batasJam)->lte($sekarang)) {
                    $this->akhiri($j); // rute tak kunjung terdeteksi: tutup paksa agar driver tidak ter-link selamanya
                } else {
                    $this->periksaRute($j);
                }
            }

            $j->refresh();
            if ($j->status === JadwalPerjalanan::STATUS_BERJALAN
                && $j->waktu_selesai?->copy()->addMinutes($grace)->lte($sekarang)) {
                $this->akhiri($j);
            }
        }

        $j->refresh();
        if ($j->status === JadwalPerjalanan::STATUS_SELESAI && $j->trip_ditarik_pada === null) {
            $sudahLama = $j->trip_tarik_percobaan_pada === null
                || $j->trip_tarik_percobaan_pada->copy()->addMinutes(self::JEDA_ULANG_TARIK_MENIT)->lte(now());
            $siap = $j->waktu_selesai === null
                || $j->waktu_selesai->copy()->addMinutes($grace)->lte($sekarang)
                || $j->updated_at->gte(now()->subMinute()); // diselesaikan manual: tarik langsung

            if ($sudahLama && $siap) {
                try {
                    $this->tarikTrip($j);
                } catch (CartrackException $e) {
                    // sudah dicatat di trip_tarik_error; dicoba lagi otomatis nanti
                }
            }
        }
    }

    /**
     * Link driver ke kendaraan di Cartrack. Kegagalan sementara dicoba lagi tiap menit sampai
     * MAKS_PERCOBAAN_LINK; kegagalan permanen (data belum lengkap / ditolak Cartrack) langsung "gagal".
     */
    public function kirimLink(JadwalPerjalanan $j): void
    {
        if (!config('services.cartrack.push_enabled')) {
            $j->update(['status' => JadwalPerjalanan::STATUS_BERJALAN, 'link_status' => 'dilewati']);

            return;
        }

        $registrasi = $this->direktori->registrasiUntuk($j->nopol);
        $driverId = $j->operator?->cartrack_driver_id;

        $galatPermanen = match (true) {
            $registrasi === null => "Nopol {$j->nopol} tidak ditemukan di kendaraan Cartrack.",
            !$driverId => 'Driver '.($j->operator?->nama ?? '?').' belum dipetakan ke driver Cartrack (menu Pemetaan Cartrack).',
            default => null,
        };

        if ($galatPermanen) {
            $j->update([
                'link_status' => 'gagal',
                'link_percobaan' => $j->link_percobaan + 1,
                'link_error' => $galatPermanen,
            ]);

            return;
        }

        try {
            $this->client->linkDriver($registrasi, $driverId);
        } catch (CartrackException $e) {
            $percobaan = $j->link_percobaan + 1;
            $permanen = ($e->status !== null && $e->status >= 400 && $e->status < 500 && $e->status !== 429)
                || $percobaan >= JadwalPerjalanan::MAKS_PERCOBAAN_LINK;

            $j->update([
                'link_status' => $permanen ? 'gagal' : 'menunggu',
                'link_percobaan' => $percobaan,
                'link_error' => $e->getMessage(),
            ]);

            return;
        }

        $j->update([
            'status' => JadwalPerjalanan::STATUS_BERJALAN,
            'link_status' => 'terkirim',
            'link_percobaan' => $j->link_percobaan + 1,
            'link_dikirim_pada' => now(),
            'link_error' => null,
        ]);
    }

    /** Kirim ulang link yang gagal (tombol manual). Bila waktu mulai belum tiba, tetap menunggu jadwalnya. */
    public function ulangiLink(JadwalPerjalanan $j): void
    {
        $j->update(['link_status' => 'menunggu', 'link_percobaan' => 0, 'link_error' => null]);
        $this->proses($j);
    }

    /**
     * Tutup jadwal: lepas driver dari kendaraan (kecuali ada jadwal aktif lain yang memakai kendaraan
     * atau driver yang sama, supaya link jadwal berikutnya tidak ikut terhapus), lalu tandai selesai.
     */
    public function akhiri(JadwalPerjalanan $j): void
    {
        $this->lepasBilaAman($j);
        $j->update([
            'status' => JadwalPerjalanan::STATUS_SELESAI,
            // selesai karena rute tuntas: waktunya sudah terisi; selesai manual / tutup paksa: sekarang
            'waktu_selesai' => $j->waktu_selesai ?? self::sekarangLokal(),
            // tarikan terakhir harus terjadi sesudah selesai, jadi tarikan selama berjalan tidak dihitung
            // (dan percobaan terakhir direset supaya tarikan akhir tidak menunggu jeda ulang)
            'trip_ditarik_pada' => null,
            'trip_tarik_percobaan_pada' => null,
        ]);
    }

    public function selesaikanSekarang(JadwalPerjalanan $j): void
    {
        $this->akhiri($j);
        $this->proses($j);
    }

    public function batalkan(JadwalPerjalanan $j): void
    {
        $this->lepasBilaAman($j);
        $j->update(['status' => JadwalPerjalanan::STATUS_BATAL]);
    }

    /**
     * Tarik trip dari Cartrack untuk jadwal ini, lalu catat tujuan mana saja yang sudah tercapai.
     * Galat disimpan di jadwal dan dilempar ulang.
     */
    public function tarikTrip(JadwalPerjalanan $j, ?int $userId = null): array
    {
        try {
            $hasil = $this->puller->tarik($j, $userId);
            $this->catatRute($j, $hasil['segmen']);

            return $hasil;
        } catch (Throwable $e) {
            $j->update(['trip_tarik_percobaan_pada' => now(), 'trip_tarik_error' => mb_substr($e->getMessage(), 0, 500)]);

            throw $e instanceof CartrackException ? $e : new CartrackException($e->getMessage());
        }
    }

    /**
     * Cek rute ke Cartrack (jarang-jarang, sesuai rute_cek_menit). True bila semua tujuan sudah tercapai.
     * Gagal menghubungi Cartrack tidak dianggap tuntas; dicoba lagi pada putaran berikutnya.
     */
    private function periksaRute(JadwalPerjalanan $j, bool $paksa = false): bool
    {
        $jeda = max(1, (int) config('services.cartrack.rute_cek_menit', 10));
        if (!$paksa && $j->rute_dicek_pada && $j->rute_dicek_pada->copy()->addMinutes($jeda)->gt(now())) {
            return false;
        }

        $j->update(['rute_dicek_pada' => now()]);

        try {
            $this->tarikTrip($j);
        } catch (CartrackException) {
            return false;
        }

        return $j->refresh()->waktu_selesai !== null;
    }

    /**
     * Simpan kapan tiap tujuan tercapai. Begitu tujuan terakhir tercapai, waktunya menjadi waktu selesai
     * jadwal (kecuali jadwal sudah punya waktu selesai). Tujuan yang sudah tercatat tidak dihapus.
     *
     * @param array<int, array> $segmen
     */
    public function catatRute(JadwalPerjalanan $j, array $segmen): void
    {
        $rute = $j->rute()->get();
        if ($rute->isEmpty()) {
            return;
        }

        $tercapai = CartrackTripPuller::hitungRute(
            $rute->map(fn ($r) => ['id' => $r->id, 'tujuan' => $r->tujuan])->all(),
            $segmen,
            $j->waktu_mulai->format('Y-m-d H:i:s')
        );

        foreach ($rute as $r) {
            if (isset($tercapai[$r->id]) && $r->tercapai_pada === null) {
                $r->update(['tercapai_pada' => $tercapai[$r->id]]);
            }
        }

        if ($j->waktu_selesai === null && $rute->every(fn ($r) => $r->tercapai_pada !== null)) {
            $j->update(['waktu_selesai' => $rute->last()->tercapai_pada]);
        }
    }

    private function lepasBilaAman(JadwalPerjalanan $j): void
    {
        if ($j->status !== JadwalPerjalanan::STATUS_BERJALAN || $j->link_status !== 'terkirim' || $j->unlink_pada) {
            return;
        }

        $adaLain = JadwalPerjalanan::where('id', '!=', $j->id)
            ->where('status', JadwalPerjalanan::STATUS_BERJALAN)
            ->get()
            ->contains(fn (JadwalPerjalanan $x) => $x->operator_gtm_id === $j->operator_gtm_id || $x->kunci_nopol === $j->kunci_nopol);

        if ($adaLain) {
            return;
        }

        $registrasi = $this->direktori->registrasiUntuk($j->nopol);
        $driverId = $j->operator?->cartrack_driver_id;
        if (!$registrasi || !$driverId) {
            return;
        }

        try {
            $this->client->unlinkDriver($registrasi, $driverId);
            $j->update(['unlink_pada' => now()]);
        } catch (CartrackException $e) {
            Log::warning("Unlink driver jadwal #{$j->id} gagal: ".$e->getMessage());
            $j->update(['link_error' => 'Gagal melepas driver di Cartrack: '.$e->getMessage()]);
        }
    }
}
