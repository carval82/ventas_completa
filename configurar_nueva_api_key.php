<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

echo "🔑 CONFIGURAR NUEVA API KEY DE SENDGRID\n";
echo "======================================\n\n";

// Nueva API Key de SendGrid
$nuevaApiKey = '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';

echo "🔑 Nueva API Key: " . substr($nuevaApiKey, 0, 20) . "... (Longitud: " . strlen($nuevaApiKey) . ")\n\n";

// Obtener usuario
$user = User::first();
echo "👤 Usuario: {$user->name} (Empresa ID: {$user->empresa_id})\n\n";

echo "🔄 ACTUALIZANDO CONFIGURACIÓN SENDGRID...\n";
echo "=========================================\n";

try {
    // Actualizar API Key en SendGrid
    $updated = DB::table('email_configurations')
                ->where('empresa_id', $user->empresa_id)
                ->where('proveedor', 'sendgrid')
                ->update([
                    'api_key' => $nuevaApiKey,
                    'activo' => 1,
                    'es_backup' => 1,
                    'es_acuses' => 1,
                    'es_notificaciones' => 1,
                    'updated_at' => now()
                ]);
    
    // Desactivar otras configuraciones para usar SendGrid
    DB::table('email_configurations')
      ->where('empresa_id', $user->empresa_id)
      ->where('proveedor', '!=', 'sendgrid')
      ->update(['activo' => 0]);
    
    if ($updated > 0) {
        echo "✅ API Key actualizada exitosamente\n";
        echo "✅ SendGrid activado como proveedor principal\n";
        echo "✅ Otras configuraciones desactivadas\n\n";
        
        // Verificar configuración
        $config = DB::table('email_configurations')
                    ->where('empresa_id', $user->empresa_id)
                    ->where('proveedor', 'sendgrid')
                    ->first();
        
        echo "📋 CONFIGURACIÓN SENDGRID:\n";
        echo "=========================\n";
        echo "Nombre: {$config->nombre}\n";
        echo "Proveedor: {$config->proveedor}\n";
        echo "Host: {$config->host}\n";
        echo "Port: {$config->port}\n";
        echo "From: {$config->from_address}\n";
        echo "Estado: " . ($config->activo ? '🟢 ACTIVA' : '🔴 INACTIVA') . "\n";
        echo "API Key configurada: ✅\n";
        echo "Backup: " . ($config->es_backup ? '✅' : '❌') . "\n";
        echo "Acuses: " . ($config->es_acuses ? '✅' : '❌') . "\n\n";
        
        echo "⚠️ IMPORTANTE:\n";
        echo "==============\n";
        echo "Ahora necesitas verificar el email remitente en SendGrid:\n";
        echo "1. Ve a SendGrid > Settings > Sender Authentication\n";
        echo "2. Click 'Verify a Single Sender'\n";
        echo "3. Email: {$config->from_address}\n";
        echo "4. Confirma en tu bandeja de entrada\n\n";
        
        echo "🧪 PROBAR INMEDIATAMENTE:\n";
        echo "========================\n";
        echo "php test_sendgrid_nueva_api.php\n\n";
        
        echo "🎉 Nueva API Key configurada correctamente!\n";
        
    } else {
        echo "❌ No se pudo actualizar la configuración\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🏁 Configuración completada\n";
