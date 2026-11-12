<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GpsTrip extends Model
{
    protected $table = 'gps_trip';

    protected $fillable = [
        'gps_trip_import_id',
        'nopol',
        'sumber',
        'jadwal_perjalanan_id',
        'divalidasi',
        'divalidasi_oleh',
        'divalidasi_pada',
        'model_kendaraan',
        'waktu_mulai',
        'kunci_waktu_mulai',
        'waktu_selesai',
        'geofence_mulai',
        'geofence_selesai',
        'jarak_km',
        'durasi_jalan_detik',
        'jeda_terpanjang_detik',
        'jumlah_segmen',
        'segmen',
        'driver_gps',
        'operator_gtm_id',
        'operator_gtm_lembur_id',
        'status',
        'dihitung',
        'bukan_noise',
        'diedit',
        'catatan',
    ];

    protected $casts = [
        'waktu_mulai' => 'datetime',
        'kunci_waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
        'jarak_km' => 'float',
        'segmen' => 'array',
        'dihitung' => 'boolean',
        'bukan_noise' => 'boolean',
        'diedit' => 'boolean',
        'divalidasi' => 'boolean',
        'divalidasi_pada' => 'datetime',
    ];

    public const SUMBER_EXCEL = 'excel';
    public const SUMBER_CARTRACK = 'cartrack';

    /** Jeda antar segmen di atas ini (detik) diberi penanda "menunggu" (tetap dihitung sebagai jam kerja). */
    public const AMBANG_JEDA_DETIK = 6 * 3600;

    /**
     * Berhenti lebih lama dari ini (detik) di titik yang sama DI LUAR geofence mana pun
     * (mogok / berhenti di tengah jalan) tidak dihitung sebagai jam kerja.
     */
    public const AMBANG_BERHENTI_DETIK = 3600;

    /** Titik berhenti dan titik jalan lagi dianggap sama bila berjarak segini atau kurang (meter). */
    public const JARAK_TITIK_SAMA_METER = 150;

    /** Trip yang lamanya di bawah ini (detik) dianggap noise dari sistem GPS. */
    public const AMBANG_NOISE_DETIK = 5 * 60;

    /**
     * Trip noise: durasi (mulai -> selesai) kurang dari 5 menit dan belum ditinjau admin.
     */
    public function scopeNoise($query)
    {
        return $query->whereNotNull('waktu_selesai')
            ->where('bukan_noise', false)
            ->whereRaw('TIMESTAMPDIFF(SECOND, waktu_mulai, waktu_selesai) < ?', [self::AMBANG_NOISE_DETIK]);
    }

    public function operator()
    {
        return $this->belongsTo(OperatorGtm::class, 'operator_gtm_id');
    }

    public function jadwal()
    {
        return $this->belongsTo(JadwalPerjalanan::class, 'jadwal_perjalanan_id');
    }

    public function pengvalidasi()
    {
        return $this->belongsTo(User::class, 'divalidasi_oleh');
    }

    public function lembur()
    {
        return $this->belongsTo(OperatorGtmLembur::class, 'operator_gtm_lembur_id');
    }

    public function import()
    {
        return $this->belongsTo(GpsTripImport::class, 'gps_trip_import_id');
    }

    /**
     * Durasi trip (detik) dari titik mulai sampai titik selesai, termasuk waktu berhenti.
     */
    public function getDurasiTripDetikAttribute(): ?int
    {
        if (!$this->waktu_mulai || !$this->waktu_selesai) {
            return null;
        }

        return max(0, $this->waktu_selesai->getTimestamp() - $this->waktu_mulai->getTimestamp());
    }

    /**
     * Waktu berhenti panjang di tengah jalan: selang antara dua segmen berurutan (mesin mati) yang
     * lebih dari AMBANG_BERHENTI_DETIK, dengan segmen sebelumnya berakhir dan segmen berikutnya mulai
     * di titik yang sama dan sama-sama tanpa geofence (SPBG, customer, dll. tetap dihitung kerja).
     * "Titik yang sama" = jarak koordinat <= JARAK_TITIK_SAMA_METER bila koordinat ada (data Cartrack),
     * selain itu teks lokasi (alamat) identik. Trip yang diedit manual dipakai apa adanya.
     *
     * @return array<int, array{mulai:int, selesai:int, durasi:int, tanggal:string, jam_mulai:string, jam_selesai:string, lokasi:string, lat:?float, lng:?float, peta:?string}>
     */
    public function hentiDiJalan(): array
    {
        if ($this->diedit || empty($this->segmen)) {
            return [];
        }

        $segmen = $this->segmen;
        usort($segmen, fn ($a, $b) => strcmp($a['mulai'], $b['mulai']));

        $norm = fn ($t) => mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $t)));
        $henti = [];
        $selesai = strtotime($segmen[0]['selesai']);
        $selesaiTeks = $segmen[0]['selesai'];
        for ($i = 1; $i < count($segmen); $i++) {
            $prev = $segmen[$i - 1];
            $seg = $segmen[$i];
            $mulai = strtotime($seg['mulai']);

            if ($mulai - $selesai > self::AMBANG_BERHENTI_DETIK
                && empty($prev['geofence_selesai'])
                && empty($seg['geofence_mulai'])
                && self::titikSama($prev, $seg, $norm)) {
                $lat = $prev['lat_selesai'] ?? null;
                $lng = $prev['lng_selesai'] ?? null;
                $henti[] = [
                    'mulai' => $selesai,
                    'selesai' => $mulai,
                    'durasi' => $mulai - $selesai,
                    'tanggal' => substr($selesaiTeks, 0, 10),
                    'jam_mulai' => substr($selesaiTeks, 11, 5),
                    'jam_selesai' => substr($seg['mulai'], 11, 5),
                    'lokasi' => trim((string) ($prev['lokasi_selesai'] ?? '')),
                    'lat' => $lat,
                    'lng' => $lng,
                    'peta' => ($lat !== null && $lng !== null) ? "https://www.google.com/maps?q={$lat},{$lng}" : null,
                ];
            }
            if (strtotime($seg['selesai']) > $selesai) {
                $selesai = strtotime($seg['selesai']);
                $selesaiTeks = $seg['selesai'];
            }
        }

        return $henti;
    }

    /** Segmen sebelumnya berakhir & segmen berikutnya mulai di titik yang sama? */
    private static function titikSama(array $prev, array $seg, callable $norm): bool
    {
        $a = [$prev['lat_selesai'] ?? null, $prev['lng_selesai'] ?? null];
        $b = [$seg['lat_mulai'] ?? null, $seg['lng_mulai'] ?? null];

        if (!in_array(null, $a, true) && !in_array(null, $b, true)) {
            return self::jarakMeter($a[0], $a[1], $b[0], $b[1]) <= self::JARAK_TITIK_SAMA_METER;
        }

        $lokasi = $norm($prev['lokasi_selesai'] ?? '');

        return $lokasi !== '' && $lokasi === $norm($seg['lokasi_mulai'] ?? '');
    }

    private static function jarakMeter(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $h = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($h)));
    }

    public function getHentiDiJalanDetikAttribute(): int
    {
        return array_sum(array_map(fn ($h) => $h['selesai'] - $h['mulai'], $this->hentiDiJalan()));
    }

    public function getPerluDicekAttribute(): bool
    {
        return $this->status === 'tidak_lengkap';
    }

    /**
     * Nama geofence yang disinggahi di antara titik mulai dan titik selesai (unik, berurutan).
     */
    public function getPerhentianAttribute(): array
    {
        $skip = array_filter([$this->geofence_mulai, $this->geofence_selesai]);
        $hasil = [];

        foreach ($this->segmen ?? [] as $seg) {
            foreach ([$seg['geofence_mulai'] ?? null, $seg['geofence_selesai'] ?? null] as $nama) {
                if ($nama && !in_array($nama, $skip, true) && !in_array($nama, $hasil, true)) {
                    $hasil[] = $nama;
                }
            }
        }

        return $hasil;
    }

    /** Format menit:detik untuk durasi pendek (trip noise), mis. 0:10 atau 4:32. */
    public static function formatMenitDetik(?int $detik): string
    {
        if ($detik === null) {
            return '-';
        }

        return sprintf('%d:%02d', intdiv($detik, 60), $detik % 60);
    }

    public static function formatDurasi(?int $detik): string
    {
        if ($detik === null) {
            return '-';
        }

        $jam = intdiv($detik, 3600);
        $menit = intdiv($detik % 3600, 60);

        return sprintf('%d:%02d', $jam, $menit);
    }
}
