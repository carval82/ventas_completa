<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crear tabla de terceros según normativa colombiana NIF
     */
    public function up(): void
    {
        Schema::create('terceros', function (Blueprint $table) {
            $table->id();
            
            // Identificación
            $table->enum('tipo_documento', ['CC', 'CE', 'NIT', 'TI', 'PP', 'RC', 'TE']); // Tipos documento Colombia
            $table->string('numero_documento', 20)->unique();
            $table->string('digito_verificacion', 1)->nullable(); // Solo para NIT
            
            // Información básica
            $table->string('razon_social', 200);
            $table->string('nombre_comercial', 200)->nullable();
            $table->string('primer_nombre', 50)->nullable();
            $table->string('segundo_nombre', 50)->nullable();
            $table->string('primer_apellido', 50)->nullable();
            $table->string('segundo_apellido', 50)->nullable();
            
            // Clasificación
            $table->enum('tipo_persona', ['natural', 'juridica']);
            $table->enum('tipo_tercero', ['cliente', 'proveedor', 'empleado', 'accionista', 'otro']);
            $table->json('clasificaciones')->nullable(); // Puede ser múltiple: ["cliente", "proveedor"]
            
            // Régimen tributario
            $table->enum('regimen_fiscal', [
                'simplificado',
                'comun',
                'gran_contribuyente',
                'autorretenedor',
                'no_responsable',
                'regimen_simple'
            ]);
            
            // Responsabilidades fiscales (múltiples)
            $table->json('responsabilidades_fiscales')->nullable(); // Códigos DIAN
            
            // Información de contacto
            $table->string('email', 100)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('celular', 20)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->string('departamento', 100)->nullable();
            $table->string('codigo_postal', 10)->nullable();
            $table->string('pais', 50)->default('Colombia');
            
            // Información comercial
            $table->decimal('cupo_credito', 15, 2)->default(0);
            $table->integer('dias_credito')->default(0);
            $table->boolean('bloquear_cartera')->default(false);
            
            // Información bancaria
            $table->string('banco', 100)->nullable();
            $table->enum('tipo_cuenta', ['ahorros', 'corriente'])->nullable();
            $table->string('numero_cuenta', 50)->nullable();
            
            // Retenciones
            $table->boolean('autorretenedor_renta')->default(false);
            $table->boolean('autorretenedor_iva')->default(false);
            $table->boolean('autorretenedor_ica')->default(false);
            $table->decimal('porcentaje_retencion_renta', 5, 2)->default(0);
            $table->decimal('porcentaje_retencion_iva', 5, 2)->default(0);
            $table->decimal('porcentaje_retencion_ica', 5, 2)->default(0);
            
            // Información adicional
            $table->text('observaciones')->nullable();
            $table->boolean('estado')->default(true);
            $table->date('fecha_registro')->default(now());
            
            // Auditoría
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            
            // Índices
            $table->index(['tipo_documento', 'numero_documento']);
            $table->index('tipo_tercero');
            $table->index('regimen_fiscal');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terceros');
    }
};
