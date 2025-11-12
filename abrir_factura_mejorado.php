<?php
/**
 * Script para abrir facturas en Alegra usando un enfoque mejorado
 * Este script puede ser usado directamente o incluido en otros scripts
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Empresa;
use Illuminate\Support\Facades\Log;

/**
 * Función para abrir una factura en Alegra (cambiar estado de draft a open)
 * 
 * @param string $facturaId ID de la factura en Alegra
 * @param bool $verbose Si es true, muestra mensajes detallados en la consola
 * @param int $maxIntentos Número máximo de intentos por formato
 * @return array Resultado de la operación
 */
function abrirFacturaAlegra($facturaId, $verbose = true, $maxIntentos = 3) {
    // Obtener credenciales
    $empresa = Empresa::first();
    
    if (!$empresa || empty($empresa->alegra_email) || empty($empresa->alegra_token)) {
        $mensaje = "Error: No se encontraron credenciales de Alegra válidas.";
        if ($verbose) echo $mensaje . "\n";
        Log::error($mensaje);
        return ['success' => false, 'mensaje' => $mensaje];
    }
    
    $email = $empresa->alegra_email;
    $token = $empresa->alegra_token;
    
    // Verificar el estado actual de la factura
    $estadoInicial = consultarEstadoFacturaAlegra($facturaId, $email, $token, $verbose);
    
    if (!$estadoInicial['success']) {
        $mensaje = "Error al consultar el estado inicial de la factura.";
        if ($verbose) echo $mensaje . "\n";
        Log::error($mensaje, ['factura_id' => $facturaId, 'error' => $estadoInicial['error'] ?? 'Error desconocido']);
        return $estadoInicial;
    }
    
    // Si la factura ya está en estado 'open', no es necesario cambiarla
    if ($estadoInicial['data']['status'] === 'open') {
        $mensaje = "La factura ya está en estado open, no es necesario cambiarla.";
        if ($verbose) echo $mensaje . "\n";
        Log::info($mensaje, ['factura_id' => $facturaId]);
        return [
            'success' => true,
            'mensaje' => $mensaje,
            'data' => $estadoInicial['data']
        ];
    }
    
    // Si la factura no está en estado 'draft', puede haber problemas al cambiarla
    if ($estadoInicial['data']['status'] !== 'draft') {
        $mensaje = "La factura no está en estado draft, puede haber problemas al cambiarla.";
        if ($verbose) echo $mensaje . "\n";
        Log::warning($mensaje, ['factura_id' => $facturaId, 'estado_actual' => $estadoInicial['data']['status']]);
    }
    
    // Formatos de payload a intentar, en orden de preferencia
    $formatos = [
        'completo' => [
            'paymentForm' => 'CASH',
            'paymentMethod' => 'CASH'
        ],
        'minimo' => [
            'paymentForm' => 'CASH'
        ],
        'vacio' => []
    ];
    
    // Intentar cada formato con múltiples intentos
    foreach ($formatos as $nombreFormato => $datosFormato) {
        for ($intento = 1; $intento <= $maxIntentos; $intento++) {
            if ($verbose) echo "\nIntentando abrir factura con formato '{$nombreFormato}' (intento {$intento}/{$maxIntentos})...\n";
            Log::info("Intentando abrir factura con formato", [
                'factura_id' => $facturaId,
                'formato' => $nombreFormato,
                'intento' => $intento,
                'datos' => $datosFormato
            ]);
            
            // Preparar los datos
            $datos = json_encode($datosFormato);
            
            // Configurar cURL
            $ch = curl_init();
            $url = "https://api.alegra.com/api/v1/invoices/{$facturaId}/open";
            
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $datos);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($email . ':' . $token)
            ]);
            
            // Ejecutar la solicitud
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            // Registrar la respuesta
            Log::info('Respuesta de apertura de factura', [
                'factura_id' => $facturaId,
                'formato' => $nombreFormato,
                'intento' => $intento,
                'http_code' => $httpCode,
                'error' => $error,
                'response' => $response
            ]);
            
            if ($verbose) {
                echo "HTTP Code: {$httpCode}\n";
                if (!empty($error)) echo "Error cURL: {$error}\n";
                echo "Respuesta: " . substr($response, 0, 100) . (strlen($response) > 100 ? "..." : "") . "\n";
            }
            
            // Esperar un momento para que el cambio se aplique
            sleep(2);
            
            // Verificar el estado actual de la factura
            $nuevoEstado = consultarEstadoFacturaAlegra($facturaId, $email, $token, $verbose);
            
            if ($nuevoEstado['success'] && $nuevoEstado['data']['status'] === 'open') {
                $mensaje = "Factura abierta correctamente con formato '{$nombreFormato}' en el intento {$intento}.";
                if ($verbose) echo "\n✅ {$mensaje}\n";
                Log::info($mensaje, [
                    'factura_id' => $facturaId,
                    'formato_exitoso' => $nombreFormato,
                    'intento' => $intento
                ]);
                
                return [
                    'success' => true,
                    'mensaje' => $mensaje,
                    'data' => $nuevoEstado['data'],
                    'formato_exitoso' => $nombreFormato,
                    'intento' => $intento
                ];
            }
            
            $mensaje = "La solicitud fue procesada pero la factura no cambió a estado open.";
            if ($verbose) echo "❌ {$mensaje}\n";
            Log::warning($mensaje, [
                'factura_id' => $facturaId,
                'formato' => $nombreFormato,
                'intento' => $intento,
                'http_code' => $httpCode,
                'estado_actual' => $nuevoEstado['data']['status'] ?? 'desconocido'
            ]);
            
            // Esperar un poco más antes del siguiente intento
            sleep(1);
        }
    }
    
    // Si llegamos aquí, ninguno de los formatos funcionó
    $mensaje = "No se pudo abrir la factura con ninguno de los formatos intentados.";
    if ($verbose) echo "\n⚠️ {$mensaje}\n";
    Log::error($mensaje, [
        'factura_id' => $facturaId,
        'formatos_intentados' => array_keys($formatos),
        'max_intentos' => $maxIntentos
    ]);
    
    return [
        'success' => false,
        'mensaje' => $mensaje,
        'formatos_intentados' => array_keys($formatos),
        'max_intentos' => $maxIntentos
    ];
}

/**
 * Función para consultar el estado de una factura en Alegra
 * 
 * @param string $facturaId ID de la factura en Alegra
 * @param string $email Email de Alegra
 * @param string $token Token de Alegra
 * @param bool $verbose Si es true, muestra mensajes detallados en la consola
 * @return array Resultado de la operación con los datos de la factura
 */
function consultarEstadoFacturaAlegra($facturaId, $email, $token, $verbose = true) {
    try {
        if ($verbose) echo "Consultando estado de factura {$facturaId}...\n";
        Log::info('Consultando estado de factura', ['factura_id' => $facturaId]);
        
        // Configurar cURL
        $ch = curl_init();
        $url = "https://api.alegra.com/api/v1/invoices/{$facturaId}";
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($email . ':' . $token)
        ]);
        
        // Ejecutar la solicitud
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        // Registrar la respuesta
        Log::info('Respuesta de consulta de factura', [
            'factura_id' => $facturaId,
            'http_code' => $httpCode,
            'error' => $error
        ]);
        
        // Procesar la respuesta
        if ($httpCode >= 200 && $httpCode < 300) {
            $data = json_decode($response, true);
            
            if ($verbose) {
                echo "Estado actual: " . ($data['status'] ?? 'desconocido') . "\n";
                echo "Número: " . ($data['numberTemplate']['fullNumber'] ?? 'N/A') . "\n";
            }
            
            return [
                'success' => true,
                'data' => $data
            ];
        }
        
        if ($verbose) {
            echo "Error al consultar factura: HTTP {$httpCode}\n";
            echo "Respuesta: " . substr($response, 0, 100) . (strlen($response) > 100 ? "..." : "") . "\n";
            if (!empty($error)) echo "Error cURL: {$error}\n";
        }
        
        return [
            'success' => false,
            'mensaje' => 'Error al consultar la factura',
            'error' => $response,
            'http_code' => $httpCode
        ];
    } catch (\Exception $e) {
        $mensaje = "Excepción al consultar estado de factura: " . $e->getMessage();
        if ($verbose) echo "{$mensaje}\n";
        Log::error($mensaje, [
            'factura_id' => $facturaId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return ['success' => false, 'mensaje' => $e->getMessage()];
    }
}

/**
 * Función para enviar una factura a la DIAN
 * 
 * @param string $facturaId ID de la factura en Alegra
 * @param bool $verbose Si es true, muestra mensajes detallados en la consola
 * @return array Resultado de la operación
 */
function enviarFacturaDIAN($facturaId, $verbose = true) {
    // Obtener credenciales
    $empresa = Empresa::first();
    
    if (!$empresa || empty($empresa->alegra_email) || empty($empresa->alegra_token)) {
        $mensaje = "Error: No se encontraron credenciales de Alegra válidas.";
        if ($verbose) echo $mensaje . "\n";
        Log::error($mensaje);
        return ['success' => false, 'mensaje' => $mensaje];
    }
    
    $email = $empresa->alegra_email;
    $token = $empresa->alegra_token;
    
    // Verificar que la factura esté en estado 'open'
    $estadoActual = consultarEstadoFacturaAlegra($facturaId, $email, $token, $verbose);
    
    if (!$estadoActual['success']) {
        $mensaje = "Error al consultar el estado de la factura antes de enviar a DIAN.";
        if ($verbose) echo $mensaje . "\n";
        Log::error($mensaje, ['factura_id' => $facturaId]);
        return $estadoActual;
    }
    
    if ($estadoActual['data']['status'] !== 'open') {
        $mensaje = "La factura no está en estado 'open'. No se puede enviar a la DIAN.";
        if ($verbose) echo $mensaje . "\n";
        Log::warning($mensaje, ['factura_id' => $facturaId, 'estado_actual' => $estadoActual['data']['status']]);
        return [
            'success' => false,
            'mensaje' => $mensaje,
            'data' => $estadoActual['data']
        ];
    }
    
    if ($verbose) echo "\nEnviando factura a la DIAN...\n";
    Log::info('Enviando factura a la DIAN', ['factura_id' => $facturaId]);
    
    // Configurar cURL
    $ch = curl_init();
    $url = "https://api.alegra.com/api/v1/invoices/{$facturaId}/email/dian";
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, "{}");
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($email . ':' . $token)
    ]);
    
    // Ejecutar la solicitud
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    // Registrar la respuesta
    Log::info('Respuesta de envío a DIAN', [
        'factura_id' => $facturaId,
        'http_code' => $httpCode,
        'error' => $error,
        'response' => $response
    ]);
    
    if ($verbose) {
        echo "HTTP Code: {$httpCode}\n";
        echo "Respuesta: " . substr($response, 0, 100) . (strlen($response) > 100 ? "..." : "") . "\n";
        if (!empty($error)) echo "Error cURL: {$error}\n";
    }
    
    // Verificar si la solicitud fue exitosa
    if ($httpCode >= 200 && $httpCode < 300) {
        $mensaje = "Factura enviada exitosamente a la DIAN.";
        if ($verbose) echo "✅ {$mensaje}\n";
        Log::info($mensaje, ['factura_id' => $facturaId]);
        
        return [
            'success' => true,
            'mensaje' => $mensaje,
            'data' => json_decode($response, true)
        ];
    } else {
        $mensaje = "Error al enviar factura a la DIAN.";
        if ($verbose) echo "❌ {$mensaje}\n";
        Log::error($mensaje, [
            'factura_id' => $facturaId,
            'http_code' => $httpCode,
            'response' => $response
        ]);
        
        return [
            'success' => false,
            'mensaje' => $mensaje,
            'error' => $response,
            'http_code' => $httpCode
        ];
    }
}

// Si este script se ejecuta directamente (no incluido), procesar argumentos
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    // Verificar argumentos
    if ($argc < 2) {
        echo "Error: Debe proporcionar el ID de la factura como argumento.\n";
        echo "Uso: php abrir_factura_mejorado.php <id_factura> [dian]\n";
        echo "  dian: Opcional, si se incluye, se enviará la factura a la DIAN después de abrirla\n";
        exit(1);
    }
    
    $facturaId = $argv[1];
    $enviarDIAN = isset($argv[2]) && $argv[2] === 'dian';
    
    echo "Iniciando proceso para factura con ID: {$facturaId}\n\n";
    
    // Abrir la factura
    $resultado = abrirFacturaAlegra($facturaId, true, 3);
    
    // Si se solicitó enviar a DIAN y la factura se abrió correctamente, enviarla
    if ($enviarDIAN && $resultado['success']) {
        echo "\nLa factura se abrió correctamente. Procediendo a enviar a la DIAN...\n";
        $resultadoDIAN = enviarFacturaDIAN($facturaId);
        
        if ($resultadoDIAN['success']) {
            echo "\n✅ Proceso completo: Factura abierta y enviada a la DIAN exitosamente.\n";
        } else {
            echo "\n⚠️ La factura se abrió correctamente pero hubo un error al enviarla a la DIAN.\n";
        }
    }
    
    echo "\nProceso finalizado.\n";
}
