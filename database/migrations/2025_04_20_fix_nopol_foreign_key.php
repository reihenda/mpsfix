<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop foreign key constraint if exists
        Schema::table('rekap_pengambilan', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $foreignKeys = $sm->listTableForeignKeys('rekap_pengambilan');
            
            foreach ($foreignKeys as $foreignKey) {
                if ($foreignKey->getLocalColumns() === ['nopol']) {
                    $table->dropForeign($foreignKey->getName());
                    break;
                }
            }
        });
        
        // Update database to add correct foreign key with CASCADE
        Schema::table('rekap_pengambilan', function (Blueprint $table) {
            $table->foreign('nopol')
                  ->references('nopol')
                  ->on('nomor_polisi')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_pengambilan', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $foreignKeys = $sm->listTableForeignKeys('rekap_pengambilan');
            
            foreach ($foreignKeys as $foreignKey) {
                if ($foreignKey->getLocalColumns() === ['nopol']) {
                    $table->dropForeign($foreignKey->getName());
                    break;
                }
            }
        });
    }
};
