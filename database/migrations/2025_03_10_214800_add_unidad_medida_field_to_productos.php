<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('productos', function (Blueprint $table) {
            if (!Schema::hasColumn('productos', 'unidad_medida')) {
                $table->string('unidad_medida')->default('unit')->after('estado');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('productos', function (Blueprint $table) {
            if (Schema::hasColumn('productos', 'unidad_medida')) {
                $table->dropColumn('unidad_medida');
            }
        });
    }
};
