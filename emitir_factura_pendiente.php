<?php

// Cargar el framework Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Importar las clases necesarias
use App\Services\AlegraService;
use Illuminate\Support\Facades\Log;

// Verificar si se proporcionó un ID de factura como argumento
$idFactura = isset($argv[1]) ? $argv[1] : null;

if (!$idFactura) {
    echo "Error: Debe proporcionar el ID de la factura como argumento\n";
    echo "Uso: php emitir_factura_pendiente.php ID_FACTURA\n";
    exit(1);
}

// Crear instancia del servicio de Alegra
$alegraService = new AlegraService();

echo "Verificando estado de la factura {$idFactura}...\n";

// Obtener el estado actual de la factura
$estadoFactura = $alegraService->obtenerEstadoFactura($idFactura);

if (!$estadoFactura['success']) {
    echo "❌ Error al consultar el estado de la factura: " . $estadoFactura['message'] . "\n";
    exit(1);
}

$factura = $estadoFactura['data'];
$estadoActual = $factura['status'];
echo "Estado actual de la factura: {$estadoActual}\n";

// Verificar si la factura ya fue enviada a la DIAN
$tieneCufe = isset($factura['stampData']) && isset($factura['stampData']['cufe']);
$estadoEnviado = in_array($factura['status'], ['accepted', 'sent']);

if ($tieneCufe || $estadoEnviado) {
    echo "❌ La factura ya fue enviada a la DIAN anteriormente\n";
    
    if ($tieneCufe) {
        echo "CUFE: " . $factura['stampData']['cufe'] . "\n";
    }
    
    echo "Estado: " . $factura['status'] . "\n";
    exit(1);
}

// Si la factura está en estado borrador, intentar abrirla primero
if ($estadoActual === 'draft') {
    echo "La factura está en estado borrador, intentando abrirla...\n";
    
    $resultadoApertura = $alegraService->abrirFacturaDirecto($idFactura);
    
    if (!$resultadoApertura['success']) {
        echo "❌ Error al abrir la factura: " . $resultadoApertura['message'] . "\n";
        exit(1);
    }
    
    echo "✅ Factura abierta correctamente\n";
    
    // Verificar nuevamente el estado
    $estadoFactura = $alegraService->obtenerEstadoFactura($idFactura);
    
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

// Usar el método público enviarFacturaADian
$resultadoEmision = $alegraService->enviarFacturaADian($idFactura);

if ($resultadoEmision['success']) {
    echo "✅ Factura enviada a la DIAN correctamente\n";
    echo "Detalles: " . json_encode($resultadoEmision['data'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "❌ Error al enviar la factura a la DIAN: " . $resultadoEmision['message'] . "\n";
    
    if (isset($resultadoEmision['error_details'])) {
        echo "Detalles del error: " . json_encode($resultadoEmision['error_details'], JSON_PRETTY_PRINT) . "\n";
    }
    
    // Si el error es 403 Forbidden, proporcionar posibles causas
    if (isset($resultadoEmision['error_details']) && 
        isset($resultadoEmision['error_details']['message']) && 
        $resultadoEmision['error_details']['message'] === 'Forbidden') {
        
        echo "\nPosibles causas del error 403 Forbidden:\n";
        echo "1. La factura ya fue enviada a la DIAN anteriormente\n";
        echo "2. No tiene permisos para enviar facturas electrónicas\n";
        echo "3. La cuenta no está configurada correctamente para facturación electrónica\n";
        echo "4. El formato de la factura no cumple con los requisitos de la DIAN\n";
        
        // Verificar si hay algún problema con la numeración electrónica
        echo "\nVerificando si hay problemas con la numeración electrónica...\n";
        
        // Verificar si la factura usa una plantilla de numeración electrónica
        if (isset($factura['numberTemplate']) && isset($factura['numberTemplate']['isElectronic'])) {
            echo "La factura usa numeración electrónica: " . ($factura['numberTemplate']['isElectronic'] ? 'Sí' : 'No') . "\n";
            
            if (isset($factura['numberTemplate']['status'])) {
                echo "Estado de la numeración: " . $factura['numberTemplate']['status'] . "\n";
                
                if ($factura['numberTemplate']['status'] !== 'active') {
                    echo "⚠️ La numeración no está activa. Debe estar activa para emitir facturas electrónicas.\n";
                }
            }
        } else {
            echo "⚠️ La factura no tiene configurada una plantilla de numeración electrónica.\n";
        }
    }
}
