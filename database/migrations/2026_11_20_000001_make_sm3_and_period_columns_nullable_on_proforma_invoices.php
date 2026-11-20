<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Proforma Invoice customer MMBTU tidak memakai volume_per_day, price_per_sm3,
 * total_days, maupun periode (period_start_date / period_end_date).
 * Kolom-kolom tsb sebelumnya NOT NULL tanpa default sehingga INSERT
 * proforma MMBTU gagal (SQLSTATE 1364).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('proforma_invoices')) {
            return;
        }

        Schema::table('proforma_invoices', function (Blueprint $table) {
            $table->decimal('volume_per_day', 10, 3)->nullable()->change();
            $table->decimal('price_per_sm3', 12, 2)->nullable()->change();
            $table->integer('total_days')->nullable()->change();
            $table->date('period_start_date')->nullable()->change();
            $table->date('period_end_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Sengaja tidak dikembalikan ke NOT NULL: baris MMBTU berisi NULL
        // sehingga rollback akan gagal / merusak data.
    }
};
