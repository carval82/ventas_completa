<?php
echo "🔧 CONFIGURADOR AUTOMÁTICO DE SENDGRID\n";
echo "======================================\n\n";

$envPath = __DIR__ . '/.env';

if (!file_exists($envPath)) {
    echo "❌ Archivo .env no encontrado\n";
    exit(1);
}

echo "📝 INGRESA TU API KEY DE SENDGRID:\n";
echo "==================================\n";
echo "1. Ve a https://sendgrid.com\n";
echo "2. Crea cuenta gratuita (100 emails/día)\n";
echo "3. Ve a Settings > API Keys\n";
echo "4. Crea API Key con permisos 'Mail Send'\n";
echo "5. Copia la API Key (empieza con SG.)\n\n";

echo "🔑 Ingresa tu API Key de SendGrid: ";
$handle = fopen("php://stdin", "r");
$apiKey = trim(fgets($handle));
fclose($handle);

if (empty($apiKey)) {
    echo "❌ API Key no puede estar vacía\n";
    exit(1);
}

if (!str_starts_with($apiKey, 'SG.')) {
    echo "⚠️ Advertencia: La API Key debería empezar con 'SG.'\n";
    echo "¿Continuar de todos modos? (s/n): ";
    $handle = fopen("php://stdin", "r");
    $continuar = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($continuar) !== 's' && strtolower($continuar) !== 'si') {
        echo "❌ Configuración cancelada\n";
        exit(1);
    }
}

echo "\n📧 CONFIGURANDO .ENV:\n";
echo "=====================\n";

// Leer archivo .env actual
$envContent = file_get_contents($envPath);

// Configuración SendGrid
$sendgridConfig = [
    'MAIL_MAILER' => 'smtp',
    'MAIL_HOST' => 'smtp.sendgrid.net',
    'MAIL_PORT' => '587',
    'MAIL_USERNAME' => 'apikey',
    'MAIL_PASSWORD' => $apiKey,
    'MAIL_ENCRYPTION' => 'tls',
    'MAIL_FROM_ADDRESS' => 'interveredanet.cr@gmail.com',
    'MAIL_FROM_NAME' => '"Sistema DIAN"'
];

// Actualizar o agregar cada configuración
foreach ($sendgridConfig as $key => $value) {
    $pattern = "/^{$key}=.*$/m";
    $replacement = "{$key}={$value}";
    
    if (preg_match($pattern, $envContent)) {
        // Actualizar existente
        $envContent = preg_replace($pattern, $replacement, $envContent);
        echo "✅ Actualizado: {$key}\n";
    } else {
        // Agregar nuevo
        $envContent .= "\n{$replacement}";
        echo "✅ Agregado: {$key}\n";
    }
}

// Guardar archivo .env
if (file_put_contents($envPath, $envContent)) {
    echo "\n🎉 CONFIGURACIÓN COMPLETADA\n";
    echo "===========================\n";
    echo "✅ Archivo .env actualizado\n";
    echo "✅ SendGrid configurado correctamente\n\n";
    
    echo "📋 CONFIGURACIÓN APLICADA:\n";
    echo "==========================\n";
    foreach ($sendgridConfig as $key => $value) {
        $displayValue = $key === 'MAIL_PASSWORD' ? substr($value, 0, 10) . '...' : $value;
        echo "{$key}={$displayValue}\n";
    }
    
    echo "\n⚠️ IMPORTANTE:\n";
    echo "==============\n";
    echo "1. Ve a SendGrid > Settings > Sender Authentication\n";
    echo "2. Verifica el email: interveredanet.cr@gmail.com\n";
    echo "3. Revisa tu bandeja de entrada y confirma\n";
    echo "4. Sin verificación, los emails no se enviarán\n\n";
    
    echo "🧪 PRUEBAS DISPONIBLES:\n";
    echo "=======================\n";
    echo "1. php artisan config:clear\n";
    echo "2. php test_sendgrid.php\n";
    echo "3. php artisan backup:database --send-email\n";
    echo "4. Probar acuses DIAN desde dashboard\n\n";
    
    echo "📊 LÍMITES GRATUITOS:\n";
    echo "=====================\n";
    echo "• 100 emails por día\n";
    echo "• Estadísticas básicas\n";
    echo "• Soporte por email\n\n";
    
    echo "🎯 PRÓXIMO PASO:\n";
    echo "================\n";
    echo "Ejecuta: php artisan config:clear\n";
    echo "Luego: php test_sendgrid.php\n\n";
    
} else {
    echo "❌ Error escribiendo archivo .env\n";
    exit(1);
}

echo "🏁 Configuración automática completada\n";
