<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   // create_detalle_compras_table
public function up()
{
    Schema::create('detalle_compras', function (Blueprint $table) {
        $table->id();
        $table->foreignId('compra_id')->constrained('compras')->onDelete('cascade');
        $table->foreignId('producto_id')->constrained('productos');
        $table->integer('cantidad');
        $table->decimal('precio_unitario', 10, 2);
        $table->decimal('subtotal', 10, 2);
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_compras');
    }
};
