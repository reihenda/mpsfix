<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_lokasi', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lokasi');
            $table->enum('status', ['pending', 'approved'])->default('pending');
            $table->foreignId('diajukan_oleh_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_lokasi');
    }
};
