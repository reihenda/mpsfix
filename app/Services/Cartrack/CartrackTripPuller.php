<?php

namespace App\Services\Cartrack;

use App\Models\GpsGeofence;
use App\Models\GpsTrip;
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
        $akhir = Carbon::parse($jadwal->waktu_selesai->format('Y-m-d H:i:s'), $zona)->addHours(self::MARGIN_JAM);

        $baris = $this->client->tripsKendaraan(
            $registrasi,
            $awal->format('Y-m-d H:i:s'),
            $akhir->format('Y-m-d H:i:s')
        );

        $segmen = self::ubahKeSegmen($baris, $zona);
        if (empty($segmen)) {
            $jadwal->update(['trip_ditarik_pada' => now(), 'trip_tarik_percobaan_pada' => now(), 'trip_tarik_error' => null]);

            return ['stat' => [], 'geofence_baru' => 0, 'tertaut' => 0, 'jumlah_api' => 0];
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
            "Cartrack API {$registrasi} {$awal->format('d/m H:i')}-{$akhir->format('d/m H:i')}",
            $userId,
            ['sumber' => GpsTrip::SUMBER_CARTRACK, 'divalidasi' => false]
        );

        $tertaut = $this->tautkanKeJadwal($nopol);

        $jadwal->update([
            'trip_ditarik_pada' => now(),
            'trip_tarik_percobaan_pada' => now(),
            'trip_tarik_error' => null,
        ]);

        return ['stat' => $stat, 'geofence_baru' => $geofenceBaru, 'tertaut' => $tertaut, 'jumlah_api' => count($baris)];
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
    private function tautkanKeJadwal(string $nopol): int
    {
        $kunci = GpsTripImporter::kunciNopol($nopol);
        $trips = GpsTrip::where('nopol', $nopol)
            ->where('sumber', GpsTrip::SUMBER_CARTRACK)
            ->where('divalidasi', false)
            ->get();

        if ($trips->isEmpty()) {
            return 0;
        }

        $jadwals = JadwalPerjalanan::where('status', '!=', JadwalPerjalanan::STATUS_BATAL)
            ->where('waktu_mulai', '<', $trips->max('waktu_selesai') ?? $trips->max('waktu_mulai'))
            ->where('waktu_selesai', '>', $trips->min('waktu_mulai'))
            ->get()
            ->filter(fn (JadwalPerjalanan $j) => $j->kunci_nopol === $kunci);

        $tertaut = 0;

        foreach ($trips as $trip) {
            $mulai = $trip->waktu_mulai->getTimestamp();
            $selesai = ($trip->waktu_selesai ?? $trip->waktu_mulai)->getTimestamp();

            $terbaik = null;
            $irisanTerbaik = 0;
            foreach ($jadwals as $j) {
                $irisan = min($selesai, $j->waktu_selesai->getTimestamp()) - max($mulai, $j->waktu_mulai->getTimestamp());
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
