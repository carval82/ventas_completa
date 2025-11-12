<?php

// Este script devuelve las credenciales de Alegra configuradas en la base de datos
// o las del archivo .env como respaldo

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Empresa;
use Illuminate\Support\Facades\Log;

try {
    // Intentar obtener las credenciales de la empresa
    $empresa = Empresa::first();
    
    if ($empresa && $empresa->alegra_email && $empresa->alegra_token) {
        // Usar credenciales de la empresa
        $email = $empresa->alegra_email;
        $token = $empresa->alegra_token;
        Log::info('Script usando credenciales de Alegra configuradas en la empresa');
    } else {
        // Usar credenciales del archivo .env como respaldo
        $email = config('alegra.user');
        $token = config('alegra.token');
        Log::info('Script usando credenciales de Alegra del archivo .env');
    }
    
    // Devolver las credenciales en formato JSON
    echo json_encode([
        'success' => true,
        'email' => $email,
        'token' => $token
    ]);
    
} catch (Exception $e) {
    Log::error('Error al obtener credenciales de Alegra: ' . $e->getMessage());
    
    // En caso de error, intentar usar las credenciales del archivo .env
    echo json_encode([
        'success' => false,
        'email' => config('alegra.user'),
        'token' => config('alegra.token'),
        'error' => $e->getMessage()
    ]);
}
