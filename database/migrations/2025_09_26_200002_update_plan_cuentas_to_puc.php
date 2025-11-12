<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Actualizar plan de cuentas para cumplir con PUC colombiano
     */
    public function up(): void
    {
        Schema::table('plan_cuentas', function (Blueprint $table) {
            // Agregar campos para PUC colombiano
            $table->enum('clase', ['1', '2', '3', '4', '5', '6', '7', '8', '9'])->nullable()->after('codigo');
            $table->string('grupo', 2)->nullable()->after('clase');
            $table->string('cuenta', 4)->nullable()->after('grupo');
            $table->string('subcuenta', 6)->nullable()->after('cuenta');
            $table->string('auxiliar', 10)->nullable()->after('subcuenta');
            
            // Actualizar tipos según NIF
            $table->dropColumn('tipo');
        });
        
        Schema::table('plan_cuentas', function (Blueprint $table) {
            $table->enum('tipo_cuenta', [
                'activo_corriente',
                'activo_no_corriente', 
                'pasivo_corriente',
                'pasivo_no_corriente',
                'patrimonio',
                'ingreso_operacional',
                'ingreso_no_operacional',
                'costo_ventas',
                'gasto_operacional',
                'gasto_no_operacional',
                'cuenta_orden'
            ])->after('auxiliar');
            
            // Naturaleza de la cuenta
            $table->enum('naturaleza', ['debito', 'credito'])->after('tipo_cuenta');
            
            // Configuraciones adicionales
            $table->boolean('exige_tercero')->default(false)->after('naturaleza');
            $table->boolean('exige_centro_costo')->default(false)->after('exige_tercero');
            $table->boolean('maneja_base')->default(false)->after('exige_centro_costo');
            $table->boolean('cuenta_puente')->default(false)->after('maneja_base');
            
            // Descripción más detallada
            $table->text('descripcion')->nullable()->after('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_cuentas', function (Blueprint $table) {
            $table->dropColumn([
                'clase',
                'grupo', 
                'cuenta',
                'subcuenta',
                'auxiliar',
                'tipo_cuenta',
                'naturaleza',
                'exige_tercero',
                'exige_centro_costo',
                'maneja_base',
                'cuenta_puente',
                'descripcion'
            ]);
            
            $table->enum('tipo', ['Activo', 'Pasivo', 'Patrimonio', 'Ingreso', 'Gasto'])->after('nombre');
        });
    }
};
