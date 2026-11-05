<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master klasifikasi nama geofence dari laporan GPS.
        // tipe 'customer'  => batas trip (trip mulai/berakhir di sini)
        // tipe 'spbg'      => titik singgah, trip tetap lanjut
        // tipe 'lainnya'   => workshop/pool/dll, trip tetap lanjut
        Schema::create('gps_geofence', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->enum('tipe', ['customer', 'spbg', 'lainnya'])->default('customer');
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Riwayat upload laporan Summary Trip Report
        Schema::create('gps_trip_import', function (Blueprint $table) {
            $table->id();
            $table->string('nama_file');
            $table->date('periode_mulai')->nullable();
            $table->date('periode_selesai')->nullable();
            $table->unsignedInteger('jumlah_kendaraan')->default(0);
            $table->unsignedInteger('jumlah_trip')->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('gps_trip', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gps_trip_import_id')->nullable()->constrained('gps_trip_import')->nullOnDelete();
            $table->string('nopol');
            $table->string('model_kendaraan')->nullable();

            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai')->nullable();
            $table->string('geofence_mulai')->nullable();
            $table->string('geofence_selesai')->nullable();

            $table->decimal('jarak_km', 8, 2)->default(0);
            $table->unsignedInteger('durasi_jalan_detik')->default(0);   // total ignition-on
            $table->unsignedInteger('jeda_terpanjang_detik')->default(0); // jeda terpanjang antar segmen
            $table->unsignedSmallInteger('jumlah_segmen')->default(1);
            $table->json('segmen')->nullable();

            $table->string('driver_gps')->nullable(); // nama driver sesuai laporan GPS
            $table->foreignId('operator_gtm_id')->nullable()->constrained('operator_gtm')->nullOnDelete();

            // selesai        => berakhir di geofence customer
            // tidak_lengkap  => akhir tidak terbaca / trip belum selesai di akhir laporan
            $table->enum('status', ['selesai', 'tidak_lengkap'])->default('selesai');
            $table->boolean('dihitung')->default(true);
            $table->boolean('diedit')->default(false); // true = sudah disesuaikan manual, upload ulang tidak menimpa
            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->unique(['nopol', 'waktu_mulai']);
            $table->index('waktu_mulai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gps_trip');
        Schema::dropIfExists('gps_trip_import');
        Schema::dropIfExists('gps_geofence');
    }
};
