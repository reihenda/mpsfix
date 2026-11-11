<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mencatat periode lembur (26-25) mana yang sudah pernah ditarik PENUH dari Cartrack,
        // supaya halaman Trip GPS bisa memberi tahu bila periode lama belum disinkronkan.
        Schema::create('gps_sinkron_periode', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7)->unique(); // YYYY-MM, label = bulan tanggal 25
            $table->dateTime('awal');
            $table->dateTime('akhir');
            $table->dateTime('terakhir_penuh_pada');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gps_sinkron_periode');
    }
};
