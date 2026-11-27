<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kejadian berhenti lama yang ditandai "tetap dihitung" (bukan berhenti sungguhan, mis. geofence customer
 * belum akurat sehingga mobil yang sedang di customer terbaca di luar geofence). Kejadian ini tidak lagi
 * menunggu bukti dan tidak dipotong. Dikenali dari nopol + waktu mulai berhenti, sama seperti gps_henti_bukti.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gps_henti_abaikan', function (Blueprint $table) {
            $table->id();
            $table->string('nopol', 30);
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai');
            $table->string('alasan', 500)->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['nopol', 'waktu_mulai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gps_henti_abaikan');
    }
};
