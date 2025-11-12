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
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('numero_cotizacion')->unique();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->date('fecha_cotizacion');
            $table->date('fecha_vencimiento');
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada', 'vencida', 'convertida'])->default('pendiente');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('descuento', 15, 2)->default(0);
            $table->decimal('impuestos', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->text('condiciones_comerciales')->nullable();
            $table->string('forma_pago')->nullable();
            $table->integer('dias_validez')->default(30);
            $table->foreignId('vendedor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->onDelete('set null'); // Si se convierte en venta
            $table->timestamps();
            
            $table->index(['estado', 'fecha_cotizacion']);
            $table->index(['cliente_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
