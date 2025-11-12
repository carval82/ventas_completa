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
        Schema::create('configuracion_dian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            
            // Configuración del Email DIAN
            $table->string('email_dian')->nullable();
            $table->string('password_email')->nullable();
            $table->string('servidor_imap')->default('imap.gmail.com');
            $table->integer('puerto_imap')->default(993);
            $table->boolean('ssl_enabled')->default(true);
            
            // Configuración de Procesamiento
            $table->string('carpeta_descarga')->default('storage/facturas_dian');
            $table->string('carpeta_procesadas')->default('storage/facturas_procesadas');
            $table->string('carpeta_errores')->default('storage/facturas_errores');
            
            // Configuración de Acuses
            $table->string('email_remitente')->nullable();
            $table->string('nombre_remitente')->nullable();
            $table->text('plantilla_acuse')->nullable();
            
            // Configuración de Automatización
            $table->boolean('procesamiento_automatico')->default(true);
            $table->integer('frecuencia_minutos')->default(60); // Cada hora
            $table->time('hora_inicio')->default('08:00:00');
            $table->time('hora_fin')->default('18:00:00');
            
            // Estadísticas
            $table->integer('facturas_procesadas')->default(0);
            $table->integer('acuses_enviados')->default(0);
            $table->timestamp('ultimo_procesamiento')->nullable();
            
            // Estado del módulo
            $table->boolean('activo')->default(false);
            $table->text('configuracion_adicional')->nullable(); // JSON para configs extras
            
            $table->timestamps();
            
            // Índices
            $table->index(['empresa_id', 'activo']);
            $table->index('ultimo_procesamiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracion_dian');
    }
};
