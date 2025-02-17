<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('regimen_tributario');
            
            $table->enum('regimen_tributario', [
                'responsable_iva',
                'no_responsable_iva',
                'regimen_simple'
            ])->default('no_responsable_iva');
            
            $table->string('resolucion_facturacion')->nullable();
            $table->date('fecha_resolucion')->nullable();
            $table->boolean('factura_electronica_habilitada')->default(false);
        });
    }

    public function down()
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('regimen_tributario');
            $table->string('regimen_tributario');
            
            $table->dropColumn([
                'resolucion_facturacion',
                'fecha_resolucion',
                'factura_electronica_habilitada'
            ]);
        });
    }
}; 