<?php

namespace App\Services\Cartrack;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Mencocokkan koordinat dengan wilayah (poligon) geofence Cartrack YANG BERLAKU SEKARANG.
 *
 * Cartrack menyimpan nama geofence di trip saat trip terjadi dan tidak menghitungnya ulang bila wilayah
 * geofence direvisi. Trip lama yang titik awal/akhirnya kini berada di dalam wilayah revisi tetap tanpa
 * nama. Kelas ini mengisinya dari koordinat segmen.
 */
class CartrackWilayahGeofence
{
    private const KUNCI_CACHE = 'cartrack.wilayah_geofence';

    /** Singkat supaya revisi wilayah di Cartrack cepat terbawa. */
    private const TTL_DETIK = 600;

    /** Titik yang berada sampai sejauh ini di luar tepi wilayah tetap dianggap di dalam (toleransi akurasi GPS). */
    private const TOLERANSI_METER = 50;

    /** @var array<int, array{nama:string, kunci:string, poligon:array<int, array{0:float,1:float}>, luas:float}>|null */
    private ?array $wilayah = null;

    public function __construct(private CartrackClient $client)
    {
    }

    /**
     * Samakan nama geofence tiap titik segmen dengan wilayah geofence yang berlaku sekarang:
     *  - nama kosong, tapi koordinat kini di dalam sebuah geofence -> diisi;
     *  - nama ada, geofence itu masih ada di daftar, tapi koordinat berada lebih dari TOLERANSI_METER
     *    di luar wilayahnya (label lama yang salah / wilayah sudah diperbaiki) -> diganti geofence lain
     *    yang memuat titik itu, atau dikosongkan.
     * Nama yang geofencenya sudah tidak ada di daftar, dan titik tanpa koordinat, dibiarkan.
     *
     * @param  array<int, array> $segmen format CartrackTripPuller::ubahKeSegmen()
     * @return array{0: array<int, array>, 1: int, 2: int} [segmen, jumlah diisi, jumlah dikoreksi]
     */
    public function sesuaikan(array $segmen): array
    {
        $diisi = 0;
        $dikoreksi = 0;

        foreach ($segmen as $i => $s) {
            foreach (['mulai', 'selesai'] as $sisi) {
                $lat = $s["lat_{$sisi}"] ?? null;
                $lng = $s["lng_{$sisi}"] ?? null;
                if ($lat === null || $lng === null) {
                    continue;
                }

                $nama = (string) ($s["geofence_{$sisi}"] ?? '');
                $baru = $this->cari($lat, $lng);

                if ($nama === '') {
                    if ($baru !== null) {
                        $segmen[$i]["geofence_{$sisi}"] = $baru;
                        $diisi++;
                    }
                } elseif ($this->terlaluJauh($nama, $lat, $lng)) {
                    $segmen[$i]["geofence_{$sisi}"] = $baru ?? '';
                    $dikoreksi++;
                }
            }
        }

        return [$segmen, $diisi, $dikoreksi];
    }

    /** Geofence bernama ini masih ada, tapi titik berada > TOLERANSI_METER di luar semua wilayahnya? */
    private function terlaluJauh(string $nama, float $lat, float $lng): bool
    {
        $kunci = self::kunci($nama);
        $ada = false;

        foreach ($this->daftar() as $w) {
            if ($w['kunci'] !== $kunci) {
                continue;
            }
            $ada = true;
            if (self::didalam($lat, $lng, $w['poligon']) || self::jarakKeTepiMeter($lat, $lng, $w['poligon']) <= self::TOLERANSI_METER) {
                return false;
            }
        }

        return $ada;
    }

    private static function kunci(string $nama): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $nama)));
    }

    /** Jarak (meter) dari titik ke tepi poligon terdekat, pendekatan bidang datar lokal. */
    private static function jarakKeTepiMeter(float $lat, float $lng, array $poligon): float
    {
        $kx = 111320 * cos(deg2rad($lat));
        $ky = 110540;
        $pts = array_map(fn ($p) => [($p[0] - $lng) * $kx, ($p[1] - $lat) * $ky], $poligon);

        $min = INF;
        for ($i = 0, $j = count($pts) - 1; $i < count($pts); $j = $i++) {
            [$ax, $ay] = $pts[$j];
            [$bx, $by] = $pts[$i];
            $dx = $bx - $ax;
            $dy = $by - $ay;
            $l2 = $dx * $dx + $dy * $dy;
            $t = $l2 > 0 ? max(0, min(1, (-$ax * $dx - $ay * $dy) / $l2)) : 0;
            $min = min($min, hypot($ax + $t * $dx, $ay + $t * $dy));
        }

        return $min;
    }

    /** Nama geofence terkecil yang memuat titik ini, atau null. */
    public function cari(?float $lat, ?float $lng): ?string
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        $terbaik = null;
        foreach ($this->daftar() as $w) {
            if (self::didalam($lat, $lng, $w['poligon']) && ($terbaik === null || $w['luas'] < $terbaik['luas'])) {
                $terbaik = $w;
            }
        }

        return $terbaik['nama'] ?? null;
    }

    /** @return array<int, array{nama:string, kunci:string, poligon:array, luas:float}> */
    private function daftar(): array
    {
        if ($this->wilayah !== null) {
            return $this->wilayah;
        }

        try {
            $baris = Cache::remember(self::KUNCI_CACHE, self::TTL_DETIK, fn () => $this->client->getAll('geofences', [], limit: 100, maxPages: 20));
        } catch (\Throwable $e) {
            // Tanpa daftar wilayah, tarikan tetap jalan memakai nama bawaan Cartrack.
            Log::warning('Cartrack: daftar wilayah geofence gagal diambil: '.$e->getMessage());

            return $this->wilayah = [];
        }

        $this->wilayah = [];
        foreach ($baris as $g) {
            $poligon = self::urai((string) ($g['polygon'] ?? ''));
            $nama = trim(preg_replace('/\s+/u', ' ', (string) ($g['name'] ?? '')));
            if ($nama === '' || count($poligon) < 3) {
                continue;
            }
            $this->wilayah[] = ['nama' => $nama, 'kunci' => self::kunci($nama), 'poligon' => $poligon, 'luas' => self::luas($poligon)];
        }

        return $this->wilayah;
    }

    /** "POLYGON((lng lat,lng lat,...))" -> [[lng, lat], ...] */
    public static function urai(string $wkt): array
    {
        if (!preg_match('/\(\(\s*(.+?)\s*\)\)/s', $wkt, $m)) {
            return [];
        }

        $titik = [];
        foreach (explode(',', $m[1]) as $pasang) {
            $bagian = preg_split('/\s+/', trim($pasang));
            if (count($bagian) >= 2 && is_numeric($bagian[0]) && is_numeric($bagian[1])) {
                $titik[] = [(float) $bagian[0], (float) $bagian[1]];
            }
        }

        return $titik;
    }

    /** Ray casting; poligon berformat [[lng, lat], ...]. */
    public static function didalam(float $lat, float $lng, array $poligon): bool
    {
        $dalam = false;
        for ($i = 0, $j = count($poligon) - 1; $i < count($poligon); $j = $i++) {
            [$xi, $yi] = $poligon[$i];
            [$xj, $yj] = $poligon[$j];
            if ((($yi > $lat) !== ($yj > $lat)) && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi)) {
                $dalam = !$dalam;
            }
        }

        return $dalam;
    }

    private static function luas(array $poligon): float
    {
        $l = 0.0;
        for ($i = 0, $j = count($poligon) - 1; $i < count($poligon); $j = $i++) {
            $l += ($poligon[$j][0] + $poligon[$i][0]) * ($poligon[$j][1] - $poligon[$i][1]);
        }

        return abs($l / 2);
    }
}
