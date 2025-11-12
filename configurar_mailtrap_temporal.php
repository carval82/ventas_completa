<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

echo "📧 CONFIGURAR MAILTRAP TEMPORAL\n";
echo "===============================\n\n";

echo "🎯 OBJETIVO:\n";
echo "============\n";
echo "Configurar Mailtrap para probar el sistema mientras\n";
echo "verificas tu email en SendGrid.\n\n";

echo "📝 PASOS PARA MAILTRAP:\n";
echo "=======================\n";
echo "1. Ve a: https://mailtrap.io\n";
echo "2. Regístrate gratis (si no tienes cuenta)\n";
echo "3. Ve a 'Email Testing' > 'Inboxes'\n";
echo "4. Copia las credenciales SMTP\n\n";

echo "🔧 CREDENCIALES MAILTRAP TÍPICAS:\n";
echo "=================================\n";
echo "Host: sandbox.smtp.mailtrap.io\n";
echo "Port: 2525\n";
echo "Username: [tu_username]\n";
echo "Password: [tu_password]\n\n";

// Obtener usuario
$user = User::first();
echo "👤 Usuario: {$user->name} (Empresa ID: {$user->empresa_id})\n\n";

echo "🔄 ACTIVANDO CONFIGURACIÓN MAILTRAP...\n";
echo "======================================\n";

try {
    // Activar Mailtrap y desactivar SendGrid temporalmente
    DB::table('email_configurations')
      ->where('empresa_id', $user->empresa_id)
      ->where('proveedor', 'sendgrid')
      ->update(['activo' => 0]);
    
    $updated = DB::table('email_configurations')
                ->where('empresa_id', $user->empresa_id)
                ->where('nombre', 'Mailtrap (Desarrollo)')
                ->update([
                    'activo' => 1,
                    'es_backup' => 1,
                    'es_acuses' => 1,
                    'es_notificaciones' => 1,
                    'host' => 'sandbox.smtp.mailtrap.io',
                    'port' => 2525,
                    'username' => 'tu_username_aqui',
                    'password' => 'tu_password_aqui',
                    'from_address' => 'sistema@test.com',
                    'from_name' => 'Sistema DIAN Test'
                ]);
    
    if ($updated > 0) {
        echo "✅ Configuración Mailtrap activada\n";
        echo "⚠️ SendGrid desactivado temporalmente\n\n";
        
        echo "🔧 ACTUALIZAR CREDENCIALES:\n";
        echo "===========================\n";
        echo "Edita el archivo y actualiza:\n";
        echo "• username: tu_username_de_mailtrap\n";
        echo "• password: tu_password_de_mailtrap\n\n";
        
        echo "📝 COMANDO PARA ACTUALIZAR:\n";
        echo "===========================\n";
        echo "php actualizar_mailtrap.php username password\n\n";
        
    } else {
        echo "❌ No se pudo activar Mailtrap\n";
    }
    
    // Mostrar configuraciones actuales
    echo "📋 CONFIGURACIONES ACTUALES:\n";
    echo "============================\n";
    
    $configs = DB::table('email_configurations')
                ->where('empresa_id', $user->empresa_id)
                ->get();
    
    foreach ($configs as $config) {
        $estado = $config->activo ? '🟢 ACTIVA' : '🔴 INACTIVA';
        echo "• {$config->nombre} ({$config->proveedor}) - {$estado}\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n💡 ALTERNATIVA RÁPIDA:\n";
echo "=====================\n";
echo "Si prefieres usar Gmail directamente:\n";
echo "php configurar_gmail_app_password.php\n\n";

echo "🎯 DESPUÉS DE CONFIGURAR:\n";
echo "========================\n";
echo "1. php test_sistema_final.php\n";
echo "2. php artisan backup:database --send-email\n";
echo "3. Verifica emails en Mailtrap inbox\n\n";

echo "🏁 Configuración temporal lista\n";
