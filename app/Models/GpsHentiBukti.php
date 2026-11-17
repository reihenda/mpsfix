<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu foto bukti untuk satu kejadian berhenti lama di luar geofence (lihat GpsTrip::hentiDiJalan()).
 */
class GpsHentiBukti extends Model
{
    protected $table = 'gps_henti_bukti';

    protected $fillable = ['nopol', 'waktu_mulai', 'waktu_selesai', 'path', 'nama_asli', 'catatan', 'diunggah_oleh'];

    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
    ];

    public const DISK = 'local';

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diunggah_oleh');
    }

    /** Kunci kejadian: "NOPOL|Y-m-d H:i:s" (waktu mulai berhenti, waktu lokal tanpa zona). */
    public static function kunci(string $nopol, string $waktuMulai): string
    {
        return $nopol.'|'.$waktuMulai;
    }

    /**
     * Daftar kejadian yang sudah punya bukti, untuk waktu mulai berhenti di rentang ini.
     *
     * @return array<string, int> kunci => jumlah foto
     */
    public static function himpunan(\DateTimeInterface|string $awal, \DateTimeInterface|string $akhir): array
    {
        $hasil = [];
        foreach (static::whereBetween('waktu_mulai', [$awal, $akhir])->get(['nopol', 'waktu_mulai']) as $b) {
            $k = static::kunci($b->nopol, $b->waktu_mulai->format('Y-m-d H:i:s'));
            $hasil[$k] = ($hasil[$k] ?? 0) + 1;
        }

        return $hasil;
    }
}
