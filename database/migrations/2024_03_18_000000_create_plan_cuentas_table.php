<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('plan_cuentas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->enum('tipo', ['Activo', 'Pasivo', 'Patrimonio', 'Ingreso', 'Gasto']);
            $table->integer('nivel');
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });

        Schema::table('plan_cuentas', function (Blueprint $table) {
            $table->foreignId('cuenta_padre_id')->nullable()
                ->constrained('plan_cuentas')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('plan_cuentas');
    }
};