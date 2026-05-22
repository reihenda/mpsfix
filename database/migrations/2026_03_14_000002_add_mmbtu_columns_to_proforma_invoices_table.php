<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('proforma_invoices')) {
            Schema::table('proforma_invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('proforma_invoices', 'volume_mmbtu')) {
                    $table->decimal('volume_mmbtu', 14, 4)->nullable()->after('total_volume');
                }
                if (!Schema::hasColumn('proforma_invoices', 'price_per_mmbtu_usd')) {
                    $table->decimal('price_per_mmbtu_usd', 14, 4)->nullable()->after('volume_mmbtu');
                }
                if (!Schema::hasColumn('proforma_invoices', 'kurs_usd')) {
                    $table->decimal('kurs_usd', 14, 2)->nullable()->after('price_per_mmbtu_usd');
                }
                if (!Schema::hasColumn('proforma_invoices', 'total_usd')) {
                    $table->decimal('total_usd', 14, 2)->nullable()->after('kurs_usd');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('proforma_invoices')) {
            Schema::table('proforma_invoices', function (Blueprint $table) {
                $table->dropColumnIfExists(['volume_mmbtu', 'price_per_mmbtu_usd', 'kurs_usd', 'total_usd']);
            });
        }
    }
};
