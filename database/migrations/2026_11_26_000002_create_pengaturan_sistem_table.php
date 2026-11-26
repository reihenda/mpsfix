<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Pengaturan sederhana kunci–nilai (mis. penerima email notifikasi saldo). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pengaturan_sistem')) {
            return;
        }
        Schema::create('pengaturan_sistem', function (Blueprint $table) {
            $table->id();
            $table->string('kunci', 100)->unique();
            $table->text('nilai')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_sistem');
    }
};
