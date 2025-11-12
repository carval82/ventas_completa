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
        Schema::create('conversiones_unidades', function (Blueprint $table) {
            $table->id();
            $table->string('unidad_origen', 20); // kg, g, l, ml, etc.
            $table->string('unidad_destino', 20); // kg, g, l, ml, etc.
            $table->decimal('factor_conversion', 15, 6); // Factor de conversión
            $table->string('categoria', 20); // peso, volumen, cantidad
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['unidad_origen', 'unidad_destino']);
            $table->index('categoria');
            
            // Evitar duplicados
            $table->unique(['unidad_origen', 'unidad_destino']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversiones_unidades');
    }
};
