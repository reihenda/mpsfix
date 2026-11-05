<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GpsTrip extends Model
{
    protected $table = 'gps_trip';

    protected $fillable = [
        'gps_trip_import_id',
        'nopol',
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
    ];

    /** Jeda antar segmen di atas ini (detik) ditandai "perlu dicek". */
    public const AMBANG_JEDA_DETIK = 6 * 3600;

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

    public function getPerluDicekAttribute(): bool
    {
        return $this->status === 'tidak_lengkap'
            || $this->jeda_terpanjang_detik > self::AMBANG_JEDA_DETIK;
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
