<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kejadian berhenti lama (GpsTrip::hentiDiJalan()) yang ditandai "tetap dihitung", biasanya karena geofence
 * belum akurat. Kejadian ini tidak menunggu bukti dan jam kerjanya tidak dipotong.
 */
class GpsHentiAbaikan extends Model
{
    protected $table = 'gps_henti_abaikan';

    protected $fillable = ['nopol', 'waktu_mulai', 'waktu_selesai', 'alasan', 'dibuat_oleh'];

    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
    ];

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * Kejadian yang ditandai tetap dihitung, untuk waktu mulai berhenti di rentang ini.
     *
     * @return array<string, array{id:int, alasan:?string, oleh:?string, waktu:?string}> kunci (GpsHentiBukti::kunci) => info
     */
    public static function himpunan(\DateTimeInterface|string $awal, \DateTimeInterface|string $akhir): array
    {
        $hasil = [];
        foreach (static::with('pembuat:id,name')->whereBetween('waktu_mulai', [$awal, $akhir])->get() as $a) {
            $hasil[GpsHentiBukti::kunci($a->nopol, $a->waktu_mulai->format('Y-m-d H:i:s'))] = [
                'id' => $a->id,
                'alasan' => $a->alasan,
                'oleh' => $a->pembuat->name ?? null,
                'waktu' => $a->created_at?->format('d/m/Y H:i'),
            ];
        }

        return $hasil;
    }
}
