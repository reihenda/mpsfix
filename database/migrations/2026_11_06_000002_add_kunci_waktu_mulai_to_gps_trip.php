<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gps_trip', function (Blueprint $table) {
            // Waktu mulai asli hasil deteksi dari laporan GPS. Tidak berubah walau admin mengedit
            // waktu_mulai, dipakai sebagai kunci pencocokan saat upload ulang (mencegah trip ganda).
            $table->dateTime('kunci_waktu_mulai')->nullable()->after('waktu_mulai');
            $table->index(['nopol', 'kunci_waktu_mulai']);
        });

        // Backfill: waktu mulai asli = awal segmen pertama (tetap benar walau waktu_mulai sudah diedit)
        DB::table('gps_trip')->select('id', 'waktu_mulai', 'segmen')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $segmen = $row->segmen ? json_decode($row->segmen, true) : null;
                $asli = $segmen[0]['mulai'] ?? $row->waktu_mulai;

                DB::table('gps_trip')->where('id', $row->id)->update(['kunci_waktu_mulai' => $asli]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('gps_trip', function (Blueprint $table) {
            $table->dropIndex(['nopol', 'kunci_waktu_mulai']);
            $table->dropColumn('kunci_waktu_mulai');
        });
    }
};
