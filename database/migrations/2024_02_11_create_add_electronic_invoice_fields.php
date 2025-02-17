<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('alegra_id')->nullable();
            $table->string('cufe')->nullable();
            $table->string('qr_code')->nullable();
            $table->string('estado_dian')->nullable();
            $table->text('xml_enviado')->nullable();
            $table->text('xml_respuesta')->nullable();
            $table->timestamp('fecha_validacion')->nullable();
            $table->string('url_pdf')->nullable();
            $table->string('url_xml')->nullable();
        });
    }

    public function down()
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn([
                'alegra_id', 'cufe', 'qr_code', 'estado_dian',
                'xml_enviado', 'xml_respuesta', 'fecha_validacion',
                'url_pdf', 'url_xml'
            ]);
        });
    }
}; 