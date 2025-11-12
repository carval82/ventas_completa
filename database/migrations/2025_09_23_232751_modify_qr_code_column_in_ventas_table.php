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
            // Cambiar qr_code de VARCHAR(255) a TEXT para soportar QR codes largos de DIAN
            $table->text('qr_code')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            // Revertir a VARCHAR(255)
            $table->string('qr_code', 255)->nullable()->change();
        });
    }
};
