<?php
/**
 * Sincroniza QRs de facturas electrónicas que no lo tienen
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════\n";
echo "  SINCRONIZAR QRs DE FACTURAS ELECTRÓNICAS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Buscar ventas con alegra_id pero sin qr_code
$ventasSinQR = DB::table('ventas')
    ->whereNotNull('alegra_id')
    ->whereNull('qr_code')
    ->get();

echo "Facturas sin QR encontradas: " . count($ventasSinQR) . "\n\n";

if (count($ventasSinQR) == 0) {
    echo "✅ No hay facturas pendientes de sincronización\n";
    exit(0);
}

// Obtener credenciales
$empresa = DB::table('empresas')->first();
$email = $empresa->alegra_email;
$token = $empresa->alegra_token;

$actualizadas = 0;
$errores = 0;

foreach ($ventasSinQR as $venta) {
    echo "Consultando venta #{$venta->id} (Alegra ID: {$venta->alegra_id})...\n";
    
    // Consultar factura en Alegra
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.alegra.com/api/v1/invoices/{$venta->alegra_id}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($email . ':' . $token)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        
        if (isset($data['stamp']['barCodeContent'])) {
            // Actualizar con QR
            DB::table('ventas')
                ->where('id', $venta->id)
                ->update([
                    'cufe' => $data['stamp']['cufe'] ?? null,
                    'qr_code' => $data['stamp']['barCodeContent'],
                    'estado_dian' => $data['stamp']['legalStatus'] ?? null,
                    'url_pdf_alegra' => $data['stamp']['pdfUrl'] ?? null,
                    'updated_at' => now()
                ]);
            
            echo "  ✅ QR actualizado\n";
            $actualizadas++;
        } else {
            echo "  ⚠️  Sin stamp (aún no procesada por DIAN)\n";
            $errores++;
        }
    } else {
        echo "  ❌ Error HTTP {$httpCode}\n";
        $errores++;
    }
    
    // Esperar un poco entre consultas
    usleep(300000); // 0.3 segundos
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "  RESULTADO\n";
echo "═══════════════════════════════════════════════════════════\n\n";
echo "Actualizadas: {$actualizadas}\n";
echo "Errores/Sin stamp: {$errores}\n";
echo "\n✅ Sincronización completada\n";
