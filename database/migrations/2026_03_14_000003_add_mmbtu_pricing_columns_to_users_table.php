<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'harga_per_mmbtu_usd')) {
                $table->decimal('harga_per_mmbtu_usd', 14, 4)->nullable()->default(0)->after('harga_per_meter_kubik');
            }
            if (!Schema::hasColumn('users', 'pembagi_sm3_ke_mmbtu')) {
                $table->decimal('pembagi_sm3_ke_mmbtu', 14, 6)->nullable()->default(1)->after('harga_per_mmbtu_usd');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumnIfExists(['harga_per_mmbtu_usd', 'pembagi_sm3_ke_mmbtu']);
        });
    }
};
