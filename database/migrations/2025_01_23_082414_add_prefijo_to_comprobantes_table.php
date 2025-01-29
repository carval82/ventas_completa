<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Primero respaldamos los números existentes
        $comprobantes = DB::table('comprobantes')->get();
        
        Schema::table('comprobantes', function (Blueprint $table) {
            // Agregamos la columna prefijo
            $table->string('prefijo', 5)->after('id')->default('V');
            
            // Modificamos la columna numero temporalmente para evitar conflictos
            $table->string('numero_temp', 20)->after('numero')->nullable();
        });

        // Actualizamos los registros existentes
        foreach($comprobantes as $comprobante) {
            DB::table('comprobantes')
                ->where('id', $comprobante->id)
                ->update([
                    'prefijo' => 'V',
                    'numero_temp' => preg_replace('/[^0-9]/', '', $comprobante->numero)
                ]);
        }

        // Eliminamos la columna numero original y renombramos la temporal
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropColumn('numero');
        });

        Schema::table('comprobantes', function (Blueprint $table) {
            $table->renameColumn('numero_temp', 'numero');
        });
    }

    public function down()
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropColumn('prefijo');
        });
    }
};