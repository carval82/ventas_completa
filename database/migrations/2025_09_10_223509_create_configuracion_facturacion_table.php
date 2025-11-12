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
        Schema::create('configuracion_facturacion', function (Blueprint $table) {
            $table->id();
            $table->string('proveedor')->unique(); // alegra, dian, siigo, worldoffice
            $table->json('configuracion'); // Datos de configuración específicos
            $table->boolean('activo')->default(false);
            $table->boolean('configurado')->default(false);
            $table->timestamp('ultima_prueba')->nullable();
            $table->text('resultado_prueba')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracion_facturacion');
    }
};
