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
            // Campo para referenciar el producto base (padre)
            $table->unsignedBigInteger('producto_base_id')->nullable()->after('id');
            
            // Factor para calcular el stock del producto base
            // Ejemplo: 1 libra = 0.04 pacas, entonces factor_stock = 0.04
            $table->decimal('factor_stock', 10, 6)->default(1.000000)->after('producto_base_id');
            
            // Indica si este producto es el producto base (principal)
            $table->boolean('es_producto_base')->default(true)->after('factor_stock');
            
            // Agregar índices para mejorar performance
            $table->index('producto_base_id');
            $table->index('es_producto_base');
            
            // Relación con el producto base
            $table->foreign('producto_base_id')->references('id')->on('productos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['producto_base_id']);
            $table->dropIndex(['producto_base_id']);
            $table->dropIndex(['es_producto_base']);
            $table->dropColumn(['producto_base_id', 'factor_stock', 'es_producto_base']);
        });
    }
};
