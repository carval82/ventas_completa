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
        Schema::create('facturas_dian_procesadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            
            // Información del Email
            $table->string('mensaje_id')->unique(); // ID único del mensaje de email
            $table->string('asunto_email');
            $table->string('remitente_email');
            $table->timestamp('fecha_email');
            
            // Información de la Factura
            $table->string('cufe')->nullable(); // Código CUFE extraído
            $table->string('numero_factura')->nullable();
            $table->string('nit_emisor')->nullable();
            $table->string('nombre_emisor')->nullable();
            $table->decimal('valor_total', 15, 2)->nullable();
            $table->date('fecha_factura')->nullable();
            
            // Archivos Procesados
            $table->json('archivos_adjuntos'); // Lista de archivos adjuntos
            $table->json('archivos_extraidos')->nullable(); // Archivos extraídos de ZIP/RAR
            $table->string('ruta_xml')->nullable(); // Ruta del archivo XML principal
            $table->string('ruta_pdf')->nullable(); // Ruta del PDF si existe
            
            // Estado del Procesamiento
            $table->enum('estado', ['pendiente', 'procesando', 'procesada', 'error', 'acuse_enviado'])
                  ->default('pendiente');
            $table->text('detalles_procesamiento')->nullable(); // Log del procesamiento
            $table->text('errores')->nullable(); // Errores encontrados
            
            // Acuse de Recibido
            $table->boolean('acuse_enviado')->default(false);
            $table->timestamp('fecha_acuse')->nullable();
            $table->string('id_acuse')->nullable(); // ID del email de acuse enviado
            $table->text('contenido_acuse')->nullable();
            
            // Metadatos
            $table->integer('intentos_procesamiento')->default(0);
            $table->timestamp('ultimo_intento')->nullable();
            $table->json('metadatos_adicionales')->nullable(); // Información extra en JSON
            
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['empresa_id', 'estado']);
            $table->index(['cufe']);
            $table->index(['fecha_email']);
            $table->index(['acuse_enviado']);
            $table->index(['nit_emisor']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas_dian_procesadas');
    }
};
