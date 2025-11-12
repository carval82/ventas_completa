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
        Schema::create('detalle_cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->decimal('cantidad', 10, 3);
            $table->string('unidad_medida')->default('UND');
            $table->decimal('precio_unitario', 15, 2);
            $table->decimal('descuento_porcentaje', 5, 2)->default(0);
            $table->decimal('descuento_valor', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('impuesto_porcentaje', 5, 2)->default(0);
            $table->decimal('impuesto_valor', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            $table->index(['cotizacion_id', 'producto_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_cotizaciones');
    }
};
