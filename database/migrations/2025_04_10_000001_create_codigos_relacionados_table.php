<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCodigosRelacionadosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('codigos_relacionados', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('descripcion')->nullable();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('codigos_relacionados');
    }
}
