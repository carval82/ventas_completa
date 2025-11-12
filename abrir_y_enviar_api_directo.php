<?php
/**
 * Script para abrir una factura y enviarla a la DIAN directamente usando la API de Alegra
 * Este enfoque evita las capas de abstracción de la aplicación
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
    echo "Uso: php abrir_y_enviar_api_directo.php ID_FACTURA_ALEGRA\n";
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
echo "          PROCESO DE FACTURACIÓN ELECTRÓNICA DIAN\n";
echo "=================================================================\n";
echo "ID de Factura: $idFactura\n";
echo "Credenciales: $email\n";
echo "-----------------------------------------------------------------\n";

// Función para hacer solicitudes a la API
function hacerSolicitudApi($metodo, $url, $datos = null, $email, $token) {
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

// Paso 1: Obtener detalles actuales de la factura
echo "\n>>> PASO 1: Verificando estado actual de la factura\n";

$url = "https://api.alegra.com/api/v1/invoices/{$idFactura}";
$resultado = hacerSolicitudApi('GET', $url, null, $email, $token);

if ($resultado['codigo'] >= 200 && $resultado['codigo'] < 300) {
    echo "✅ Factura obtenida correctamente\n";
    $factura = $resultado['respuesta'];
    
    echo "Información de la factura:\n";
    echo "- Estado: " . $factura['status'] . "\n";
    echo "- Cliente: " . $factura['client']['name'] . "\n";
    echo "- Fecha: " . $factura['date'] . "\n";
    echo "- Total: " . $factura['total'] . "\n";
    echo "- Artículos: " . count($factura['items']) . "\n";
    
    if ($factura['status'] === 'open') {
        echo "⚠️ La factura ya está abierta\n";
    } else if ($factura['status'] !== 'draft') {
        echo "⚠️ La factura no está en estado borrador, está en estado: " . $factura['status'] . "\n";
    }
} else {
    echo "❌ Error al obtener la factura: HTTP " . $resultado['codigo'] . "\n";
    echo "Respuesta: " . print_r($resultado['respuesta_raw'], true) . "\n";
    exit(1);
}

// Paso 2: Si la factura está en estado borrador, abrirla
if ($factura['status'] === 'draft') {
    echo "\n>>> PASO 2: Abriendo la factura (cambiar de borrador a abierta)\n";
    
    // Vamos a intentar diferentes formatos para abrir la factura
    $metodosAProbar = [
        // Método 1: JSON simple con forma de pago en CASH
        [
            'paymentForm' => 'CASH',
            'paymentMethod' => 'CASH'
        ],
        
        // Método 2: JSON con forma de pago y método como objetos
        [
            'paymentForm' => [
                'id' => 1
            ],
            'paymentMethod' => [
                'id' => 10
            ]
        ],
        
        // Método 3: JSON con todos los campos completos
        [
            'paymentForm' => [
                'id' => 1
            ],
            'paymentMethod' => [
                'id' => 10
            ],
            'account' => [
                'id' => 1
            ]
        ],
        
        // Método 4: JSON con valores de texto para payment 
        [
            'paymentForm' => 'CASH',
            'paymentMethod' => 'CASH',
            'account' => [
                'id' => 1
            ]
        ]
    ];
    
    $exito = false;
    
    foreach ($metodosAProbar as $index => $datos) {
        $numeroMetodo = $index + 1;
        echo "\nProbando método {$numeroMetodo}: " . json_encode($datos) . "\n";
        
        $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/open";
        $resultado = hacerSolicitudApi('PUT', $url, $datos, $email, $token);
        
        if ($resultado['codigo'] >= 200 && $resultado['codigo'] < 300) {
            echo "✅ Factura abierta exitosamente usando el método {$numeroMetodo}\n";
            $exito = true;
            break;
        } else {
            echo "❌ Error al abrir la factura con método {$numeroMetodo}: HTTP " . $resultado['codigo'] . "\n";
            echo "Respuesta: " . print_r($resultado['respuesta_raw'], true) . "\n";
        }
    }
    
    if (!$exito) {
        echo "\n⚠️ No se pudo abrir la factura con ninguno de los métodos predefinidos.\n";
        
        // Preguntar si desea intentar con otro formato
        echo "¿Desea intentar con otro formato personalizado? (s/n): ";
        $respuesta = trim(fgets(STDIN));
        
        if (strtolower($respuesta) === 's') {
            // Formato alternativo que viene del script anterior donde funcionó
            echo "Ingrese el formato JSON para abrir la factura (ejemplo: {\"paymentForm\":\"CASH\"}): ";
            $formatoJson = trim(fgets(STDIN));
            $datosPersonalizados = json_decode($formatoJson, true);
            
            if ($datosPersonalizados) {
                $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/open";
                $resultado = hacerSolicitudApi('PUT', $url, $datosPersonalizados, $email, $token);
                
                if ($resultado['codigo'] >= 200 && $resultado['codigo'] < 300) {
                    echo "✅ Factura abierta exitosamente con formato personalizado\n";
                    $exito = true;
                } else {
                    echo "❌ Error al abrir la factura con formato personalizado: HTTP " . $resultado['codigo'] . "\n";
                    echo "Respuesta: " . print_r($resultado['respuesta_raw'], true) . "\n";
                }
            } else {
                echo "❌ Formato JSON inválido\n";
            }
        }
    }
    
    if (!$exito) {
        echo "\n❌ No se pudo abrir la factura. No se continuará con el envío a la DIAN.\n";
        exit(1);
    }
} else {
    echo "\n>>> PASO 2: La factura ya está en estado '" . $factura['status'] . "', no es necesario abrirla\n";
}

// Paso 3: Enviar la factura a la DIAN
echo "\n>>> PASO 3: Enviando la factura a la DIAN\n";

$url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/stamp";
$datos = [
    'generateStamp' => true,
    'generateQrCode' => true
];

$resultado = hacerSolicitudApi('POST', $url, $datos, $email, $token);

if ($resultado['codigo'] >= 200 && $resultado['codigo'] < 300) {
    echo "✅ Factura enviada a la DIAN correctamente\n";
    
    echo "\nRespuesta de la DIAN:\n";
    echo "- Status: " . ($resultado['respuesta']['status'] ?? 'No disponible') . "\n";
    echo "- CUFE: " . ($resultado['respuesta']['cufe'] ?? 'No disponible') . "\n";
    
    // Opcional: Actualizar el estado en la base de datos
    $ventaId = isset($argv[2]) ? $argv[2] : null;
    if ($ventaId) {
        $venta = \App\Models\Venta::find($ventaId);
        if ($venta) {
            $venta->update([
                'estado_dian' => $resultado['respuesta']['status'] ?? 'Enviado',
                'cufe' => $resultado['respuesta']['cufe'] ?? null,
                'qr_code' => $resultado['respuesta']['qrCode'] ?? null,
            ]);
            echo "\n✅ Estado actualizado en la base de datos para venta ID: {$ventaId}\n";
        }
    }
} else {
    echo "❌ Error al enviar la factura a la DIAN: HTTP " . $resultado['codigo'] . "\n";
    echo "Respuesta: " . print_r($resultado['respuesta_raw'], true) . "\n";
    
    if ($resultado['codigo'] === 403) {
        echo "\n⚠️ Error 403 Forbidden. Posibles causas:\n";
        echo "1. La factura no está en estado 'open' (abierta)\n";
        echo "2. Su plan de Alegra no permite facturación electrónica\n";
        echo "3. No tiene permisos para realizar esta acción\n";
    }
}

echo "\n=================================================================\n";
echo "                    PROCESO FINALIZADO\n";
echo "=================================================================\n";
