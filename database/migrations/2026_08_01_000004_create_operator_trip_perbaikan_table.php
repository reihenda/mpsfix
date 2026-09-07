<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operator_trip_perbaikan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_gtm_id')->constrained('operator_gtm')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('nopol')->nullable();
            $table->string('asal_nama')->nullable();
            $table->string('tujuan_nama')->nullable();
            $table->decimal('tekanan', 8, 2)->nullable();
            $table->string('foto_bukti_path')->nullable();
            $table->timestamp('waktu_klaim')->nullable();
            $table->text('keterangan')->nullable();
            $table->enum('status', ['pending', 'approved', 'ditolak'])->default('pending');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan_admin')->nullable();
            $table->unsignedTinyInteger('sesi_ke')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_trip_perbaikan');
    }
};
