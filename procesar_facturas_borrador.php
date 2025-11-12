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

// Obtener todas las facturas (limitado a las últimas 20)
echo "Obteniendo listado de facturas...\n";
$facturas = $alegraService->obtenerFacturas(20);

if (!$facturas['success']) {
    echo "❌ Error al obtener facturas: " . $facturas['message'] . "\n";
    exit(1);
}

// Filtrar solo las facturas en estado borrador
$facturasBorrador = [];
foreach ($facturas['data'] as $factura) {
    if (isset($factura['status']) && $factura['status'] === 'draft') {
        $facturasBorrador[] = $factura;
    }
}

// Mostrar las facturas en estado borrador
echo "\nFacturas en estado borrador:\n";
echo str_repeat('-', 100) . "\n";
echo sprintf("%-5s | %-15s | %-30s | %-10s | %-15s | %-15s\n", 
    "ID", "Número", "Cliente", "Fecha", "Total", "Estado");
echo str_repeat('-', 100) . "\n";

if (count($facturasBorrador) === 0) {
    echo "No se encontraron facturas en estado borrador.\n";
    exit(0);
}

foreach ($facturasBorrador as $factura) {
    $id = $factura['id'] ?? 'N/A';
    $numero = isset($factura['numberTemplate']) ? ($factura['numberTemplate']['fullNumber'] ?? 'Sin número') : 'Sin número';
    $clienteNombre = isset($factura['client']) ? ($factura['client']['name'] ?? 'N/A') : 'N/A';
    $fecha = $factura['date'] ?? 'N/A';
    $total = isset($factura['total']) ? number_format($factura['total'], 2, ',', '.') : 'N/A';
    $estado = $factura['status'] ?? 'N/A';
    
    echo sprintf("%-5s | %-15s | %-30s | %-10s | %-15s | %-15s\n", 
        $id, $numero, $clienteNombre, $fecha, $total, $estado);
}

echo str_repeat('-', 100) . "\n\n";

// Preguntar qué factura procesar
echo "¿Qué factura desea procesar? (Ingrese el ID o 'todas' para procesar todas): ";
$respuesta = trim(fgets(STDIN));

// Procesar todas las facturas o una específica
$facturasAProcesar = [];

if (strtolower($respuesta) === 'todas') {
    $facturasAProcesar = $facturasBorrador;
    echo "Se procesarán todas las facturas en estado borrador.\n";
} else if (is_numeric($respuesta)) {
    // Buscar la factura con el ID proporcionado
    $facturaEncontrada = null;
    foreach ($facturasBorrador as $factura) {
        if ($factura['id'] == $respuesta) {
            $facturaEncontrada = $factura;
            break;
        }
    }
    
    if ($facturaEncontrada) {
        $facturasAProcesar[] = $facturaEncontrada;
        echo "Se procesará la factura con ID: " . $respuesta . "\n";
    } else {
        echo "❌ No se encontró ninguna factura en estado borrador con el ID: " . $respuesta . "\n";
        exit(1);
    }
} else {
    echo "❌ Respuesta inválida. Debe ingresar un ID numérico o 'todas'.\n";
    exit(1);
}

// Procesar cada factura seleccionada
foreach ($facturasAProcesar as $factura) {
    $idFactura = $factura['id'];
    $numeroFactura = isset($factura['numberTemplate']) ? ($factura['numberTemplate']['fullNumber'] ?? 'Sin número') : 'Sin número';
    $clienteNombre = isset($factura['client']) ? ($factura['client']['name'] ?? 'N/A') : 'N/A';
    
    echo "\n" . str_repeat('=', 100) . "\n";
    echo "Procesando factura: ID {$idFactura} - {$numeroFactura} - Cliente: {$clienteNombre}\n";
    echo str_repeat('=', 100) . "\n";
    
    // Paso 1: Abrir la factura (cambiar de estado borrador a abierto)
    echo "Paso 1: Abriendo factura (cambiando de estado borrador a abierto)...\n";
    $resultadoApertura = $alegraService->abrirFacturaDirecto($idFactura);
    
    if (!$resultadoApertura['success']) {
        echo "❌ Error al abrir la factura: " . $resultadoApertura['message'] . "\n";
        echo "Pasando a la siguiente factura...\n";
        continue;
    }
    
    echo "✅ Factura abierta correctamente\n";
    
    // Verificar que la factura cambió a estado abierto
    $estadoFactura = $alegraService->obtenerEstadoFactura($idFactura);
    
    if (!$estadoFactura['success'] || $estadoFactura['data']['status'] !== 'open') {
        echo "❌ La factura no cambió a estado abierto después de intentar abrirla\n";
        echo "Estado actual: " . ($estadoFactura['data']['status'] ?? 'desconocido') . "\n";
        echo "Pasando a la siguiente factura...\n";
        continue;
    }
    
    // Paso 2: Enviar la factura a la DIAN
    echo "Paso 2: Enviando factura a la DIAN...\n";
    $resultadoEmision = $alegraService->enviarFacturaADian($idFactura);
    
    if ($resultadoEmision['success']) {
        echo "✅ Factura enviada a la DIAN correctamente\n";
        
        // Mostrar detalles de la respuesta
        if (isset($resultadoEmision['data'])) {
            echo "Detalles de la respuesta:\n";
            
            if (isset($resultadoEmision['data']['status'])) {
                echo "Estado: " . $resultadoEmision['data']['status'] . "\n";
            }
            
            if (isset($resultadoEmision['data']['stampData']) && isset($resultadoEmision['data']['stampData']['cufe'])) {
                echo "CUFE: " . $resultadoEmision['data']['stampData']['cufe'] . "\n";
            }
        }
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
            if (isset($estadoFactura['data']['numberTemplate']) && isset($estadoFactura['data']['numberTemplate']['isElectronic'])) {
                echo "\nInformación de la numeración electrónica:\n";
                echo "La factura usa numeración electrónica: " . ($estadoFactura['data']['numberTemplate']['isElectronic'] ? 'Sí' : 'No') . "\n";
                
                if (isset($estadoFactura['data']['numberTemplate']['status'])) {
                    echo "Estado de la numeración: " . $estadoFactura['data']['numberTemplate']['status'] . "\n";
                    
                    if ($estadoFactura['data']['numberTemplate']['status'] !== 'active') {
                        echo "⚠️ La numeración no está activa. Debe estar activa para emitir facturas electrónicas.\n";
                    }
                }
            }
        }
    }
}

echo "\nProcesamiento de facturas completado.\n";
