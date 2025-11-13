<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════\n";
echo "  VERIFICAR QR DE FACTURA ELECTRÓNICA\n";
echo "═══════════════════════════════════════════\n\n";

// Obtener la última venta
$venta = DB::table('ventas')
    ->orderBy('id', 'desc')
    ->first();

if (!$venta) {
    echo "❌ No hay ventas en el sistema\n";
    exit(1);
}

echo "Venta ID: {$venta->id}\n";
echo "Número: {$venta->numero_factura}\n";
echo "Total: \${$venta->total}\n";
echo "Fecha: {$venta->created_at}\n\n";

echo "Datos de Alegra:\n";
echo "─────────────────────────────────────────────────\n";
echo "alegra_id: " . ($venta->alegra_id ?? 'NULL') . "\n";
echo "numero_factura_alegra: " . ($venta->numero_factura_alegra ?? 'NULL') . "\n";
echo "url_pdf_alegra: " . ($venta->url_pdf_alegra ?? 'NULL') . "\n\n";

echo "Datos DIAN:\n";
echo "─────────────────────────────────────────────────\n";
echo "cufe: " . ($venta->cufe ?? 'NULL') . "\n";
echo "qr_code: " . ($venta->qr_code ? substr($venta->qr_code, 0, 100) . '...' : 'NULL') . "\n";
echo "estado_dian: " . ($venta->estado_dian ?? 'NULL') . "\n\n";

echo "QR Local:\n";
echo "─────────────────────────────────────────────────\n";
echo "cufe_local: " . ($venta->cufe_local ?? 'NULL') . "\n";
echo "qr_local: " . ($venta->qr_local ? 'Sí (existe)' : 'NULL') . "\n\n";

// Verificar empresa
$empresa = DB::table('empresas')->first();
echo "Configuración Empresa:\n";
echo "─────────────────────────────────────────────────\n";
echo "generar_qr_local: " . ($empresa->generar_qr_local ? 'SÍ' : 'NO') . "\n";
echo "factura_electronica_habilitada: " . ($empresa->factura_electronica_habilitada ? 'SÍ' : 'NO') . "\n\n";

echo "═══════════════════════════════════════════\n";
echo "  DIAGNÓSTICO\n";
echo "═══════════════════════════════════════════\n\n";

if (!$venta->qr_code && !$venta->qr_local) {
    echo "❌ NO HAY QR GENERADO\n\n";
    
    if (!$venta->alegra_id) {
        echo "Causa: La factura NO se envió a Alegra\n";
        echo "Solución: Verificar por qué falló la creación en Alegra\n";
    } elseif (!$venta->cufe) {
        echo "Causa: Alegra no devolvió el CUFE\n";
        echo "Solución: \n";
        echo "  1. La factura debe ser emitida a la DIAN desde Alegra\n";
        echo "  2. Una vez emitida, Alegra devolverá el CUFE y QR\n";
        echo "  3. Alternativamente, habilitar generar_qr_local=1 en empresa\n";
    } else {
        echo "Causa: Datos de stamp no llegaron de Alegra\n";
        echo "Solución: Consultar la factura en Alegra para obtener el QR\n";
    }
} else {
    echo "✅ QR EXISTE\n";
    if ($venta->qr_code) {
        echo "Fuente: Alegra (DIAN)\n";
    } else {
        echo "Fuente: Local (generado por el sistema)\n";
    }
}
