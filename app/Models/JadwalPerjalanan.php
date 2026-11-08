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
        'rute_dicek_pada',
        'dibuat_oleh',
    ];

    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
        'link_dikirim_pada' => 'datetime',
        'unlink_pada' => 'datetime',
        'trip_ditarik_pada' => 'datetime',
        'trip_tarik_percobaan_pada' => 'datetime',
        'rute_dicek_pada' => 'datetime',
    ];

    /**
     * Jadwal tidak punya waktu selesai di muka. waktu_selesai baru terisi saat tujuan terakhir pada
     * rute tercapai (atau jadwal diselesaikan manual). Untuk cek bentrok, jadwal yang belum selesai
     * dianggap memakai kendaraan dan driver selama sekian jam sejak mulai.
     */
    public const PERKIRAAN_JAM = 12;

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

    /** Sudah selesai: tujuan terakhir tercapai atau diselesaikan manual. */
    public function getRuteSelesaiAttribute(): bool
    {
        return $this->waktu_selesai !== null;
    }

    /**
     * Sampai kapan jadwal ini dianggap memakai kendaraan/driver. Sudah selesai: waktu selesainya.
     * Sedang berjalan tapi belum selesai: tanpa batas. Belum mulai: perkiraan sejak waktu mulai.
     */
    public function batasPemakaian(): ?\Carbon\Carbon
    {
        if ($this->waktu_selesai) {
            return $this->waktu_selesai;
        }

        return $this->status === self::STATUS_BERJALAN
            ? null
            : $this->waktu_mulai->copy()->addHours(self::PERKIRAAN_JAM);
    }

    /**
     * Jadwal lain (bukan batal) pada kendaraan atau driver yang sama yang masih memakainya saat
     * jadwal baru mulai. Dipakai untuk mencegah satu mobil/satu driver dobel dalam waktu yang sama.
     *
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function bentrok(string $nopol, int $operatorId, $mulai, ?int $kecualiId = null)
    {
        $kunci = GpsTripImporter::kunciNopol($nopol);
        $akhirBaru = $mulai->copy()->addHours(self::PERKIRAAN_JAM);

        return static::query()
            ->where('status', '!=', self::STATUS_BATAL)
            ->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId))
            ->where('waktu_mulai', '<', $akhirBaru)
            ->where(fn ($q) => $q->whereNull('waktu_selesai')->orWhere('waktu_selesai', '>', $mulai))
            ->get()
            ->filter(fn (self $j) => $j->operator_gtm_id === $operatorId || $j->kunci_nopol === $kunci)
            ->filter(fn (self $j) => ($batas = $j->batasPemakaian()) === null || $batas->gt($mulai))
            ->values();
    }
}
