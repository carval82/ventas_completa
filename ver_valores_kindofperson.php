<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Services\AlegraService;

try {
    echo "=== ANALIZANDO VALORES DE kindOfPerson EN ALEGRA ===\n\n";
    
    $alegraService = new AlegraService();
    $clientes = $alegraService->obtenerClientes();
    
    if ($clientes['success']) {
        echo "✅ Conexión exitosa con Alegra\n";
        echo "📊 Total de clientes: " . count($clientes['data']) . "\n\n";
        
        $valoresKindOfPerson = [];
        $clientesAnalizados = 0;
        
        foreach ($clientes['data'] as $cliente) {
            $clientesAnalizados++;
            
            echo "--- CLIENTE {$clientesAnalizados} ---\n";
            echo "ID: " . ($cliente['id'] ?? 'N/A') . "\n";
            echo "Nombre: " . ($cliente['name'] ?? 'N/A') . "\n";
            echo "Identificación: " . ($cliente['identification'] ?? 'N/A') . "\n";
            
            // Buscar el campo kindOfPerson
            if (isset($cliente['kindOfPerson'])) {
                $valor = $cliente['kindOfPerson'];
                echo "✅ kindOfPerson: " . json_encode($valor) . "\n";
                
                // Contar valores únicos
                if (!isset($valoresKindOfPerson[$valor])) {
                    $valoresKindOfPerson[$valor] = 0;
                }
                $valoresKindOfPerson[$valor]++;
            } else {
                echo "❌ kindOfPerson: NO ENCONTRADO\n";
            }
            
            // Mostrar tipo de identificación para correlacionar
            if (isset($cliente['identificationObject']['type'])) {
                echo "Tipo ID: " . $cliente['identificationObject']['type'] . "\n";
            }
            
            echo "\n";
            
            // Solo analizar los primeros 10 para no saturar
            if ($clientesAnalizados >= 10) {
                break;
            }
        }
        
        echo "=== RESUMEN DE VALORES kindOfPerson ===\n";
        if (empty($valoresKindOfPerson)) {
            echo "❌ No se encontró el campo 'kindOfPerson' en ningún cliente\n";
            
            // Buscar campos similares
            echo "\n🔍 Buscando campos similares...\n";
            $camposSimilares = [];
            foreach ($clientes['data'] as $cliente) {
                foreach (array_keys($cliente) as $campo) {
                    if (stripos($campo, 'person') !== false || 
                        stripos($campo, 'tipo') !== false || 
                        stripos($campo, 'kind') !== false) {
                        if (!in_array($campo, $camposSimilares)) {
                            $camposSimilares[] = $campo;
                        }
                    }
                }
            }
            
            if (!empty($camposSimilares)) {
                echo "Campos similares encontrados:\n";
                foreach ($camposSimilares as $campo) {
                    echo "  - {$campo}\n";
                }
            }
        } else {
            foreach ($valoresKindOfPerson as $valor => $cantidad) {
                echo "'{$valor}': {$cantidad} clientes\n";
            }
        }
        
    } else {
        echo "❌ Error al obtener clientes: " . ($clientes['error'] ?? 'Error desconocido') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Excepción: " . $e->getMessage() . "\n";
}
