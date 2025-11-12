<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$empresa = App\Models\Empresa::first();

if ($empresa) {
    echo "Empresa encontrada: " . $empresa->nombre_comercial . PHP_EOL;
    echo "Logo en BD: " . ($empresa->logo ? $empresa->logo : 'NULL') . PHP_EOL;
    
    if ($empresa->logo) {
        $logoPath = storage_path('app/public/' . $empresa->logo);
        echo "Ruta completa: " . $logoPath . PHP_EOL;
        echo "Archivo existe: " . (file_exists($logoPath) ? 'SI' : 'NO') . PHP_EOL;
        
        if (file_exists($logoPath)) {
            echo "Tamaño: " . filesize($logoPath) . " bytes" . PHP_EOL;
        }
        
        // Verificar también la ruta pública
        $publicPath = public_path('storage/' . $empresa->logo);
        echo "\nRuta pública: " . $publicPath . PHP_EOL;
        echo "Archivo existe en public: " . (file_exists($publicPath) ? 'SI' : 'NO') . PHP_EOL;
    } else {
        echo "\nNo hay logo configurado en la base de datos." . PHP_EOL;
        echo "Para agregar un logo:" . PHP_EOL;
        echo "1. Sube una imagen a storage/app/public/" . PHP_EOL;
        echo "2. Actualiza el campo 'logo' en la tabla empresas con el nombre del archivo" . PHP_EOL;
    }
} else {
    echo "No se encontró ninguna empresa en la base de datos" . PHP_EOL;
}
