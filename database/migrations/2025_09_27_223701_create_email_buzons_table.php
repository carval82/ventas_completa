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
        Schema::create('email_buzons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('mensaje_id')->unique(); // ID único del email
            $table->string('cuenta_email'); // Cuenta que recibió el email
            $table->string('remitente_email');
            $table->string('remitente_nombre')->nullable();
            $table->string('asunto');
            $table->text('contenido_texto')->nullable();
            $table->longText('contenido_html')->nullable();
            $table->timestamp('fecha_email')->nullable();
            $table->timestamp('fecha_descarga')->nullable();
            $table->json('archivos_adjuntos')->nullable(); // Lista de archivos adjuntos
            $table->boolean('tiene_facturas')->default(false);
            $table->boolean('procesado')->default(false);
            $table->timestamp('fecha_procesado')->nullable();
            $table->enum('estado', ['nuevo', 'procesando', 'procesado', 'error'])->default('nuevo');
            $table->json('metadatos')->nullable(); // Headers adicionales del email
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->index(['empresa_id', 'procesado']);
            $table->index(['cuenta_email', 'fecha_email']);
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_buzons');
    }
};
