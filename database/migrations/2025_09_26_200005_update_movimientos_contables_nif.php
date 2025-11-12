<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Actualizar movimientos contables para NIF Colombia
     */
    public function up(): void
    {
        Schema::table('movimientos_contables', function (Blueprint $table) {
            // Información de terceros
            $table->foreignId('tercero_id')->nullable()->constrained('terceros')->after('cuenta_id');
            
            // Centro de costo (opcional para futuro)
            $table->string('centro_costo', 20)->nullable()->after('tercero_id');
            
            // Base para cálculos de retenciones
            $table->decimal('base_gravable', 15, 2)->default(0)->after('credito');
            $table->decimal('base_iva', 15, 2)->default(0)->after('base_gravable');
            
            // Información de impuestos
            $table->foreignId('impuesto_id')->nullable()->constrained('impuestos')->after('base_iva');
            $table->decimal('porcentaje_impuesto', 8, 4)->default(0)->after('impuesto_id');
            
            // Documento soporte
            $table->string('documento_soporte', 100)->nullable()->after('referencia_tipo');
            $table->date('fecha_documento')->nullable()->after('documento_soporte');
            
            // Conciliación bancaria
            $table->boolean('conciliado')->default(false)->after('fecha_documento');
            $table->date('fecha_conciliacion')->nullable()->after('conciliado');
            
            // Índices adicionales
            $table->index('tercero_id');
            $table->index('centro_costo');
            $table->index('impuesto_id');
            $table->index('conciliado');
            $table->index(['fecha', 'tercero_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimientos_contables', function (Blueprint $table) {
            $table->dropColumn([
                'tercero_id',
                'centro_costo',
                'base_gravable',
                'base_iva',
                'impuesto_id',
                'porcentaje_impuesto',
                'documento_soporte',
                'fecha_documento',
                'conciliado',
                'fecha_conciliacion'
            ]);
        });
    }
};
