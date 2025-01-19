<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateConfiguracionContableTable extends Migration
{
    public function up()
    {
        Schema::table('configuracion_contable', function (Blueprint $table) {
            // Solo agregar campos que no existen
            if (!Schema::hasColumn('configuracion_contable', 'tipo_movimiento')) {
                $table->enum('tipo_movimiento', ['debito', 'credito'])->after('cuenta_id');
            }
            
            if (!Schema::hasColumn('configuracion_contable', 'estado')) {
                $table->boolean('estado')->default(true);
            }
            
            if (!Schema::hasColumn('configuracion_contable', 'modulo')) {
                $table->string('modulo')->nullable();
            }
            
            if (!Schema::hasColumn('configuracion_contable', 'orden')) {
                $table->integer('orden')->default(0);
            }
        });
    }

    public function down()
    {
        Schema::table('configuracion_contable', function (Blueprint $table) {
            // Solo eliminar las columnas que agregamos
            if (Schema::hasColumn('configuracion_contable', 'tipo_movimiento')) {
                $table->dropColumn('tipo_movimiento');
            }
            if (Schema::hasColumn('configuracion_contable', 'estado')) {
                $table->dropColumn('estado');
            }
            if (Schema::hasColumn('configuracion_contable', 'modulo')) {
                $table->dropColumn('modulo');
            }
            if (Schema::hasColumn('configuracion_contable', 'orden')) {
                $table->dropColumn('orden');
            }
        });
    }
}