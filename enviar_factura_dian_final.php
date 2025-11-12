<?php

// Script final para enviar facturas a la DIAN directamente
// Incluye verificación de existencia, apertura y emisión de facturas

// Cargar el framework Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Importar las clases necesarias
use Illuminate\Support\Facades\Log;

// ID de la factura a emitir (debe ser una factura existente en Alegra)
$idFactura = isset($argv[1]) ? $argv[1] : null;

if (!$idFactura) {
    echo "Error: Debe proporcionar el ID de la factura como argumento\n";
    echo "Uso: php enviar_factura_dian_final.php ID_FACTURA\n";
    exit(1);
}

// Obtener credenciales de Alegra
function obtenerCredencialesAlegra() {
    try {
        // Intentar obtener las credenciales de la empresa
        $empresa = \App\Models\Empresa::first();
        
        if ($empresa && $empresa->alegra_email && $empresa->alegra_token) {
            // Usar credenciales de la empresa
            $email = $empresa->alegra_email;
            $token = $empresa->alegra_token;
            echo "Usando credenciales de Alegra configuradas en la empresa\n";
        } else {
            // Usar credenciales del archivo .env como respaldo
            $email = config('alegra.user');
            $token = config('alegra.token');
            echo "Usando credenciales de Alegra del archivo .env\n";
        }
        
        if (empty($email) || empty($token)) {
            echo "Error: Credenciales de Alegra vacías\n";
            return null;
        }
        
        return [
            'email' => $email,
            'token' => $token
        ];
    } catch (\Exception $e) {
        echo "Error al obtener credenciales de Alegra: " . $e->getMessage() . "\n";
        return null;
    }
}

// Verificar el estado actual de la factura
function verificarEstadoFactura($idFactura, $credenciales) {
    echo "Verificando estado de la factura {$idFactura}...\n";
    
    // Configurar cURL
    $ch = curl_init();
    $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}";
    
    // Configurar opciones de cURL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($credenciales['email'] . ':' . $credenciales['token'])
    ]);
    
    // Ejecutar la solicitud
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    // Verificar si hubo errores
    if ($httpCode < 200 || $httpCode >= 300) {
        echo "Error al obtener estado de la factura: HTTP {$httpCode}\n";
        echo "Respuesta: {$response}\n";
        echo "Error cURL: {$error}\n";
        return null;
    }
    
    // Procesar la respuesta
    $factura = json_decode($response, true);
    
    echo "Estado actual de la factura: " . $factura['status'] . "\n";
    
    // Mostrar información detallada de la factura para depuración
    echo "Información detallada de la factura:\n";
    echo "ID: " . $factura['id'] . "\n";
    echo "Número: " . ($factura['numberTemplate']['fullNumber'] ?? 'No disponible') . "\n";
    echo "Fecha: " . $factura['date'] . "\n";
    echo "Cliente: " . $factura['client']['name'] . "\n";
    echo "Total: " . number_format($factura['total'], 2) . "\n";
    
    // Verificar si ya tiene CUFE (ya está emitida)
    if (isset($factura['stamp']) && isset($factura['stamp']['cufe'])) {
        echo "La factura ya está emitida electrónicamente con CUFE: " . $factura['stamp']['cufe'] . "\n";
        return 'emitida';
    }
    
    return $factura['status'];
}

// Abrir la factura (cambiar de estado borrador a abierta)
function abrirFactura($idFactura, $credenciales) {
    echo "Abriendo factura {$idFactura}...\n";
    
    // Configurar cURL
    $ch = curl_init();
    $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/open";
    
    // Datos para abrir la factura - exactamente como en AlegraService.php
    $datos = json_encode([
        'paymentForm' => 'CASH',
        'paymentMethod' => 'CASH'
    ]);
    
    echo "Datos enviados para abrir factura: " . $datos . "\n";
    
    // Configurar opciones de cURL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datos);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($credenciales['email'] . ':' . $credenciales['token'])
    ]);
    
    // Ejecutar la solicitud
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    // Mostrar respuesta completa para depuración
    echo "Respuesta HTTP: " . $httpCode . "\n";
    
    // Verificar si hubo errores
    if ($httpCode < 200 || $httpCode >= 300) {
        echo "Error al abrir la factura: HTTP {$httpCode}\n";
        echo "Respuesta: {$response}\n";
        echo "Error cURL: {$error}\n";
        return false;
    }
    
    echo "Factura abierta correctamente según la respuesta HTTP\n";
    
    // Esperar un momento para que Alegra procese el cambio (espera más larga)
    echo "Esperando 5 segundos para que Alegra procese el cambio...\n";
    sleep(5);
    
    return true;
}

// Enviar la factura a la DIAN
function enviarFacturaADian($idFactura, $credenciales) {
    echo "Enviando factura {$idFactura} a la DIAN...\n";
    
    // Configurar cURL
    $ch = curl_init();
    $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/stamp";
    
    // Datos para la solicitud
    $datos = json_encode([
        'generateStamp' => true,
        'generateQrCode' => true
    ]);
    
    echo "Datos enviados para emitir factura: " . $datos . "\n";
    
    // Configurar opciones de cURL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datos);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($credenciales['email'] . ':' . $credenciales['token'])
    ]);
    
    // Ejecutar la solicitud
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    // Mostrar respuesta completa para depuración
    echo "Respuesta HTTP: " . $httpCode . "\n";
    
    // Verificar si hubo errores
    if ($httpCode < 200 || $httpCode >= 300) {
        echo "Error al enviar la factura a la DIAN: HTTP {$httpCode}\n";
        echo "Respuesta: {$response}\n";
        echo "Error cURL: {$error}\n";
        
        // Intentar mostrar detalles del error
        $errorData = json_decode($response, true);
        if (isset($errorData['message'])) {
            echo "Mensaje de error: " . $errorData['message'] . "\n";
        }
        
        return false;
    }
    
    // Procesar la respuesta
    $data = json_decode($response, true);
    
    echo "Factura enviada a la DIAN correctamente\n";
    
    // Mostrar información del CUFE si está disponible
    if (isset($data['stamp']) && isset($data['stamp']['cufe'])) {
        echo "CUFE: " . $data['stamp']['cufe'] . "\n";
    }
    
    return true;
}

// Proceso principal
echo "=== Proceso de envío de factura a la DIAN (Versión Final) ===\n";

// Obtener credenciales
$credenciales = obtenerCredencialesAlegra();
if (!$credenciales) {
    exit(1);
}

// Verificar si la factura existe en Alegra
echo "Verificando si la factura {$idFactura} existe en Alegra...\n";
$ch = curl_init();
$url = "https://api.alegra.com/api/v1/invoices/{$idFactura}";

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($credenciales['email'] . ':' . $credenciales['token'])
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

if ($httpCode == 404) {
    echo "❌ Error: La factura con ID {$idFactura} no existe en Alegra o fue eliminada.\n";
    exit(1);
} else if ($httpCode < 200 || $httpCode >= 300) {
    echo "❌ Error al verificar la factura: HTTP {$httpCode}\n";
    echo "Respuesta: {$response}\n";
    echo "Error cURL: {$error}\n";
    exit(1);
}

echo "✅ La factura existe en Alegra.\n";

// Verificar estado actual
$estadoActual = verificarEstadoFactura($idFactura, $credenciales);
if (!$estadoActual) {
    exit(1);
}

// Si ya está emitida, terminar
if ($estadoActual === 'emitida') {
    echo "La factura ya está emitida electrónicamente. No es necesario procesarla.\n";
    exit(0);
}

// Si está en borrador, abrirla primero
if ($estadoActual === 'draft') {
    echo "La factura está en estado borrador, intentando abrirla...\n";
    
    // Intentar abrir la factura hasta 3 veces
    $intentos = 0;
    $abierta = false;
    
    while ($intentos < 3 && !$abierta) {
        $intentos++;
        echo "Intento #{$intentos} de abrir la factura...\n";
        
        if (abrirFactura($idFactura, $credenciales)) {
            // Verificar que cambió a estado abierto
            $nuevoEstado = verificarEstadoFactura($idFactura, $credenciales);
            
            if ($nuevoEstado === 'open') {
                echo "✅ La factura se abrió correctamente.\n";
                $abierta = true;
                break;
            } else {
                echo "⚠️ La factura no cambió a estado abierto después de intentar abrirla.\n";
                
                // Esperar un poco más antes del siguiente intento
                if ($intentos < 3) {
                    echo "Esperando 10 segundos antes del siguiente intento...\n";
                    sleep(10);
                }
            }
        } else {
            echo "⚠️ Error al intentar abrir la factura.\n";
            
            // Esperar un poco más antes del siguiente intento
            if ($intentos < 3) {
                echo "Esperando 10 segundos antes del siguiente intento...\n";
                sleep(10);
            }
        }
    }
    
    if (!$abierta) {
        echo "❌ No se pudo abrir la factura después de {$intentos} intentos. Proceso abortado.\n";
        exit(1);
    }
} else if ($estadoActual !== 'open') {
    echo "La factura está en estado {$estadoActual}, no se puede procesar. Debe estar en estado 'draft' o 'open'.\n";
    exit(1);
}

// Enviar a la DIAN
echo "Enviando factura a la DIAN...\n";
if (enviarFacturaADian($idFactura, $credenciales)) {
    echo "✅ Proceso completado correctamente. La factura ha sido enviada a la DIAN.\n";
} else {
    echo "❌ Error al enviar la factura a la DIAN. Verifique los logs para más detalles.\n";
}
