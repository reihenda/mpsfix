<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pemetaan driver kita -> driver di Cartrack (uuid driver_id)
        Schema::table('operator_gtm', function (Blueprint $table) {
            $table->string('cartrack_driver_id', 36)->nullable()->after('nama');
            $table->index('cartrack_driver_id');
        });

        // Jadwal perjalanan: kendaraan + driver + jendela waktu. Dibuat staff operasional/admin,
        // didorong ke Cartrack (link driver) saat waktu mulai, trip ditarik setelah selesai.
        Schema::create('jadwal_perjalanan', function (Blueprint $table) {
            $table->id();
            $table->string('nopol');
            $table->foreignId('operator_gtm_id')->constrained('operator_gtm')->restrictOnDelete();
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai');
            $table->text('catatan')->nullable();

            // terjadwal -> berjalan (driver sudah di-link) -> selesai ; batal kapan saja sebelum selesai
            $table->enum('status', ['terjadwal', 'berjalan', 'selesai', 'batal'])->default('terjadwal');

            // Status dorong (push) ke Cartrack
            $table->enum('link_status', ['menunggu', 'terkirim', 'gagal', 'dilewati'])->default('menunggu');
            $table->unsignedTinyInteger('link_percobaan')->default(0);
            $table->dateTime('link_dikirim_pada')->nullable();
            $table->text('link_error')->nullable();
            $table->dateTime('unlink_pada')->nullable();

            // Penarikan trip dari Cartrack
            $table->dateTime('trip_ditarik_pada')->nullable();
            $table->dateTime('trip_tarik_percobaan_pada')->nullable();
            $table->text('trip_tarik_error')->nullable();

            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['nopol', 'waktu_mulai']);
            $table->index(['operator_gtm_id', 'waktu_mulai']);
            $table->index('status');
        });

        // Rute: urutan tujuan yang harus dikunjungi driver
        Schema::create('jadwal_perjalanan_rute', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_perjalanan_id')->constrained('jadwal_perjalanan')->cascadeOnDelete();
            $table->unsignedSmallInteger('urutan');
            $table->string('tujuan');
            $table->string('catatan')->nullable();
            $table->timestamps();

            $table->index(['jadwal_perjalanan_id', 'urutan']);
        });

        // Trip GPS: asal data, tautan ke jadwal, dan validasi.
        // Trip lama (Excel) dianggap sudah tervalidasi (default true); trip dari API Cartrack
        // dibuat belum tervalidasi dan baru ikut hitungan lembur setelah divalidasi.
        Schema::table('gps_trip', function (Blueprint $table) {
            $table->string('sumber', 20)->default('excel')->after('nopol');
            $table->foreignId('jadwal_perjalanan_id')->nullable()->after('operator_gtm_id')
                ->constrained('jadwal_perjalanan')->nullOnDelete();
            $table->boolean('divalidasi')->default(true)->after('dihitung');
            $table->foreignId('divalidasi_oleh')->nullable()->after('divalidasi')
                ->constrained('users')->nullOnDelete();
            $table->dateTime('divalidasi_pada')->nullable()->after('divalidasi_oleh');
            $table->index('divalidasi');
        });
    }

    public function down(): void
    {
        Schema::table('gps_trip', function (Blueprint $table) {
            $table->dropConstrainedForeignId('divalidasi_oleh');
            $table->dropConstrainedForeignId('jadwal_perjalanan_id');
            $table->dropIndex(['divalidasi']);
            $table->dropColumn(['sumber', 'divalidasi', 'divalidasi_pada']);
        });

        Schema::dropIfExists('jadwal_perjalanan_rute');
        Schema::dropIfExists('jadwal_perjalanan');

        Schema::table('operator_gtm', function (Blueprint $table) {
            $table->dropIndex(['cartrack_driver_id']);
            $table->dropColumn('cartrack_driver_id');
        });
    }
};
