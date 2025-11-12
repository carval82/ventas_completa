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
        Schema::create('movimientos_contables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comprobante_id')->constrained('comprobantes');
            $table->foreignId('cuenta_id')->constrained('plan_cuentas');
            $table->date('fecha');
            $table->text('descripcion');
            $table->decimal('debito', 12, 2)->default(0);
            $table->decimal('credito', 12, 2)->default(0);
            $table->string('referencia')->nullable();
            $table->string('referencia_tipo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_contables');
    }
};
