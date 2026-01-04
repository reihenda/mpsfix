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
        Schema::create('billings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->string('billing_number');
            $table->date('billing_date');
            $table->decimal('total_volume', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('total_deposit', 12, 2);
            $table->decimal('previous_balance', 12, 2);
            $table->decimal('current_balance', 12, 2);
            $table->decimal('amount_to_pay', 12, 2);
            $table->integer('period_month');
            $table->integer('period_year');
            $table->enum('status', ['paid', 'unpaid', 'partial', 'cancelled'])->default('unpaid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billings');
    }
};
