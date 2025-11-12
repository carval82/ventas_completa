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
        Schema::table('detalle_compras', function (Blueprint $table) {
            $table->string('unidad_medida')->default('UND')->after('cantidad');
            $table->decimal('factor_conversion', 10, 6)->default(1)->after('unidad_medida');
            $table->decimal('cantidad_stock', 10, 3)->after('factor_conversion')->comment('Cantidad que se agrega al stock (cantidad * factor_conversion)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detalle_compras', function (Blueprint $table) {
            $table->dropColumn(['unidad_medida', 'factor_conversion', 'cantidad_stock']);
        });
    }
};
