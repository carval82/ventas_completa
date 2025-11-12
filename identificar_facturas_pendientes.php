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
$idsFacturas = [114, 113, 112, 111, 110, 45, 44, 43];

echo "\nVerificando estado de las últimas facturas:\n";
echo str_repeat('-', 100) . "\n";
echo sprintf("%-5s | %-15s | %-10s | %-15s | %-15s | %-20s\n", 
    "ID", "Número", "Fecha", "Estado", "Enviada DIAN", "Observaciones");
echo str_repeat('-', 100) . "\n";

$facturasPendientes = [];

foreach ($idsFacturas as $idFactura) {
    $resultadoConsulta = $alegraService->obtenerEstadoFactura($idFactura);
    
    if (!$resultadoConsulta['success']) {
        echo sprintf("%-5s | %-15s | %-10s | %-15s | %-15s | %-20s\n", 
            $idFactura, "ERROR", "ERROR", "ERROR", "ERROR", $resultadoConsulta['message']);
        continue;
    }
    
    $factura = $resultadoConsulta['data'];
    $numero = isset($factura['numberTemplate']) ? ($factura['numberTemplate']['fullNumber'] ?? 'N/A') : 'N/A';
    $fecha = $factura['date'] ?? 'N/A';
    $estado = $factura['status'] ?? 'N/A';
    
    // Verificar si la factura ya fue enviada a la DIAN
    $tieneCufe = isset($factura['stampData']) && isset($factura['stampData']['cufe']);
    $estadoEnviado = in_array($factura['status'], ['accepted', 'sent']);
    $enviada = $tieneCufe || $estadoEnviado;
    
    $observaciones = "";
    
    if ($enviada) {
        $observaciones = "Ya enviada a DIAN";
        if ($tieneCufe) {
            $observaciones .= " (tiene CUFE)";
        }
    } else if ($estado === 'draft') {
        $observaciones = "Pendiente abrir";
        $facturasPendientes[] = [
            'id' => $idFactura,
            'numero' => $numero,
            'estado' => $estado,
            'accion_requerida' => 'abrir'
        ];
    } else if ($estado === 'open') {
        $observaciones = "Pendiente enviar a DIAN";
        $facturasPendientes[] = [
            'id' => $idFactura,
            'numero' => $numero,
            'estado' => $estado,
            'accion_requerida' => 'enviar'
        ];
    }
    
    echo sprintf("%-5s | %-15s | %-10s | %-15s | %-15s | %-20s\n", 
        $idFactura, $numero, $fecha, $estado, ($enviada ? 'Sí' : 'No'), $observaciones);
}

echo str_repeat('-', 100) . "\n\n";

// Mostrar facturas pendientes de procesar
if (count($facturasPendientes) > 0) {
    echo "FACTURAS PENDIENTES DE PROCESAR:\n";
    echo str_repeat('-', 80) . "\n";
    echo sprintf("%-5s | %-15s | %-15s | %-30s\n", 
        "ID", "Número", "Estado", "Acción requerida");
    echo str_repeat('-', 80) . "\n";
    
    foreach ($facturasPendientes as $factura) {
        echo sprintf("%-5s | %-15s | %-15s | %-30s\n", 
            $factura['id'], $factura['numero'], $factura['estado'], $factura['accion_requerida']);
    }
    
    echo str_repeat('-', 80) . "\n\n";
    
    // Preguntar qué factura procesar
    echo "¿Qué factura desea procesar? (Ingrese el ID): ";
    $idFacturaProcesar = trim(fgets(STDIN));
    
    if (!is_numeric($idFacturaProcesar)) {
        echo "❌ ID de factura inválido. Debe ser un número.\n";
        exit(1);
    }
    
    // Buscar la factura seleccionada en la lista de pendientes
    $facturaSeleccionada = null;
    foreach ($facturasPendientes as $factura) {
        if ($factura['id'] == $idFacturaProcesar) {
            $facturaSeleccionada = $factura;
            break;
        }
    }
    
    if (!$facturaSeleccionada) {
        echo "❌ La factura seleccionada no está en la lista de pendientes o ya fue procesada.\n";
        exit(1);
    }
    
    // Procesar la factura según la acción requerida
    if ($facturaSeleccionada['accion_requerida'] === 'abrir') {
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
        
        // Ahora que está abierta, preguntar si desea enviarla a la DIAN
        echo "¿Desea enviar la factura a la DIAN ahora? (s/n): ";
        $respuesta = strtolower(trim(fgets(STDIN)));
        
        if ($respuesta !== 's') {
            echo "Operación cancelada por el usuario.\n";
            exit(0);
        }
    }
    
    // Enviar la factura a la DIAN
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
} else {
    echo "No se encontraron facturas pendientes de procesar.\n";
}
