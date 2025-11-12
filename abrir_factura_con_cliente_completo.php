<?php
/**
 * Script para abrir una factura usando un formato completo
 * que incluye cliente, método de pago y verificación de numeración
 */

// Cargar el framework Laravel para tener acceso a las credenciales
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// ID de la factura de Alegra (se pasa como argumento)
$idFactura = isset($argv[1]) ? $argv[1] : null;

if (!$idFactura) {
    echo "Error: Debe proporcionar el ID de la factura en Alegra\n";
    echo "Uso: php abrir_factura_con_cliente_completo.php ID_FACTURA_ALEGRA\n";
    exit(1);
}

// Obtener credenciales
$empresa = \App\Models\Empresa::first();
if ($empresa && $empresa->alegra_email && $empresa->alegra_token) {
    $email = $empresa->alegra_email;
    $token = $empresa->alegra_token;
    echo "Usando credenciales de la empresa\n";
} else {
    $email = config('alegra.user');
    $token = config('alegra.token');
    echo "Usando credenciales del archivo .env\n";
}

echo "=================================================================\n";
echo "    APERTURA DE FACTURA CON FORMATO COMPLETO DE CLIENTE\n";
echo "=================================================================\n";
echo "ID de Factura: $idFactura\n";
echo "Credenciales: $email\n";
echo "-----------------------------------------------------------------\n";

// Función para hacer solicitudes a la API
function hacerSolicitudApi($url, $metodo = 'GET', $datos = null, $email, $token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($email . ':' . $token)
    ]);
    
    if ($metodo === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($datos) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
        }
    } else if ($metodo === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($datos) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'codigo' => $httpCode,
        'respuesta' => $response ? json_decode($response, true) : null,
        'respuesta_raw' => $response,
        'error' => $error
    ];
}

// 1. Obtener detalles de la factura para conocer el cliente
echo "\n>>> PASO 1: Obteniendo detalles de la factura\n";
$url = "https://api.alegra.com/api/v1/invoices/{$idFactura}";
$resultado = hacerSolicitudApi($url, 'GET', null, $email, $token);

if ($resultado['codigo'] >= 200 && $resultado['codigo'] < 300) {
    $factura = $resultado['respuesta'];
    
    // Mostrar información básica
    echo "✅ Factura obtenida correctamente\n";
    echo "- ID Factura: " . ($factura['id'] ?? 'N/A') . "\n";
    echo "- Estado: " . ($factura['status'] ?? 'N/A') . "\n";
    echo "- Número: " . (($factura['numberTemplate']['prefix'] ?? '') . ($factura['numberTemplate']['number'] ?? '')) . "\n";
    
    // Verificar si está en borrador
    if (($factura['status'] ?? '') !== 'draft') {
        echo "⚠️ La factura no está en estado borrador (draft). No puede ser abierta.\n";
        exit(1);
    }
    
    // Extraer info del cliente
    $clienteId = $factura['client']['id'] ?? null;
    $clienteNombre = $factura['client']['name'] ?? 'Desconocido';
    
    if (!$clienteId) {
        echo "❌ Error: No se pudo obtener el ID del cliente de la factura.\n";
        exit(1);
    }
    
    echo "- Cliente: " . $clienteNombre . " (ID: " . $clienteId . ")\n";
    
    // 2. Verificar numeraciones disponibles
    echo "\n>>> PASO 2: Verificando plantillas de numeración\n";
    $url = "https://api.alegra.com/api/v1/number-templates";
    $resultadoNumeraciones = hacerSolicitudApi($url, 'GET', null, $email, $token);
    
    if ($resultadoNumeraciones['codigo'] >= 200 && $resultadoNumeraciones['codigo'] < 300) {
        $numeraciones = $resultadoNumeraciones['respuesta'];
        
        // Buscar numeraciones electrónicas activas
        $numeracionesElectronicasActivas = array_filter($numeraciones, function($numeracion) {
            return isset($numeracion['isElectronic']) && 
                  $numeracion['isElectronic'] === true && 
                  isset($numeracion['status']) && 
                  $numeracion['status'] === 'active';
        });
        
        echo "Numeraciones electrónicas activas encontradas: " . count($numeracionesElectronicasActivas) . "\n";
        
        if (empty($numeracionesElectronicasActivas)) {
            echo "⚠️ Advertencia: No se encontraron numeraciones electrónicas activas.\n";
        }
    } else {
        echo "⚠️ No se pudieron verificar las numeraciones. Continuando...\n";
    }
    
    // 3. Verificar métodos de pago disponibles
    echo "\n>>> PASO 3: Verificando métodos de pago\n";
    $url = "https://api.alegra.com/api/v1/payment-methods";
    $resultadoMetodosPago = hacerSolicitudApi($url, 'GET', null, $email, $token);
    
    $paymentMethodId = 10; // Valor por defecto
    
    if ($resultadoMetodosPago['codigo'] >= 200 && $resultadoMetodosPago['codigo'] < 300) {
        $metodosPago = $resultadoMetodosPago['respuesta'];
        
        if (is_array($metodosPago) && !empty($metodosPago)) {
            // Tomar el ID del primer método de pago disponible
            $paymentMethodId = $metodosPago[0]['id'] ?? 10;
            echo "Método de pago seleccionado: ID " . $paymentMethodId . "\n";
        } else {
            echo "⚠️ No se encontraron métodos de pago. Usando ID por defecto: " . $paymentMethodId . "\n";
        }
    } else {
        echo "⚠️ No se pudieron verificar los métodos de pago. Usando ID por defecto: " . $paymentMethodId . "\n";
    }
    
    // 4. Verificar cuentas disponibles
    echo "\n>>> PASO 4: Verificando cuentas disponibles\n";
    $url = "https://api.alegra.com/api/v1/accounts";
    $resultadoCuentas = hacerSolicitudApi($url, 'GET', null, $email, $token);
    
    $accountId = 1; // Valor por defecto
    
    if ($resultadoCuentas['codigo'] >= 200 && $resultadoCuentas['codigo'] < 300) {
        $cuentas = $resultadoCuentas['respuesta'];
        
        if (is_array($cuentas) && !empty($cuentas)) {
            // Tomar el ID de la primera cuenta disponible
            $accountId = $cuentas[0]['id'] ?? 1;
            echo "Cuenta seleccionada: ID " . $accountId . "\n";
        } else {
            echo "⚠️ No se encontraron cuentas. Usando ID por defecto: " . $accountId . "\n";
        }
    } else {
        echo "⚠️ No se pudieron verificar las cuentas. Usando ID por defecto: " . $accountId . "\n";
    }
    
    // 5. Abrir factura con formato más completo posible
    echo "\n>>> PASO 5: Intentando abrir la factura con formato completo\n";
    $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/open";
    
    // Formato completo con todos los datos que podrían ser necesarios
    $datosCompletos = [
        "client" => [
            "id" => intval($clienteId)
        ],
        "payment" => [
            "paymentMethod" => [
                "id" => $paymentMethodId
            ],
            "account" => [
                "id" => $accountId
            ]
        ],
        "paymentForm" => "CASH"
    ];
    
    echo "Enviando datos completos: " . json_encode($datosCompletos) . "\n";
    
    $resultadoAbrir = hacerSolicitudApi($url, 'PUT', $datosCompletos, $email, $token);
    
    echo "Código de respuesta HTTP: " . $resultadoAbrir['codigo'] . "\n";
    
    if ($resultadoAbrir['codigo'] >= 200 && $resultadoAbrir['codigo'] < 300) {
        $facturaAbierta = $resultadoAbrir['respuesta'];
        echo "✅ Solicitud aceptada correctamente\n";
        
        // Verificar estado después de la operación
        $estadoDespues = $facturaAbierta['status'] ?? 'desconocido';
        echo "Estado después de la operación: " . $estadoDespues . "\n";
        
        if ($estadoDespues === 'open') {
            echo "✅ ¡ÉXITO! La factura se abrió correctamente y cambió su estado a 'open'\n";
            
            // 6. Enviar factura a DIAN
            echo "\n>>> PASO 6: Enviando factura a DIAN\n";
            $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/stamp";
            $datosDian = [
                'generateStamp' => true,
                'generateQrCode' => true
            ];
            
            $resultadoDian = hacerSolicitudApi($url, 'POST', $datosDian, $email, $token);
            
            echo "Código de respuesta DIAN: " . $resultadoDian['codigo'] . "\n";
            
            if ($resultadoDian['codigo'] >= 200 && $resultadoDian['codigo'] < 300) {
                echo "✅ Factura enviada exitosamente a DIAN\n";
                $respuestaDian = $resultadoDian['respuesta'];
                echo "Estado DIAN: " . ($respuestaDian['status'] ?? 'No disponible') . "\n";
                echo "CUFE: " . ($respuestaDian['cufe'] ?? 'No disponible') . "\n";
            } else {
                echo "❌ Error al enviar a DIAN: HTTP " . $resultadoDian['codigo'] . "\n";
                echo "Respuesta: " . $resultadoDian['respuesta_raw'] . "\n";
            }
        } else {
            echo "⚠️ La solicitud fue aceptada por la API pero la factura NO cambió a estado 'open'\n";
            
            // 7. Probar formato alternativo solo con paymentForm
            echo "\n>>> PASO 7: Probando formato alternativo solo con paymentForm\n";
            
            $datosAlternativos = [
                "paymentForm" => "CASH"
            ];
            
            echo "Enviando formato alternativo: " . json_encode($datosAlternativos) . "\n";
            
            $resultadoAbrirAlt = hacerSolicitudApi($url, 'PUT', $datosAlternativos, $email, $token);
            
            echo "Código de respuesta HTTP: " . $resultadoAbrirAlt['codigo'] . "\n";
            
            if ($resultadoAbrirAlt['codigo'] >= 200 && $resultadoAbrirAlt['codigo'] < 300) {
                $facturaAbiertaAlt = $resultadoAbrirAlt['respuesta'];
                echo "✅ Solicitud alternativa aceptada correctamente\n";
                
                $estadoDespuesAlt = $facturaAbiertaAlt['status'] ?? 'desconocido';
                echo "Estado después de la operación alternativa: " . $estadoDespuesAlt . "\n";
                
                if ($estadoDespuesAlt === 'open') {
                    echo "✅ ¡ÉXITO con formato alternativo! La factura se abrió y cambió a 'open'\n";
                } else {
                    echo "⚠️ La solicitud alternativa también fue aceptada pero la factura sigue sin cambiar a 'open'\n";
                }
            } else {
                echo "❌ Error al abrir con formato alternativo: HTTP " . $resultadoAbrirAlt['codigo'] . "\n";
                echo "Respuesta: " . $resultadoAbrirAlt['respuesta_raw'] . "\n";
            }
        }
    } else {
        echo "❌ Error al abrir la factura: HTTP " . $resultadoAbrir['codigo'] . "\n";
        
        if (isset($resultadoAbrir['respuesta']['message'])) {
            echo "Mensaje de error: " . $resultadoAbrir['respuesta']['message'] . "\n";
        } else {
            echo "Respuesta: " . $resultadoAbrir['respuesta_raw'] . "\n";
        }
        
        // Probar formato alternativo si el primero falló
        echo "\n>>> PASO 5B: Probando formato alternativo solo con paymentForm\n";
        
        $datosAlternativos = [
            "paymentForm" => "CASH"
        ];
        
        echo "Enviando formato alternativo: " . json_encode($datosAlternativos) . "\n";
        
        $resultadoAbrirAlt = hacerSolicitudApi($url, 'PUT', $datosAlternativos, $email, $token);
        
        echo "Código de respuesta HTTP: " . $resultadoAbrirAlt['codigo'] . "\n";
        
        if ($resultadoAbrirAlt['codigo'] >= 200 && $resultadoAbrirAlt['codigo'] < 300) {
            $facturaAbiertaAlt = $resultadoAbrirAlt['respuesta'];
            echo "✅ Solicitud alternativa aceptada correctamente\n";
            
            $estadoDespuesAlt = $facturaAbiertaAlt['status'] ?? 'desconocido';
            echo "Estado después de la operación alternativa: " . $estadoDespuesAlt . "\n";
            
            if ($estadoDespuesAlt === 'open') {
                echo "✅ ¡ÉXITO con formato alternativo! La factura se abrió y cambió a 'open'\n";
                
                // Enviar a DIAN si se abrió correctamente
                echo "\n>>> PASO 6B: Enviando factura a DIAN (alternativa)\n";
                $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/stamp";
                $datosDian = [
                    'generateStamp' => true,
                    'generateQrCode' => true
                ];
                
                $resultadoDian = hacerSolicitudApi($url, 'POST', $datosDian, $email, $token);
                
                echo "Código de respuesta DIAN: " . $resultadoDian['codigo'] . "\n";
                
                if ($resultadoDian['codigo'] >= 200 && $resultadoDian['codigo'] < 300) {
                    echo "✅ Factura enviada exitosamente a DIAN\n";
                    $respuestaDian = $resultadoDian['respuesta'];
                    echo "Estado DIAN: " . ($respuestaDian['status'] ?? 'No disponible') . "\n";
                    echo "CUFE: " . ($respuestaDian['cufe'] ?? 'No disponible') . "\n";
                } else {
                    echo "❌ Error al enviar a DIAN: HTTP " . $resultadoDian['codigo'] . "\n";
                    echo "Respuesta: " . $resultadoDian['respuesta_raw'] . "\n";
                }
            } else {
                echo "⚠️ La solicitud alternativa también fue aceptada pero la factura sigue sin cambiar a 'open'\n";
            }
        } else {
            echo "❌ Error al abrir con formato alternativo: HTTP " . $resultadoAbrirAlt['codigo'] . "\n";
            echo "Respuesta: " . $resultadoAbrirAlt['respuesta_raw'] . "\n";
        }
    }
} else {
    echo "❌ Error al obtener detalles de la factura: HTTP " . $resultado['codigo'] . "\n";
    echo "Respuesta: " . $resultado['respuesta_raw'] . "\n";
}

echo "\n=================================================================\n";
echo "                PROCESO DE APERTURA FINALIZADO\n";
echo "=================================================================\n";

// Verificar estado final de la factura
$url = "https://api.alegra.com/api/v1/invoices/{$idFactura}";
$verificacionFinal = hacerSolicitudApi($url, 'GET', null, $email, $token);

if ($verificacionFinal['codigo'] >= 200 && $verificacionFinal['codigo'] < 300) {
    $facturaFinal = $verificacionFinal['respuesta'];
    $estadoFinal = $facturaFinal['status'] ?? 'desconocido';
    
    echo "Estado final de la factura: " . $estadoFinal . "\n";
    
    if ($estadoFinal === 'open' || $estadoFinal === 'closed') {
        echo "✅ ÉXITO: La factura está ahora en estado '" . $estadoFinal . "'\n";
    } else if ($estadoFinal === 'draft') {
        echo "❌ No se pudo cambiar el estado de la factura. Sigue en borrador (draft).\n";
        echo "Es posible que existan restricciones adicionales en Alegra que impiden la apertura.\n";
    }
} else {
    echo "No se pudo verificar el estado final de la factura.\n";
}

echo "=================================================================\n";
