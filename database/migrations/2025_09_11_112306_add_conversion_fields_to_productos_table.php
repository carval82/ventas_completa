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
            // Campos para conversiones
            $table->boolean('permite_conversiones')->default(false)->after('unidad_medida');
            $table->decimal('peso_por_unidad', 10, 4)->nullable()->after('permite_conversiones'); // kg por unidad
            $table->decimal('volumen_por_unidad', 10, 4)->nullable()->after('peso_por_unidad'); // litros por unidad
            $table->integer('unidades_por_bulto')->nullable()->after('volumen_por_unidad'); // unidades por bulto/caja
            $table->decimal('peso_por_bulto', 10, 4)->nullable()->after('unidades_por_bulto'); // kg por bulto
            $table->string('unidad_venta_alternativa', 20)->nullable()->after('peso_por_bulto'); // unidad alternativa de venta
            
            // Índices
            $table->index('permite_conversiones');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex(['permite_conversiones']);
            $table->dropColumn([
                'permite_conversiones',
                'peso_por_unidad',
                'volumen_por_unidad',
                'unidades_por_bulto',
                'peso_por_bulto',
                'unidad_venta_alternativa'
            ]);
        });
    }
};
