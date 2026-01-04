<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nomor_polisi', function (Blueprint $table) {
            $table->string('jenis')->nullable()->after('keterangan');
            $table->string('ukuran')->nullable()->after('jenis');
            $table->string('no_gtm')->nullable()->after('ukuran');
            $table->enum('status', ['milik', 'sewa', 'disewakan'])->nullable()->after('no_gtm');
            $table->enum('iso', ['ISO - 11439', 'ISO - 11119'])->nullable()->after('status');
            $table->enum('coi', ['sudah', 'belum'])->nullable()->after('iso');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nomor_polisi', function (Blueprint $table) {
            $table->dropColumn('jenis');
            $table->dropColumn('ukuran');
            $table->dropColumn('no_gtm');
            $table->dropColumn('status');
            $table->dropColumn('iso');
            $table->dropColumn('coi');
        });
    }
};
