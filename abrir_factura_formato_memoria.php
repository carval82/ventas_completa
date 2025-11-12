<?php
/**
 * Script para abrir facturas en Alegra usando exactamente el formato mencionado en la memoria
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
    echo "Uso: php abrir_factura_formato_memoria.php ID_FACTURA_ALEGRA\n";
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
echo "      APERTURA DE FACTURA - FORMATO EXACTO DE MEMORIA           \n";
echo "=================================================================\n";
echo "ID de Factura: $idFactura\n";
echo "Credenciales: $email\n";

// Verificar estado inicial
$url = "https://api.alegra.com/api/v1/invoices/{$idFactura}";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($email . ':' . $token)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $factura = json_decode($response, true);
    echo "Estado inicial: " . ($factura['status'] ?? 'desconocido') . "\n";
    echo "Número: " . (($factura['numberTemplate']['prefix'] ?? '') . ($factura['numberTemplate']['number'] ?? '')) . "\n";
    
    // Obtener ID del cliente
    $clienteId = $factura['client']['id'] ?? null;
    echo "ID del cliente: " . ($clienteId ?? 'No disponible') . "\n";
    
    if (!$clienteId) {
        echo "Error: No se pudo obtener el ID del cliente\n";
        exit(1);
    }
} else {
    echo "Error al obtener el estado inicial: HTTP $httpCode\n";
    exit(1);
}

// Intentar abrir la factura con el formato exacto de la memoria
$urlAbrir = "https://api.alegra.com/api/v1/invoices/{$idFactura}/open";

// Probar con el formato exacto mencionado en la memoria
$datos = [
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
    ]
];

echo "\nAbriendo factura con formato exacto de memoria...\n";
echo "Enviando: " . json_encode($datos) . "\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $urlAbrir);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($email . ':' . $token)
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Código de respuesta HTTP: $httpCode\n";
echo "Respuesta: " . ($response ?: 'Ninguna') . "\n";

// Verificar estado después de abrir
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($email . ':' . $token)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $factura = json_decode($response, true);
    $estadoFinal = $factura['status'] ?? 'desconocido';
    echo "Estado después de intentar abrir: $estadoFinal\n\n";
    
    if ($estadoFinal === 'open') {
        echo "✅ ÉXITO! La factura se ha abierto correctamente.\n";
        
        // Si se abrió correctamente, intentar enviar a DIAN
        echo "\nEnviando factura a DIAN...\n";
        $urlDian = "https://api.alegra.com/api/v1/invoices/{$idFactura}/stamp";
        $datosDian = [
            'generateStamp' => true,
            'generateQrCode' => true
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlDian);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($email . ':' . $token)
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datosDian));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "Código de respuesta DIAN: $httpCode\n";
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $respuestaDian = json_decode($response, true);
            echo "✅ Factura enviada exitosamente a DIAN\n";
            echo "Estado DIAN: " . ($respuestaDian['status'] ?? 'No disponible') . "\n";
            echo "CUFE: " . ($respuestaDian['cufe'] ?? 'No disponible') . "\n";
        } else {
            echo "❌ Error al enviar a DIAN: HTTP $httpCode\n";
            echo "Respuesta: $response\n";
        }
    } else {
        echo "❌ La factura no se pudo abrir. Sigue en estado: $estadoFinal\n";
    }
} else {
    echo "Error al verificar estado final: HTTP $httpCode\n";
}

// Probar con un segundo formato que incluye el paymentForm
if ($estadoFinal !== 'open') {
    echo "\n\nIntentando con formato alternativo (con paymentForm)...\n";
    
    $datosAlt = [
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
    ];
    
    echo "Enviando: " . json_encode($datosAlt) . "\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $urlAbrir);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($email . ':' . $token)
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datosAlt));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Código de respuesta HTTP: $httpCode\n";
    echo "Respuesta: " . ($response ?: 'Ninguna') . "\n";
    
    // Verificar estado final
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($email . ':' . $token)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $factura = json_decode($response, true);
        $estadoFinal = $factura['status'] ?? 'desconocido';
        echo "Estado después de segundo intento: $estadoFinal\n";
        
        if ($estadoFinal === 'open') {
            echo "✅ ÉXITO con el formato alternativo! La factura se ha abierto correctamente.\n";
        } else {
            echo "❌ La factura sigue sin abrirse. Estado final: $estadoFinal\n";
        }
    }
}

echo "=================================================================\n";
