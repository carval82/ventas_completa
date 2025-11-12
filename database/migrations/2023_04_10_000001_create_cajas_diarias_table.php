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
        Schema::create('cajas_diarias', function (Blueprint $table) {
            $table->id();
            $table->dateTime('fecha_apertura');
            $table->dateTime('fecha_cierre')->nullable();
            $table->decimal('monto_apertura', 12, 2);
            $table->decimal('monto_cierre', 12, 2)->nullable();
            $table->decimal('total_ventas', 12, 2)->default(0);
            $table->decimal('total_gastos', 12, 2)->default(0);
            $table->decimal('total_pagos', 12, 2)->default(0);
            $table->decimal('diferencia', 12, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['abierta', 'cerrada'])->default('abierta');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cajas_diarias');
    }
};
