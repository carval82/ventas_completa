<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Actualizar tipos de comprobantes según NIF Colombia
     */
    public function up(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            // Eliminar la restricción del enum actual
            $table->dropColumn('tipo');
        });
        
        Schema::table('comprobantes', function (Blueprint $table) {
            // Agregar nuevos tipos de comprobante según NIF
            $table->enum('tipo', [
                'ingreso',
                'egreso', 
                'diario',
                'apertura',
                'cierre',
                'ajuste',
                'causacion',
                'nota_contable',
                'comprobante_ventas',
                'comprobante_compras',
                'nomina',
                'depreciacion',
                'provision',
                'conciliacion_bancaria',
                'diferencia_cambio'
            ])->after('fecha');
            
            // Agregar campos adicionales para NIF
            $table->string('concepto', 200)->nullable()->after('descripcion');
            $table->foreignId('tercero_id')->nullable()->constrained('terceros')->after('concepto');
            $table->string('documento_referencia', 50)->nullable()->after('tercero_id');
            $table->enum('moneda', ['COP', 'USD', 'EUR'])->default('COP')->after('documento_referencia');
            $table->decimal('tasa_cambio', 10, 4)->default(1)->after('moneda');
            
            // Campos de control
            $table->date('fecha_vencimiento')->nullable()->after('tasa_cambio');
            $table->boolean('afecta_terceros')->default(false)->after('fecha_vencimiento');
            $table->boolean('reversible')->default(true)->after('afecta_terceros');
            
            // Índices
            $table->index('tipo');
            $table->index('tercero_id');
            $table->index(['fecha', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropColumn([
                'concepto',
                'tercero_id',
                'documento_referencia', 
                'moneda',
                'tasa_cambio',
                'fecha_vencimiento',
                'afecta_terceros',
                'reversible'
            ]);
            
            $table->dropColumn('tipo');
        });
        
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->enum('tipo', ['Ingreso', 'Egreso', 'Diario'])->after('fecha');
        });
    }
};
