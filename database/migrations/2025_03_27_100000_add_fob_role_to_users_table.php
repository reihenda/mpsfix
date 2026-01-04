<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modifikasi kolom role untuk menerima nilai 'fob' dan 'demo'
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'superadmin', 'customer', 'fob', 'demo') DEFAULT 'customer'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke nilai enum original
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'superadmin', 'customer') DEFAULT 'customer'");
    }
};
