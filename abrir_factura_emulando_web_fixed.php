<?php
/**
 * Script para abrir facturas emulando el comportamiento de una aplicación web.
 * Con múltiples intentos y diferentes enfoques para resolver el problema.
 * Versión mejorada con mejor manejo de tipos de datos.
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
    echo "Uso: php abrir_factura_emulando_web_fixed.php ID_FACTURA_ALEGRA\n";
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
echo "      APERTURA DE FACTURA EMULANDO COMPORTAMIENTO WEB\n";
echo "=================================================================\n";
echo "ID de Factura: $idFactura\n";
echo "Credenciales: $email\n";
echo "-----------------------------------------------------------------\n";

// Función para mostrar datos de manera segura (evitando errores de tipo)
function mostrarValor($valor, $etiqueta = null) {
    if (is_null($valor)) {
        return 'N/A';
    } elseif (is_array($valor) || is_object($valor)) {
        return json_encode($valor);
    } else {
        return (string)$valor;
    }
}

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

// Función para verificar estado de la factura
function verificarEstadoFactura($idFactura, $email, $token) {
    $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}";
    $resultado = hacerSolicitudApi($url, 'GET', null, $email, $token);
    
    if ($resultado['codigo'] >= 200 && $resultado['codigo'] < 300) {
        return [
            'estado' => $resultado['respuesta']['status'] ?? 'desconocido',
            'factura' => $resultado['respuesta']
        ];
    }
    
    return [
        'estado' => 'error',
        'factura' => null
    ];
}

// 1. Verificar estado inicial y obtener detalles
echo "\n>>> PASO 1: Verificando estado inicial y obteniendo detalles\n";
$infoInicial = verificarEstadoFactura($idFactura, $email, $token);
$estadoInicial = $infoInicial['estado'];
$factura = $infoInicial['factura'];

echo "Estado inicial: $estadoInicial\n";

if ($estadoInicial !== 'draft') {
    echo "La factura no está en estado borrador (draft). No puede ser abierta.\n";
    exit(1);
}

if (!$factura) {
    echo "No se pudieron obtener los detalles de la factura.\n";
    exit(1);
}

// Mostrar información relevante con seguridad de tipos
echo "- ID Factura: " . mostrarValor($factura['id'] ?? null) . "\n";
echo "- Número: " . mostrarValor(($factura['numberTemplate']['prefix'] ?? '') . ($factura['numberTemplate']['number'] ?? '')) . "\n";
echo "- Cliente: " . mostrarValor($factura['client']['name'] ?? null) . " (ID: " . mostrarValor($factura['client']['id'] ?? null) . ")\n";
echo "- Total: " . mostrarValor($factura['total'] ?? null) . "\n";

// Extraer IDs importantes
$clienteId = $factura['client']['id'] ?? null;
$numeracionId = $factura['numberTemplate']['id'] ?? null;

if (!$clienteId) {
    echo "❌ Error: No se pudo obtener el ID del cliente de la factura.\n";
    exit(1);
}

// 2. Emular el flujo completo de una aplicación web
echo "\n>>> PASO 2: Emulando el flujo completo de interacción con Alegra\n";

// Primero obtenemos la configuración de la empresa
echo "2.1. Obteniendo configuración de la empresa...\n";
$url = "https://api.alegra.com/api/v1/company";
$resultadoEmpresa = hacerSolicitudApi($url, 'GET', null, $email, $token);

if ($resultadoEmpresa['codigo'] >= 200 && $resultadoEmpresa['codigo'] < 300) {
    echo "✅ Configuración de empresa obtenida correctamente\n";
    
    $empresa = $resultadoEmpresa['respuesta'];
    echo "- Nombre: " . mostrarValor($empresa['name'] ?? null) . "\n";
    echo "- Identificación: " . mostrarValor($empresa['identification'] ?? null) . "\n";
    
    // Verificar si la facturación electrónica está activa
    $electronicBillingEnabled = false;
    if (isset($empresa['administrativeSettings']) && isset($empresa['administrativeSettings']['electronicBillingEnabled'])) {
        $electronicBillingEnabled = $empresa['administrativeSettings']['electronicBillingEnabled'];
    }
    echo "- Facturación electrónica habilitada: " . ($electronicBillingEnabled ? 'Sí' : 'No') . "\n";
} else {
    echo "⚠️ No se pudo obtener la configuración de la empresa. Continuando...\n";
}

// Obtener información del cliente
echo "\n2.2. Obteniendo información detallada del cliente...\n";
$url = "https://api.alegra.com/api/v1/contacts/{$clienteId}";
$resultadoCliente = hacerSolicitudApi($url, 'GET', null, $email, $token);

if ($resultadoCliente['codigo'] >= 200 && $resultadoCliente['codigo'] < 300) {
    echo "✅ Información del cliente obtenida correctamente\n";
    
    $cliente = $resultadoCliente['respuesta'];
    echo "- Nombre: " . mostrarValor($cliente['name'] ?? null) . "\n";
    echo "- Tipo: " . mostrarValor($cliente['type'] ?? null) . "\n";
    echo "- Identificación: " . mostrarValor($cliente['identification'] ?? null) . "\n";
    echo "- Estado cliente: " . mostrarValor($cliente['status'] ?? null) . "\n";
} else {
    echo "⚠️ No se pudo obtener información detallada del cliente. Continuando...\n";
}

// Verificar plantillas de numeración
echo "\n2.3. Verificando plantillas de numeración disponibles...\n";
$url = "https://api.alegra.com/api/v1/number-templates";
$resultadoNumeraciones = hacerSolicitudApi($url, 'GET', null, $email, $token);

$numeracionesElectronicasActivas = [];
if ($resultadoNumeraciones['codigo'] >= 200 && $resultadoNumeraciones['codigo'] < 300) {
    echo "✅ Plantillas de numeración obtenidas correctamente\n";
    
    $numeraciones = $resultadoNumeraciones['respuesta'];
    $numeracionesElectronicasActivas = array_filter($numeraciones, function($numeracion) {
        return isset($numeracion['isElectronic']) && 
               $numeracion['isElectronic'] === true && 
               isset($numeracion['status']) && 
               $numeracion['status'] === 'active';
    });
    
    echo "Numeraciones electrónicas activas encontradas: " . count($numeracionesElectronicasActivas) . "\n";
    
    if (!empty($numeracionesElectronicasActivas)) {
        foreach ($numeracionesElectronicasActivas as $numElectronica) {
            echo "  - ID: " . mostrarValor($numElectronica['id'] ?? null) . 
                 " | Prefijo: " . mostrarValor($numElectronica['prefix'] ?? null) . 
                 " | Estado: " . mostrarValor($numElectronica['status'] ?? null) . "\n";
        }
    }
} else {
    echo "⚠️ No se pudieron verificar las plantillas de numeración. Continuando...\n";
}

// Verificar inventario de productos en la factura
echo "\n2.4. Verificando inventario de productos en la factura...\n";
if (isset($factura['items']) && is_array($factura['items'])) {
    foreach ($factura['items'] as $index => $item) {
        $num = $index + 1;
        echo "{$num}. " . mostrarValor($item['name'] ?? 'Producto sin nombre') . 
             " - ID: " . mostrarValor($item['id'] ?? null) .
             " - Cantidad: " . mostrarValor($item['quantity'] ?? null) . "\n";
        
        if (isset($item['id'])) {
            $productoId = $item['id'];
            $url = "https://api.alegra.com/api/v1/items/{$productoId}";
            $resultadoProducto = hacerSolicitudApi($url, 'GET', null, $email, $token);
            
            if ($resultadoProducto['codigo'] >= 200 && $resultadoProducto['codigo'] < 300) {
                $producto = $resultadoProducto['respuesta'];
                echo "   - Inventario: " . (($producto['inventory'] ?? false) ? 'Sí' : 'No') . "\n";
                echo "   - Stock: " . mostrarValor($producto['inventory']['availableQuantity'] ?? 'N/A') . "\n";
            }
        }
    }
} else {
    echo "No hay artículos en la factura o no se pudo acceder a ellos.\n";
}

// 3. Ahora intentaremos diferentes formatos para abrir la factura
echo "\n>>> PASO 3: Intentando abrir la factura con diferentes formatos\n";

$formatosParaProbar = [
    // Formato 1: Basado en memorias y conocimientos previos
    [
        'nombre' => 'Formato según memoria (cliente y pago)',
        'datos' => [
            'client' => [
                'id' => intval($clienteId)
            ],
            'payment' => [
                'paymentMethod' => [
                    'id' => 10
                ],
                'account' => [
                    'id' => 1
                ]
            ],
            'paymentForm' => 'CASH'
        ]
    ],
    
    // Formato 2: Solo con cliente y paymentForm
    [
        'nombre' => 'Cliente y paymentForm como string',
        'datos' => [
            'client' => [
                'id' => intval($clienteId)
            ],
            'paymentForm' => 'CASH'
        ]
    ],
    
    // Formato 3: Intentando hacer un POST completo como lo haría una aplicación web
    [
        'nombre' => 'POST completo con todos los campos',
        'datos' => [
            'client' => [
                'id' => intval($clienteId)
            ],
            'date' => date('Y-m-d'),
            'dueDate' => date('Y-m-d', strtotime('+30 days')),
            'paymentForm' => 'CASH',
            'paymentMethod' => 10,
            'account' => 1,
            'observations' => 'Apertura vía API',
            'aclaro' => 'Simulando envío completo como aplicación web'
        ]
    ],
    
    // Formato 4: Formato simplificado con paymentMethod como entero directo
    [
        'nombre' => 'Formato con paymentMethod como número',
        'datos' => [
            'client' => [
                'id' => intval($clienteId)
            ],
            'paymentForm' => 'CASH',
            'paymentMethod' => 10,
            'account' => 1
        ]
    ],
    
    // Formato 5: Usando formato de ejemplo del sitio de la API de Alegra
    [
        'nombre' => 'Formato de documentación API',
        'datos' => [
            'dueDate' => date('Y-m-d'),
            'client' => intval($clienteId),
            'observations' => 'Apertura desde API test',
            'paymentForm' => 'CASH',
            'discount' => 0
        ]
    ],
    
    // Formato 6: Intentando solo enviar status=open
    [
        'nombre' => 'Solo enviando estado open',
        'datos' => [
            'status' => 'open'
        ]
    ]
];

// Intentar cada formato
foreach ($formatosParaProbar as $indice => $formato) {
    echo "\n3." . ($indice + 1) . ". Probando: " . $formato['nombre'] . "\n";
    
    $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/open";
    echo "Enviando datos: " . json_encode($formato['datos']) . "\n";
    
    $resultado = hacerSolicitudApi($url, 'PUT', $formato['datos'], $email, $token);
    
    echo "Código de respuesta HTTP: " . $resultado['codigo'] . "\n";
    
    if ($resultado['codigo'] >= 200 && $resultado['codigo'] < 300) {
        echo "✅ Solicitud aceptada correctamente\n";
        
        // Verificar el estado después de la solicitud
        $infoActual = verificarEstadoFactura($idFactura, $email, $token);
        $estadoActual = $infoActual['estado'];
        
        echo "Estado después de la solicitud: " . $estadoActual . "\n";
        
        if ($estadoActual === 'open') {
            echo "✅ ¡ÉXITO! La factura ha cambiado a estado 'open'\n";
            
            // Intentar enviar a DIAN
            echo "\nEnviando factura a DIAN...\n";
            $urlDian = "https://api.alegra.com/api/v1/invoices/{$idFactura}/stamp";
            $datosDian = [
                'generateStamp' => true,
                'generateQrCode' => true
            ];
            
            $resultadoDian = hacerSolicitudApi($urlDian, 'POST', $datosDian, $email, $token);
            
            echo "Código de respuesta DIAN: " . $resultadoDian['codigo'] . "\n";
            
            if ($resultadoDian['codigo'] >= 200 && $resultadoDian['codigo'] < 300) {
                echo "✅ Factura enviada exitosamente a DIAN\n";
                $respuestaDian = $resultadoDian['respuesta'];
                echo "Estado DIAN: " . mostrarValor($respuestaDian['status'] ?? null) . "\n";
                echo "CUFE: " . mostrarValor($respuestaDian['cufe'] ?? null) . "\n";
            } else {
                echo "❌ Error al enviar a DIAN: HTTP " . $resultadoDian['codigo'] . "\n";
                echo "Respuesta: " . $resultadoDian['respuesta_raw'] . "\n";
            }
            
            // Salir del bucle si tuvimos éxito
            echo "\n=================================================================\n";
            echo "✅ SOLUCIÓN ENCONTRADA: " . $formato['nombre'] . "\n";
            echo "JSON: " . json_encode($formato['datos']) . "\n";
            echo "=================================================================\n";
            exit(0);
        } else {
            echo "⚠️ La solicitud fue aceptada por la API pero la factura sigue en estado '$estadoActual'\n";
        }
    } else {
        echo "❌ Error con formato '" . $formato['nombre'] . "': HTTP " . $resultado['codigo'] . "\n";
        if (isset($resultado['respuesta']['message'])) {
            echo "Mensaje de error: " . mostrarValor($resultado['respuesta']['message'] ?? null) . "\n";
        } else {
            echo "Respuesta: " . $resultado['respuesta_raw'] . "\n";
        }
    }
}

// 4. Investigar el endpoint específico de factura y sus datos
echo "\n>>> PASO 4: Investigando endpoint específico de la factura para detectar posibles problemas\n";

// Obtener datos específicos de la factura
$url = "https://api.alegra.com/api/v1/invoices/{$idFactura}?metadata=true&expand=items";
$resultadoDetalles = hacerSolicitudApi($url, 'GET', null, $email, $token);

if ($resultadoDetalles['codigo'] >= 200 && $resultadoDetalles['codigo'] < 300) {
    $detalleFactura = $resultadoDetalles['respuesta'];
    
    // Buscar posibles bloqueos o restricciones
    echo "Buscando posibles restricciones o validaciones pendientes...\n";
    
    // Verificar si hay metadata sobre validaciones
    if (isset($detalleFactura['metadata'])) {
        $metadata = $detalleFactura['metadata'];
        echo "Metadata encontrada: " . json_encode($metadata) . "\n";
    }
    
    // Verificar si hay campos obligatorios faltantes
    if (isset($detalleFactura['missingFields']) && !empty($detalleFactura['missingFields'])) {
        echo "Campos faltantes detectados:\n";
        foreach ($detalleFactura['missingFields'] as $campo) {
            echo "- " . mostrarValor($campo) . "\n";
        }
    } else {
        echo "No se detectaron campos faltantes.\n";
    }
    
    // Verificar si hay warnings o restricciones
    if (isset($detalleFactura['warnings']) && !empty($detalleFactura['warnings'])) {
        echo "Advertencias detectadas:\n";
        foreach ($detalleFactura['warnings'] as $warning) {
            echo "- " . json_encode($warning) . "\n";
        }
    } else {
        echo "No se detectaron advertencias.\n";
    }
    
    // Verificar todos los campos posibles de la factura
    echo "\nCampos disponibles en la factura:\n";
    foreach ($detalleFactura as $campo => $valor) {
        if (!is_array($valor) && !is_object($valor)) {
            echo "- $campo: " . mostrarValor($valor) . "\n";
        } else {
            echo "- $campo: (objeto/array)\n";
        }
    }
} else {
    echo "No se pudieron obtener detalles extendidos de la factura.\n";
}

// 5. Solución extrema: actualizar la factura completa antes de intentar abrirla
echo "\n>>> PASO 5: Intentando actualizar la factura completa antes de abrirla\n";

// Crear un payload completo para la factura
$payloadCompleto = [
    'date' => date('Y-m-d'),
    'dueDate' => date('Y-m-d', strtotime('+30 days')),
    'client' => [
        'id' => intval($clienteId)
    ],
    'paymentForm' => 'CASH',
    'paymentMethod' => 10,
    'account' => 1,
    'observations' => 'Actualizada vía API'
];

// Actualizar la factura completa
$url = "https://api.alegra.com/api/v1/invoices/{$idFactura}";
echo "Actualizando factura completa...\n";
echo "Enviando: " . json_encode($payloadCompleto) . "\n";

$resultadoUpdate = hacerSolicitudApi($url, 'PUT', $payloadCompleto, $email, $token);

echo "Código de respuesta HTTP: " . $resultadoUpdate['codigo'] . "\n";

if ($resultadoUpdate['codigo'] >= 200 && $resultadoUpdate['codigo'] < 300) {
    echo "✅ Factura actualizada correctamente\n";
    
    // Ahora intentamos abrirla
    echo "\nIntentando abrir la factura después de la actualización...\n";
    $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/open";
    
    $datosApertura = [
        'paymentForm' => 'CASH'
    ];
    
    echo "Enviando para apertura: " . json_encode($datosApertura) . "\n";
    
    $resultadoApertura = hacerSolicitudApi($url, 'PUT', $datosApertura, $email, $token);
    
    echo "Código de respuesta HTTP: " . $resultadoApertura['codigo'] . "\n";
    
    if ($resultadoApertura['codigo'] >= 200 && $resultadoApertura['codigo'] < 300) {
        echo "✅ Solicitud de apertura aceptada\n";
        
        // Verificar el estado después de la solicitud
        $infoFinal = verificarEstadoFactura($idFactura, $email, $token);
        $estadoFinal = $infoFinal['estado'];
        
        echo "Estado después de la apertura: " . $estadoFinal . "\n";
        
        if ($estadoFinal === 'open') {
            echo "✅ ¡ÉXITO! La factura ha cambiado a estado 'open' después de la actualización\n";
            
            // Intentar enviar a DIAN
            echo "\nEnviando factura a DIAN...\n";
            $urlDian = "https://api.alegra.com/api/v1/invoices/{$idFactura}/stamp";
            $datosDian = [
                'generateStamp' => true,
                'generateQrCode' => true
            ];
            
            $resultadoDian = hacerSolicitudApi($urlDian, 'POST', $datosDian, $email, $token);
            
            echo "Código de respuesta DIAN: " . $resultadoDian['codigo'] . "\n";
            
            if ($resultadoDian['codigo'] >= 200 && $resultadoDian['codigo'] < 300) {
                echo "✅ Factura enviada exitosamente a DIAN\n";
                $respuestaDian = $resultadoDian['respuesta'];
                echo "Estado DIAN: " . mostrarValor($respuestaDian['status'] ?? null) . "\n";
                echo "CUFE: " . mostrarValor($respuestaDian['cufe'] ?? null) . "\n";
            } else {
                echo "❌ Error al enviar a DIAN: HTTP " . $resultadoDian['codigo'] . "\n";
                echo "Respuesta: " . $resultadoDian['respuesta_raw'] . "\n";
            }
        } else {
            echo "⚠️ La solicitud de apertura fue aceptada pero la factura sigue en estado '$estadoFinal'\n";
        }
    } else {
        echo "❌ Error al abrir la factura después de la actualización: HTTP " . $resultadoApertura['codigo'] . "\n";
        echo "Respuesta: " . $resultadoApertura['respuesta_raw'] . "\n";
    }
} else {
    echo "❌ Error al actualizar la factura: HTTP " . $resultadoUpdate['codigo'] . "\n";
    echo "Respuesta: " . $resultadoUpdate['respuesta_raw'] . "\n";
}

// 6. Intentar cambiar el estado directamente
echo "\n>>> PASO 6: Intentando cambiar el estado directamente\n";

$url = "https://api.alegra.com/api/v1/invoices/{$idFactura}";
$datosEstado = [
    'status' => 'open'
];

echo "Enviando cambio directo de estado: " . json_encode($datosEstado) . "\n";
$resultadoEstadoDirecto = hacerSolicitudApi($url, 'PUT', $datosEstado, $email, $token);

echo "Código de respuesta HTTP: " . $resultadoEstadoDirecto['codigo'] . "\n";

if ($resultadoEstadoDirecto['codigo'] >= 200 && $resultadoEstadoDirecto['codigo'] < 300) {
    echo "✅ Solicitud aceptada\n";
    
    // Verificar el estado después de la solicitud
    $infoEstadoDirecto = verificarEstadoFactura($idFactura, $email, $token);
    $estadoDirecto = $infoEstadoDirecto['estado'];
    
    echo "Estado después de cambio directo: " . $estadoDirecto . "\n";
    
    if ($estadoDirecto === 'open') {
        echo "✅ ¡ÉXITO! La factura ha cambiado a estado 'open' mediante cambio directo\n";
    } else {
        echo "⚠️ El cambio directo fue aceptado pero la factura sigue en estado '$estadoDirecto'\n";
    }
} else {
    echo "❌ Error al cambiar el estado directamente: HTTP " . $resultadoEstadoDirecto['codigo'] . "\n";
    if (isset($resultadoEstadoDirecto['respuesta']['message'])) {
        echo "Mensaje de error: " . mostrarValor($resultadoEstadoDirecto['respuesta']['message'] ?? null) . "\n";
    } else {
        echo "Respuesta: " . $resultadoEstadoDirecto['respuesta_raw'] . "\n";
    }
}

// 7. Conclusiones y estado final
echo "\n>>> PASO 7: Verificando estado final y recomendaciones\n";

$infoFinal = verificarEstadoFactura($idFactura, $email, $token);
$estadoFinal = $infoFinal['estado'];

echo "\n=================================================================\n";
echo "                   RESUMEN DE LA OPERACIÓN\n";
echo "=================================================================\n";
echo "Estado inicial de la factura: " . $estadoInicial . "\n";
echo "Estado final de la factura: " . $estadoFinal . "\n";

if ($estadoFinal === 'open') {
    echo "✅ ¡ÉXITO! La factura se abrió correctamente.\n";
} else if ($estadoFinal === 'draft') {
    echo "❌ La factura continúa en estado borrador (draft).\n";
    echo "\nRECOMENDACIONES:\n";
    echo "1. Verificar si hay otros campos obligatorios en la factura según Alegra\n";
    echo "2. Verificar si la cuenta de Alegra tiene alguna restricción específica\n";
    echo "3. Intentar abrir la factura manualmente en la interfaz de Alegra\n";
    echo "4. Verificar si hay alguna restricción con facturas electrónicas\n";
    echo "5. Contactar al soporte de Alegra para obtener más información\n";
} else {
    echo "⚠️ La factura terminó en un estado inesperado: " . $estadoFinal . "\n";
}

echo "=================================================================\n";
