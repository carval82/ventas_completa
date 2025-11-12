<?php
/**
 * Script para añadir columnas de IVA a la tabla detalle_compras
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

echo "=== Añadiendo columnas de IVA a la tabla detalle_compras ===\n\n";

try {
    // Verificar si las columnas ya existen
    if (!Schema::hasColumn('detalle_compras', 'tiene_iva')) {
        Schema::table('detalle_compras', function (Blueprint $table) {
            $table->boolean('tiene_iva')->default(false)->after('subtotal');
            echo "- Columna 'tiene_iva' añadida correctamente.\n";
        });
    } else {
        echo "- La columna 'tiene_iva' ya existe.\n";
    }
    
    if (!Schema::hasColumn('detalle_compras', 'porcentaje_iva')) {
        Schema::table('detalle_compras', function (Blueprint $table) {
            $table->decimal('porcentaje_iva', 5, 2)->default(0)->after('tiene_iva');
            echo "- Columna 'porcentaje_iva' añadida correctamente.\n";
        });
    } else {
        echo "- La columna 'porcentaje_iva' ya existe.\n";
    }
    
    if (!Schema::hasColumn('detalle_compras', 'valor_iva')) {
        Schema::table('detalle_compras', function (Blueprint $table) {
            $table->decimal('valor_iva', 12, 2)->default(0)->after('porcentaje_iva');
            echo "- Columna 'valor_iva' añadida correctamente.\n";
        });
    } else {
        echo "- La columna 'valor_iva' ya existe.\n";
    }
    
    if (!Schema::hasColumn('detalle_compras', 'total_con_iva')) {
        Schema::table('detalle_compras', function (Blueprint $table) {
            $table->decimal('total_con_iva', 12, 2)->default(0)->after('valor_iva');
            echo "- Columna 'total_con_iva' añadida correctamente.\n";
        });
    } else {
        echo "- La columna 'total_con_iva' ya existe.\n";
    }
    
    echo "\nColumnas añadidas correctamente a la tabla detalle_compras.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Proceso completado ===\n";
