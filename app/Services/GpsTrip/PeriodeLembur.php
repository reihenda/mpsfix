<?php

namespace App\Services\GpsTrip;

use Carbon\Carbon;

/**
 * Periode data lembur: tanggal 26 bulan sebelumnya sampai tanggal 25 bulan berlabel.
 * Contoh: periode "2026-11" = 26 Okt 2026 00:00 s/d 25 Nov 2026 23:59:59.
 */
class PeriodeLembur
{
    public const TANGGAL_AWAL = 26;
    public const TANGGAL_AKHIR = 25;

    /** @return array{0: Carbon, 1: Carbon} [awal, akhir] */
    public static function rentang(string $bulan): array
    {
        $akhirBulan = Carbon::createFromFormat('!Y-m', $bulan)->day(self::TANGGAL_AKHIR)->endOfDay();
        $awal = Carbon::createFromFormat('!Y-m', $bulan)->subMonthNoOverflow()->day(self::TANGGAL_AWAL)->startOfDay();

        return [$awal, $akhirBulan];
    }

    /** Label periode (YYYY-MM) yang memuat tanggal ini. */
    public static function bulanUntuk(Carbon $tanggal): string
    {
        $ujung = $tanggal->day >= self::TANGGAL_AWAL ? $tanggal->copy()->startOfMonth()->addMonthNoOverflow() : $tanggal;

        return $ujung->format('Y-m');
    }

    public static function valid(?string $bulan): bool
    {
        return (bool) $bulan && (bool) preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan);
    }

    /** Mis. "26 Okt – 25 Nov 2026". */
    public static function label(string $bulan): string
    {
        [$awal, $akhir] = self::rentang($bulan);

        return $awal->locale('id')->translatedFormat('j M')
            .($awal->year !== $akhir->year ? ' '.$awal->year : '')
            .' – '.$akhir->locale('id')->translatedFormat('j M Y');
    }
}
