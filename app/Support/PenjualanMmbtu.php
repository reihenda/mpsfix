<?php

namespace App\Support;

use App\Models\User;

/**
 * Nilai penjualan satu data pencatatan customer MMBTU untuk laporan Rupiah
 * (Rekap Penjualan & dashboard admin): Sm3 dan Rupiah = USD x kurs bulan pemakaian.
 */
class PenjualanMmbtu
{
    /** @var array<int, User|null> */
    private static array $customer = [];

    /** @return array{0: float, 1: float} [volume Sm3, penjualan Rupiah] */
    public static function hitung(int $customerId, $item): array
    {
        if (!array_key_exists($customerId, self::$customer)) {
            self::$customer[$customerId] = User::find($customerId);
        }
        $customer = self::$customer[$customerId];
        if (!$customer) {
            return [0.0, 0.0];
        }

        $p = $customer->hitungPencatatanMmbtu($item);
        return [$p['sm3'], $p['idr']];
    }
}
