<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tandai data lembur yang dibuat dari trip GPS. Dibutuhkan karena trip yang melewati tengah malam kini
 * dipecah ke dua hari lembur, sedangkan satu trip hanya bisa tertaut ke satu data lembur; tanpa tanda ini
 * hari kedua akan terlihat seperti input manual dan tidak diperbarui lagi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operator_gtm_lembur', function (Blueprint $table) {
            $table->boolean('sumber_gps')->default(false)->after('upah_lembur');
        });

        // Data lembur lama yang sudah tertaut ke trip GPS = berasal dari GPS
        DB::table('operator_gtm_lembur')
            ->whereIn('id', DB::table('gps_trip')->whereNotNull('operator_gtm_lembur_id')->select('operator_gtm_lembur_id'))
            ->update(['sumber_gps' => true]);
    }

    public function down(): void
    {
        Schema::table('operator_gtm_lembur', function (Blueprint $table) {
            $table->dropColumn('sumber_gps');
        });
    }
};
