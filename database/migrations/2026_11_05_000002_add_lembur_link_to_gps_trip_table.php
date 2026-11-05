<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gps_trip', function (Blueprint $table) {
            // Lembur yang dibentuk dari trip ini (diisi saat "Terapkan ke Lembur")
            $table->foreignId('operator_gtm_lembur_id')->nullable()->after('operator_gtm_id')
                ->constrained('operator_gtm_lembur')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gps_trip', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operator_gtm_lembur_id');
        });
    }
};
