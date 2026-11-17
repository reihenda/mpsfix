<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foto bukti untuk berhenti lama di luar geofence. Jam kerja baru dipotong bila kejadian itu punya
 * minimal satu foto bukti. Kejadian dikenali dari nopol + waktu mulai berhenti (turunan dari segmen trip),
 * bukan dari id trip, supaya bukti tetap menempel walau trip dihitung ulang saat sinkron.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gps_henti_bukti', function (Blueprint $table) {
            $table->id();
            $table->string('nopol', 30);
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai');
            $table->string('path');
            $table->string('nama_asli')->nullable();
            $table->string('catatan', 500)->nullable();
            $table->foreignId('diunggah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['nopol', 'waktu_mulai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gps_henti_bukti');
    }
};
