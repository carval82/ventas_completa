<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════\n";
echo "  PRUEBA DE TOKEN ALEGRA\n";
echo "═══════════════════════════════════════════\n\n";

$empresa = DB::table('empresas')->first();

$email = $empresa->alegra_email;
$token = $empresa->alegra_token;

echo "Credenciales en BD:\n";
echo "  Email: {$email}\n";
echo "  Token: {$token}\n\n";

echo "Probando conexión con Alegra...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.alegra.com/api/v1/items?start=0&limit=1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($email . ':' . $token)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "\nResultado:\n";
echo "  HTTP Code: {$httpCode}\n";
echo "  Error: " . ($error ?: 'Ninguno') . "\n\n";

if ($httpCode == 200) {
    echo "✅ TOKEN VÁLIDO - Conexión exitosa\n";
    $data = json_decode($response, true);
    if (isset($data[0]['name'])) {
        echo "   Primer producto: {$data[0]['name']}\n";
    }
} elseif ($httpCode == 401) {
    echo "❌ TOKEN INVÁLIDO - Error 401 Unauthorized\n\n";
    echo "POSIBLES CAUSAS:\n";
    echo "1. El token es de otra cuenta (Plásticos Sánchez)\n";
    echo "2. El token expiró o fue revocado en Alegra\n";
    echo "3. El email no coincide con el token\n\n";
    echo "SOLUCIÓN:\n";
    echo "1. Ve a Alegra → Configuración → Integraciones\n";
    echo "2. Genera un NUEVO token para: {$email}\n";
    echo "3. Actualiza el token en el sistema\n";
} else {
    echo "⚠️  Código HTTP inesperado: {$httpCode}\n";
    echo "   Respuesta: " . substr($response, 0, 200) . "\n";
}
