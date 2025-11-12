<?php

/**
 * Script para mejorar el logo actual (dimensiones optimizadas)
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$empresa = App\Models\Empresa::first();

if (!$empresa) {
    echo "❌ No se encontró ninguna empresa\n";
    exit(1);
}

echo "🎨 Mejorando logo para: " . $empresa->nombre_comercial . "\n\n";

// Obtener iniciales
$nombreEmpresa = $empresa->nombre_comercial ?? 'MI EMPRESA';
$iniciales = '';

$palabras = explode(' ', $nombreEmpresa);
foreach ($palabras as $palabra) {
    if (!empty($palabra)) {
        $iniciales .= strtoupper(substr($palabra, 0, 1));
    }
}
$iniciales = substr($iniciales, 0, 3);

// Crear SVG mejorado con dimensiones optimizadas
$svg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg width="250" height="100" viewBox="0 0 250 100" xmlns="http://www.w3.org/2000/svg">
  <!-- Fondo con gradiente -->
  <defs>
    <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#2563eb;stop-opacity:1" />
      <stop offset="100%" style="stop-color:#1e40af;stop-opacity:1" />
    </linearGradient>
    <filter id="shadow">
      <feGaussianBlur in="SourceAlpha" stdDeviation="2"/>
      <feOffset dx="0" dy="2" result="offsetblur"/>
      <feComponentTransfer>
        <feFuncA type="linear" slope="0.3"/>
      </feComponentTransfer>
      <feMerge> 
        <feMergeNode/>
        <feMergeNode in="SourceGraphic"/> 
      </feMerge>
    </filter>
  </defs>
  
  <!-- Rectángulo de fondo con bordes redondeados -->
  <rect x="2" y="2" width="246" height="96" rx="12" fill="url(#grad1)" filter="url(#shadow)"/>
  
  <!-- Borde decorativo -->
  <rect x="2" y="2" width="246" height="96" rx="12" fill="none" stroke="white" stroke-width="2" opacity="0.3"/>
  
  <!-- Iniciales grandes y centradas -->
  <text x="125" y="58" font-family="Arial, sans-serif" font-size="42" font-weight="bold" 
        fill="white" text-anchor="middle" dominant-baseline="middle">
    $iniciales
  </text>
  
  <!-- Nombre de empresa pequeño en la parte inferior -->
  <text x="125" y="82" font-family="Arial, sans-serif" font-size="10" 
        fill="white" text-anchor="middle" opacity="0.9">
    $nombreEmpresa
  </text>
</svg>
SVG;

// Guardar el archivo mejorado
$rutaLogo = storage_path('app/public/logos/logo_empresa.svg');
file_put_contents($rutaLogo, $svg);

echo "✅ Logo mejorado guardado: $rutaLogo\n";
echo "   Dimensiones: 250x100 pixeles\n";
echo "   Tamaño: " . filesize($rutaLogo) . " bytes\n\n";

echo "🎉 ¡LOGO MEJORADO EXITOSAMENTE!\n\n";
echo "Mejoras aplicadas:\n";
echo "  ✓ Dimensiones optimizadas (250x100 px)\n";
echo "  ✓ Proporción perfecta para documentos\n";
echo "  ✓ Gradiente azul profesional\n";
echo "  ✓ Sombra sutil para profundidad\n";
echo "  ✓ Borde decorativo\n";
echo "  ✓ Iniciales: $iniciales\n\n";

echo "📝 El logo se verá mucho mejor en:\n";
echo "  ✓ Formulario de edición\n";
echo "  ✓ Facturas PDF\n";
echo "  ✓ Documentos impresos\n\n";

echo "💡 Tip: Ahora puedes subir tu logo personalizado desde:\n";
echo "   Configuración → Empresa → Editar → Sección 'Logo'\n\n";
