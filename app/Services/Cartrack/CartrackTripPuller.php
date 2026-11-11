<?php

namespace App\Services\Cartrack;

use App\Models\GpsGeofence;
use App\Models\GpsTrip;
use App\Models\GpsTripDibuang;
use App\Models\GpsTripImport;
use App\Models\JadwalPerjalanan;
use App\Models\User;
use App\Services\GpsTrip\GpsTripImporter;
use App\Services\GpsTrip\GpsTripParser;
use Carbon\Carbon;

/**
 * Menarik trip satu kendaraan dari Cartrack untuk sebuah jadwal, lalu memasukkannya lewat jalur yang
 * sama dengan import Excel (GpsTripBuilder + GpsTripImporter), jadi aturan trip (batas customer,
 * noise, trip mikro) identik. Trip hasil tarikan dibuat BELUM tervalidasi dan ditautkan ke jadwal.
 */
class CartrackTripPuller
{
    /** Jendela tarikan diperlebar sekian jam di kedua sisi agar trip yang melintasi batas jadwal terbaca utuh. */
    private const MARGIN_JAM = 2;

    public function __construct(
        private CartrackClient $client,
        private CartrackDirectory $direktori,
        private GpsTripImporter $importer,
    ) {
    }

    /**
     * @return array{stat: array, geofence_baru: int, tertaut: int, jumlah_api: int}
     */
    public function tarik(JadwalPerjalanan $jadwal, ?int $userId = null): array
    {
        $registrasi = $this->direktori->registrasiUntuk($jadwal->nopol);
        if ($registrasi === null) {
            throw new CartrackException("Nopol {$jadwal->nopol} tidak ditemukan di daftar kendaraan Cartrack.");
        }

        $zona = config('services.cartrack.zona_lokal', 'Asia/Jakarta');
        $awal = Carbon::parse($jadwal->waktu_mulai->format('Y-m-d H:i:s'), $zona)->subHours(self::MARGIN_JAM);
        // Jadwal yang belum selesai ditarik sampai sekarang; yang sudah selesai diberi margin di ujung akhir
        $akhir = $jadwal->waktu_selesai
            ? Carbon::parse($jadwal->waktu_selesai->format('Y-m-d H:i:s'), $zona)->addHours(self::MARGIN_JAM)
            : Carbon::now($zona);

        $hasil = $this->tarikRentang($registrasi, $awal, $akhir, $userId);

        $jadwal->update([
            'trip_ditarik_pada' => now(),
            'trip_tarik_percobaan_pada' => now(),
            'trip_tarik_error' => null,
        ]);

        return $hasil;
    }

    /**
     * Tarik trip satu kendaraan pada rentang waktu (waktu lokal) dan simpan lewat jalur import yang sama.
     * Dipakai penarikan per jadwal maupun sinkron berkala semua kendaraan. Trip yang tidak punya jadwal
     * tetap disimpan (belum tervalidasi, driver dicocokkan dari nama di Cartrack bila ada).
     *
     * @return array{stat: array, geofence_baru: int, tertaut: int, jumlah_api: int, segmen: array}
     */
    public function tarikRentang(string $registrasi, Carbon $awal, Carbon $akhir, ?int $userId = null): array
    {
        $zona = config('services.cartrack.zona_lokal', 'Asia/Jakarta');

        $baris = $this->client->tripsKendaraan(
            $registrasi,
            $awal->format('Y-m-d H:i:s'),
            $akhir->format('Y-m-d H:i:s')
        );

        $segmen = self::ubahKeSegmen($baris, $zona);
        if (empty($segmen)) {
            return ['stat' => [], 'geofence_baru' => 0, 'tertaut' => 0, 'jumlah_api' => 0, 'segmen' => []];
        }

        $geofenceBaru = $this->daftarkanGeofenceBaru($segmen);

        $nopol = strtoupper(preg_replace('/\s+/', '', $registrasi));
        $tanggal = array_map(fn ($s) => substr($s['mulai'], 0, 10), $segmen);
        $parsed = [
            'periode' => [min($tanggal), max($tanggal)],
            'kendaraan' => [$nopol => ['model' => null, 'segmen' => $segmen]],
        ];

        $stat = $this->importer->import(
            $parsed,
            'Cartrack sinkron',
            $userId,
            ['sumber' => GpsTrip::SUMBER_CARTRACK, 'divalidasi' => false],
            $this->importHarian($zona)
        );

        $tertaut = $this->tautkanKeJadwal($nopol, $awal);
        $stat['noise_dibuang'] = $this->buangNoiseTanpaJadwal($nopol, $awal);

        return ['stat' => $stat, 'geofence_baru' => $geofenceBaru, 'tertaut' => $tertaut, 'jumlah_api' => count($baris), 'segmen' => $segmen];
    }

    /**
     * Satu baris riwayat import per hari untuk semua tarikan Cartrack. Sinkron berjalan tiap 15 menit untuk
     * 20-an kendaraan; satu baris per tarikan akan menumpuk ribuan baris per hari.
     */
    private function importHarian(string $zona): int
    {
        $hari = Carbon::now($zona)->toDateString();

        return GpsTripImport::firstOrCreate(
            ['nama_file' => "Cartrack sinkron {$hari}"],
            ['periode_mulai' => $hari, 'periode_selesai' => $hari]
        )->id;
    }

    /**
     * Trip noise (kurang dari 5 menit) hasil Cartrack yang tidak punya jadwal tidak berguna dan hanya
     * memenuhi panel noise: dibuang dan dicatat agar tidak dibuat lagi. Noise yang tertaut jadwal, sudah
     * diberi driver, diedit, atau sudah masuk lembur dibiarkan untuk ditinjau admin.
     */
    private function buangNoiseTanpaJadwal(string $nopol, Carbon $sejak): int
    {
        $noise = GpsTrip::noise()
            ->where('nopol', $nopol)
            ->where('sumber', GpsTrip::SUMBER_CARTRACK)
            // Hanya yang sudah selesai: trip yang baru mulai dan masih berjalan sementara tampak pendek,
            // jangan sampai dibuang lalu tak pernah dibuat utuh
            ->where('status', 'selesai')
            ->where('divalidasi', false)
            ->where('diedit', false)
            ->whereNull('jadwal_perjalanan_id')
            ->whereNull('operator_gtm_id')
            ->whereNull('operator_gtm_lembur_id')
            ->where('waktu_mulai', '>=', $sejak->format('Y-m-d H:i:s'))
            ->get();

        foreach ($noise as $trip) {
            GpsTripDibuang::firstOrCreate([
                'nopol' => $trip->nopol,
                'waktu_mulai' => ($trip->kunci_waktu_mulai ?? $trip->waktu_mulai)->format('Y-m-d H:i:s'),
            ]);
            $trip->delete();
        }

        return $noise->count();
    }

    /**
     * Tujuan mana saja pada rute yang sudah tercapai, dilihat dari geofence yang dilewati kendaraan.
     *
     * Tujuan dicocokkan BERURUTAN: tiap geofence yang dilewati (awal lalu akhir tiap segmen, sejak jadwal
     * mulai) hanya dipakai untuk tujuan berikutnya yang belum tercapai. Jadi rute "SPBG, Customer A, SPBG"
     * baru tuntas setelah kendaraan kembali ke SPBG, bukan begitu keluar dari SPBG.
     *
     * @param array<int, array{id:int, tujuan:string}> $rute berurutan sesuai rute
     * @param array<int, array>                        $segmen format ubahKeSegmen()
     * @return array<int, string> id tujuan => waktu tercapai (Y-m-d H:i:s) untuk yang sudah tercapai
     */
    public static function hitungRute(array $rute, array $segmen, string $waktuMulai): array
    {
        usort($segmen, fn ($a, $b) => strcmp($a['mulai'], $b['mulai']));

        $kejadian = [];
        foreach ($segmen as $s) {
            if ($s['selesai'] < $waktuMulai) {
                continue; // perjalanan sebelum jadwal dimulai
            }
            if ($s['geofence_mulai'] !== '') {
                $kejadian[] = [GpsTripParser::normalisasiNama($s['geofence_mulai']), $s['mulai']];
            }
            if ($s['geofence_selesai'] !== '') {
                $kejadian[] = [GpsTripParser::normalisasiNama($s['geofence_selesai']), $s['selesai']];
            }
        }

        $hasil = [];
        $i = 0;
        foreach ($kejadian as [$nama, $waktu]) {
            if (!isset($rute[$i])) {
                break;
            }
            $tujuan = GpsTripParser::normalisasiNama($rute[$i]['tujuan']);
            if ($tujuan !== '' && ($nama === $tujuan || str_contains($nama, $tujuan) || str_contains($tujuan, $nama))) {
                $hasil[$rute[$i]['id']] = $waktu;
                $i++;
            }
        }

        return $hasil;
    }

    /**
     * Baris trip Cartrack -> segmen (format yang sama dengan GpsTripParser).
     * Timestamp Cartrack membawa offset ("2026-11-02 08:50:14+07"); disimpan sebagai waktu lokal tanpa zona.
     *
     * @param array<int, array> $baris
     */
    public static function ubahKeSegmen(array $baris, string $zona = 'Asia/Jakarta'): array
    {
        $segmen = [];

        foreach ($baris as $r) {
            if (empty($r['start_timestamp']) || empty($r['end_timestamp'])) {
                continue; // trip masih berjalan / tidak lengkap
            }

            $driver = trim(($r['driver_name'] ?? '').' '.($r['driver_surname'] ?? ''));

            $segmen[] = [
                'driver' => $driver,
                'mulai' => Carbon::parse($r['start_timestamp'])->setTimezone($zona)->format('Y-m-d H:i:s'),
                'selesai' => Carbon::parse($r['end_timestamp'])->setTimezone($zona)->format('Y-m-d H:i:s'),
                'lokasi_mulai' => trim((string) ($r['start_location'] ?? '')),
                'lokasi_selesai' => trim((string) ($r['end_location'] ?? '')),
                'geofence_mulai' => self::namaGeofence($r['start_geofence_name'] ?? null),
                'geofence_selesai' => self::namaGeofence($r['end_geofence_name'] ?? null),
                'jarak_km' => round(((float) ($r['trip_distance'] ?? 0)) / 1000, 2),
            ];
        }

        usort($segmen, fn ($a, $b) => strcmp($a['mulai'], $b['mulai']));

        return $segmen;
    }

    private static function namaGeofence($nilai): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) ($nilai ?? '')));
    }

    /**
     * Geofence yang belum pernah dilihat didaftarkan dengan tebakan tipe, supaya tidak ada tipe
     * "tidak dikenal" saat trip dibangun. Admin bisa mengoreksi di halaman Geofence.
     */
    private function daftarkanGeofenceBaru(array $segmen): int
    {
        $dikenal = GpsGeofence::pluck('nama')->map(fn ($n) => GpsTripParser::normalisasiNama($n))->flip();
        $customers = null;
        $baru = 0;

        foreach ($segmen as $s) {
            foreach (['geofence_mulai', 'geofence_selesai'] as $f) {
                $nama = $s[$f];
                if ($nama === '' || isset($dikenal[GpsTripParser::normalisasiNama($nama)])) {
                    continue;
                }

                $customers ??= User::whereIn('role', [User::ROLE_CUSTOMER, User::ROLE_MMBTU])->get(['id', 'name']);
                $tipe = GpsTripImporter::sarankanTipe($nama);

                GpsGeofence::firstOrCreate(['nama' => $nama], [
                    'tipe' => $tipe,
                    'customer_id' => $tipe === GpsGeofence::TIPE_CUSTOMER
                        ? GpsTripImporter::sarankanCustomer($nama, $customers)
                        : null,
                ]);
                $dikenal[GpsTripParser::normalisasiNama($nama)] = true;
                $baru++;
            }
        }

        return $baru;
    }

    /**
     * Tautkan trip kendaraan ini yang belum tervalidasi ke jadwal dengan irisan waktu terbesar,
     * dan isi driver trip dari jadwal (sumber kebenaran: kita yang menugaskan, bukan tebakan nama GPS).
     * Trip yang sudah tervalidasi atau sudah diedit manual tidak diubah drivernya.
     */
    private function tautkanKeJadwal(string $nopol, Carbon $sejak): int
    {
        $kunci = GpsTripImporter::kunciNopol($nopol);
        // Hanya trip yang menyentuh rentang tarikan; trip lama tidak perlu dievaluasi ulang tiap sinkron
        $trips = GpsTrip::where('nopol', $nopol)
            ->where('sumber', GpsTrip::SUMBER_CARTRACK)
            ->where('divalidasi', false)
            ->where(fn ($q) => $q->whereNull('waktu_selesai')->orWhere('waktu_selesai', '>=', $sejak->format('Y-m-d H:i:s')))
            ->get();

        if ($trips->isEmpty()) {
            return 0;
        }

        // Terbaru dulu: bila irisan sama besar, trip masuk ke jadwal yang mulai paling akhir
        $jadwals = JadwalPerjalanan::where('status', '!=', JadwalPerjalanan::STATUS_BATAL)
            ->where('waktu_mulai', '<', $trips->max('waktu_selesai') ?? $trips->max('waktu_mulai'))
            ->where(fn ($q) => $q->whereNull('waktu_selesai')->orWhere('waktu_selesai', '>', $trips->min('waktu_mulai')))
            ->orderByDesc('waktu_mulai')
            ->get()
            ->filter(fn (JadwalPerjalanan $j) => $j->kunci_nopol === $kunci);

        $tertaut = 0;

        foreach ($trips as $trip) {
            $mulai = $trip->waktu_mulai->getTimestamp();
            $selesai = ($trip->waktu_selesai ?? $trip->waktu_mulai)->getTimestamp();

            $terbaik = null;
            $irisanTerbaik = 0;
            foreach ($jadwals as $j) {
                // Jadwal yang belum selesai tidak punya batas akhir
                $irisan = min($selesai, $j->waktu_selesai?->getTimestamp() ?? PHP_INT_MAX) - max($mulai, $j->waktu_mulai->getTimestamp());
                if ($irisan > $irisanTerbaik) {
                    $irisanTerbaik = $irisan;
                    $terbaik = $j;
                }
            }

            if (!$terbaik) {
                continue;
            }

            $ubah = ['jadwal_perjalanan_id' => $terbaik->id];
            if (!$trip->diedit) {
                $ubah['operator_gtm_id'] = $terbaik->operator_gtm_id;
            }
            $trip->update($ubah);
            $tertaut++;
        }

        return $tertaut;
    }
}
