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
        Schema::table('empresas', function (Blueprint $table) {
            $table->enum('formato_impresion', ['58mm', '80mm', 'media_carta'])
                  ->default('80mm')
                  ->after('logo')
                  ->comment('Formato de impresión predeterminado para facturas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('formato_impresion');
        });
    }
};
