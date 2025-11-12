<?php
/**
 * Script de diagnóstico para verificar el logo de la empresa
 */

// Cargar el autoloader de Laravel
require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Empresa;

echo "<h2>Diagnóstico de Logo de Empresa</h2>";

// Verificar que existe el enlace simbólico
$storageLink = __DIR__ . '/storage';
echo "<h3>1. Verificación de Enlace Simbólico</h3>";
echo "<p><strong>Ruta del enlace:</strong> {$storageLink}</p>";

if (is_link($storageLink)) {
    echo "<p style='color: green;'>✓ El enlace simbólico existe</p>";
    $target = readlink($storageLink);
    echo "<p><strong>Apunta a:</strong> {$target}</p>";
} else {
    echo "<p style='color: red;'>✗ El enlace simbólico NO existe</p>";
    echo "<p><strong>Solución:</strong> Ejecutar: <code>php artisan storage:link</code></p>";
}

// Obtener información de la empresa
echo "<h3>2. Información de la Empresa</h3>";
$empresa = Empresa::first();

if ($empresa) {
    echo "<p><strong>Nombre:</strong> {$empresa->nombre_comercial}</p>";
    echo "<p><strong>Logo en BD:</strong> " . ($empresa->logo ?? 'No configurado') . "</p>";
    
    if ($empresa->logo) {
        // Verificar que el archivo existe físicamente
        $logoPathStorage = storage_path('app/public/' . $empresa->logo);
        echo "<p><strong>Ruta física:</strong> {$logoPathStorage}</p>";
        
        if (file_exists($logoPathStorage)) {
            echo "<p style='color: green;'>✓ El archivo existe físicamente</p>";
            $size = filesize($logoPathStorage);
            echo "<p><strong>Tamaño:</strong> " . number_format($size / 1024, 2) . " KB</p>";
        } else {
            echo "<p style='color: red;'>✗ El archivo NO existe en la ruta física</p>";
        }
        
        // Verificar URL pública
        $logoUrl = asset('storage/' . $empresa->logo);
        echo "<p><strong>URL pública:</strong> <a href='{$logoUrl}' target='_blank'>{$logoUrl}</a></p>";
        
        // Verificar acceso público
        $logoPathPublic = __DIR__ . '/storage/' . $empresa->logo;
        echo "<p><strong>Ruta pública:</strong> {$logoPathPublic}</p>";
        
        if (file_exists($logoPathPublic)) {
            echo "<p style='color: green;'>✓ Accesible públicamente</p>";
            echo "<p><strong>Prueba de visualización:</strong></p>";
            echo "<img src='{$logoUrl}' alt='Logo' style='max-width: 300px; border: 1px solid #ccc; padding: 10px;'>";
        } else {
            echo "<p style='color: red;'>✗ NO es accesible públicamente</p>";
            echo "<p><strong>Posible causa:</strong> El enlace simbólico no apunta correctamente</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ No hay logo configurado para la empresa</p>";
    }
} else {
    echo "<p style='color: red;'>✗ No se encontró información de la empresa en la base de datos</p>";
}

// Verificar permisos
echo "<h3>3. Verificación de Permisos</h3>";
$storagePath = storage_path('app/public');
echo "<p><strong>Directorio storage/app/public:</strong></p>";

if (is_writable($storagePath)) {
    echo "<p style='color: green;'>✓ El directorio tiene permisos de escritura</p>";
} else {
    echo "<p style='color: red;'>✗ El directorio NO tiene permisos de escritura</p>";
    echo "<p><strong>Solución:</strong> Dar permisos de escritura al directorio storage</p>";
}

// Listar archivos en storage/app/public/logos
echo "<h3>4. Archivos en storage/app/public/logos</h3>";
$logosPath = storage_path('app/public/logos');

if (is_dir($logosPath)) {
    $files = scandir($logosPath);
    $files = array_diff($files, ['.', '..']);
    
    if (count($files) > 0) {
        echo "<ul>";
        foreach ($files as $file) {
            $filePath = $logosPath . '/' . $file;
            $size = filesize($filePath);
            echo "<li>{$file} (" . number_format($size / 1024, 2) . " KB)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No hay archivos en el directorio de logos</p>";
    }
} else {
    echo "<p style='color: red;'>✗ El directorio logos no existe</p>";
    echo "<p><strong>Solución:</strong> Crear el directorio manualmente o subir un logo desde la interfaz</p>";
}
