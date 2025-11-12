<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "🔧 CONFIGURANDO SENDGRID DE FORMA SEGURA\n";
echo "========================================\n\n";

// Autenticar usuario
$user = User::first();
if (!$user) {
    echo "❌ No se encontró usuario en el sistema\n";
    exit(1);
}

Auth::login($user);
echo "👤 Usuario: {$user->name} (Empresa ID: {$user->empresa_id})\n\n";

// API Key proporcionada
$apiKey = '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';

echo "🔑 API Key recibida: " . substr($apiKey, 0, 15) . "... (Longitud: " . strlen($apiKey) . " caracteres)\n\n";

try {
    // Actualizar directamente con DB para evitar problemas de encriptación
    $updated = DB::table('email_configurations')
                ->where('empresa_id', $user->empresa_id)
                ->where('proveedor', 'sendgrid')
                ->update([
                    'api_key' => $apiKey,
                    'from_address' => 'interveredanet.cr@gmail.com',
                    'from_name' => 'Sistema DIAN',
                    'activo' => 1,
                    'limite_diario' => 100,
                    'es_backup' => 1,
                    'es_acuses' => 1,
                    'es_notificaciones' => 1,
                    'updated_at' => now()
                ]);
    
    if ($updated > 0) {
        echo "✅ Configuración SendGrid actualizada exitosamente\n";
        
        // Verificar la actualización
        $config = DB::table('email_configurations')
                    ->where('empresa_id', $user->empresa_id)
                    ->where('proveedor', 'sendgrid')
                    ->first();
        
        echo "\n📋 CONFIGURACIÓN ACTUALIZADA:\n";
        echo "=============================\n";
        echo "ID: {$config->id}\n";
        echo "Nombre: {$config->nombre}\n";
        echo "Proveedor: {$config->proveedor}\n";
        echo "Email From: {$config->from_address}\n";
        echo "Nombre From: {$config->from_name}\n";
        echo "Estado: " . ($config->activo ? '🟢 ACTIVA' : '🔴 INACTIVA') . "\n";
        echo "Límite diario: {$config->limite_diario} emails\n";
        echo "API Key configurada: " . (strlen($config->api_key) > 10 ? '✅ SÍ' : '❌ NO') . "\n";
        echo "Backup: " . ($config->es_backup ? '✅' : '❌') . "\n";
        echo "Acuses: " . ($config->es_acuses ? '✅' : '❌') . "\n";
        echo "Notificaciones: " . ($config->es_notificaciones ? '✅' : '❌') . "\n";
        
    } else {
        echo "⚠️ No se encontró configuración SendGrid para actualizar\n";
        echo "💡 Creando nueva configuración...\n";
        
        // Crear nueva configuración
        $id = DB::table('email_configurations')->insertGetId([
            'empresa_id' => $user->empresa_id,
            'nombre' => 'SendGrid Principal',
            'proveedor' => 'sendgrid',
            'host' => 'smtp.sendgrid.net',
            'port' => 587,
            'username' => 'apikey',
            'api_key' => $apiKey,
            'encryption' => 'tls',
            'from_address' => 'interveredanet.cr@gmail.com',
            'from_name' => 'Sistema DIAN',
            'limite_diario' => 100,
            'activo' => 1,
            'es_backup' => 1,
            'es_acuses' => 1,
            'es_notificaciones' => 1,
            'emails_enviados_hoy' => 0,
            'fecha_reset_contador' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        echo "✅ Nueva configuración creada con ID: {$id}\n";
    }
    
    echo "\n⚠️ IMPORTANTE - VERIFICAR EMAIL EN SENDGRID:\n";
    echo "============================================\n";
    echo "1. Ve a https://app.sendgrid.com/settings/sender_auth\n";
    echo "2. Click 'Verify a Single Sender'\n";
    echo "3. Email: interveredanet.cr@gmail.com\n";
    echo "4. Nombre: Sistema DIAN\n";
    echo "5. Confirma en tu bandeja de entrada\n\n";
    
    echo "🧪 PROBAR CONFIGURACIÓN:\n";
    echo "========================\n";
    echo "1. php test_sistema_completo.php\n";
    echo "2. php artisan backup:database --send-email\n";
    echo "3. http://127.0.0.1:8000/email-configurations\n\n";
    
    echo "🎉 SendGrid configurado correctamente!\n";
    
} catch (\Exception $e) {
    echo "❌ Error configurando SendGrid: " . $e->getMessage() . "\n";
    echo "\n💡 Información del error:\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}

echo "\n🏁 Configuración completada\n";
