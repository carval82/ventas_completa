<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConfiguracionContableTable extends Migration
{
    public function up()
    {
        Schema::create('configuracion_contable', function (Blueprint $table) {
            $table->id();
            $table->string('concepto');
            $table->foreignId('cuenta_id')->constrained('plan_cuentas');
            $table->string('descripcion')->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('configuracion_contable');
    }
}