<?php

namespace App\Models;

use App\Services\GpsTrip\GpsTripImporter;
use Illuminate\Database\Eloquent\Model;

class JadwalPerjalanan extends Model
{
    protected $table = 'jadwal_perjalanan';

    protected $fillable = [
        'nopol',
        'operator_gtm_id',
        'waktu_mulai',
        'waktu_selesai',
        'catatan',
        'status',
        'link_status',
        'link_percobaan',
        'link_dikirim_pada',
        'link_error',
        'unlink_pada',
        'trip_ditarik_pada',
        'trip_tarik_percobaan_pada',
        'trip_tarik_error',
        'dibuat_oleh',
    ];

    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
        'link_dikirim_pada' => 'datetime',
        'unlink_pada' => 'datetime',
        'trip_ditarik_pada' => 'datetime',
        'trip_tarik_percobaan_pada' => 'datetime',
    ];

    public const STATUS_TERJADWAL = 'terjadwal';
    public const STATUS_BERJALAN = 'berjalan';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_BATAL = 'batal';

    public const STATUS_LABEL = [
        self::STATUS_TERJADWAL => 'Terjadwal',
        self::STATUS_BERJALAN => 'Berjalan',
        self::STATUS_SELESAI => 'Selesai',
        self::STATUS_BATAL => 'Batal',
    ];

    /** Batas percobaan dorong link ke Cartrack sebelum ditandai gagal (perlu tindakan manual). */
    public const MAKS_PERCOBAAN_LINK = 5;

    public function operator()
    {
        return $this->belongsTo(OperatorGtm::class, 'operator_gtm_id');
    }

    public function rute()
    {
        return $this->hasMany(JadwalPerjalananRute::class, 'jadwal_perjalanan_id')->orderBy('urutan');
    }

    public function trips()
    {
        return $this->hasMany(GpsTrip::class, 'jadwal_perjalanan_id')->orderBy('waktu_mulai');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function getKunciNopolAttribute(): string
    {
        return GpsTripImporter::kunciNopol($this->nopol);
    }

    /** Masih bisa diubah/dibatalkan (belum selesai atau batal). */
    public function getAktifAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_TERJADWAL, self::STATUS_BERJALAN], true);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABEL[$this->status] ?? $this->status;
    }

    /**
     * Jadwal lain (bukan batal) pada kendaraan atau driver yang sama dan jendela waktunya beririsan.
     * Dipakai untuk mencegah satu mobil/satu driver dobel dalam waktu yang sama.
     *
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function bentrok(string $nopol, int $operatorId, $mulai, $selesai, ?int $kecualiId = null)
    {
        $kunci = GpsTripImporter::kunciNopol($nopol);

        return static::query()
            ->where('status', '!=', self::STATUS_BATAL)
            ->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId))
            ->where('waktu_mulai', '<', $selesai)
            ->where('waktu_selesai', '>', $mulai)
            ->get()
            ->filter(fn (self $j) => $j->operator_gtm_id === $operatorId || $j->kunci_nopol === $kunci)
            ->values();
    }
}
