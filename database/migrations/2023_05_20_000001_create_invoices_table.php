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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->string('invoice_number');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('total_amount', 12, 2);
            $table->enum('status', ['paid', 'unpaid', 'partial', 'cancelled'])->default('unpaid');
            $table->text('description')->nullable();
            $table->string('no_kontrak');
            $table->string('id_pelanggan');
            $table->integer('period_month');
            $table->integer('period_year');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
