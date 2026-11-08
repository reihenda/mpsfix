<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jadwal tidak lagi punya waktu selesai yang ditentukan di muka. Selesainya mengikuti rute:
     * kolom waktu_selesai kini diisi sistem (saat tujuan terakhir tercapai atau diselesaikan manual),
     * dan tiap tujuan mencatat kapan tercapainya.
     */
    public function up(): void
    {
        Schema::table('jadwal_perjalanan', function (Blueprint $table) {
            $table->dateTime('waktu_selesai')->nullable()->change();
            $table->dateTime('rute_dicek_pada')->nullable()->after('trip_tarik_error');
        });

        Schema::table('jadwal_perjalanan_rute', function (Blueprint $table) {
            $table->dateTime('tercapai_pada')->nullable()->after('catatan');
        });

        // Jadwal yang belum selesai ikut aturan baru: waktu selesai lama hanyalah rencana, bukan kenyataan.
        DB::table('jadwal_perjalanan')
            ->whereIn('status', ['terjadwal', 'berjalan'])
            ->update(['waktu_selesai' => null]);
    }

    public function down(): void
    {
        DB::table('jadwal_perjalanan')->whereNull('waktu_selesai')->update(['waktu_selesai' => DB::raw('waktu_mulai')]);

        Schema::table('jadwal_perjalanan_rute', function (Blueprint $table) {
            $table->dropColumn('tercapai_pada');
        });

        Schema::table('jadwal_perjalanan', function (Blueprint $table) {
            $table->dropColumn('rute_dicek_pada');
            $table->dateTime('waktu_selesai')->nullable(false)->change();
        });
    }
};
