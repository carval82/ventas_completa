<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\BackupService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

try {
    echo "=== COMPARACIÓN ESTRUCTURA VENTAS ===\n\n";
    
    $backupFile = storage_path('app/backups/2025-09-23_01-08-48_backup.zip');
    
    // Extraer contenido SQL
    $backupService = new BackupService();
    $reflection = new ReflectionClass($backupService);
    $method = $reflection->getMethod('extraerContenidoSQL');
    $method->setAccessible(true);
    $sqlContent = $method->invoke($backupService, $backupFile);
    
    // 1. Obtener estructura actual de la tabla
    echo "📊 ESTRUCTURA ACTUAL DE LA TABLA VENTAS:\n";
    $columnasActuales = Schema::getColumnListing('ventas');
    foreach ($columnasActuales as $i => $columna) {
        echo sprintf("%2d. %s\n", $i + 1, $columna);
    }
    echo "Total columnas actuales: " . count($columnasActuales) . "\n\n";
    
    // 2. Buscar CREATE TABLE de ventas en el backup para ver la estructura original
    echo "🔍 BUSCANDO ESTRUCTURA ORIGINAL EN BACKUP:\n";
    $lineas = explode("\n", $sqlContent);
    $createTable = '';
    $enCreateVentas = false;
    
    foreach ($lineas as $linea) {
        $linea = trim($linea);
        
        if (preg_match('/^CREATE TABLE\s+`?ventas`?/i', $linea)) {
            $createTable = $linea . "\n";
            $enCreateVentas = true;
            echo "✅ CREATE TABLE de ventas encontrado\n";
        } elseif ($enCreateVentas) {
            $createTable .= $linea . "\n";
            if (strpos($linea, ');') !== false || strpos($linea, ') ENGINE') !== false) {
                break;
            }
        }
    }
    
    if (!empty($createTable)) {
        echo "\n📋 CREATE TABLE completo:\n";
        echo $createTable . "\n";
        
        // Extraer columnas del CREATE TABLE
        $columnasBackup = [];
        $lineasCreate = explode("\n", $createTable);
        
        foreach ($lineasCreate as $linea) {
            $linea = trim($linea);
            // Buscar definiciones de columnas (no PRIMARY KEY, FOREIGN KEY, etc.)
            if (preg_match('/^`?([a-zA-Z_][a-zA-Z0-9_]*)`?\s+/', $linea, $matches)) {
                $nombreColumna = $matches[1];
                // Excluir palabras clave
                if (!in_array(strtoupper($nombreColumna), ['PRIMARY', 'KEY', 'FOREIGN', 'CONSTRAINT', 'INDEX', 'UNIQUE'])) {
                    $columnasBackup[] = $nombreColumna;
                }
            }
        }
        
        echo "📊 COLUMNAS EXTRAÍDAS DEL CREATE TABLE:\n";
        foreach ($columnasBackup as $i => $columna) {
            echo sprintf("%2d. %s\n", $i + 1, $columna);
        }
        echo "Total columnas backup: " . count($columnasBackup) . "\n\n";
    }
    
    // 3. Analizar el INSERT de ventas para confirmar el orden
    echo "🔍 ANALIZANDO INSERT DE VENTAS:\n";
    $insertVentas = '';
    
    foreach ($lineas as $linea) {
        if (preg_match('/^INSERT INTO\s+`?ventas`?\s/i', trim($linea))) {
            $insertVentas = trim($linea);
            // Leer líneas siguientes hasta encontrar el final
            $siguienteIndice = array_search($linea, $lineas) + 1;
            while ($siguienteIndice < count($lineas)) {
                $siguienteLinea = trim($lineas[$siguienteIndice]);
                if (!empty($siguienteLinea)) {
                    $insertVentas .= ' ' . $siguienteLinea;
                    if (substr($siguienteLinea, -1) === ';') {
                        break;
                    }
                }
                $siguienteIndice++;
            }
            break;
        }
    }
    
    if (!empty($insertVentas)) {
        // Extraer primer registro para analizar estructura
        if (preg_match('/VALUES\s*\(([^)]+)\)/', $insertVentas, $matches)) {
            $primerRegistro = $matches[1];
            $valores = explode(',', $primerRegistro);
            
            echo "📦 Primer registro encontrado:\n";
            echo "Número de valores: " . count($valores) . "\n\n";
            
            // Si tenemos la estructura del CREATE TABLE, usarla
            if (!empty($columnasBackup)) {
                echo "📋 MAPEO BASADO EN CREATE TABLE:\n";
                $mapeoFinal = [];
                
                for ($i = 0; $i < min(count($columnasBackup), count($valores)); $i++) {
                    $columnaBackup = $columnasBackup[$i];
                    $valor = trim($valores[$i]);
                    
                    // Buscar correspondencia en estructura actual
                    $columnaActual = null;
                    if (in_array($columnaBackup, $columnasActuales)) {
                        $columnaActual = $columnaBackup;
                    } else {
                        // Mapeos especiales
                        $mapeos = [
                            'factura_alegra_id' => 'alegra_id',
                            'estado_factura_dian' => 'estado_dian'
                        ];
                        if (isset($mapeos[$columnaBackup]) && in_array($mapeos[$columnaBackup], $columnasActuales)) {
                            $columnaActual = $mapeos[$columnaBackup];
                        }
                    }
                    
                    $estado = $columnaActual ? '✅' : '❌';
                    $mapeoFinal[$i] = [
                        'backup' => $columnaBackup,
                        'actual' => $columnaActual,
                        'valor_ejemplo' => substr($valor, 0, 20) . (strlen($valor) > 20 ? '...' : '')
                    ];
                    
                    echo sprintf("%2d. %-20s → %-25s %s (ej: %s)\n", 
                        $i + 1, 
                        $columnaBackup, 
                        $columnaActual ?: 'NO MAPEADO', 
                        $estado,
                        substr($valor, 0, 15)
                    );
                }
                
                echo "\n🔧 COLUMNAS NUEVAS EN ESTRUCTURA ACTUAL:\n";
                foreach ($columnasActuales as $columnaActual) {
                    $encontrada = false;
                    foreach ($mapeoFinal as $mapeo) {
                        if ($mapeo['actual'] === $columnaActual) {
                            $encontrada = true;
                            break;
                        }
                    }
                    if (!$encontrada) {
                        echo "🆕 {$columnaActual} (valor por defecto: NULL)\n";
                    }
                }
                
                // Generar código PHP para el mapeo
                echo "\n💻 CÓDIGO PHP PARA EL MAPEO:\n";
                echo "```php\n";
                echo "\$ordenColumnasBackup = [\n";
                foreach ($columnasBackup as $i => $columna) {
                    echo "    {$i} => '{$columna}',\n";
                }
                echo "];\n\n";
                
                echo "\$mapeoBackupAActual = [\n";
                foreach ($mapeoFinal as $i => $mapeo) {
                    if ($mapeo['actual']) {
                        echo "    '{$mapeo['backup']}' => '{$mapeo['actual']}',\n";
                    }
                }
                echo "];\n";
                echo "```\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
