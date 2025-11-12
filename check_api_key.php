<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\EmailConfiguration;

echo "🔍 VERIFICANDO API KEY\n";
echo "=====================\n\n";

// Verificar directamente en BD
$configDB = DB::table('email_configurations')->where('id', 1)->first();
echo "📋 DATOS DIRECTOS DE BD:\n";
echo "API Key length: " . strlen($configDB->api_key) . "\n";
echo "API Key starts with: " . substr($configDB->api_key, 0, 10) . "...\n\n";

// Verificar con modelo (desencriptado)
$configModel = EmailConfiguration::find(1);
echo "📋 DATOS DEL MODELO (DESENCRIPTADO):\n";
echo "API Key length: " . strlen($configModel->api_key ?? '') . "\n";
echo "API Key starts with: " . substr($configModel->api_key ?? '', 0, 10) . "...\n\n";

// Probar configuración
echo "🧪 PROBANDO CONFIGURACIÓN:\n";
echo "==========================\n";

if ($configModel->api_key && strlen($configModel->api_key) > 50) {
    echo "✅ API Key parece válida\n";
    
    // Probar configuración de mail
    $mailConfig = $configModel->toMailConfig();
    echo "📧 Configuración de mail generada:\n";
    echo "   Host: {$mailConfig['host']}\n";
    echo "   Port: {$mailConfig['port']}\n";
    echo "   Username: {$mailConfig['username']}\n";
    echo "   Password length: " . strlen($mailConfig['password']) . "\n";
    
} else {
    echo "❌ API Key no válida o no configurada\n";
    echo "💡 Reconfigurar API Key...\n";
    
    $apiKey = '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';
    
    // Actualizar directamente
    DB::table('email_configurations')
      ->where('id', 1)
      ->update(['api_key' => $apiKey]);
    
    echo "✅ API Key reconfigurada\n";
}

echo "\n🏁 Verificación completada\n";
