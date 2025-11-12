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
        Schema::create('proveedores_electronicos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('nombre_proveedor');
            $table->string('email_proveedor');
            $table->string('nit_proveedor')->nullable();
            $table->json('dominios_email')->nullable(); // Para emails como @donjorgesas.com
            $table->json('palabras_clave')->nullable(); // Palabras clave en asunto
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->index(['empresa_id', 'activo']);
            $table->index('email_proveedor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores_electronicos');
    }
};
