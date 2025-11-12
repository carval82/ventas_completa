<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('productos', function (Blueprint $table) {
        $table->id();
        $table->string('codigo')->unique();
        $table->string('nombre');
        $table->text('descripcion')->nullable();
        $table->decimal('precio_compra', 10, 2);
        $table->decimal('precio_venta', 10, 2);
        $table->integer('stock');
        $table->integer('stock_minimo');
        $table->boolean('estado')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
