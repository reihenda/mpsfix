<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operator_trip', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_gtm_id')->constrained('operator_gtm')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('nopol')->nullable();

            $table->enum('asal_type', ['alamat_pengambilan', 'customer', 'trip_lokasi'])->nullable();
            $table->unsignedBigInteger('asal_id')->nullable();
            $table->string('asal_nama')->nullable();

            $table->enum('tujuan_type', ['alamat_pengambilan', 'customer', 'trip_lokasi'])->nullable();
            $table->unsignedBigInteger('tujuan_id')->nullable();
            $table->string('tujuan_nama')->nullable();

            $table->decimal('tekanan', 8, 2)->nullable();

            $table->string('foto_berangkat_path')->nullable();
            $table->decimal('latitude_berangkat', 10, 7)->nullable();
            $table->decimal('longitude_berangkat', 10, 7)->nullable();
            $table->timestamp('waktu_berangkat')->nullable();

            $table->string('foto_sampai_path')->nullable();
            $table->decimal('latitude_sampai', 10, 7)->nullable();
            $table->decimal('longitude_sampai', 10, 7)->nullable();
            $table->timestamp('waktu_sampai')->nullable();

            $table->enum('status', ['berangkat', 'selesai'])->default('berangkat');

            $table->foreignId('operator_gtm_lembur_id')->nullable()->constrained('operator_gtm_lembur')->nullOnDelete();
            $table->unsignedTinyInteger('sesi_ke')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_trip');
    }
};
