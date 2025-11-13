<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════\n";
echo "  ACTUALIZAR TOKEN DE ALEGRA\n";
echo "═══════════════════════════════════════════\n\n";

if ($argc < 2) {
    echo "❌ Falta el token\n\n";
    echo "Uso:\n";
    echo "  php actualizar_token_nuevo.php TU_TOKEN_DE_ALEGRA\n\n";
    echo "Ejemplo:\n";
    echo "  php actualizar_token_nuevo.php a1b2c3d4e5f6\n\n";
    exit(1);
}

$nuevoToken = $argv[1];

echo "Token ingresado: {$nuevoToken}\n";
echo "Longitud: " . strlen($nuevoToken) . " caracteres\n\n";

if (strlen($nuevoToken) < 10) {
    echo "⚠️  El token parece muy corto. ¿Estás seguro?\n";
}

echo "¿Actualizar el token? (s/n): ";
$respuesta = trim(fgets(STDIN));

if (strtolower($respuesta) === 's') {
    $empresa = DB::table('empresas')->first();
    
    DB::table('empresas')
        ->where('id', $empresa->id)
        ->update([
            'alegra_token' => $nuevoToken,
            'updated_at' => now()
        ]);
    
    echo "\n✅ Token actualizado correctamente\n\n";
    
    // Probar el nuevo token
    echo "Probando nuevo token...\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.alegra.com/api/v1/items?start=0&limit=1');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($empresa->alegra_email . ':' . $nuevoToken)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo "✅ TOKEN VÁLIDO - Conexión exitosa\n";
        $data = json_decode($response, true);
        if (isset($data) && is_array($data) && count($data) > 0) {
            echo "   Total productos encontrados: " . count($data) . "\n";
        }
    } else {
        echo "❌ TOKEN INVÁLIDO - HTTP {$httpCode}\n";
        echo "   Verifica que el token sea correcto\n";
    }
    
    echo "\n⚠️  IMPORTANTE: Reinicia php artisan serve\n";
} else {
    echo "\n❌ No se actualizó el token\n";
}
