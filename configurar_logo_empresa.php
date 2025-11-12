<?php

/**
 * Script para configurar el logo de la empresa
 * 
 * INSTRUCCIONES:
 * 1. Coloca tu logo (formato: jpg, png, jpeg) en la carpeta: storage/app/public/
 * 2. Ejecuta este script: php configurar_logo_empresa.php nombre_del_logo.png
 * 
 * Ejemplo: php configurar_logo_empresa.php logo_empresa.png
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Verificar argumentos
if ($argc < 2) {
    echo "❌ ERROR: Debes proporcionar el nombre del archivo del logo\n\n";
    echo "USO: php configurar_logo_empresa.php nombre_del_logo.png\n\n";
    echo "PASOS:\n";
    echo "1. Sube tu logo a: storage/app/public/\n";
    echo "2. Ejecuta: php configurar_logo_empresa.php nombre_del_logo.png\n\n";
    echo "Formatos soportados: jpg, jpeg, png, gif, svg\n";
    exit(1);
}

$nombreLogo = $argv[1];
$rutaLogo = storage_path('app/public/' . $nombreLogo);

// Verificar que el archivo existe
if (!file_exists($rutaLogo)) {
    echo "❌ ERROR: El archivo no existe en: $rutaLogo\n\n";
    echo "Por favor:\n";
    echo "1. Verifica que el archivo esté en: storage/app/public/\n";
    echo "2. Verifica que el nombre sea correcto: $nombreLogo\n\n";
    exit(1);
}

// Verificar que es una imagen
$extension = strtolower(pathinfo($nombreLogo, PATHINFO_EXTENSION));
$extensionesValidas = ['jpg', 'jpeg', 'png', 'gif', 'svg'];

if (!in_array($extension, $extensionesValidas)) {
    echo "❌ ERROR: Formato no válido ($extension)\n";
    echo "Formatos permitidos: " . implode(', ', $extensionesValidas) . "\n";
    exit(1);
}

echo "🔍 Verificando archivo...\n";
echo "   Ruta: $rutaLogo\n";
echo "   Tamaño: " . round(filesize($rutaLogo) / 1024, 2) . " KB\n";
echo "   Formato: $extension\n\n";

// Obtener la empresa
$empresa = App\Models\Empresa::first();

if (!$empresa) {
    echo "❌ ERROR: No se encontró ninguna empresa en la base de datos\n";
    exit(1);
}

echo "🏢 Empresa encontrada: " . $empresa->nombre_comercial . "\n";

// Actualizar el logo
$empresa->logo = $nombreLogo;
$empresa->save();

echo "\n✅ ¡LOGO CONFIGURADO EXITOSAMENTE!\n\n";
echo "Detalles:\n";
echo "  - Empresa: " . $empresa->nombre_comercial . "\n";
echo "  - Logo: " . $nombreLogo . "\n";
echo "  - Ubicación: storage/app/public/" . $nombreLogo . "\n\n";
echo "El logo ahora aparecerá en:\n";
echo "  ✓ Facturas PDF\n";
echo "  ✓ Facturas electrónicas\n";
echo "  ✓ Impresiones de ventas\n";
echo "  ✓ Cotizaciones\n";
echo "  ✓ Remisiones\n\n";

// Verificar enlace simbólico
$publicPath = public_path('storage/' . $nombreLogo);
if (file_exists($publicPath)) {
    echo "✅ El logo también está accesible públicamente en:\n";
    echo "   http://tu-dominio/storage/" . $nombreLogo . "\n";
} else {
    echo "⚠️  NOTA: Si el logo no se ve en la web, ejecuta:\n";
    echo "   php artisan storage:link\n";
}

echo "\n";
