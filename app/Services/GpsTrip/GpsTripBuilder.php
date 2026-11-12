<?php

namespace App\Services\GpsTrip;

use App\Models\GpsTrip;
use Carbon\Carbon;

/**
 * Mengubah segmen "ignition on -> off" satu kendaraan menjadi trip.
 *
 * Aturan:
 *  - Trip DIMULAI pada segmen yang geofence awalnya adalah customer/PT (batas trip).
 *  - Trip BERAKHIR pada segmen yang geofence akhirnya adalah customer/PT (tidak harus PT yang sama).
 *  - Di antaranya (SPBG, geofence kosong, dll) trip tetap berlanjut.
 *  - Segmen di luar trip (belum ada awal trip) diabaikan.
 *
 * Kasus khusus:
 *  - Ada segmen yang mulai di customer padahal trip sebelumnya masih terbuka (akhir geofence tidak
 *    terbaca) -> trip sebelumnya ditutup sebagai 'tidak_lengkap', lalu trip baru dimulai.
 *  - Trip masih terbuka di akhir laporan -> 'tidak_lengkap'.
 *  - Satu segmen pendek (< 1 km) yang mulai & berakhir di customer = gerakan dalam area, bukan trip:
 *    tetap dicatat tapi dihitung=false.
 */
class GpsTripBuilder
{
    /** Jarak minimum (km) agar trip satu-segmen customer->customer dianggap trip sungguhan */
    public const JARAK_MIN_KM = 1.0;

    /**
     * @param array    $segmen    segmen satu kendaraan (format GpsTripParser)
     * @param callable $batasTrip fn(string $namaGeofence): bool — true bila geofence = customer/PT
     */
    public function bangun(array $segmen, callable $batasTrip): array
    {
        usort($segmen, fn ($a, $b) => strcmp($a['mulai'], $b['mulai']));

        $trips = [];
        $terbuka = null;

        foreach ($segmen as $seg) {
            $mulaiDiBatas = $seg['geofence_mulai'] !== '' && $batasTrip($seg['geofence_mulai']);
            $akhirDiBatas = $seg['geofence_selesai'] !== '' && $batasTrip($seg['geofence_selesai']);

            if ($terbuka !== null && $mulaiDiBatas) {
                $trips[] = $this->tutup($terbuka, 'tidak_lengkap');
                $terbuka = null;
            }

            if ($terbuka === null) {
                if (!$mulaiDiBatas) {
                    continue;
                }
                $terbuka = [];
            }

            $terbuka[] = $seg;

            if ($akhirDiBatas) {
                $trips[] = $this->tutup($terbuka, 'selesai');
                $terbuka = null;
            }
        }

        if ($terbuka !== null) {
            $trips[] = $this->tutup($terbuka, 'tidak_lengkap');
        }

        return $trips;
    }

    private function tutup(array $segmen, string $status): array
    {
        $pertama = $segmen[0];
        $terakhir = $segmen[count($segmen) - 1];

        $jarak = round(array_sum(array_column($segmen, 'jarak_km')), 2);

        $durasiJalan = 0;
        $jedaTerpanjang = 0;
        foreach ($segmen as $i => $seg) {
            $durasiJalan += max(0, Carbon::parse($seg['selesai'])->getTimestamp() - Carbon::parse($seg['mulai'])->getTimestamp());
            if ($i > 0) {
                $jeda = Carbon::parse($seg['mulai'])->getTimestamp() - Carbon::parse($segmen[$i - 1]['selesai'])->getTimestamp();
                $jedaTerpanjang = max($jedaTerpanjang, $jeda);
            }
        }

        $driver = [];
        foreach ($segmen as $seg) {
            if ($seg['driver'] !== '' && !in_array($seg['driver'], $driver, true)) {
                $driver[] = $seg['driver'];
            }
        }

        $mikro = $status === 'selesai'
            && count($segmen) === 1
            && $jarak < self::JARAK_MIN_KM
            && $pertama['geofence_mulai'] !== ''
            && $pertama['geofence_selesai'] !== '';

        // Trip yang lamanya < 5 menit = noise dari sistem GPS: tidak dihitung dan akan
        // muncul di panel noise untuk dihapus (admin bisa mempertahankannya).
        $noise = (Carbon::parse($terakhir['selesai'])->getTimestamp() - Carbon::parse($pertama['mulai'])->getTimestamp())
            < GpsTrip::AMBANG_NOISE_DETIK;

        return [
            'waktu_mulai' => $pertama['mulai'],
            'waktu_selesai' => $terakhir['selesai'],
            'geofence_mulai' => $pertama['geofence_mulai'] ?: null,
            'geofence_selesai' => $terakhir['geofence_selesai'] ?: null,
            'jarak_km' => $jarak,
            'durasi_jalan_detik' => $durasiJalan,
            'jeda_terpanjang_detik' => max(0, $jedaTerpanjang),
            'jumlah_segmen' => count($segmen),
            'segmen' => array_map(fn ($s) => [
                'mulai' => $s['mulai'],
                'selesai' => $s['selesai'],
                'geofence_mulai' => $s['geofence_mulai'] ?: null,
                'geofence_selesai' => $s['geofence_selesai'] ?: null,
                'lokasi_mulai' => $s['lokasi_mulai'] ?: null,
                'lokasi_selesai' => $s['lokasi_selesai'] ?: null,
                'lat_mulai' => $s['lat_mulai'] ?? null,
                'lng_mulai' => $s['lng_mulai'] ?? null,
                'lat_selesai' => $s['lat_selesai'] ?? null,
                'lng_selesai' => $s['lng_selesai'] ?? null,
                'jarak_km' => $s['jarak_km'],
            ], $segmen),
            'driver_gps' => $driver ? implode(', ', $driver) : null,
            'status' => $status,
            'dihitung' => $status === 'selesai' && !$mikro && !$noise,
        ];
    }
}
