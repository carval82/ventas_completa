<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════\n";
echo "  CONSULTAR FACTURA EN ALEGRA\n";
echo "═══════════════════════════════════════════\n\n";

// Obtener última venta
$venta = DB::table('ventas')->orderBy('id', 'desc')->first();

if (!$venta->alegra_id) {
    echo "❌ La venta no tiene ID de Alegra\n";
    exit(1);
}

echo "Consultando factura ID: {$venta->alegra_id} en Alegra...\n\n";

// Obtener credenciales
$empresa = DB::table('empresas')->first();
$email = $empresa->alegra_email;
$token = $empresa->alegra_token;

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
    
    echo "✅ Factura encontrada\n\n";
    echo "Número: " . ($data['numberTemplate']['fullNumber'] ?? 'N/A') . "\n";
    echo "Estado: " . ($data['status'] ?? 'N/A') . "\n";
    echo "Total: " . ($data['total'] ?? 'N/A') . "\n\n";
    
    echo "Datos DIAN (stamp):\n";
    echo "─────────────────────────────────────────────────\n";
    
    if (isset($data['stamp'])) {
        $stamp = $data['stamp'];
        echo "CUFE: " . ($stamp['cufe'] ?? 'NULL') . "\n";
        echo "Estado DIAN: " . ($stamp['legalStatus'] ?? 'NULL') . "\n";
        echo "QR (barCodeContent): " . ($stamp['barCodeContent'] ? 'Sí (existe)' : 'NULL') . "\n";
        echo "URL PDF: " . ($stamp['pdfUrl'] ?? 'NULL') . "\n\n";
        
        if (isset($stamp['cufe']) && isset($stamp['barCodeContent'])) {
            echo "✅ La factura tiene CUFE y QR de la DIAN\n";
            echo "Actualizando en la BD...\n";
            
            DB::table('ventas')
                ->where('id', $venta->id)
                ->update([
                    'cufe' => $stamp['cufe'],
                    'qr_code' => $stamp['barCodeContent'],
                    'estado_dian' => $stamp['legalStatus'] ?? 'sent',
                    'url_pdf_alegra' => $stamp['pdfUrl'] ?? null,
                    'updated_at' => now()
                ]);
            
            echo "✅ QR actualizado en la BD\n";
        } else {
            echo "⚠️  La factura AÚN NO tiene CUFE/QR\n";
            echo "Esto significa que Alegra aún no la procesó con la DIAN\n";
            echo "Espera unos minutos y vuelve a consultar\n";
        }
    } else {
        echo "❌ No hay información de stamp (DIAN)\n";
        echo "La factura no ha sido procesada por la DIAN\n";
    }
    
} else {
    echo "❌ Error al consultar factura: HTTP {$httpCode}\n";
    echo "Respuesta: {$response}\n";
}
