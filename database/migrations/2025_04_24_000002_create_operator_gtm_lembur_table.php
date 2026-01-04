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
        Schema::create('operator_gtm_lembur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_gtm_id')->constrained('operator_gtm')->onDelete('cascade');
            $table->date('tanggal');
            $table->time('jam_masuk_sesi_1')->nullable();
            $table->time('jam_keluar_sesi_1')->nullable();
            $table->time('jam_masuk_sesi_2')->nullable();
            $table->time('jam_keluar_sesi_2')->nullable();
            $table->integer('total_jam_kerja')->nullable(); // dalam menit
            $table->integer('total_jam_lembur')->nullable(); // dalam menit
            $table->decimal('upah_lembur', 12, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operator_gtm_lembur');
    }
};