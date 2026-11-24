<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Kurs USD -> IDR per bulan (historis).
 *
 * Dipakai untuk mengubah USD customer MMBTU ke Rupiah di halaman internal:
 * pemakaian bulan X memakai kurs bulan X. Bila bulan X belum diisi, dipakai
 * kurs bulan terdekat sebelumnya (dan bulan itu dilaporkan sebagai "belum diisi").
 */
class KursUsdBulanan extends Model
{
    protected $table = 'kurs_usd_bulanan';

    protected $fillable = ['periode', 'kurs', 'sumber', 'sumber_keterangan', 'catatan', 'diinput_oleh'];

    protected $casts = ['kurs' => 'float'];

    /** Cache per request: [periode => kurs], terurut naik. */
    private static ?array $peta = null;

    public static function lupakanCache(): void
    {
        self::$peta = null;
    }

    private static function peta(): array
    {
        if (self::$peta === null) {
            self::$peta = static::query()->orderBy('periode')->pluck('kurs', 'periode')
                ->map(fn ($k) => (float) $k)->all();
        }
        return self::$peta;
    }

    /**
     * Kurs untuk bulan tanggal tsb.
     * @return array{kurs: ?float, periode: ?string, pas: bool}
     *   pas=false bila memakai kurs bulan lain (bulan itu belum diisi).
     */
    public static function untuk($tanggal): array
    {
        $periode = $tanggal instanceof Carbon ? $tanggal->format('Y-m') : Carbon::parse($tanggal)->format('Y-m');
        $peta = self::peta();

        if (isset($peta[$periode])) {
            return ['kurs' => $peta[$periode], 'periode' => $periode, 'pas' => true];
        }

        // Kurs terdekat sebelumnya
        $sebelum = null;
        foreach ($peta as $p => $k) {
            if ($p < $periode) {
                $sebelum = $p;
            } else {
                break;
            }
        }
        if ($sebelum !== null) {
            return ['kurs' => $peta[$sebelum], 'periode' => $sebelum, 'pas' => false];
        }

        // Belum ada kurs sebelumnya: pakai kurs paling awal yang ada (bila ada)
        if ($peta) {
            $awal = array_key_first($peta);
            return ['kurs' => $peta[$awal], 'periode' => $awal, 'pas' => false];
        }

        return ['kurs' => null, 'periode' => null, 'pas' => false];
    }

    /** Kurs terbaru (bulan ini atau sebelumnya) untuk "setara Rp hari ini". */
    public static function terbaru(): array
    {
        return self::untuk(now());
    }

    /** Label singkat sumber: Manual / ECB (frankfurter.dev) / Real-time lama. */
    public function getSumberLabelAttribute(): string
    {
        return match ($this->sumber) {
            'api' => 'ECB',
            'realtime' => 'Real-time',
            default => 'Manual',
        };
    }

    public function getPeriodeLabelAttribute(): string
    {
        return Carbon::createFromFormat('Y-m', $this->periode)->locale('id')->translatedFormat('F Y');
    }
}
