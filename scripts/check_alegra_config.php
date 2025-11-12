<?php

// Cargar el entorno de Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Verificar configuración de Alegra
echo "Configuración de Alegra:\n";
echo "Base URL: " . config('alegra.base_url') . "\n";
echo "Usuario: " . (config('alegra.user') ? "Configurado" : "No configurado") . "\n";
echo "Token: " . (config('alegra.token') ? "Configurado" : "No configurado") . "\n";
echo "ID Plantilla FE: " . config('alegra.fe_template_id') . "\n";

// Verificar si podemos hacer una solicitud de prueba
$alegraService = app(\App\Services\AlegraService::class);
$result = $alegraService->testConnection();

echo "\nResultado de prueba de conexión:\n";
echo "Éxito: " . ($result['success'] ? "Sí" : "No") . "\n";

if (!$result['success']) {
    echo "Error: " . ($result['error'] ?? "Desconocido") . "\n";
} else {
    echo "Nombre de la empresa: " . ($result['data']['name'] ?? "N/A") . "\n";
}
