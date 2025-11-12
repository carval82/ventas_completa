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
        Schema::table('empresas', function (Blueprint $table) {
            $table->boolean('alegra_multiples_impuestos')->default(false)->after('alegra_token')
                ->comment('Indica si la empresa tiene habilitada la característica de múltiples impuestos en Alegra');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('alegra_multiples_impuestos');
        });
    }
};
