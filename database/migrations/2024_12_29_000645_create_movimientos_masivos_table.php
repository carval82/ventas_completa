<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('movimientos_masivos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_documento')->unique();
            $table->foreignId('ubicacion_destino_id')->constrained('ubicaciones');
            $table->foreignId('ubicacion_origen_id')->constrained('ubicaciones');
            $table->string('tipo_movimiento')->default('entrada');
            $table->string('motivo');
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->enum('estado', ['borrador', 'procesado', 'anulado'])->default('borrador');
            $table->timestamp('fecha_proceso')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('movimientos_masivos');
    }
};