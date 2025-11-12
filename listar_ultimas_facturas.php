<?php

// Cargar el framework Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Importar las clases necesarias
use App\Services\AlegraService;
use Illuminate\Support\Facades\Log;

// Crear instancia del servicio de Alegra
$alegraService = new AlegraService();

// Probar con las últimas facturas (IDs 114, 113, 112, etc.)
$idsFacturas = [114, 113, 112, 111, 110];

echo "\nVerificando estado de las últimas facturas:\n";
echo str_repeat('-', 80) . "\n";
echo sprintf("%-5s | %-15s | %-10s | %-20s | %-10s\n", 
    "ID", "Número", "Fecha", "Estado", "Electrónica");
echo str_repeat('-', 80) . "\n";

foreach ($idsFacturas as $idFactura) {
    $resultadoConsulta = $alegraService->obtenerEstadoFactura($idFactura);
    
    if (!$resultadoConsulta['success']) {
        echo sprintf("%-5s | %-15s | %-10s | %-20s | %-10s\n", 
            $idFactura, "ERROR", "ERROR", $resultadoConsulta['message'], "ERROR");
        continue;
    }
    
    $factura = $resultadoConsulta['data'];
    $numero = isset($factura['numberTemplate']) ? ($factura['numberTemplate']['fullNumber'] ?? 'N/A') : 'N/A';
    $fecha = $factura['date'] ?? 'N/A';
    $estado = $factura['status'] ?? 'N/A';
    $esElectronica = isset($factura['numberTemplate']) && isset($factura['numberTemplate']['isElectronic']) ? 
        ($factura['numberTemplate']['isElectronic'] ? 'Sí' : 'No') : 'N/A';
    
    echo sprintf("%-5s | %-15s | %-10s | %-20s | %-10s\n", 
        $idFactura, $numero, $fecha, $estado, $esElectronica);
}

echo str_repeat('-', 80) . "\n";

// Preguntar qué factura procesar
echo "\n¿Qué factura desea procesar? (Ingrese el ID): ";
$idFacturaProcesar = trim(fgets(STDIN));

if (!is_numeric($idFacturaProcesar)) {
    echo "❌ ID de factura inválido. Debe ser un número.\n";
    exit(1);
}

// Verificar el estado actual de la factura
$estadoFactura = $alegraService->obtenerEstadoFactura($idFacturaProcesar);

if (!$estadoFactura['success']) {
    echo "❌ Error al consultar el estado de la factura: " . $estadoFactura['message'] . "\n";
    exit(1);
}

$estadoActual = $estadoFactura['data']['status'];
echo "Estado actual de la factura: {$estadoActual}\n";

// Si la factura está en estado borrador, intentar abrirla primero
if ($estadoActual === 'draft') {
    echo "La factura está en estado borrador, intentando abrirla...\n";
    
    $resultadoApertura = $alegraService->abrirFacturaDirecto($idFacturaProcesar);
    
    if (!$resultadoApertura['success']) {
        echo "❌ Error al abrir la factura: " . $resultadoApertura['message'] . "\n";
        exit(1);
    }
    
    echo "✅ Factura abierta correctamente\n";
    
    // Verificar nuevamente el estado
    $estadoFactura = $alegraService->obtenerEstadoFactura($idFacturaProcesar);
    
    if (!$estadoFactura['success'] || $estadoFactura['data']['status'] !== 'open') {
        echo "❌ La factura no cambió a estado abierto después de intentar abrirla\n";
        echo "Estado actual: " . ($estadoFactura['data']['status'] ?? 'desconocido') . "\n";
        exit(1);
    }
} else if ($estadoActual !== 'open') {
    echo "❌ La factura no está en estado borrador ni abierto, no se puede procesar\n";
    echo "Estado actual: {$estadoActual}\n";
    exit(1);
}

// Intentar enviar la factura a la DIAN
echo "Enviando factura a la DIAN...\n";
$resultadoEmision = $alegraService->enviarFacturaADian($idFacturaProcesar);

if ($resultadoEmision['success']) {
    echo "✅ Factura enviada a la DIAN correctamente\n";
    echo "Detalles: " . json_encode($resultadoEmision['data'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "❌ Error al enviar la factura a la DIAN: " . $resultadoEmision['message'] . "\n";
    
    if (isset($resultadoEmision['error_details'])) {
        echo "Detalles del error: " . json_encode($resultadoEmision['error_details'], JSON_PRETTY_PRINT) . "\n";
    }
}
