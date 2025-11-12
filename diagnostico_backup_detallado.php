<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\BackupService;

try {
    echo "=== DIAGNÓSTICO DETALLADO DE BACKUP ===\n\n";
    
    $backupFile = storage_path('app/backups/2025-09-23_01-08-48_backup.zip');
    
    if (!file_exists($backupFile)) {
        echo "❌ Backup no encontrado\n";
        exit;
    }
    
    // Extraer contenido SQL
    $backupService = new BackupService();
    $reflection = new ReflectionClass($backupService);
    $method = $reflection->getMethod('extraerContenidoSQL');
    $method->setAccessible(true);
    $sqlContent = $method->invoke($backupService, $backupFile);
    
    echo "📄 Analizando contenido SQL...\n\n";
    
    $lineas = explode("\n", $sqlContent);
    $estadisticas = [
        'total_lineas' => count($lineas),
        'inserts_por_tabla' => [],
        'creates' => 0,
        'otros' => 0
    ];
    
    foreach ($lineas as $numeroLinea => $linea) {
        $linea = trim($linea);
        
        if (stripos($linea, 'CREATE TABLE') === 0) {
            $estadisticas['creates']++;
        } elseif (stripos($linea, 'INSERT INTO') === 0) {
            // Extraer nombre de tabla
            if (preg_match('/INSERT INTO\s+`?([^`\s]+)`?/i', $linea, $matches)) {
                $tabla = $matches[1];
                if (!isset($estadisticas['inserts_por_tabla'][$tabla])) {
                    $estadisticas['inserts_por_tabla'][$tabla] = 0;
                }
                $estadisticas['inserts_por_tabla'][$tabla]++;
            }
        } elseif (!empty($linea) && strpos($linea, '--') !== 0) {
            $estadisticas['otros']++;
        }
    }
    
    echo "📊 ESTADÍSTICAS DEL BACKUP:\n";
    echo "Total líneas: " . $estadisticas['total_lineas'] . "\n";
    echo "CREATE TABLE: " . $estadisticas['creates'] . "\n";
    echo "Otras sentencias: " . $estadisticas['otros'] . "\n\n";
    
    echo "📋 INSERTS POR TABLA:\n";
    arsort($estadisticas['inserts_por_tabla']);
    
    $totalInserts = 0;
    foreach ($estadisticas['inserts_por_tabla'] as $tabla => $count) {
        $totalInserts += $count;
        $existe = \Illuminate\Support\Facades\Schema::hasTable($tabla) ? '✅' : '❌';
        echo sprintf("%-25s: %3d inserts %s\n", $tabla, $count, $existe);
    }
    
    echo "\nTotal INSERTs encontrados: {$totalInserts}\n\n";
    
    // Verificar estado actual después de la restauración
    echo "📈 ESTADO ACTUAL DE LA BASE DE DATOS:\n";
    $tablasImportantes = [
        'users' => 'Usuarios',
        'empresas' => 'Empresas',
        'productos' => 'Productos',
        'clientes' => 'Clientes',
        'ventas' => 'Ventas',
        'detalle_ventas' => 'Detalles de Ventas',
        'movimientos_contables' => 'Movimientos Contables',
        'comprobantes' => 'Comprobantes',
        'plan_cuentas' => 'Plan de Cuentas'
    ];
    
    foreach ($tablasImportantes as $tabla => $nombre) {
        if (\Illuminate\Support\Facades\Schema::hasTable($tabla)) {
            $count = \Illuminate\Support\Facades\DB::table($tabla)->count();
            echo sprintf("%-20s: %3d registros\n", $nombre, $count);
        } else {
            echo sprintf("%-20s: Tabla no existe\n", $nombre);
        }
    }
    
    echo "\n🔍 ANÁLISIS DE PÉRDIDAS:\n";
    $datosOriginales = [
        'productos' => 97,
        'clientes' => 45,
        'ventas' => 21,
        'detalle_ventas' => 21,
        'movimientos_contables' => 74,
        'empresas' => 1
    ];
    
    foreach ($datosOriginales as $tabla => $original) {
        if (\Illuminate\Support\Facades\Schema::hasTable($tabla)) {
            $actual = \Illuminate\Support\Facades\DB::table($tabla)->count();
            $porcentaje = $original > 0 ? round(($actual / $original) * 100, 1) : 0;
            $estado = $porcentaje >= 90 ? '✅' : ($porcentaje >= 50 ? '⚠️' : '❌');
            echo sprintf("%-20s: %3d/%3d (%s%%) %s\n", ucfirst($tabla), $actual, $original, $porcentaje, $estado);
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
