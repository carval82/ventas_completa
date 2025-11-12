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
            $table->string('prefijo_factura')->nullable()->after('resolucion_facturacion');
            $table->string('id_resolucion_alegra')->nullable()->after('prefijo_factura');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('id_resolucion_alegra');
            $table->dropColumn('prefijo_factura');
        });
    }
};
