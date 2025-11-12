<?php

/**
 * Script para crear un logo de prueba SVG
 * Ejecuta: php crear_logo_prueba.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$empresa = App\Models\Empresa::first();

if (!$empresa) {
    echo "❌ No se encontró ninguna empresa\n";
    exit(1);
}

echo "🎨 Creando logo de prueba para: " . $empresa->nombre_comercial . "\n\n";

// Crear SVG del logo
$nombreEmpresa = $empresa->nombre_comercial ?? 'MI EMPRESA';
$iniciales = '';

// Extraer iniciales
$palabras = explode(' ', $nombreEmpresa);
foreach ($palabras as $palabra) {
    if (!empty($palabra)) {
        $iniciales .= strtoupper(substr($palabra, 0, 1));
    }
}
$iniciales = substr($iniciales, 0, 3); // Máximo 3 letras

// Crear SVG
$svg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg width="200" height="80" xmlns="http://www.w3.org/2000/svg">
  <!-- Fondo con gradiente -->
  <defs>
    <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#2c5aa0;stop-opacity:1" />
      <stop offset="100%" style="stop-color:#1e3a6f;stop-opacity:1" />
    </linearGradient>
  </defs>
  
  <!-- Rectángulo de fondo con bordes redondeados -->
  <rect x="0" y="0" width="200" height="80" rx="10" fill="url(#grad1)"/>
  
  <!-- Iniciales grandes -->
  <text x="100" y="50" font-family="Arial, sans-serif" font-size="32" font-weight="bold" 
        fill="white" text-anchor="middle" dominant-baseline="middle">
    $iniciales
  </text>
  
  <!-- Nombre de empresa pequeño -->
  <text x="100" y="70" font-family="Arial, sans-serif" font-size="8" 
        fill="#e0e0e0" text-anchor="middle">
    $nombreEmpresa
  </text>
</svg>
SVG;

// Guardar el archivo
$rutaLogo = storage_path('app/public/logo_empresa.svg');
file_put_contents($rutaLogo, $svg);

echo "✅ Logo SVG creado: $rutaLogo\n";
echo "   Tamaño: " . filesize($rutaLogo) . " bytes\n\n";

// Actualizar la empresa
$empresa->logo = 'logo_empresa.svg';
$empresa->save();

echo "✅ Logo configurado en la base de datos\n\n";
echo "🎉 ¡COMPLETADO!\n\n";
echo "El logo ahora aparecerá en todas las facturas y documentos.\n";
echo "Es un logo temporal con las iniciales: $iniciales\n\n";
echo "📝 Para usar tu propio logo:\n";
echo "   1. Sube tu logo a: storage/app/public/mi_logo.png\n";
echo "   2. Ejecuta: php configurar_logo_empresa.php mi_logo.png\n\n";
