<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EmailConfiguration;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "🔍 DEBUG: CONFIGURACIÓN DE BACKUP\n";
echo "=================================\n\n";

// Obtener usuario por defecto
$user = User::first();
echo "👤 Usuario: {$user->name} (Empresa ID: {$user->empresa_id})\n\n";

// Buscar configuraciones para backup
echo "📧 BUSCANDO CONFIGURACIONES PARA BACKUP:\n";
echo "========================================\n";

$configsBackup = EmailConfiguration::where('empresa_id', $user->empresa_id)
                                  ->where('es_backup', true)
                                  ->where('activo', true)
                                  ->get();

echo "Configuraciones encontradas: {$configsBackup->count()}\n";

foreach ($configsBackup as $config) {
    echo "\n📋 CONFIGURACIÓN #{$config->id}:\n";
    echo "  Nombre: {$config->nombre}\n";
    echo "  Proveedor: {$config->proveedor}\n";
    echo "  Activa: " . ($config->activo ? '✅' : '❌') . "\n";
    echo "  Es Backup: " . ($config->es_backup ? '✅' : '❌') . "\n";
    echo "  API Key configurada: " . (strlen($config->api_key ?? '') > 10 ? '✅' : '❌') . "\n";
    echo "  From: {$config->from_address}\n";
}

if ($configsBackup->isEmpty()) {
    echo "\n❌ NO SE ENCONTRARON CONFIGURACIONES PARA BACKUP\n";
    echo "💡 Verificando todas las configuraciones...\n\n";
    
    $todasConfigs = EmailConfiguration::where('empresa_id', $user->empresa_id)->get();
    
    foreach ($todasConfigs as $config) {
        echo "📧 {$config->nombre} ({$config->proveedor}):\n";
        echo "   Activa: " . ($config->activo ? '✅' : '❌') . "\n";
        echo "   Es Backup: " . ($config->es_backup ? '✅' : '❌') . "\n";
        echo "   Es Acuses: " . ($config->es_acuses ? '✅' : '❌') . "\n";
        echo "   Es Notificaciones: " . ($config->es_notificaciones ? '✅' : '❌') . "\n\n";
    }
    
    echo "🔧 ACTIVANDO CONFIGURACIÓN SENDGRID PARA BACKUP...\n";
    echo "==================================================\n";
    
    $sendgridConfig = EmailConfiguration::where('empresa_id', $user->empresa_id)
                                       ->where('proveedor', 'sendgrid')
                                       ->first();
    
    if ($sendgridConfig) {
        $sendgridConfig->update([
            'es_backup' => true,
            'activo' => true
        ]);
        
        echo "✅ Configuración SendGrid activada para backup\n";
        echo "📧 {$sendgridConfig->nombre} ahora puede enviar backups\n";
    } else {
        echo "❌ No se encontró configuración SendGrid\n";
    }
}

echo "\n🏁 Debug completado\n";
