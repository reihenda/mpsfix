<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kurs USD -> IDR per bulan (kurs historis) untuk menghitung Rupiah
 * pemakaian & saldo customer MMBTU di halaman internal.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kurs_usd_bulanan')) {
            return;
        }

        Schema::create('kurs_usd_bulanan', function (Blueprint $table) {
            $table->id();
            $table->char('periode', 7)->unique(); // format Y-m
            $table->decimal('kurs', 14, 2);
            $table->string('sumber', 20)->default('manual'); // manual | realtime
            $table->string('catatan', 255)->nullable();
            $table->foreignId('diinput_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurs_usd_bulanan');
    }
};
