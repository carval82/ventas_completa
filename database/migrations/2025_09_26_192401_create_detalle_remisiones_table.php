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
        Schema::create('detalle_remisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remision_id')->constrained('remisiones')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->decimal('cantidad', 10, 3);
            $table->string('unidad_medida')->default('UND');
            $table->decimal('precio_unitario', 15, 2)->default(0);
            $table->decimal('descuento_porcentaje', 5, 2)->default(0);
            $table->decimal('descuento_valor', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('impuesto_porcentaje', 5, 2)->default(0);
            $table->decimal('impuesto_valor', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('cantidad_entregada', 10, 3)->default(0);
            $table->decimal('cantidad_devuelta', 10, 3)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            $table->index(['remision_id', 'producto_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_remisiones');
    }
};
