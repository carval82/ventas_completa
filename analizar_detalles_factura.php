<?php
/**
 * Script para analizar en detalle una factura y verificar qué está impidiendo su apertura
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
    echo "Uso: php analizar_detalles_factura.php ID_FACTURA_ALEGRA\n";
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
echo "        ANÁLISIS DETALLADO DE FACTURA EN ALEGRA\n";
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

// 1. Obtener detalles de la factura
echo "\n>>> PASO 1: Obteniendo detalles completos de la factura\n";
$url = "https://api.alegra.com/api/v1/invoices/{$idFactura}";
$resultado = hacerSolicitudApi($url, 'GET', null, $email, $token);

if ($resultado['codigo'] >= 200 && $resultado['codigo'] < 300) {
    $factura = $resultado['respuesta'];
    echo "✅ Factura obtenida correctamente\n";
    
    echo "\nDETALLES DE LA FACTURA:\n";
    echo "- ID: " . $factura['id'] . "\n";
    echo "- Número: " . ($factura['numberTemplate']['prefix'] ?? '') . ($factura['numberTemplate']['number'] ?? '') . "\n";
    echo "- Estado: " . $factura['status'] . "\n";
    echo "- Fecha: " . $factura['date'] . "\n";
    echo "- Cliente: " . $factura['client']['name'] . "\n";
    echo "- ID Cliente: " . $factura['client']['id'] . "\n";
    echo "- Forma de Pago: " . ($factura['paymentForm'] ?? 'No definida') . "\n";
    echo "- Total: " . $factura['total'] . "\n";
    
    echo "\nARTÍCULOS:\n";
    foreach ($factura['items'] as $index => $item) {
        $num = $index + 1;
        echo "{$num}. {$item['name']} - Cantidad: {$item['quantity']} - Precio: {$item['price']} - Total: {$item['total']}\n";
    }
    
    echo "\nNUMERACIÓN:\n";
    if (isset($factura['numberTemplate'])) {
        $template = $factura['numberTemplate'];
        echo "- Prefijo: " . ($template['prefix'] ?? 'N/A') . "\n";
        echo "- Número: " . ($template['number'] ?? 'N/A') . "\n";
        echo "- Tipo Documento: " . ($template['documentType'] ?? 'N/A') . "\n";
        echo "- Número Completo: " . ($template['fullNumber'] ?? 'N/A') . "\n";
        echo "- Es Electrónica: " . (($template['isElectronic'] ?? false) ? 'Sí' : 'No') . "\n";
    } else {
        echo "No hay información de numeración\n";
    }

    // 2. Verificar numeraciones disponibles
    echo "\n>>> PASO 2: Verificando numeraciones disponibles\n";
    $url = "https://api.alegra.com/api/v1/number-templates";
    $resultadoNumeraciones = hacerSolicitudApi($url, 'GET', null, $email, $token);
    
    if ($resultadoNumeraciones['codigo'] >= 200 && $resultadoNumeraciones['codigo'] < 300) {
        $numeraciones = $resultadoNumeraciones['respuesta'];
        echo "✅ Numeraciones obtenidas correctamente\n";
        
        $numeracionesElectronicas = array_filter($numeraciones, function($numeracion) {
            return ($numeracion['isElectronic'] ?? false) === true;
        });
        
        echo "\nNUMERACIONES ELECTRÓNICAS DISPONIBLES:\n";
        foreach ($numeracionesElectronicas as $numeracion) {
            echo "- ID: " . $numeracion['id'] . " | Prefijo: " . $numeracion['prefix'] . " | Tipo: " . $numeracion['type'] . "\n";
            echo "  Estado: " . ($numeracion['status'] ?? 'N/A') . " | Es electrónica: " . (($numeracion['isElectronic'] ?? false) ? 'Sí' : 'No') . "\n";
        }
    } else {
        echo "❌ Error al obtener numeraciones: HTTP " . $resultadoNumeraciones['codigo'] . "\n";
        echo "Respuesta: " . $resultadoNumeraciones['respuesta_raw'] . "\n";
    }

    // 3. Verificar métodos de pago
    echo "\n>>> PASO 3: Verificando métodos de pago disponibles\n";
    $url = "https://api.alegra.com/api/v1/payment-methods";
    $resultadoMetodosPago = hacerSolicitudApi($url, 'GET', null, $email, $token);
    
    if ($resultadoMetodosPago['codigo'] >= 200 && $resultadoMetodosPago['codigo'] < 300) {
        $metodosPago = $resultadoMetodosPago['respuesta'];
        echo "✅ Métodos de pago obtenidos correctamente\n";
        
        echo "\nMÉTODOS DE PAGO DISPONIBLES:\n";
        foreach ($metodosPago as $metodo) {
            echo "- ID: " . $metodo['id'] . " | Nombre: " . $metodo['name'] . " | Tipo: " . ($metodo['type'] ?? 'N/A') . "\n";
        }
    } else {
        echo "❌ Error al obtener métodos de pago: HTTP " . $resultadoMetodosPago['codigo'] . "\n";
        echo "Respuesta: " . $resultadoMetodosPago['respuesta_raw'] . "\n";
    }

    // 4. Verificar formas de pago
    echo "\n>>> PASO 4: Verificando formas de pago disponibles\n";
    $url = "https://api.alegra.com/api/v1/payment-forms";
    $resultadoFormasPago = hacerSolicitudApi($url, 'GET', null, $email, $token);
    
    if ($resultadoFormasPago['codigo'] >= 200 && $resultadoFormasPago['codigo'] < 300) {
        $formasPago = $resultadoFormasPago['respuesta'];
        echo "✅ Formas de pago obtenidas correctamente\n";
        
        echo "\nFORMAS DE PAGO DISPONIBLES:\n";
        foreach ($formasPago as $forma) {
            echo "- ID: " . $forma['id'] . " | Nombre: " . $forma['name'] . "\n";
        }
    } else {
        echo "❌ Error al obtener formas de pago: HTTP " . $resultadoFormasPago['codigo'] . "\n";
        echo "Respuesta: " . $resultadoFormasPago['respuesta_raw'] . "\n";
    }

    // 5. Verificar cuentas (accounts) disponibles
    echo "\n>>> PASO 5: Verificando cuentas disponibles\n";
    $url = "https://api.alegra.com/api/v1/accounts";
    $resultadoCuentas = hacerSolicitudApi($url, 'GET', null, $email, $token);
    
    if ($resultadoCuentas['codigo'] >= 200 && $resultadoCuentas['codigo'] < 300) {
        $cuentas = $resultadoCuentas['respuesta'];
        echo "✅ Cuentas obtenidas correctamente\n";
        
        echo "\nCUENTAS DISPONIBLES (primeras 5):\n";
        $contador = 0;
        foreach ($cuentas as $cuenta) {
            if ($contador++ >= 5) break;
            echo "- ID: " . $cuenta['id'] . " | Nombre: " . $cuenta['name'] . " | Tipo: " . ($cuenta['type'] ?? 'N/A') . "\n";
        }
    } else {
        echo "❌ Error al obtener cuentas: HTTP " . $resultadoCuentas['codigo'] . "\n";
        echo "Respuesta: " . $resultadoCuentas['respuesta_raw'] . "\n";
    }

    // 6. Probar abriendo factura con formato completo específico
    echo "\n>>> PASO 6: Intentando abrir la factura con formato completo\n";
    
    $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/open";
    
    // Formato basado en los IDs reales obtenidos en los pasos anteriores
    $paymentFormId = $formasPago[0]['id'] ?? 1;
    $paymentMethodId = $metodosPago[0]['id'] ?? 10;
    $accountId = $cuentas[0]['id'] ?? 1;
    
    $datos = [
        "paymentForm" => [
            "id" => $paymentFormId
        ],
        "paymentMethod" => [
            "id" => $paymentMethodId
        ],
        "account" => [
            "id" => $accountId
        ]
    ];
    
    echo "Enviando: " . json_encode($datos) . "\n";
    
    $resultadoAbrir = hacerSolicitudApi($url, 'PUT', $datos, $email, $token);
    
    echo "Código de respuesta HTTP: " . $resultadoAbrir['codigo'] . "\n";
    
    if ($resultadoAbrir['codigo'] >= 200 && $resultadoAbrir['codigo'] < 300) {
        $facturaAbierta = $resultadoAbrir['respuesta'];
        echo "✅ Factura abierta exitosamente\n";
        echo "Estado después de abrir: " . ($facturaAbierta['status'] ?? 'No disponible') . "\n";
        
        // Si la factura se abrió correctamente, verificar si podemos enviarla a DIAN
        if (($facturaAbierta['status'] ?? '') === 'open') {
            echo "\n>>> PASO 7: Enviando factura abierta a DIAN\n";
            
            $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/stamp";
            $datosDian = [
                'generateStamp' => true,
                'generateQrCode' => true
            ];
            
            $resultadoDian = hacerSolicitudApi($url, 'POST', $datosDian, $email, $token);
            
            echo "Código de respuesta HTTP para DIAN: " . $resultadoDian['codigo'] . "\n";
            
            if ($resultadoDian['codigo'] >= 200 && $resultadoDian['codigo'] < 300) {
                $respuestaDian = $resultadoDian['respuesta'];
                echo "✅ Factura enviada exitosamente a DIAN\n";
                echo "Estado DIAN: " . ($respuestaDian['status'] ?? 'No disponible') . "\n";
                echo "CUFE: " . ($respuestaDian['cufe'] ?? 'No disponible') . "\n";
            } else {
                echo "❌ Error al enviar a DIAN: HTTP " . $resultadoDian['codigo'] . "\n";
                echo "Respuesta: " . $resultadoDian['respuesta_raw'] . "\n";
            }
        }
    } else {
        echo "❌ Error al abrir la factura: HTTP " . $resultadoAbrir['codigo'] . "\n";
        echo "Respuesta: " . $resultadoAbrir['respuesta_raw'] . "\n";
        
        // Probar alternativa con string en lugar de objeto
        echo "\n>>> PASO 6B: Intentando abrir con 'paymentForm' como string\n";
        
        $datosAlternativos = [
            "paymentForm" => "CASH"
        ];
        
        echo "Enviando: " . json_encode($datosAlternativos) . "\n";
        
        $resultadoAbrirAlt = hacerSolicitudApi($url, 'PUT', $datosAlternativos, $email, $token);
        
        echo "Código de respuesta HTTP: " . $resultadoAbrirAlt['codigo'] . "\n";
        
        if ($resultadoAbrirAlt['codigo'] >= 200 && $resultadoAbrirAlt['codigo'] < 300) {
            $facturaAbiertaAlt = $resultadoAbrirAlt['respuesta'];
            echo "✅ Factura abierta exitosamente con formato alternativo\n";
            echo "Estado después de abrir: " . ($facturaAbiertaAlt['status'] ?? 'No disponible') . "\n";
        } else {
            echo "❌ Error al abrir la factura con formato alternativo: HTTP " . $resultadoAbrirAlt['codigo'] . "\n";
            echo "Respuesta: " . $resultadoAbrirAlt['respuesta_raw'] . "\n";
        }
    }
} else {
    echo "❌ Error al obtener detalles de la factura: HTTP " . $resultado['codigo'] . "\n";
    echo "Respuesta: " . $resultado['respuesta_raw'] . "\n";
}

echo "\n=================================================================\n";
echo "                    ANÁLISIS FINALIZADO\n";
echo "=================================================================\n";
