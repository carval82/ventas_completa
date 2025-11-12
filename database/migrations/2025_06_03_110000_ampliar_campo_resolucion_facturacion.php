<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AmpliarCampoResolucionFacturacion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('empresas', function (Blueprint $table) {
            // Modificar el campo resolucion_facturacion para que sea de tipo TEXT
            // TEXT puede almacenar hasta 65,535 caracteres
            $table->text('resolucion_facturacion')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('empresas', function (Blueprint $table) {
            // Revertir a VARCHAR (asumiendo que era VARCHAR antes)
            $table->string('resolucion_facturacion')->change();
        });
    }
}
