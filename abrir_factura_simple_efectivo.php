<?php
/**
 * Script sencillo para abrir facturas en Alegra usando solo paymentForm
 * Enfoque simple que sabemos que funcionaba anteriormente
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
    echo "Uso: php abrir_factura_simple_efectivo.php ID_FACTURA_ALEGRA\n";
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
echo "         APERTURA DE FACTURA - ENFOQUE SIMPLE EFECTIVO          \n";
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
} else {
    echo "Error al obtener el estado inicial: HTTP $httpCode\n";
    exit(1);
}

// Intentar abrir la factura con el formato mínimo que funcionó antes
$urlAbrir = "https://api.alegra.com/api/v1/invoices/{$idFactura}/open";
$datos = [
    'paymentForm' => 'CASH'
];

echo "\nAbriendo factura con formato mínimo...\n";
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

echo "=================================================================\n";
