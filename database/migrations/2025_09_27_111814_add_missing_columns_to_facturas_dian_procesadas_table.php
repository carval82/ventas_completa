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
        Schema::table('facturas_dian_procesadas', function (Blueprint $table) {
            // Agregar columnas faltantes
            $table->string('remitente_nombre')->nullable()->after('remitente_email');
            $table->text('observaciones')->nullable()->after('errores');
            $table->string('archivo_original')->nullable()->after('ruta_pdf');
            $table->string('archivo_xml')->nullable()->after('archivo_original');
            $table->text('error_mensaje')->nullable()->after('errores');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas_dian_procesadas', function (Blueprint $table) {
            //
        });
    }
};
