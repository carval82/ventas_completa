<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('movimientos_internos', function (Blueprint $table) {
            // Cambiar 'ubicaciones' por 'ubicacions'
            $table->foreignId('ubicacion_origen_id')->constrained('ubicaciones');
            $table->foreignId('ubicacion_destino_id')->constrained('ubicaciones');
            // ... otros campos si los hay
        });
    }

    public function down()
    {
        Schema::table('movimientos_internos', function (Blueprint $table) {
            $table->dropForeign(['ubicacion_origen_id']);
            $table->dropForeign(['ubicacion_destino_id']);
            $table->dropColumn(['ubicacion_origen_id', 'ubicacion_destino_id']);
        });
    }
};