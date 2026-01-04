<?php

// use Illuminate\Database\Migrations\Migration;
// use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\Schema;

// return new class extends Migration
// {
//     /**
//      * Run the migrations.
//      */
//     public function up(): void
//     {
//         Schema::create('rekap_pengambilan', function (Blueprint $table) {
//             $table->id();
//             $table->date('tanggal');
//             $table->time('waktu');
//             $table->unsignedBigInteger('customer_id');
//             $table->string('nopol', 20);
//             $table->decimal('volume', 10, 2);
//             $table->timestamps();

//             $table->foreign('customer_id')->references('id')->on('users');
//         });
//     }

//     /**
//      * Reverse the migrations.
//      */
//     public function down(): void
//     {
//         Schema::dropIfExists('rekap_pengambilan');
//     }
// };
