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
        Schema::create('movimientos_caja', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('caja_id');
            $table->dateTime('fecha');
            $table->enum('tipo', ['ingreso', 'gasto', 'pago']);
            $table->string('concepto');
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->string('referencia_tipo')->nullable();
            $table->decimal('monto', 12, 2);
            $table->string('metodo_pago')->default('efectivo');
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('caja_id')->references('id')->on('cajas_diarias');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_caja');
    }
};
