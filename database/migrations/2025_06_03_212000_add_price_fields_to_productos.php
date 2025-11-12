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
        Schema::table('productos', function (Blueprint $table) {
            // Verificar si las columnas no existen antes de agregarlas
            if (!Schema::hasColumn('productos', 'precio_final')) {
                $table->decimal('precio_final', 10, 2)->after('precio_venta')->default(0);
            }
            
            if (!Schema::hasColumn('productos', 'valor_iva')) {
                // Solo agregar si existe la columna 'iva'
                if (Schema::hasColumn('productos', 'iva')) {
                    $table->decimal('valor_iva', 10, 2)->after('iva')->default(0);
                } else {
                    $table->decimal('valor_iva', 10, 2)->default(0);
                }
            }
            
            if (!Schema::hasColumn('productos', 'porcentaje_ganancia')) {
                if (Schema::hasColumn('productos', 'valor_iva')) {
                    $table->decimal('porcentaje_ganancia', 10, 2)->after('valor_iva')->default(0);
                } else {
                    $table->decimal('porcentaje_ganancia', 10, 2)->default(0);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (Schema::hasColumn('productos', 'precio_final')) {
                $table->dropColumn('precio_final');
            }
            if (Schema::hasColumn('productos', 'valor_iva')) {
                $table->dropColumn('valor_iva');
            }
            if (Schema::hasColumn('productos', 'porcentaje_ganancia')) {
                $table->dropColumn('porcentaje_ganancia');
            }
        });
    }
};
