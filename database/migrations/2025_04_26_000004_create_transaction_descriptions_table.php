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
        Schema::create('transaction_descriptions', function (Blueprint $table) {
            $table->id();
            $table->string('description')->unique();
            $table->boolean('is_active')->default(true);
            $table->enum('category', ['kas', 'bank', 'both'])->default('both');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_descriptions');
    }
};
