<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('codigo_alegra')->nullable()->after('codigo');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->string('codigo_alegra')->nullable()->after('cedula');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->string('factura_alegra_id')->nullable()->after('numero_factura');
        });
    }

    public function down()
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('codigo_alegra');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('codigo_alegra');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('factura_alegra_id');
        });
    }
};