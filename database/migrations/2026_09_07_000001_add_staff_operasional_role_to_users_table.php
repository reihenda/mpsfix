<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tambah 'staff_operasional' ke ENUM role
        // Role ini hanya bisa MELIHAT Lembur Operator GTM & Pencatatan Data Customer (read-only)
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'superadmin', 'customer', 'fob', 'demo', 'keuangan', 'staff', 'mmbtu', 'operator', 'staff_operasional') DEFAULT 'customer'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'superadmin', 'customer', 'fob', 'demo', 'keuangan', 'staff', 'mmbtu', 'operator') DEFAULT 'customer'");
    }
};
