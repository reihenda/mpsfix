<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gps_trip', function (Blueprint $table) {
            // true = admin sudah meninjau trip pendek ini dan memutuskan mempertahankannya (bukan noise)
            $table->boolean('bukan_noise')->default(false)->after('dihitung');
        });

        // Catatan trip noise yang sudah dihapus admin, supaya upload ulang tidak membuatnya lagi
        Schema::create('gps_trip_dibuang', function (Blueprint $table) {
            $table->id();
            $table->string('nopol');
            $table->dateTime('waktu_mulai');
            $table->timestamps();

            $table->unique(['nopol', 'waktu_mulai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gps_trip_dibuang');

        Schema::table('gps_trip', function (Blueprint $table) {
            $table->dropColumn('bukan_noise');
        });
    }
};
