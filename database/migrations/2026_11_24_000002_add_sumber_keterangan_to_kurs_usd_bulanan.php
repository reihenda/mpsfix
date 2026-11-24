<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Keterangan asal kurs (mis. "Rata-rata 22 kurs harian ... ECB via frankfurter.dev"). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kurs_usd_bulanan') && !Schema::hasColumn('kurs_usd_bulanan', 'sumber_keterangan')) {
            Schema::table('kurs_usd_bulanan', function (Blueprint $table) {
                $table->string('sumber_keterangan', 255)->nullable()->after('sumber');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('kurs_usd_bulanan', 'sumber_keterangan')) {
            Schema::table('kurs_usd_bulanan', function (Blueprint $table) {
                $table->dropColumn('sumber_keterangan');
            });
        }
    }
};
