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
//             $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
//             $table->dateTime('tanggal');
//             $table->string('nopol', 20);
//             $table->float('volume', 8, 2);
//             $table->text('keterangan')->nullable();
//             $table->timestamps();
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
