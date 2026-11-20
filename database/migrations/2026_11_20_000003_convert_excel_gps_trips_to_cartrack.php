<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fitur import Excel GPS dihapus; semua trip kini berasal dari Cartrack. Trip lama hasil Excel disamakan:
 *  - sumber -> cartrack, supaya ikut diperbarui sinkron Cartrack (dicocokkan lewat nopol + waktu mulai) dan
 *    bisa divalidasi / dibatalkan seperti trip lain;
 *  - yang sudah dipakai di data lembur tetap valid (data lembur yang ada tidak berubah);
 *  - sisanya menjadi belum valid dan harus divalidasi dulu sebelum masuk lembur.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('gps_trip')
            ->where('sumber', 'excel')
            ->whereNotNull('operator_gtm_lembur_id')
            ->update(['sumber' => 'cartrack']);

        DB::table('gps_trip')
            ->where('sumber', 'excel')
            ->whereNull('operator_gtm_lembur_id')
            ->update([
                'sumber' => 'cartrack',
                'divalidasi' => false,
                'divalidasi_oleh' => null,
                'divalidasi_pada' => null,
            ]);
    }

    public function down(): void
    {
        // Tidak bisa dikembalikan dengan pasti (asal Excel tidak disimpan di tempat lain).
    }
};
