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
        Schema::create('konfigurasi_lembur', function (Blueprint $table) {
            $table->id();
            $table->string('nama_konfigurasi');
            $table->decimal('tarif_per_jam', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default value
        DB::table('konfigurasi_lembur')->insert([
            'nama_konfigurasi' => 'Tarif Lembur Standar',
            'tarif_per_jam' => 25000.00,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('konfigurasi_lembur');
    }
};