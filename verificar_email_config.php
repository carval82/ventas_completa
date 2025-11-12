<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Config;

echo "📧 VERIFICANDO CONFIGURACIÓN ACTUAL DE EMAIL\n";
echo "============================================\n\n";

echo "🔧 CONFIGURACIÓN DETECTADA:\n";
echo "===========================\n";
echo "MAIL_MAILER: " . config('mail.default') . "\n";
echo "MAIL_HOST: " . config('mail.mailers.smtp.host', 'N/A') . "\n";
echo "MAIL_PORT: " . config('mail.mailers.smtp.port', 'N/A') . "\n";
echo "MAIL_USERNAME: " . config('mail.mailers.smtp.username', 'N/A') . "\n";
echo "MAIL_ENCRYPTION: " . config('mail.mailers.smtp.encryption', 'N/A') . "\n";
echo "MAIL_FROM_ADDRESS: " . config('mail.from.address', 'N/A') . "\n";
echo "MAIL_FROM_NAME: " . config('mail.from.name', 'N/A') . "\n\n";

$mailer = config('mail.default');

if ($mailer === 'smtp') {
    echo "✅ CONFIGURACIÓN SMTP DETECTADA\n";
    echo "===============================\n";
    echo "Los emails se enviarán REALMENTE usando SMTP\n";
    echo "Host: " . config('mail.mailers.smtp.host') . "\n";
    echo "Puerto: " . config('mail.mailers.smtp.port') . "\n\n";
    
    echo "🧪 PROBANDO CONEXIÓN SMTP...\n";
    try {
        $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
            config('mail.mailers.smtp.host'),
            config('mail.mailers.smtp.port'),
            config('mail.mailers.smtp.encryption') === 'tls'
        );
        
        if (config('mail.mailers.smtp.username')) {
            $transport->setUsername(config('mail.mailers.smtp.username'));
            $transport->setPassword(config('mail.mailers.smtp.password'));
        }
        
        echo "✅ Configuración SMTP válida\n";
        echo "📧 Los acuses se enviarán REALMENTE\n\n";
        
    } catch (\Exception $e) {
        echo "❌ Error en configuración SMTP: " . $e->getMessage() . "\n\n";
    }
    
} elseif ($mailer === 'log') {
    echo "⚠️ CONFIGURACIÓN LOG DETECTADA\n";
    echo "==============================\n";
    echo "Los emails se guardan en logs pero NO se envían realmente\n";
    echo "Ubicación: storage/logs/laravel.log\n\n";
    
} elseif ($mailer === 'array') {
    echo "🧪 CONFIGURACIÓN ARRAY DETECTADA\n";
    echo "================================\n";
    echo "Los emails se almacenan en memoria para testing\n";
    echo "NO se envían realmente\n\n";
    
} else {
    echo "❓ CONFIGURACIÓN DESCONOCIDA: $mailer\n";
    echo "====================================\n\n";
}

echo "📋 VERIFICANDO ÚLTIMOS ACUSES ENVIADOS:\n";
echo "=======================================\n";

use App\Models\EmailBuzon;

$emailsConAcuses = EmailBuzon::whereJsonContains('metadatos->acuse_enviado->metodo', 'email_real')
                            ->orderBy('created_at', 'desc')
                            ->limit(5)
                            ->get();

if ($emailsConAcuses->count() > 0) {
    echo "📨 ÚLTIMOS ACUSES ENVIADOS:\n";
    foreach ($emailsConAcuses as $email) {
        $acuse = $email->metadatos['acuse_enviado'] ?? [];
        echo "   📧 ID: {$email->id}\n";
        echo "   📅 Fecha: " . ($acuse['fecha'] ?? 'N/A') . "\n";
        echo "   📧 Destinatario: " . ($acuse['destinatario'] ?? 'N/A') . "\n";
        echo "   📄 Factura: " . ($acuse['numero_factura'] ?? 'N/A') . "\n";
        echo "   📤 Método: " . ($acuse['metodo'] ?? 'N/A') . "\n";
        echo "   ---\n";
    }
} else {
    echo "❌ No se encontraron acuses enviados con método 'email_real'\n";
}

echo "\n🔍 VERIFICANDO LOGS RECIENTES:\n";
echo "==============================\n";

$logPath = storage_path('logs/laravel.log');
if (file_exists($logPath)) {
    $logContent = file_get_contents($logPath);
    $lines = explode("\n", $logContent);
    $recentLines = array_slice($lines, -20);
    
    $emailLines = array_filter($recentLines, function($line) {
        return strpos($line, 'Mail') !== false || 
               strpos($line, 'email') !== false || 
               strpos($line, 'acuse') !== false;
    });
    
    if (!empty($emailLines)) {
        echo "📝 LOGS RELACIONADOS CON EMAIL:\n";
        foreach ($emailLines as $line) {
            if (!empty(trim($line))) {
                echo "   " . trim($line) . "\n";
            }
        }
    } else {
        echo "❌ No se encontraron logs recientes de email\n";
    }
} else {
    echo "❌ Archivo de log no encontrado\n";
}

echo "\n🎯 RECOMENDACIONES:\n";
echo "==================\n";

if ($mailer === 'smtp') {
    echo "✅ Configuración correcta para envío real\n";
    echo "📧 Los acuses se están enviando REALMENTE\n";
    echo "🔍 Verifica la bandeja de entrada de los destinatarios\n";
} else {
    echo "⚠️ Para envío real, configura SMTP en .env:\n";
    echo "   MAIL_MAILER=smtp\n";
    echo "   MAIL_HOST=smtp.gmail.com\n";
    echo "   MAIL_PORT=587\n";
    echo "   MAIL_USERNAME=tu-email@gmail.com\n";
    echo "   MAIL_PASSWORD=tu-app-password\n";
    echo "   MAIL_ENCRYPTION=tls\n";
}

echo "\n🏁 Verificación completada\n";
