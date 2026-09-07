<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE trip_lokasi MODIFY COLUMN status ENUM('pending', 'approved', 'ditolak') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE trip_lokasi MODIFY COLUMN status ENUM('pending', 'approved') NOT NULL DEFAULT 'pending'");
    }
};
