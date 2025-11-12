<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla principal para gestionar empresas/tenants
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // empresa-abc
            $table->string('nombre'); // Empresa ABC S.A.S
            $table->string('nit')->unique();
            $table->string('email');
            $table->string('telefono')->nullable();
            $table->text('direccion')->nullable();
            
            // Configuración de base de datos
            $table->string('database_name'); // ventas_empresa_abc
            $table->string('database_host')->default('localhost');
            $table->string('database_port')->default('3306');
            $table->string('database_username');
            $table->string('database_password');
            
            // Estado y configuración
            $table->boolean('activo')->default(true);
            $table->json('configuracion')->nullable(); // Configuraciones específicas
            $table->timestamp('fecha_creacion');
            $table->timestamp('fecha_expiracion')->nullable();
            
            // Plan y límites
            $table->string('plan')->default('basico'); // basico, premium, enterprise
            $table->integer('limite_usuarios')->default(5);
            $table->integer('limite_productos')->default(1000);
            $table->integer('limite_ventas_mes')->default(500);
            
            $table->timestamps();
            
            // Índices
            $table->index('slug');
            $table->index('activo');
            $table->index('plan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
