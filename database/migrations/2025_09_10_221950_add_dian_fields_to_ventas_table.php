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
        Schema::table('ventas', function (Blueprint $table) {
            // Verificar si las columnas ya existen antes de agregarlas
            if (!Schema::hasColumn('ventas', 'cufe')) {
                $table->string('cufe')->nullable()->comment('Código Único de Facturación Electrónica');
            }
            if (!Schema::hasColumn('ventas', 'estado_dian')) {
                $table->enum('estado_dian', ['pendiente', 'enviado', 'aceptado', 'rechazado'])->default('pendiente');
            }
            if (!Schema::hasColumn('ventas', 'fecha_envio_dian')) {
                $table->timestamp('fecha_envio_dian')->nullable();
            }
            if (!Schema::hasColumn('ventas', 'respuesta_dian')) {
                $table->text('respuesta_dian')->nullable()->comment('Respuesta completa de DIAN');
            }
            if (!Schema::hasColumn('ventas', 'numero_factura_electronica')) {
                $table->string('numero_factura_electronica')->nullable()->comment('Número asignado por DIAN');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['cufe', 'estado_dian', 'fecha_envio_dian', 'respuesta_dian', 'numero_factura_electronica']);
        });
    }
};
