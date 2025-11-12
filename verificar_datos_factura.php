<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Venta;
use App\Models\Empresa;

echo "=== VERIFICACIÓN DE DATOS PARA TIRILLA ===\n\n";

// Verificar última factura electrónica
$venta = Venta::whereNotNull('alegra_id')->latest()->first();

if ($venta) {
    echo "📄 FACTURA ELECTRÓNICA:\n";
    echo "  ID: {$venta->id}\n";
    echo "  Número: {$venta->numero_factura}\n";
    echo "  Alegra ID: {$venta->alegra_id}\n";
    echo "  Estado DIAN: " . ($venta->estado_dian ?? 'NULL') . "\n";
    echo "  CUFE: " . ($venta->cufe ? substr($venta->cufe, 0, 30) . '...' : 'NULL') . "\n";
    echo "  QR Code: " . ($venta->qr_code ? 'SÍ (' . strlen($venta->qr_code) . ' chars)' : 'NO - NULL') . "\n";
    echo "\n";
} else {
    echo "❌ No se encontraron facturas electrónicas\n\n";
}

// Verificar datos de empresa
$empresa = Empresa::first();

if ($empresa) {
    echo "🏢 DATOS DE EMPRESA:\n";
    echo "  Nombre: {$empresa->nombre_comercial}\n";
    echo "  NIT: {$empresa->nit}\n";
    echo "  Logo: " . ($empresa->logo ? "SÍ - {$empresa->logo}" : 'NO - NULL') . "\n";
    
    if ($empresa->logo) {
        $logoPath = storage_path('app/public/' . $empresa->logo);
        echo "  Ruta completa: {$logoPath}\n";
        echo "  Archivo existe: " . (file_exists($logoPath) ? 'SÍ' : 'NO - ARCHIVO NO ENCONTRADO') . "\n";
        
        if (file_exists($logoPath)) {
            echo "  Tamaño: " . filesize($logoPath) . " bytes\n";
        }
    }
    echo "\n";
} else {
    echo "❌ No se encontró información de empresa\n\n";
}

// Verificar storage link
$publicLink = public_path('storage');
echo "🔗 STORAGE LINK:\n";
echo "  Ruta: {$publicLink}\n";
echo "  Existe: " . (file_exists($publicLink) ? 'SÍ' : 'NO - NECESITA php artisan storage:link') . "\n";
echo "  Es link: " . (is_link($publicLink) ? 'SÍ' : 'NO') . "\n";
echo "\n";

echo "=== RECOMENDACIONES ===\n\n";

if ($venta && !$venta->qr_code) {
    echo "⚠️  La factura NO tiene QR guardado en BD.\n";
    echo "   Solución: Ejecuta 'Verificar Estado' en la factura para sincronizar.\n\n";
}

if ($empresa && !$empresa->logo) {
    echo "⚠️  La empresa NO tiene logo configurado.\n";
    echo "   Solución: Sube un logo en Configuración -> Empresa -> Editar.\n\n";
}

if ($empresa && $empresa->logo && !file_exists(storage_path('app/public/' . $empresa->logo))) {
    echo "⚠️  El archivo de logo NO existe en el servidor.\n";
    echo "   Solución: Vuelve a subir el logo.\n\n";
}

if (!file_exists($publicLink)) {
    echo "⚠️  El enlace simbólico de storage NO existe.\n";
    echo "   Solución: Ejecuta: php artisan storage:link\n\n";
}

echo "✅ Verificación completada\n";
