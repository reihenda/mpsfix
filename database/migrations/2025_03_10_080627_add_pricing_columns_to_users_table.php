<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPricingColumnsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Harga per meter kubik
            $table->decimal('harga_per_meter_kubik', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Harga per meter kubik');

            // Tekanan keluar (Bar)
            $table->decimal('tekanan_keluar', 10, 3)
                ->nullable()
                ->default(0)
                ->comment('Tekanan keluar dalam Bar');

            // Suhu (Celsius)
            $table->decimal('suhu', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Suhu dalam Celsius');

            // Koreksi meter
            $table->decimal('koreksi_meter', 16, 14)
                ->nullable()
                ->default(1)
                ->comment('Faktor koreksi meter');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'harga_per_meter_kubik',
                'tekanan_keluar',
                'suhu',
                'koreksi_meter'
            ]);
        });
    }
}
