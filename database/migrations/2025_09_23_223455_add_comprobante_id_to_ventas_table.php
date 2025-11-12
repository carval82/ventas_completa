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
        Schema::table('ventas', function (Blueprint $table) {
            // Agregar campo comprobante_id para relacionar con comprobantes contables
            $table->unsignedBigInteger('comprobante_id')->nullable()->after('user_id');
            
            // Agregar índice para mejorar performance
            $table->index('comprobante_id');
            
            // Agregar foreign key si la tabla comprobantes existe
            if (Schema::hasTable('comprobantes')) {
                $table->foreign('comprobante_id')->references('id')->on('comprobantes')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            // Eliminar foreign key si existe
            if (Schema::hasTable('comprobantes')) {
                $table->dropForeign(['comprobante_id']);
            }
            
            // Eliminar índice y columna
            $table->dropIndex(['comprobante_id']);
            $table->dropColumn('comprobante_id');
        });
    }
};
