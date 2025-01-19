<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // create_proveedores_table
public function up()
{
    Schema::create('proveedores', function (Blueprint $table) {
        $table->id();
        $table->string('nit')->unique();
        $table->string('razon_social');
        $table->string('regimen')->nullable();
        $table->string('tipo_identificacion')->nullable();
        $table->string('direccion');
        $table->string('ciudad')->nullable();
        $table->string('telefono');
        $table->string('celular')->nullable();
        $table->string('fax')->nullable();
        $table->string('correo_electronico')->nullable();
        $table->string('contacto')->nullable();
        $table->boolean('estado')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
