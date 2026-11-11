<?php

namespace App\Services\Cartrack;

use App\Models\GpsSinkronPeriode;
use App\Services\GpsTrip\PeriodeLembur;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Sinkron SEMUA kendaraan di Cartrack ke halaman Trip GPS, tanpa menunggu ada jadwal perjalanan.
 * Trip yang ditemukan lewat jalur yang sama dengan import Excel; yang cocok dengan jadwal otomatis
 * ditautkan (driver dari jadwal), sisanya masuk dengan driver kosong untuk diisi admin.
 *
 * Dua jenis tarikan, keduanya aman diulang pada rentang yang tumpang tindih (kunci trip = nopol + waktu mulai):
 *  - ringan  : beberapa hari terakhir, tiap 15 menit
 *  - periode : seluruh periode lembur 26-25, sekali sehari untuk periode berjalan atau atas permintaan
 */
class CartrackSinkronService
{
    public const KUNCI_HASIL = 'cartrack:sinkron_trip_terakhir';
    public const KUNCI_DIMINTA = 'cartrack:sinkron_trip_diminta';

    /** Selang tarikan ringan (menit). */
    public const SELANG_MENIT = 15;

    /** Periode berjalan ditarik penuh ulang paling cepat sekian jam sekali (jaring pengaman). */
    public const SELANG_PERIODE_JAM = 24;

    /** Cartrack membatasi satu permintaan maksimal 31 hari; rentang lebih panjang dipecah per sekian hari. */
    private const POTONG_HARI = 30;

    public function __construct(
        private CartrackDirectory $direktori,
        private CartrackTripPuller $puller,
    ) {
    }

    /**
     * Tarikan ringan: sekian hari ke belakang (termasuk hari ini), 1 = hanya hari ini.
     *
     * @return array{kendaraan:int, baru:int, diperbarui:int, tertaut:int, noise_dibuang:int, geofence_baru:int, galat:array<string,string>}
     */
    public function sinkron(int $hari = 3, ?int $userId = null, ?string $nopol = null): array
    {
        $zona = config('services.cartrack.zona_lokal', 'Asia/Jakarta');
        $sekarang = Carbon::now($zona);
        $awal = $sekarang->copy()->subDays(max(1, $hari) - 1)->startOfDay();

        $hasil = $this->jalankan($awal, $sekarang, $userId, $nopol);
        $this->simpanHasil($hasil, "{$hari} hari terakhir");

        return $hasil;
    }

    /**
     * Tarik penuh satu periode lembur (26 bulan lalu s/d 25 bulan berlabel; periode yang belum berakhir
     * dipotong sampai sekarang). Dicatat sebagai "sudah ditarik" hanya bila semua kendaraan berhasil.
     */
    public function sinkronPeriode(string $bulan, ?int $userId = null): array
    {
        [$awal, $akhir] = PeriodeLembur::rentang($bulan);
        $zona = config('services.cartrack.zona_lokal', 'Asia/Jakarta');
        $awal = Carbon::parse($awal->format('Y-m-d H:i:s'), $zona);
        $sekarang = Carbon::now($zona);
        $ujung = Carbon::parse($akhir->format('Y-m-d H:i:s'), $zona);
        $sampai = $ujung->lessThan($sekarang) ? $ujung : $sekarang;

        $hasil = $this->jalankan($awal, $sampai, $userId, null);

        if (! $hasil['galat'] && $hasil['kendaraan'] > 0) {
            GpsSinkronPeriode::updateOrCreate(
                ['periode' => $bulan],
                // Semua waktu di tabel ini disimpan sebagai waktu lokal tanpa zona (APP_TIMEZONE bisa UTC)
                ['awal' => $awal->format('Y-m-d H:i:s'), 'akhir' => $ujung->format('Y-m-d H:i:s'), 'terakhir_penuh_pada' => $sekarang->format('Y-m-d H:i:s')]
            );
        }
        $this->simpanHasil($hasil, 'periode '.PeriodeLembur::label($bulan));

        return $hasil;
    }

    private function jalankan(Carbon $awalSemua, Carbon $sekarang, ?int $userId, ?string $nopol): array
    {
        $hasil = ['kendaraan' => 0, 'baru' => 0, 'diperbarui' => 0, 'tertaut' => 0, 'noise_dibuang' => 0, 'geofence_baru' => 0, 'galat' => []];

        try {
            $daftar = array_column($this->direktori->kendaraan(), 'registration');
        } catch (\Throwable $e) {
            $hasil['galat']['(daftar kendaraan)'] = $e->getMessage();

            return $hasil;
        }

        $kunciFilter = $nopol ? strtoupper(preg_replace('/\s+/', '', $nopol)) : null;

        foreach ($daftar as $registrasi) {
            $registrasi = trim((string) $registrasi);
            if ($registrasi === '') {
                continue;
            }
            if ($kunciFilter && strtoupper(preg_replace('/\s+/', '', $registrasi)) !== $kunciFilter) {
                continue;
            }

            try {
                foreach ($this->potong($awalSemua, $sekarang) as [$awal, $akhir]) {
                    $r = $this->puller->tarikRentang($registrasi, $awal, $akhir, $userId);
                    $hasil['baru'] += $r['stat']['baru'] ?? 0;
                    $hasil['diperbarui'] += $r['stat']['diperbarui'] ?? 0;
                    $hasil['noise_dibuang'] += $r['stat']['noise_dibuang'] ?? 0;
                    $hasil['tertaut'] += $r['tertaut'];
                    $hasil['geofence_baru'] += $r['geofence_baru'];
                }
                $hasil['kendaraan']++;
            } catch (\Throwable $e) {
                // Satu kendaraan gagal tidak menghentikan yang lain
                $hasil['galat'][$registrasi] = $e->getMessage();
                Log::warning("Sinkron Cartrack gagal untuk {$registrasi}: ".$e->getMessage());
            }
        }

        return $hasil;
    }

    // ---- Status & permintaan -------------------------------------------------------------------

    /** @return array{waktu:string, cakupan:string, kendaraan:int, baru:int, diperbarui:int, tertaut:int, noise_dibuang:int, galat:array<string,string>}|null */
    public static function hasilTerakhir(): ?array
    {
        $nilai = Cache::get(self::KUNCI_HASIL);

        return is_array($nilai) ? $nilai : null;
    }

    /**
     * Minta tarik penuh satu periode segera. Satu putaran 20-an kendaraan bisa memakan waktu semenit lebih,
     * terlalu lama untuk request web, jadi permintaan hanya dicatat dan dikerjakan penjadwal pada menit berikutnya.
     */
    public static function mintaPeriode(string $bulan): void
    {
        Cache::put(self::KUNCI_DIMINTA, $bulan, now()->addHour());
    }

    /** Periode (YYYY-MM) yang diminta dan belum dikerjakan, tanpa mengambilnya. */
    public static function permintaanMenunggu(): ?string
    {
        $nilai = Cache::get(self::KUNCI_DIMINTA);

        return is_string($nilai) && PeriodeLembur::valid($nilai) ? $nilai : null;
    }

    /**
     * Dipanggil penjadwal tiap menit: tarikan apa yang harus jalan sekarang.
     *  1. permintaan manual dari halaman Trip GPS (periode tertentu)
     *  2. periode berjalan belum pernah / sudah lama tidak ditarik penuh
     *  3. periode yang baru berakhir belum ditarik penuh SETELAH berakhir (menangkap hari-hari terakhirnya)
     *  4. tarikan ringan tiap SELANG_MENIT
     *
     * @return array{jenis:'periode', bulan:string}|array{jenis:'ringan'}|null
     */
    public static function rencana(): ?array
    {
        $diminta = Cache::pull(self::KUNCI_DIMINTA);
        if (is_string($diminta) && PeriodeLembur::valid($diminta)) {
            return ['jenis' => 'periode', 'bulan' => $diminta];
        }

        // Tarikan otomatis (bukan permintaan manual) tidak lebih sering dari SELANG_MENIT, termasuk setelah gagal,
        // supaya galat tidak membuat API dipanggil tiap menit
        $terakhir = self::hasilTerakhir();
        if ($terakhir && Carbon::parse($terakhir['waktu'])->diffInMinutes(now()) < self::SELANG_MENIT) {
            return null;
        }

        // Waktu di tabel periode disimpan lokal tanpa zona: bandingkan sebagai teks Y-m-d H:i:s
        $zona = config('services.cartrack.zona_lokal', 'Asia/Jakarta');
        $sekarang = Carbon::now($zona);
        $bulanIni = PeriodeLembur::bulanUntuk($sekarang);

        $catatIni = GpsSinkronPeriode::where('periode', $bulanIni)->first();
        $batasLama = $sekarang->copy()->subHours(self::SELANG_PERIODE_JAM)->format('Y-m-d H:i:s');
        if (! $catatIni || $catatIni->getRawOriginal('terakhir_penuh_pada') < $batasLama) {
            return ['jenis' => 'periode', 'bulan' => $bulanIni];
        }

        // Periode sebelumnya: hanya bila sudah pernah dilacak, lalu dipastikan ada tarikan penuh sesudah periode itu berakhir
        $bulanLalu = Carbon::createFromFormat('!Y-m', $bulanIni)->subMonthNoOverflow()->format('Y-m');
        $catatLalu = GpsSinkronPeriode::where('periode', $bulanLalu)->first();
        if ($catatLalu && $catatLalu->getRawOriginal('terakhir_penuh_pada') < $catatLalu->getRawOriginal('akhir')) {
            return ['jenis' => 'periode', 'bulan' => $bulanLalu];
        }

        return ['jenis' => 'ringan'];
    }

    private function simpanHasil(array $hasil, string $cakupan): void
    {
        try {
            Cache::forever(self::KUNCI_HASIL, [
                'waktu' => now()->toIso8601String(),
                'cakupan' => $cakupan,
                'kendaraan' => $hasil['kendaraan'],
                'baru' => $hasil['baru'],
                'diperbarui' => $hasil['diperbarui'],
                'tertaut' => $hasil['tertaut'],
                'noise_dibuang' => $hasil['noise_dibuang'],
                'galat' => $hasil['galat'],
            ]);
        } catch (\Throwable $e) {
            // Catatan status tidak boleh menggagalkan sinkron
        }
    }

    /**
     * Pecah rentang panjang jadi potongan <= 30 hari yang saling tumpang tindih 1 hari, supaya trip yang
     * melintasi batas potongan tetap terbaca utuh di salah satunya.
     *
     * @return array<int, array{0:Carbon, 1:Carbon}>
     */
    private function potong(Carbon $awal, Carbon $akhir): array
    {
        $potongan = [];
        $mulai = $awal->copy();

        while (true) {
            $ujung = $mulai->copy()->addDays(self::POTONG_HARI);
            if ($ujung >= $akhir) {
                $potongan[] = [$mulai, $akhir->copy()];
                break;
            }
            $potongan[] = [$mulai, $ujung];
            $mulai = $ujung->copy()->subDay();
        }

        return $potongan;
    }
}
