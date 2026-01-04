<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('total_deposit', 15, 2)->default(0)->after('email');
            $table->decimal('total_purchases', 15, 2)->default(0)->after('total_deposit');
            $table->text('deposit_history')->nullable()->after('total_purchases');
            $table->json('pricing_history')->nullable()->after('deposit_history'); // Kolom baru untuk pricing history
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['total_deposit', 'total_purchases', 'deposit_history', 'pricing_history']);
        });
    }
};
