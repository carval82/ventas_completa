<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pagos_credito', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('credito_id');
            $table->decimal('monto', 10, 2);
            $table->date('fecha_pago');
            $table->string('comprobante')->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->foreign('credito_id')
                  ->references('id')
                  ->on('creditos')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pagos_credito');
    }
}; 