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
        // Agregar campo id_alegra a la tabla clientes
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('id_alegra')->nullable()->after('estado');
            $table->index('id_alegra');
        });

        // Agregar campo id_alegra a la tabla productos
        Schema::table('productos', function (Blueprint $table) {
            $table->string('id_alegra')->nullable()->after('estado');
            $table->index('id_alegra');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('id_alegra');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('id_alegra');
        });
    }
};
