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
        Schema::create('producto_equivalencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->string('unidad_origen', 20); // ej: 'unidad', 'paca', 'bulto'
            $table->string('unidad_destino', 20); // ej: 'kg', 'lb', 'g'
            $table->decimal('factor_conversion', 10, 4); // ej: 25.0000 (1 paca = 25 libras)
            $table->string('descripcion')->nullable(); // ej: "1 paca contiene 25 libras"
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            // Índices con nombres personalizados más cortos
            $table->index(['producto_id', 'unidad_origen', 'unidad_destino'], 'prod_equiv_search_idx');
            $table->unique(['producto_id', 'unidad_origen', 'unidad_destino'], 'prod_equiv_unique_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_equivalencias');
    }
};
