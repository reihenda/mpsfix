<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rekap_pengambilan', function (Blueprint $table) {
            $table->text('alamat_pengambilan')->nullable()->after('volume');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_pengambilan', function (Blueprint $table) {
            $table->dropColumn('alamat_pengambilan');
        });
    }
};
