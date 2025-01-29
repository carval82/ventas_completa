<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sugeridos_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->integer('cantidad_sugerida');
            $table->decimal('consumo_promedio_semanal', 10, 2);
            $table->integer('stock_actual');
            $table->integer('stock_minimo');
            $table->date('fecha_calculo');
            $table->enum('estado', ['pendiente', 'procesado', 'cancelado'])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sugeridos_compra');
    }
}; 