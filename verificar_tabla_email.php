<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "🔍 VERIFICANDO TABLA EMAIL_CONFIGURATIONS\n";
echo "=========================================\n\n";

try {
    // Verificar si la tabla existe
    if (Schema::hasTable('email_configurations')) {
        echo "✅ Tabla 'email_configurations' existe\n\n";
        
        // Obtener estructura de la tabla
        $columns = DB::select("DESCRIBE email_configurations");
        
        echo "📋 ESTRUCTURA DE LA TABLA:\n";
        echo "==========================\n";
        foreach ($columns as $column) {
            echo "- {$column->Field} ({$column->Type}) " . 
                 ($column->Null === 'YES' ? 'NULL' : 'NOT NULL') . 
                 ($column->Key ? " [{$column->Key}]" : '') . "\n";
        }
        
        // Verificar si existe la columna api_key
        if (Schema::hasColumn('email_configurations', 'api_key')) {
            echo "\n✅ Columna 'api_key' existe\n";
        } else {
            echo "\n❌ Columna 'api_key' NO existe\n";
            echo "💡 Necesitas ejecutar: php artisan migrate:fresh\n";
        }
        
        // Contar registros existentes
        $count = DB::table('email_configurations')->count();
        echo "\n📊 Registros existentes: {$count}\n";
        
        if ($count > 0) {
            echo "\n📋 REGISTROS ACTUALES:\n";
            echo "=====================\n";
            $configs = DB::table('email_configurations')->get();
            foreach ($configs as $config) {
                echo "- ID: {$config->id} | Nombre: {$config->nombre} | Proveedor: {$config->proveedor}\n";
            }
        }
        
    } else {
        echo "❌ Tabla 'email_configurations' NO existe\n";
        echo "💡 Ejecuta: php artisan migrate\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error verificando tabla: " . $e->getMessage() . "\n";
    echo "\n💡 SOLUCIONES:\n";
    echo "==============\n";
    echo "1. php artisan migrate:fresh\n";
    echo "2. php artisan db:seed --class=EmailConfigurationSeeder\n";
}

echo "\n🏁 Verificación completada\n";
