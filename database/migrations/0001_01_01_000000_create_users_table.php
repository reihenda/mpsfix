<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'superadmin', 'customer'])->default('customer');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('data_pencatatan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('nama_customer');
            $table->json('data_input')->nullable();
            $table->decimal('harga_final', 15, 2)->default(0);
            $table->enum('status_pembayaran', ['belum_lunas', 'lunas'])->default('belum_lunas');
            $table->timestamps();

            $table->foreign('customer_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_pencatatan');
        Schema::dropIfExists('users');
    }
};
