<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('movimientos_masivos_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movimiento_masivo_id')->constrained('movimientos_masivos')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained();
            $table->integer('cantidad');
            $table->decimal('costo_unitario', 10, 2)->nullable();
            $table->text('observacion_detalle')->nullable();
            $table->boolean('procesado')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('movimientos_masivos_detalle');
    }
};