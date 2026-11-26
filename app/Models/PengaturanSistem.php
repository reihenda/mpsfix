<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Pengaturan kunci–nilai sistem. */
class PengaturanSistem extends Model
{
    protected $table = 'pengaturan_sistem';

    protected $fillable = ['kunci', 'nilai'];

    public const NOTIF_SALDO_PENERIMA = 'notif_saldo_email_penerima';
    public const NOTIF_SALDO_AKTIF = 'notif_saldo_aktif';

    public static function ambil(string $kunci, $bawaan = null)
    {
        return static::where('kunci', $kunci)->value('nilai') ?? $bawaan;
    }

    public static function simpan(string $kunci, $nilai): void
    {
        static::updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
    }

    /** Daftar email penerima notifikasi saldo (sudah divalidasi formatnya). */
    public static function penerimaNotifSaldo(): array
    {
        $daftar = preg_split('/[\s,;]+/', (string) static::ambil(self::NOTIF_SALDO_PENERIMA, ''), -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_unique(array_filter($daftar, fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))));
    }

    public static function notifSaldoAktif(): bool
    {
        return (bool) static::ambil(self::NOTIF_SALDO_AKTIF, false);
    }
}
