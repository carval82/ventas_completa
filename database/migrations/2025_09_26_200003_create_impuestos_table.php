<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crear tabla de configuración de impuestos colombianos
     */
    public function up(): void
    {
        Schema::create('impuestos', function (Blueprint $table) {
            $table->id();
            
            // Información básica del impuesto
            $table->string('codigo', 10)->unique(); // IVA19, RTE35, etc.
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            
            // Tipo de impuesto
            $table->enum('tipo', [
                'iva',
                'retencion_renta',
                'retencion_iva', 
                'retencion_ica',
                'impuesto_consumo',
                'otro'
            ]);
            
            // Configuración del impuesto
            $table->decimal('porcentaje', 8, 4); // Hasta 4 decimales para precisión
            $table->decimal('base_minima', 15, 2)->default(0); // Base mínima para aplicar
            $table->decimal('valor_fijo', 15, 2)->default(0); // Valor fijo si aplica
            
            // Configuración contable
            $table->foreignId('cuenta_impuesto_id')->nullable()->constrained('plan_cuentas');
            $table->foreignId('cuenta_por_pagar_id')->nullable()->constrained('plan_cuentas');
            $table->foreignId('cuenta_por_cobrar_id')->nullable()->constrained('plan_cuentas');
            
            // Configuración de aplicación
            $table->boolean('aplica_compras')->default(false);
            $table->boolean('aplica_ventas')->default(false);
            $table->boolean('es_retencion')->default(false);
            $table->boolean('calcula_sobre_iva')->default(false); // Para retención de IVA
            
            // Configuración por régimen
            $table->json('regimenes_aplica')->nullable(); // ['comun', 'simplificado']
            $table->json('responsabilidades_exentas')->nullable(); // Responsabilidades fiscales exentas
            
            // Rangos de aplicación (para UVT, salarios, etc.)
            $table->decimal('rango_desde', 15, 2)->nullable();
            $table->decimal('rango_hasta', 15, 2)->nullable();
            
            // Configuración adicional
            $table->boolean('activo')->default(true);
            $table->date('fecha_inicio')->default(now());
            $table->date('fecha_fin')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index('tipo');
            $table->index('activo');
            $table->index(['fecha_inicio', 'fecha_fin']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('impuestos');
    }
};
