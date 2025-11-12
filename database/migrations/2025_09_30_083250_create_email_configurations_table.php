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
        Schema::create('email_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('nombre')->comment('Nombre descriptivo de la configuración');
            $table->enum('proveedor', ['smtp', 'sendgrid', 'mailgun', 'ses', 'postmark'])->default('smtp');
            $table->string('host')->nullable();
            $table->integer('port')->nullable();
            $table->string('username')->nullable();
            $table->text('password')->nullable(); // Encriptado
            $table->enum('encryption', ['tls', 'ssl', 'none'])->default('tls');
            $table->string('from_address');
            $table->string('from_name');
            $table->string('api_key')->nullable(); // Para SendGrid, Mailgun, etc.
            $table->json('configuracion_adicional')->nullable(); // Configuraciones específicas del proveedor
            $table->boolean('activo')->default(true);
            $table->boolean('es_backup')->default(false)->comment('Para envío de backups');
            $table->boolean('es_acuses')->default(false)->comment('Para envío de acuses DIAN');
            $table->boolean('es_notificaciones')->default(false)->comment('Para notificaciones generales');
            $table->integer('limite_diario')->nullable()->comment('Límite de emails por día');
            $table->integer('emails_enviados_hoy')->default(0);
            $table->date('fecha_reset_contador')->nullable();
            $table->timestamp('ultimo_envio')->nullable();
            $table->json('estadisticas')->nullable()->comment('Estadísticas de envío');
            $table->timestamps();
            
            // Índices
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->index(['empresa_id', 'activo']);
            $table->index(['empresa_id', 'proveedor']);
            $table->unique(['empresa_id', 'nombre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_configurations');
    }
};
