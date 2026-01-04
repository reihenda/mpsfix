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
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number')->unique();
            $table->foreignId('account_id')->constrained('financial_accounts');
            $table->date('transaction_date');
            $table->text('description')->nullable();
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->integer('year');
            $table->integer('month');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
