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
            if (!Schema::hasColumn('productos', 'peso_bulto')) {
                $table->decimal('peso_bulto', 8, 3)->nullable()->after('unidad_medida')->comment('Peso del bulto en kilogramos');
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
            if (Schema::hasColumn('productos', 'peso_bulto')) {
                $table->dropColumn('peso_bulto');
            }
        });
    }
};
