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
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('numero_factura_alegra')->nullable()->after('alegra_id');
            $table->text('url_pdf_alegra')->nullable()->after('numero_factura_alegra');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['numero_factura_alegra', 'url_pdf_alegra']);
        });
    }
};
