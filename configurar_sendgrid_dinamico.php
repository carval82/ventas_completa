<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EmailConfiguration;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "🔧 CONFIGURANDO SENDGRID EN SISTEMA DINÁMICO\n";
echo "============================================\n\n";

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

echo "🔑 API Key recibida: " . substr($apiKey, 0, 15) . "...\n\n";

// Buscar configuración SendGrid existente
$configSendGrid = EmailConfiguration::where('empresa_id', $user->empresa_id)
                                   ->where('proveedor', 'sendgrid')
                                   ->first();

if ($configSendGrid) {
    echo "📧 Configuración SendGrid encontrada: {$configSendGrid->nombre}\n";
    
    // Actualizar con API Key
    $configSendGrid->update([
        'api_key' => $apiKey,
        'from_address' => 'interveredanet.cr@gmail.com',
        'from_name' => 'Sistema DIAN',
        'activo' => true,
        'limite_diario' => 100,
        'es_backup' => true,
        'es_acuses' => true,
        'es_notificaciones' => true
    ]);
    
    echo "✅ Configuración actualizada exitosamente\n";
} else {
    echo "📧 Creando nueva configuración SendGrid...\n";
    
    // Crear nueva configuración
    $configSendGrid = EmailConfiguration::create([
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
        'activo' => true,
        'es_backup' => true,
        'es_acuses' => true,
        'es_notificaciones' => true,
        'fecha_reset_contador' => now()->toDateString(),
        'configuracion_adicional' => [
            'descripcion' => 'Configuración SendGrid principal',
            'plan' => 'gratuito',
            'limite_mensual' => 3000,
            'configurado_automaticamente' => true,
            'fecha_configuracion' => now()->toISOString()
        ]
    ]);
    
    echo "✅ Nueva configuración creada exitosamente\n";
}

echo "\n📋 DETALLES DE LA CONFIGURACIÓN:\n";
echo "================================\n";
echo "ID: {$configSendGrid->id}\n";
echo "Nombre: {$configSendGrid->nombre}\n";
echo "Proveedor: {$configSendGrid->proveedor}\n";
echo "Email From: {$configSendGrid->from_address}\n";
echo "Nombre From: {$configSendGrid->from_name}\n";
echo "Estado: " . ($configSendGrid->activo ? '🟢 ACTIVA' : '🔴 INACTIVA') . "\n";
echo "Límite diario: {$configSendGrid->limite_diario} emails\n";
echo "Backup: " . ($configSendGrid->es_backup ? '✅' : '❌') . "\n";
echo "Acuses: " . ($configSendGrid->es_acuses ? '✅' : '❌') . "\n";
echo "Notificaciones: " . ($configSendGrid->es_notificaciones ? '✅' : '❌') . "\n";

echo "\n⚠️ IMPORTANTE:\n";
echo "==============\n";
echo "1. Verifica el email 'interveredanet.cr@gmail.com' en SendGrid\n";
echo "2. Ve a Settings > Sender Authentication\n";
echo "3. Click 'Verify a Single Sender'\n";
echo "4. Confirma el email en tu bandeja de entrada\n\n";

echo "🧪 PRÓXIMOS PASOS:\n";
echo "==================\n";
echo "1. php test_sistema_completo.php (Probar sistema)\n";
echo "2. php artisan backup:database --send-email (Probar backup)\n";
echo "3. Ir a http://127.0.0.1:8000/email-configurations (Ver configuraciones)\n";
echo "4. Ir a http://127.0.0.1:8000/dian/buzon (Probar acuses)\n\n";

echo "🎉 SendGrid configurado y listo para usar!\n";
