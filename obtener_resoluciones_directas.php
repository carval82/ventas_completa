<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Empresa;
use Illuminate\Support\Facades\Http;

echo "🔍 OBTENER RESOLUCIONES DIRECTAS DE ALEGRA\n";
echo "==========================================\n\n";

try {
    // Obtener empresa con credenciales
    $empresa = Empresa::first();
    
    if (!$empresa || !$empresa->alegra_email || !$empresa->alegra_token) {
        echo "❌ No hay empresa con credenciales de Alegra configuradas\n";
        exit(1);
    }

    echo "📋 Usando credenciales:\n";
    echo "   - Email: {$empresa->alegra_email}\n";
    echo "   - Token: " . substr($empresa->alegra_token, 0, 8) . "...\n\n";

    // Hacer petición directa a la API de Alegra
    $auth = base64_encode($empresa->alegra_email . ':' . $empresa->alegra_token);
    
    echo "🔍 Consultando plantillas de numeración...\n\n";
    
    $response = Http::withHeaders([
        'Authorization' => 'Basic ' . $auth,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
    ])->get('https://api.alegra.com/api/v1/number-templates');

    if ($response->successful()) {
        $templates = $response->json();
        
        echo "✅ Plantillas de numeración encontradas:\n\n";
        
        foreach ($templates as $index => $template) {
            echo "📄 Plantilla #" . ($index + 1) . ":\n";
            echo "   - ID: " . ($template['id'] ?? 'N/A') . "\n";
            echo "   - Nombre: " . ($template['name'] ?? 'N/A') . "\n";
            echo "   - Prefijo: " . ($template['prefix'] ?? 'N/A') . "\n";
            echo "   - Número inicial: " . ($template['initialNumber'] ?? 'N/A') . "\n";
            echo "   - Número final: " . ($template['finalNumber'] ?? 'N/A') . "\n";
            echo "   - Número actual: " . ($template['currentNumber'] ?? 'N/A') . "\n";
            echo "   - Fecha inicio: " . ($template['startDate'] ?? 'N/A') . "\n";
            echo "   - Fecha fin: " . ($template['endDate'] ?? 'N/A') . "\n";
            echo "   - Activa: " . (isset($template['active']) ? ($template['active'] ? 'SÍ' : 'NO') : 'N/A') . "\n";
            
            // Verificar si es la resolución correcta
            $nombre = $template['name'] ?? '';
            $prefijo = $template['prefix'] ?? '';
            
            if (strpos($nombre, '18764098256287') !== false) {
                echo "   🎯 ¡ESTA ES LA RESOLUCIÓN CORRECTA! (18764098256287)\n";
            } elseif (strpos($nombre, 'FACTURA2025-1') !== false) {
                echo "   🎯 ¡ESTA PODRÍA SER LA RESOLUCIÓN CORRECTA! (FACTURA2025-1)\n";
            } elseif ($prefijo === 'FEVP') {
                echo "   🎯 ¡ESTA PODRÍA SER LA RESOLUCIÓN CORRECTA! (prefijo FEVP)\n";
            } elseif (strpos($nombre, '18764083087981') !== false) {
                echo "   ❌ Esta es la resolución INCORRECTA (18764083087981)\n";
            }
            
            echo "\n";
        }
        
        echo "🎯 INFORMACIÓN DE LA RESOLUCIÓN CORRECTA:\n";
        echo "   - Nombre esperado: FACTURA2025-1\n";
        echo "   - Autorización: 18764098256287\n";
        echo "   - Fecha: 2025-09-05\n";
        echo "   - Prefijo: FEVP\n";
        echo "   - Rango: FEVP83 hasta FEVP1000\n";
        echo "   - Vigencia: hasta 2026-03-05\n\n";
        
        // Buscar la resolución correcta por ID o características
        $resolucionCorrecta = null;
        foreach ($templates as $template) {
            $nombre = $template['name'] ?? '';
            $prefijo = $template['prefix'] ?? '';
            
            if (strpos($nombre, '18764098256287') !== false || 
                strpos($nombre, 'FACTURA2025-1') !== false || 
                $prefijo === 'FEVP') {
                $resolucionCorrecta = $template;
                break;
            }
        }
        
        if ($resolucionCorrecta) {
            echo "✅ RESOLUCIÓN CORRECTA ENCONTRADA:\n";
            echo "   - ID: " . $resolucionCorrecta['id'] . "\n";
            echo "   - Nombre: " . $resolucionCorrecta['name'] . "\n";
            echo "   - Prefijo: " . $resolucionCorrecta['prefix'] . "\n\n";
            
            echo "🔧 Para actualizar en el sistema:\n";
            echo "   - Usar ID: " . $resolucionCorrecta['id'] . "\n";
            echo "   - Usar prefijo: " . $resolucionCorrecta['prefix'] . "\n";
        } else {
            echo "❌ No se encontró la resolución correcta\n";
        }
        
    } else {
        echo "❌ Error al consultar Alegra\n";
        echo "   Código: " . $response->status() . "\n";
        echo "   Respuesta: " . $response->body() . "\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR:\n";
    echo "   Mensaje: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n🎯 Consulta completada\n";
