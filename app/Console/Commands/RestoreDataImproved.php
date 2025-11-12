<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RestoreDataImproved extends Command
{
    protected $signature = 'backup:restore-data-improved {file : Nombre del archivo de backup} {--force : Forzar la restauración sin confirmación}';
    protected $description = 'Restaura datos de backup con método mejorado y más robusto';

    public function handle()
    {
        try {
            $filename = $this->argument('file');
            $backupPath = storage_path('app/backups/' . $filename);
            
            if (!File::exists($backupPath)) {
                $this->error("El archivo de backup no existe: {$filename}");
                return 1;
            }
            
            $this->info("🔄 Iniciando restauración mejorada de datos...");
            $this->info("📁 Archivo: {$filename}");
            $this->info("📍 Ruta: {$backupPath}");
            
            // Aumentar límites
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '1G');
            
            // Leer contenido del backup
            $this->info("📖 Leyendo archivo de backup...");
            $sqlContent = $this->obtenerContenidoSQL($backupPath, $filename);
            $this->info("✅ Archivo procesado: " . strlen($sqlContent) . " caracteres");
            
            // Desactivar restricciones
            $this->info("🔓 Desactivando restricciones de clave foránea...");
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::statement('SET AUTOCOMMIT=0');
            DB::beginTransaction();
            
            $insercionesExitosas = 0;
            $insercionesFallidas = 0;
            
            try {
                // Método 1: Intentar ejecutar el SQL completo por bloques
                $this->info("🎯 Método 1: Ejecutando por bloques...");
                
                // Dividir el contenido en sentencias individuales
                $statements = $this->dividirEnSentencias($sqlContent);
                $this->info("📊 Sentencias encontradas: " . count($statements));
                
                foreach ($statements as $index => $statement) {
                    $statement = trim($statement);
                    
                    // Solo procesar INSERT statements
                    if (stripos($statement, 'INSERT INTO') === 0) {
                        try {
                            DB::statement($statement);
                            $insercionesExitosas++;
                            
                            if (($index + 1) % 50 == 0) {
                                $this->info("✅ Procesadas " . ($index + 1) . " sentencias...");
                            }
                        } catch (\Exception $e) {
                            $insercionesFallidas++;
                            $this->warn("⚠️ Error en sentencia " . ($index + 1) . ": " . substr($e->getMessage(), 0, 100));
                            
                            // Intentar método alternativo para esta sentencia
                            if ($this->intentarInsercionAlternativa($statement)) {
                                $insercionesExitosas++;
                                $insercionesFallidas--;
                            }
                        }
                    }
                }
                
                // Si no se insertó nada, intentar método alternativo
                if ($insercionesExitosas == 0) {
                    $this->info("🔄 Método 2: Análisis detallado de INSERT...");
                    $resultado = $this->restaurarPorAnalisisDetallado($sqlContent);
                    $insercionesExitosas += $resultado['exitosas'];
                    $insercionesFallidas += $resultado['fallidas'];
                }
                
                DB::commit();
                $this->info("✅ Transacción confirmada");
                
            } catch (\Exception $e) {
                DB::rollback();
                $this->error("❌ Error en transacción, rollback ejecutado: " . $e->getMessage());
                throw $e;
            } finally {
                // Reactivar restricciones
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
                DB::statement('SET AUTOCOMMIT=1');
            }
            
            // Mostrar resultados
            $this->info("\n🎉 RESTAURACIÓN COMPLETADA");
            $this->info("==========================");
            $this->info("✅ Inserciones exitosas: {$insercionesExitosas}");
            if ($insercionesFallidas > 0) {
                $this->warn("⚠️ Inserciones fallidas: {$insercionesFallidas}");
            }
            
            // Verificar datos restaurados
            $this->verificarDatosRestaurados();
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error crítico: ' . $e->getMessage());
            Log::error('Error en restauración mejorada', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
    
    /**
     * Obtener contenido SQL del archivo (ZIP o SQL directo)
     */
    private function obtenerContenidoSQL($backupPath, $filename)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        
        if ($extension === 'zip') {
            $this->info("📦 Procesando archivo ZIP...");
            
            $zip = new \ZipArchive();
            if ($zip->open($backupPath) === TRUE) {
                // Buscar el archivo SQL dentro del ZIP
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $fileInfo = $zip->statIndex($i);
                    $fileName = $fileInfo['name'];
                    
                    if (pathinfo($fileName, PATHINFO_EXTENSION) === 'sql') {
                        $this->info("📄 Encontrado archivo SQL: {$fileName}");
                        $sqlContent = $zip->getFromIndex($i);
                        $zip->close();
                        
                        if ($sqlContent === false) {
                            throw new \Exception("No se pudo extraer el contenido SQL del ZIP");
                        }
                        
                        return $sqlContent;
                    }
                }
                
                $zip->close();
                throw new \Exception("No se encontró archivo SQL dentro del ZIP");
            } else {
                throw new \Exception("No se pudo abrir el archivo ZIP");
            }
        } else {
            // Archivo SQL directo
            $this->info("📄 Procesando archivo SQL directo...");
            return File::get($backupPath);
        }
    }
    
    private function dividirEnSentencias($sqlContent)
    {
        // Dividir por punto y coma, pero respetando las comillas
        $statements = [];
        $currentStatement = '';
        $inQuotes = false;
        $quoteChar = '';
        
        for ($i = 0; $i < strlen($sqlContent); $i++) {
            $char = $sqlContent[$i];
            
            if (!$inQuotes && ($char == '"' || $char == "'")) {
                $inQuotes = true;
                $quoteChar = $char;
            } elseif ($inQuotes && $char == $quoteChar) {
                // Verificar si es escape
                if ($i > 0 && $sqlContent[$i-1] != '\\') {
                    $inQuotes = false;
                    $quoteChar = '';
                }
            } elseif (!$inQuotes && $char == ';') {
                $statement = trim($currentStatement);
                if (!empty($statement)) {
                    $statements[] = $statement;
                }
                $currentStatement = '';
                continue;
            }
            
            $currentStatement .= $char;
        }
        
        // Agregar la última sentencia si existe
        $statement = trim($currentStatement);
        if (!empty($statement)) {
            $statements[] = $statement;
        }
        
        return $statements;
    }
    
    private function intentarInsercionAlternativa($statement)
    {
        try {
            // Extraer tabla y datos del INSERT
            if (preg_match('/INSERT INTO `?([^`\s]+)`?\s*(?:\([^)]+\))?\s*VALUES\s*(.+)/i', $statement, $matches)) {
                $tabla = $matches[1];
                $valuesSection = $matches[2];
                
                // Verificar si la tabla existe
                if (!Schema::hasTable($tabla)) {
                    $this->warn("⚠️ Tabla {$tabla} no existe, omitiendo...");
                    return false;
                }
                
                // Intentar inserción directa con DB::table
                $this->info("🔄 Intentando inserción alternativa en {$tabla}...");
                
                // Por ahora, intentar ejecutar la sentencia original sin modificaciones
                DB::statement($statement);
                return true;
            }
        } catch (\Exception $e) {
            $this->warn("⚠️ Inserción alternativa falló: " . substr($e->getMessage(), 0, 50));
            return false;
        }
        
        return false;
    }
    
    private function restaurarPorAnalisisDetallado($sqlContent)
    {
        $this->info("🔬 Iniciando análisis detallado...");
        
        $exitosas = 0;
        $fallidas = 0;
        
        // Buscar todas las sentencias INSERT con regex más simple
        $pattern = '/INSERT INTO[^;]+;/i';
        preg_match_all($pattern, $sqlContent, $matches);
        
        $this->info("🔍 Encontradas " . count($matches[0]) . " sentencias INSERT");
        
        foreach ($matches[0] as $index => $insertStatement) {
            try {
                DB::statement($insertStatement);
                $exitosas++;
                
                if (($index + 1) % 25 == 0) {
                    $this->info("📈 Progreso: " . ($index + 1) . "/" . count($matches[0]));
                }
            } catch (\Exception $e) {
                $fallidas++;
                if ($fallidas <= 5) { // Solo mostrar los primeros 5 errores
                    $this->warn("⚠️ Error " . $fallidas . ": " . substr($e->getMessage(), 0, 80));
                }
            }
        }
        
        return ['exitosas' => $exitosas, 'fallidas' => $fallidas];
    }
    
    private function verificarDatosRestaurados()
    {
        $this->info("\n🔍 VERIFICANDO DATOS RESTAURADOS");
        $this->info("================================");
        
        $tablas = ['users', 'productos', 'clientes', 'ventas', 'empresas', 'proveedores'];
        
        foreach ($tablas as $tabla) {
            if (Schema::hasTable($tabla)) {
                $count = DB::table($tabla)->count();
                $this->info("📊 {$tabla}: {$count} registros");
            }
        }
    }
}
