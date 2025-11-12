<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ConfiguracionDian;

echo "🔧 DIAGNÓSTICO COMPLETO DE IMAP\n";
echo "===============================\n\n";

// Verificar extensión IMAP
echo "1. VERIFICACIÓN DE EXTENSIÓN IMAP:\n";
echo "==================================\n";
echo "Extension loaded: " . (extension_loaded('imap') ? '✅ SÍ' : '❌ NO') . "\n";
echo "Function exists: " . (function_exists('imap_open') ? '✅ SÍ' : '❌ NO') . "\n";

if (extension_loaded('imap')) {
    $functions = get_extension_funcs('imap');
    echo "Funciones disponibles: " . count($functions) . "\n";
} else {
    echo "❌ IMAP no está disponible\n";
    exit;
}

echo "\n2. CONFIGURACIÓN:\n";
echo "=================\n";

$config = ConfiguracionDian::where('activo', true)->first();
if (!$config) {
    echo "❌ No hay configuración DIAN activa\n";
    exit;
}

echo "📧 Email: " . $config->email_dian . "\n";
echo "🔐 Password length: " . strlen($config->password_email) . "\n";
echo "🖥️  Servidor: " . $config->servidor_imap . "\n";
echo "🔌 Puerto: " . $config->puerto_imap . "\n";

echo "\n3. PRUEBA DE CONEXIÓN IMAP:\n";
echo "===========================\n";

$servidor = '{' . $config->servidor_imap . ':' . $config->puerto_imap . '/imap/ssl}INBOX';
$email = $config->email_dian;
$password = $config->password_email;

echo "🔗 Servidor completo: $servidor\n";
echo "👤 Usuario: $email\n";
echo "🔑 Password: " . str_repeat('*', strlen($password)) . "\n\n";

echo "🔄 Intentando conexión...\n";

// Limpiar errores previos
imap_errors();
imap_alerts();

$conexion = @imap_open($servidor, $email, $password);

if (!$conexion) {
    echo "❌ ERROR DE CONEXIÓN:\n";
    
    $errors = imap_errors();
    if ($errors) {
        echo "Errores IMAP:\n";
        foreach ($errors as $error) {
            echo "- $error\n";
        }
    }
    
    $alerts = imap_alerts();
    if ($alerts) {
        echo "Alertas IMAP:\n";
        foreach ($alerts as $alert) {
            echo "- $alert\n";
        }
    }
    
    $last_error = imap_last_error();
    if ($last_error) {
        echo "Último error: $last_error\n";
    }
    
    echo "\n💡 POSIBLES SOLUCIONES:\n";
    echo "1. Verificar que la contraseña de aplicación sea correcta\n";
    echo "2. Verificar que IMAP esté habilitado en Gmail\n";
    echo "3. Verificar que la verificación en 2 pasos esté activa\n";
    echo "4. Probar con diferentes configuraciones SSL\n";
    
    // Probar configuraciones alternativas
    echo "\n🔄 Probando configuraciones alternativas...\n";
    
    $configuraciones_alt = [
        '{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX',
        '{imap.gmail.com:993/imap/ssl/novalidate-cert/norsh}INBOX',
        '{imap.gmail.com:143/imap/tls}INBOX',
        '{imap.gmail.com:143/imap/notls}INBOX'
    ];
    
    foreach ($configuraciones_alt as $i => $config_alt) {
        echo ($i + 1) . ". Probando: $config_alt\n";
        $test_conexion = @imap_open($config_alt, $email, $password);
        if ($test_conexion) {
            echo "   ✅ ¡CONEXIÓN EXITOSA!\n";
            imap_close($test_conexion);
            break;
        } else {
            echo "   ❌ Falló\n";
        }
    }
    
} else {
    echo "✅ ¡CONEXIÓN EXITOSA!\n\n";
    
    echo "4. INFORMACIÓN DEL BUZÓN:\n";
    echo "=========================\n";
    
    $info = imap_mailboxmsginfo($conexion);
    echo "📊 Total de mensajes: " . $info->Nmsgs . "\n";
    echo "📧 Mensajes recientes: " . $info->Recent . "\n";
    echo "📭 Mensajes no leídos: " . $info->Unread . "\n";
    echo "📁 Tamaño del buzón: " . number_format($info->Size) . " bytes\n\n";
    
    echo "5. BÚSQUEDA DE EMAILS RECIENTES:\n";
    echo "================================\n";
    
    // Buscar emails de los últimos 30 días
    $fecha_desde = date('d-M-Y', strtotime('-30 days'));
    $busqueda = "SINCE \"$fecha_desde\"";
    echo "🔍 Criterio de búsqueda: $busqueda\n";
    
    $emails_ids = imap_search($conexion, $busqueda);
    
    if ($emails_ids) {
        echo "📧 Emails encontrados: " . count($emails_ids) . "\n\n";
        
        echo "6. PRIMEROS 5 EMAILS:\n";
        echo "=====================\n";
        
        $limite = min(5, count($emails_ids));
        for ($i = 0; $i < $limite; $i++) {
            $email_id = $emails_ids[$i];
            $header = imap_headerinfo($conexion, $email_id);
            
            $from = isset($header->from[0]) ? $header->from[0] : null;
            $remitente = $from ? $from->mailbox . '@' . $from->host : 'Desconocido';
            $remitente_nombre = $from ? (isset($from->personal) ? $from->personal : $from->mailbox) : 'Desconocido';
            $asunto = isset($header->subject) ? $header->subject : 'Sin asunto';
            $fecha = isset($header->date) ? $header->date : 'Sin fecha';
            
            echo ($i + 1) . ". Email ID: $email_id\n";
            echo "   De: $remitente ($remitente_nombre)\n";
            echo "   Asunto: $asunto\n";
            echo "   Fecha: $fecha\n\n";
        }
    } else {
        echo "📭 No se encontraron emails en los últimos 30 días\n";
        
        // Probar búsqueda más amplia
        echo "\n🔍 Probando búsqueda más amplia (últimos 90 días)...\n";
        $fecha_desde_amplia = date('d-M-Y', strtotime('-90 days'));
        $busqueda_amplia = "SINCE \"$fecha_desde_amplia\"";
        $emails_ids_amplia = imap_search($conexion, $busqueda_amplia);
        
        if ($emails_ids_amplia) {
            echo "📧 Emails encontrados (90 días): " . count($emails_ids_amplia) . "\n";
        } else {
            echo "📭 No se encontraron emails en los últimos 90 días\n";
        }
    }
    
    imap_close($conexion);
}

echo "\n🏁 Diagnóstico completado\n";
