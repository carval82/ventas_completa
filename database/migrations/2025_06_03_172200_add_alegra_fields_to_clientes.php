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
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('ciudad')->nullable()->after('direccion');
            $table->string('departamento')->nullable()->after('ciudad');
            $table->string('tipo_documento')->default('CC')->after('departamento');
            $table->enum('tipo_persona', ['PERSON_ENTITY', 'LEGAL_ENTITY'])->default('PERSON_ENTITY')->after('tipo_documento');
            $table->enum('regimen', ['SIMPLIFIED_REGIME', 'COMMON_REGIME', 'SPECIAL_REGIME', 'NATIONAL_CONSUMPTION_TAX'])->default('SIMPLIFIED_REGIME')->after('tipo_persona');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn([
                'ciudad',
                'departamento',
                'tipo_documento',
                'tipo_persona',
                'regimen'
            ]);
        });
    }
};
