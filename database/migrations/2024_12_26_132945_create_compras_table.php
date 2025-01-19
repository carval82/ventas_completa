<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   // create_compras_table
public function up()
{
    Schema::create('compras', function (Blueprint $table) {
        $table->id();
        $table->string('numero_factura');
        $table->timestamp('fecha_compra');
        $table->decimal('subtotal', 10, 2);
        $table->decimal('iva', 10, 2);
        $table->decimal('total', 10, 2);
        $table->foreignId('proveedor_id')->constrained('proveedores'); // Aquí especificamos el nombre correcto de la tabla
        $table->foreignId('user_id')->constrained();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
