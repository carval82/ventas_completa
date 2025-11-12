<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ConfiguracionDian;

echo "🧪 PROBANDO CONEXIÓN IMAP REAL\n";
echo "================================\n\n";

$config = ConfiguracionDian::where('activo', true)->first();

if (!$config) {
    echo "❌ No se encontró configuración activa\n";
    exit;
}

echo "📧 Email: " . $config->email_dian . "\n";
echo "🔐 Contraseña length: " . strlen($config->password_email) . " caracteres\n";
echo "🖥️  Servidor: " . $config->servidor_imap . "\n";
echo "🔌 Puerto: " . $config->puerto_imap . "\n\n";

// Verificar extensión IMAP
if (!function_exists('imap_open')) {
    echo "❌ Extensión IMAP no disponible\n";
    exit;
}

echo "✅ Extensión IMAP disponible\n\n";

// Configurar conexión
$servidor = '{' . $config->servidor_imap . ':' . $config->puerto_imap . '/imap/ssl}INBOX';
$email = $config->email_dian;
$password = $config->password_email;

echo "🔗 Intentando conexión a: $servidor\n";
echo "👤 Usuario: $email\n\n";

// Intentar conexión
$conexion = @imap_open($servidor, $email, $password);

if (!$conexion) {
    echo "❌ ERROR DE CONEXIÓN:\n";
    echo "Error: " . imap_last_error() . "\n\n";
    echo "💡 POSIBLES SOLUCIONES:\n";
    echo "1. Verificar que la contraseña de aplicación sea correcta\n";
    echo "2. Verificar que IMAP esté habilitado en Gmail\n";
    echo "3. Verificar que la verificación en 2 pasos esté activa\n";
} else {
    echo "✅ ¡CONEXIÓN EXITOSA!\n\n";
    
    // Obtener información del buzón
    $info = imap_mailboxmsginfo($conexion);
    echo "📊 INFORMACIÓN DEL BUZÓN:\n";
    echo "Total de mensajes: " . $info->Nmsgs . "\n";
    echo "Mensajes recientes: " . $info->Recent . "\n";
    echo "Mensajes no leídos: " . $info->Unread . "\n\n";
    
    // Buscar emails recientes
    $busqueda = 'SINCE "' . date('d-M-Y', strtotime('-7 days')) . '"';
    echo "🔍 Buscando emails de los últimos 7 días...\n";
    echo "Criterio: $busqueda\n";
    
    $emails_ids = imap_search($conexion, $busqueda);
    
    if ($emails_ids) {
        echo "📧 Encontrados " . count($emails_ids) . " emails recientes\n\n";
        
        // Mostrar los primeros 3 emails
        $limite = min(3, count($emails_ids));
        echo "📋 PRIMEROS $limite EMAILS:\n";
        
        for ($i = 0; $i < $limite; $i++) {
            $email_id = $emails_ids[$i];
            $header = imap_headerinfo($conexion, $email_id);
            
            $from = isset($header->from[0]) ? $header->from[0] : null;
            $remitente = $from ? $from->mailbox . '@' . $from->host : 'Desconocido';
            $asunto = isset($header->subject) ? $header->subject : 'Sin asunto';
            $fecha = isset($header->date) ? $header->date : 'Sin fecha';
            
            echo ($i + 1) . ". De: $remitente\n";
            echo "   Asunto: $asunto\n";
            echo "   Fecha: $fecha\n\n";
        }
    } else {
        echo "📭 No se encontraron emails recientes\n";
    }
    
    imap_close($conexion);
}

echo "\n🏁 Prueba completada\n";
