<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Minimum saldo per customer untuk peringatan di daftar Pencatatan Data Customer.
 * - saldo_minimum: nominal (Rupiah; USD untuk customer MMBTU), null = belum diatur
 * - saldo_minimum_tanpa_deposit: customer memang tidak memakai deposit (saldo wajar minus)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'saldo_minimum')) {
                $table->decimal('saldo_minimum', 18, 2)->nullable();
            }
            if (!Schema::hasColumn('users', 'saldo_minimum_tanpa_deposit')) {
                $table->boolean('saldo_minimum_tanpa_deposit')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['saldo_minimum', 'saldo_minimum_tanpa_deposit'] as $kolom) {
                if (Schema::hasColumn('users', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }
};
