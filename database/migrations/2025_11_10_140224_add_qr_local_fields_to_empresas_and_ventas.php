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
        // Agregar campo en empresas para activar QR local
        Schema::table('empresas', function (Blueprint $table) {
            $table->boolean('generar_qr_local')
                  ->default(false)
                  ->after('formato_impresion')
                  ->comment('Generar QR y CUFE simulado para facturas locales');
        });
        
        // Agregar campos en ventas para CUFE y QR local
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('cufe_local', 255)
                  ->nullable()
                  ->after('qr_code')
                  ->comment('CUFE simulado generado localmente');
            $table->text('qr_local')
                  ->nullable()
                  ->after('cufe_local')
                  ->comment('Código QR generado localmente en base64');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('generar_qr_local');
        });
        
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['cufe_local', 'qr_local']);
        });
    }
};
