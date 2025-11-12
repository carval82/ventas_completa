<?php

// Script para crear una nueva factura en Alegra, abrirla y enviarla a la DIAN
// Cargar el framework Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

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

// ID del cliente a usar (debe ser un cliente existente en Alegra)
$idCliente = isset($argv[1]) ? $argv[1] : "38"; // Por defecto usamos el cliente con ID 38

// Crear una nueva factura
echo "Creando nueva factura para el cliente {$idCliente}...\n";

// Datos para la nueva factura
$datosFactura = [
    'date' => date('Y-m-d'),
    'dueDate' => date('Y-m-d'),
    'client' => [
        'id' => $idCliente
    ],
    'items' => [
        [
            'id' => '67', // ID del producto (ANKOFEN)
            'price' => 26000,
            'quantity' => 1
        ]
    ],
    'paymentForm' => 'CASH',
    'paymentMethod' => 'CASH'
];

// Configurar cURL para crear la factura
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.alegra.com/api/v1/invoices");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datosFactura));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($email . ':' . $token)
]);

// Ejecutar la solicitud
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Verificar si hubo errores
if ($httpCode < 200 || $httpCode >= 300) {
    echo "Error al crear la factura: HTTP {$httpCode}\n";
    echo "Respuesta: {$response}\n";
    exit(1);
}

// Procesar la respuesta
$factura = json_decode($response, true);
$idFactura = $factura['id'];

echo "Factura creada correctamente con ID: {$idFactura}\n";
echo "Estado inicial: " . $factura['status'] . "\n";
echo "Número: " . $factura['numberTemplate']['fullNumber'] . "\n";

// Esperar un momento
echo "Esperando 3 segundos...\n";
sleep(3);

// Abrir la factura
echo "Abriendo la factura {$idFactura}...\n";

// Configurar cURL para abrir la factura
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.alegra.com/api/v1/invoices/{$idFactura}/open");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'paymentForm' => 'CASH',
    'paymentMethod' => 'CASH'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($email . ':' . $token)
]);

// Ejecutar la solicitud
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Verificar si hubo errores
if ($httpCode < 200 || $httpCode >= 300) {
    echo "Error al abrir la factura: HTTP {$httpCode}\n";
    echo "Respuesta: {$response}\n";
    exit(1);
}

echo "Factura abierta correctamente\n";

// Esperar un momento
echo "Esperando 3 segundos...\n";
sleep(3);

// Verificar el estado de la factura
echo "Verificando estado de la factura {$idFactura}...\n";

// Configurar cURL para verificar el estado
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.alegra.com/api/v1/invoices/{$idFactura}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($email . ':' . $token)
]);

// Ejecutar la solicitud
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Verificar si hubo errores
if ($httpCode < 200 || $httpCode >= 300) {
    echo "Error al verificar estado de la factura: HTTP {$httpCode}\n";
    echo "Respuesta: {$response}\n";
    exit(1);
}

// Procesar la respuesta
$factura = json_decode($response, true);
echo "Estado después de abrir: " . $factura['status'] . "\n";

// Si la factura está abierta, enviarla a la DIAN
if ($factura['status'] === 'open') {
    echo "Enviando factura {$idFactura} a la DIAN...\n";
    
    // Configurar cURL para enviar a la DIAN
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.alegra.com/api/v1/invoices/{$idFactura}/stamp");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'generateStamp' => true,
        'generateQrCode' => true
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($email . ':' . $token)
    ]);
    
    // Ejecutar la solicitud
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Verificar si hubo errores
    if ($httpCode < 200 || $httpCode >= 300) {
        echo "Error al enviar la factura a la DIAN: HTTP {$httpCode}\n";
        echo "Respuesta: {$response}\n";
        
        // Intentar mostrar detalles del error
        $errorData = json_decode($response, true);
        if (isset($errorData['message'])) {
            echo "Mensaje de error: " . $errorData['message'] . "\n";
        }
        
        exit(1);
    }
    
    // Procesar la respuesta
    $data = json_decode($response, true);
    
    echo "Factura enviada a la DIAN correctamente\n";
    
    // Mostrar información del CUFE si está disponible
    if (isset($data['stamp']) && isset($data['stamp']['cufe'])) {
        echo "CUFE: " . $data['stamp']['cufe'] . "\n";
    }
    
    echo "✅ Proceso completado correctamente\n";
} else {
    echo "❌ La factura no está en estado abierto, no se puede enviar a la DIAN\n";
}
