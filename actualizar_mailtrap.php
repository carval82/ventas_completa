<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

echo "🔧 ACTUALIZAR CREDENCIALES MAILTRAP\n";
echo "===================================\n\n";

// Obtener credenciales de argumentos
$username = $argv[1] ?? null;
$password = $argv[2] ?? null;

if (!$username || !$password) {
    echo "❌ Uso: php actualizar_mailtrap.php username password\n\n";
    echo "📝 EJEMPLO:\n";
    echo "php actualizar_mailtrap.php abc123def456 xyz789uvw012\n\n";
    echo "💡 OBTENER CREDENCIALES:\n";
    echo "1. Ve a https://mailtrap.io\n";
    echo "2. Login en tu cuenta\n";
    echo "3. Ve a Email Testing > Inboxes\n";
    echo "4. Selecciona tu inbox\n";
    echo "5. Ve a SMTP Settings\n";
    echo "6. Copia Username y Password\n\n";
    exit(1);
}

// Obtener usuario
$user = User::first();
echo "👤 Usuario: {$user->name} (Empresa ID: {$user->empresa_id})\n\n";

echo "🔑 CREDENCIALES RECIBIDAS:\n";
echo "=========================\n";
echo "Username: {$username}\n";
echo "Password: " . str_repeat('*', strlen($password)) . "\n\n";

try {
    // Actualizar configuración Mailtrap
    $updated = DB::table('email_configurations')
                ->where('empresa_id', $user->empresa_id)
                ->where('nombre', 'Mailtrap (Desarrollo)')
                ->update([
                    'username' => $username,
                    'password' => $password,
                    'activo' => 1,
                    'es_backup' => 1,
                    'es_acuses' => 1,
                    'es_notificaciones' => 1,
                    'updated_at' => now()
                ]);
    
    if ($updated > 0) {
        echo "✅ Credenciales Mailtrap actualizadas exitosamente\n\n";
        
        // Verificar configuración
        $config = DB::table('email_configurations')
                    ->where('empresa_id', $user->empresa_id)
                    ->where('nombre', 'Mailtrap (Desarrollo)')
                    ->first();
        
        echo "📋 CONFIGURACIÓN ACTUALIZADA:\n";
        echo "=============================\n";
        echo "Nombre: {$config->nombre}\n";
        echo "Host: {$config->host}\n";
        echo "Port: {$config->port}\n";
        echo "Username: {$config->username}\n";
        echo "Estado: " . ($config->activo ? '🟢 ACTIVA' : '🔴 INACTIVA') . "\n";
        echo "Backup: " . ($config->es_backup ? '✅' : '❌') . "\n";
        echo "Acuses: " . ($config->es_acuses ? '✅' : '❌') . "\n\n";
        
        echo "🧪 PROBAR CONFIGURACIÓN:\n";
        echo "========================\n";
        echo "1. php test_sistema_final.php\n";
        echo "2. php artisan backup:database --send-email\n";
        echo "3. Verifica emails en tu Mailtrap inbox\n\n";
        
        echo "🎉 Mailtrap configurado y listo para usar!\n";
        
    } else {
        echo "❌ No se pudo actualizar la configuración\n";
        echo "💡 Verifica que existe la configuración Mailtrap\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error actualizando credenciales: " . $e->getMessage() . "\n";
}

echo "\n🏁 Actualización completada\n";
