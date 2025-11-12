<?php
// Cargar el framework Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Importar las clases necesarias
use App\Services\AlegraService;

// ID de la factura a descargar
$idFactura = isset($argv[1]) ? $argv[1] : null;

if (!$idFactura) {
    echo "Error: Debe proporcionar el ID de la factura como argumento\n";
    echo "Uso: php descargar_pdf.php ID_FACTURA\n";
    exit(1);
}

// Crear instancia del servicio de Alegra
$alegraService = new AlegraService();

// Ruta donde guardar el PDF
$rutaDestino = __DIR__ . "/facturas/factura_{$idFactura}.pdf";

// Crear directorio si no existe
if (!is_dir(__DIR__ . "/facturas")) {
    mkdir(__DIR__ . "/facturas", 0755, true);
}

echo "Descargando PDF de la factura {$idFactura}...\n";

// Descargar el PDF
$resultado = $alegraService->descargarPdfFacturaDirecto($idFactura, $rutaDestino);

if ($resultado['success']) {
    echo "✅ PDF descargado correctamente en: {$resultado['ruta_archivo']}\n";
} else {
    echo "❌ Error al descargar el PDF: {$resultado['message']}\n";
}
