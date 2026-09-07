<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'superadmin', 'customer', 'fob', 'demo', 'keuangan', 'staff', 'mmbtu', 'operator') DEFAULT 'customer'");

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'operator_gtm_id')) {
                $table->foreignId('operator_gtm_id')->nullable()->after('id')->constrained('operator_gtm')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'operator_gtm_id')) {
                $table->dropForeign(['operator_gtm_id']);
                $table->dropColumn('operator_gtm_id');
            }
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'superadmin', 'customer', 'fob', 'demo', 'keuangan', 'staff', 'mmbtu') DEFAULT 'customer'");
    }
};
