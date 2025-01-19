<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('movimientos_internos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained();
            $table->foreignId('ubicacion_id')->nullable()->constrained('ubicaciones');
            $table->string('tipo_movimiento');
            $table->integer('cantidad');
            $table->string('motivo');
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('movimientos_internos');
    }
};