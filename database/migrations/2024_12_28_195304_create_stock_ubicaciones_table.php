<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_ubicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained()->onDelete('cascade');
            $table->foreignId('ubicacion_id')->constrained('ubicaciones')->onDelete('cascade');
            $table->integer('stock')->default(0);
            $table->timestamps();

            $table->unique(['producto_id', 'ubicacion_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_ubicaciones');
    }
};