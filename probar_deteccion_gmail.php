<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Dian\EmailProcessorService;

echo "=== PRUEBA DE DETECCIÓN DE CONFIGURACIÓN GMAIL ===\n\n";

// 1. Probar detección de Gmail
echo "🔍 1. DETECTANDO CONFIGURACIÓN DE GMAIL...\n";
$configGmail = EmailProcessorService::detectarConfiguracionGmail();

echo "📊 Resultados de la detección:\n";
foreach ($configGmail as $key => $value) {
    $emoji = $value === true ? '✅' : ($value === false ? '❌' : '📧');
    echo "  {$emoji} {$key}: " . ($value === null ? 'null' : ($value === true ? 'true' : ($value === false ? 'false' : $value))) . "\n";
}

echo "\n";

// 2. Verificar variables de entorno
echo "🌍 2. VERIFICANDO VARIABLES DE ENTORNO...\n";
$envVars = [
    'MAIL_MAILER' => env('MAIL_MAILER'),
    'MAIL_HOST' => env('MAIL_HOST'),
    'MAIL_PORT' => env('MAIL_PORT'),
    'MAIL_USERNAME' => env('MAIL_USERNAME'),
    'MAIL_PASSWORD' => env('MAIL_PASSWORD') ? '***CONFIGURADA***' : null,
    'MAIL_ENCRYPTION' => env('MAIL_ENCRYPTION'),
    'MAIL_FROM_ADDRESS' => env('MAIL_FROM_ADDRESS'),
    'MAIL_FROM_NAME' => env('MAIL_FROM_NAME')
];

foreach ($envVars as $var => $value) {
    $status = $value ? '✅' : '❌';
    echo "  {$status} {$var}: " . ($value ?: 'NO CONFIGURADA') . "\n";
}

echo "\n";

// 3. Recomendaciones
echo "💡 3. RECOMENDACIONES:\n";

if ($configGmail['configuracion_encontrada']) {
    echo "  🎉 ¡Excelente! Se detectó configuración de Gmail.\n";
    echo "  📧 Email detectado: {$configGmail['email_detectado']}\n";
    echo "  🌐 Servidor IMAP: {$configGmail['servidor_detectado']}:{$configGmail['puerto_detectado']}\n";
    echo "  🔒 SSL: " . ($configGmail['ssl_detectado'] ? 'Activado' : 'Desactivado') . "\n";
    echo "\n";
    echo "  ✅ PASOS SIGUIENTES:\n";
    echo "     1. Ve a la configuración del módulo DIAN\n";
    echo "     2. Haz clic en 'Autocompletar Configuración'\n";
    echo "     3. Verifica que los datos sean correctos\n";
    echo "     4. Prueba la conexión IMAP\n";
    echo "     5. Activa el módulo\n";
} else {
    echo "  ⚠️ No se detectó configuración de Gmail automáticamente.\n";
    echo "\n";
    echo "  🔧 CONFIGURACIÓN MANUAL NECESARIA:\n";
    echo "     1. Configura las variables MAIL_* en tu archivo .env\n";
    echo "     2. O configura manualmente en el módulo DIAN:\n";
    echo "        - Email: tu_email@gmail.com\n";
    echo "        - Servidor IMAP: imap.gmail.com\n";
    echo "        - Puerto: 993\n";
    echo "        - SSL: Activado\n";
    echo "        - Contraseña: tu_contraseña_o_app_password\n";
}

echo "\n";

// 4. Verificar extensión IMAP
echo "🔌 4. VERIFICANDO EXTENSIÓN IMAP DE PHP...\n";
if (extension_loaded('imap')) {
    echo "  ✅ Extensión IMAP está instalada y disponible\n";
    
    // Verificar funciones IMAP específicas
    $funcionesImap = ['imap_open', 'imap_search', 'imap_fetchstructure', 'imap_fetchbody', 'imap_close'];
    foreach ($funcionesImap as $funcion) {
        if (function_exists($funcion)) {
            echo "  ✅ Función {$funcion} disponible\n";
        } else {
            echo "  ❌ Función {$funcion} NO disponible\n";
        }
    }
} else {
    echo "  ❌ Extensión IMAP NO está instalada\n";
    echo "  🔧 Para instalar IMAP en XAMPP:\n";
    echo "     1. Descomenta ;extension=imap en php.ini\n";
    echo "     2. Reinicia Apache\n";
    echo "     3. Verifica con phpinfo()\n";
}

echo "\n";

// 5. Acceso directo
echo "🚀 5. ACCESO DIRECTO AL MÓDULO:\n";
echo "  🏠 Dashboard DIAN: http://127.0.0.1:8000/dian\n";
echo "  ⚙️ Configuración: http://127.0.0.1:8000/dian/configuracion\n";

echo "\n✅ Prueba de detección completada.\n";
