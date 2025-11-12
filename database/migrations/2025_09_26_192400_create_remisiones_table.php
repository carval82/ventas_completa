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
        Schema::create('remisiones', function (Blueprint $table) {
            $table->id();
            $table->string('numero_remision')->unique();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->date('fecha_remision');
            $table->date('fecha_entrega')->nullable();
            $table->enum('estado', ['pendiente', 'en_transito', 'entregada', 'devuelta', 'cancelada'])->default('pendiente');
            $table->enum('tipo', ['venta', 'traslado', 'devolucion', 'muestra'])->default('venta');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('descuento', 15, 2)->default(0);
            $table->decimal('impuestos', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->string('direccion_entrega')->nullable();
            $table->string('transportador')->nullable();
            $table->string('vehiculo')->nullable();
            $table->string('conductor')->nullable();
            $table->string('cedula_conductor')->nullable();
            $table->foreignId('vendedor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->onDelete('set null'); // Si proviene de una venta
            $table->foreignId('cotizacion_id')->nullable()->constrained('cotizaciones')->onDelete('set null'); // Si proviene de una cotización
            $table->timestamps();
            
            $table->index(['estado', 'fecha_remision']);
            $table->index(['cliente_id', 'estado']);
            $table->index(['tipo', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remisiones');
    }
};
