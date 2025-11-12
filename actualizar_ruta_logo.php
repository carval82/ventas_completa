<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$empresa = App\Models\Empresa::first();

if (!$empresa) {
    echo "❌ No se encontró la empresa\n";
    exit(1);
}

echo "🏢 Empresa: " . $empresa->nombre_comercial . "\n";
echo "📂 Logo actual en BD: " . ($empresa->logo ?? 'NULL') . "\n\n";

// Verificar si existe el logo en la ubicación actual
if ($empresa->logo) {
    $rutaActual = storage_path('app/public/' . $empresa->logo);
    
    if (file_exists($rutaActual)) {
        echo "✅ Logo encontrado en: $rutaActual\n";
        
        // Si el logo no está en la carpeta 'logos', moverlo
        if (!str_starts_with($empresa->logo, 'logos/')) {
            // Crear la carpeta logos si no existe
            $carpetaLogos = storage_path('app/public/logos');
            if (!is_dir($carpetaLogos)) {
                mkdir($carpetaLogos, 0755, true);
                echo "📁 Carpeta 'logos' creada\n";
            }
            
            // Obtener el nombre del archivo
            $nombreArchivo = basename($empresa->logo);
            $nuevaRuta = 'logos/' . $nombreArchivo;
            $rutaCompletaNueva = storage_path('app/public/' . $nuevaRuta);
            
            // Copiar el archivo
            if (copy($rutaActual, $rutaCompletaNueva)) {
                echo "✅ Logo copiado a: logos/$nombreArchivo\n";
                
                // Actualizar la base de datos
                $empresa->logo = $nuevaRuta;
                $empresa->save();
                
                echo "✅ Base de datos actualizada\n";
                echo "\n📍 Nueva ruta del logo: $nuevaRuta\n";
            } else {
                echo "❌ Error al copiar el logo\n";
            }
        } else {
            echo "✅ El logo ya está en la carpeta 'logos'\n";
        }
    } else {
        echo "❌ El archivo del logo no existe en: $rutaActual\n";
        echo "💡 Puedes crear uno nuevo con: php crear_logo_prueba.php\n";
    }
} else {
    echo "⚠️  No hay logo configurado\n";
    echo "💡 Ejecuta: php crear_logo_prueba.php\n";
}

echo "\n";
