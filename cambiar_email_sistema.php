<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔄 CAMBIAR EMAIL DEL SISTEMA\n";
echo "============================\n\n";

// Obtener email del argumento o pedir al usuario
$nuevoEmail = $argv[1] ?? null;

if (!$nuevoEmail) {
    echo "📧 Ingresa el nuevo email que quieres usar:\n";
    echo "Ejemplo: tu_email@gmail.com\n";
    echo "Email: ";
    
    $handle = fopen("php://stdin", "r");
    $nuevoEmail = trim(fgets($handle));
    fclose($handle);
}

// Validar email
if (!filter_var($nuevoEmail, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Email inválido: {$nuevoEmail}\n";
    echo "💡 Usa un formato válido como: usuario@dominio.com\n";
    exit(1);
}

echo "\n🔍 VERIFICANDO EMAIL: {$nuevoEmail}\n";
echo "====================================\n";

// Mostrar configuraciones actuales
$configs = DB::table('email_configurations')->get();

echo "📋 CONFIGURACIONES ACTUALES:\n";
foreach ($configs as $config) {
    echo "• ID {$config->id}: {$config->nombre} - {$config->from_address}\n";
}

echo "\n🔄 ACTUALIZANDO CONFIGURACIONES...\n";
echo "===================================\n";

try {
    // Actualizar todas las configuraciones
    $updated = DB::table('email_configurations')
                ->update([
                    'from_address' => $nuevoEmail,
                    'updated_at' => now()
                ]);
    
    echo "✅ {$updated} configuraciones actualizadas\n";
    
    // Mostrar configuraciones actualizadas
    echo "\n📋 CONFIGURACIONES ACTUALIZADAS:\n";
    $configsUpdated = DB::table('email_configurations')->get();
    
    foreach ($configsUpdated as $config) {
        echo "• ID {$config->id}: {$config->nombre} - {$config->from_address}\n";
    }
    
    echo "\n⚠️ IMPORTANTE - VERIFICAR EN SENDGRID:\n";
    echo "=====================================\n";
    echo "1. Ve a: https://app.sendgrid.com/settings/sender_auth\n";
    echo "2. Verifica el email: {$nuevoEmail}\n";
    echo "3. Si no está verificado, agrégalo como nuevo sender\n";
    echo "4. Confirma la verificación en tu bandeja de entrada\n\n";
    
    echo "🧪 PROBAR NUEVA CONFIGURACIÓN:\n";
    echo "==============================\n";
    echo "1. php test_sendgrid_directo.php\n";
    echo "2. php artisan backup:database --send-email\n";
    echo "3. http://127.0.0.1:8000/email-configurations\n\n";
    
    echo "✅ Email del sistema cambiado exitosamente!\n";
    echo "📧 Nuevo email: {$nuevoEmail}\n";
    
} catch (\Exception $e) {
    echo "❌ Error actualizando configuraciones: " . $e->getMessage() . "\n";
    echo "\n💡 Verifica que la base de datos esté disponible\n";
}

echo "\n🏁 Proceso completado\n";
