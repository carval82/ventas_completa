<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RestoreDataOnly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:restore-data {file : Nombre del archivo de backup} {--force : Forzar la restauración sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restaura solo los datos de la base de datos, preservando la estructura de tablas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $filename = $this->argument('file');
            $backupPath = storage_path('app/backups/' . $filename);
            
            if (!File::exists($backupPath)) {
                $this->error("El archivo de backup no existe: {$filename}");
                Log::error("Intento de restaurar un archivo inexistente", [
                    'filename' => $filename,
                    'path' => $backupPath
                ]);
                return 1;
            }
            
            // Confirmar la acción a menos que se use --force
            if (!$this->option('force') && !$this->confirm('Esta acción limpiará todas las tablas y restaurará solo los datos. ¿Desea continuar?')) {
                $this->info('Operación cancelada por el usuario');
                return 0;
            }
            
            // Crear un backup previo por seguridad
            $this->call('backup:database');
            
            $this->info('Iniciando restauración de datos...');
            Log::info('Iniciando restauración de datos', ['filename' => $filename]);
            
            // Aumentar límites de tiempo y memoria
            ini_set('max_execution_time', 600); // 10 minutos
            ini_set('memory_limit', '512M');
            
            // Leer el contenido del archivo SQL
            $sqlContent = File::get($backupPath);
            
            // Procesar el contenido SQL para extraer solo las sentencias INSERT
            $this->info('Analizando archivo de backup...');
            
            // Desactivar restricciones de clave foránea
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            // Contador de inserciones
            $insercionesExitosas = 0;
            $insercionesFallidas = 0;
            $tablasLimpiadas = [];
            
            try {
                // Extraer todas las sentencias INSERT
                $pattern = '/INSERT INTO `([^`]+)`/';
                preg_match_all($pattern, $sqlContent, $matches);
                
                // Obtener tablas únicas
                $tablas = array_unique($matches[1]);
                
                $this->info('Tablas encontradas en el backup: ' . count($tablas));
                
                // Limpiar tablas antes de insertar
                foreach ($tablas as $tabla) {
                    // Verificar si la tabla existe en la base de datos actual
                    if (Schema::hasTable($tabla)) {
                        $this->info("Limpiando tabla: {$tabla}");
                        DB::table($tabla)->delete();
                        $tablasLimpiadas[] = $tabla;
                        
                        // Reiniciar el auto_increment
                        DB::statement("ALTER TABLE `{$tabla}` AUTO_INCREMENT = 1");
                    } else {
                        $this->warn("La tabla {$tabla} no existe en la base de datos actual y será omitida");
                    }
                }
                
                // Procesar productos en lotes
                $this->info('Procesando sentencias INSERT...');
                
                // Extraer todas las sentencias INSERT completas - patrón mejorado
                $pattern = '/INSERT INTO `([^`]+)`\s*(\([^)]+\))?\s*VALUES\s*(.*?);/s';
                preg_match_all($pattern, $sqlContent, $matches, PREG_SET_ORDER);
                
                $this->info('Sentencias INSERT encontradas: ' . count($matches));
                
                // Si no se encontraron sentencias INSERT, intentar con otro patrón más simple
                if (count($matches) == 0) {
                    $this->info('Intentando con patrón alternativo...');
                    $pattern = '/INSERT INTO.*?;/s';
                    preg_match_all($pattern, $sqlContent, $rawMatches);
                    
                    if (!empty($rawMatches[0])) {
                        $this->info('Encontradas ' . count($rawMatches[0]) . ' sentencias INSERT en bruto');
                        
                        // Ejecutar directamente las sentencias encontradas
                        foreach ($rawMatches[0] as $index => $rawInsert) {
                            try {
                                DB::statement($rawInsert);
                                $insercionesExitosas++;
                                
                                // Mostrar progreso
                                if (($index + 1) % 10 == 0 || $index == count($rawMatches[0]) - 1) {
                                    $this->info("Ejecutada sentencia " . ($index + 1) . "/" . count($rawMatches[0]));
                                }
                            } catch (\Exception $e) {
                                $insercionesFallidas++;
                                $this->error("Error al ejecutar sentencia: " . $e->getMessage());
                                Log::error("Error al ejecutar sentencia SQL", [
                                    'error' => $e->getMessage(),
                                    'query' => substr($rawInsert, 0, 200) . '...'
                                ]);
                            }
                        }
                    } else {
                        $this->warn('No se encontraron sentencias INSERT en el archivo de backup');
                    }
                } else {
                    // Procesar las sentencias INSERT encontradas
                    foreach ($matches as $match) {
                        $tabla = $match[1];
                        $columnsPart = isset($match[2]) ? $match[2] : '';
                        $valuesStatement = $match[3];
                        
                        // Verificar si la tabla existe
                        if (!Schema::hasTable($tabla)) {
                            $this->warn("Omitiendo inserciones para la tabla inexistente: {$tabla}");
                            continue;
                        }
                        
                        $this->info("Procesando inserciones para la tabla: {$tabla}");
                        
                        // Extraer los conjuntos de valores
                        $pattern = '/\(([^)]+)\)/';
                        preg_match_all($pattern, $valuesStatement, $valueMatches);
                        
                        $this->info("  - Valores encontrados: " . count($valueMatches[0]));
                        
                        // Obtener las columnas de la tabla
                        $columnasDB = Schema::getColumnListing($tabla);
                        
                        // Extraer las columnas del INSERT
                        $columnasBackup = [];
                        if (!empty($columnsPart)) {
                            // Si las columnas están especificadas en el INSERT
                            preg_match('/\(([^)]+)\)/', $columnsPart, $columnMatches);
                            if (isset($columnMatches[1])) {
                                $columnasBackup = array_map('trim', explode(',', str_replace('`', '', $columnMatches[1])));
                            }
                        } else {
                            // Si no hay columnas especificadas, usar todas las columnas de la tabla
                            $columnasBackup = $columnasDB;
                        }
                        
                        $this->info("  - Columnas en el backup: " . implode(", ", $columnasBackup));
                        $this->info("  - Columnas en la BD: " . implode(", ", $columnasDB));
                        
                        // Verificar compatibilidad de columnas
                        $columnasCompatibles = array_intersect($columnasBackup, $columnasDB);
                        
                        $this->info("  - Columnas compatibles: " . count($columnasCompatibles));
                        
                        if (count($columnasCompatibles) > 0) {
                            // Procesar en lotes de 50
                            $lotes = array_chunk($valueMatches[0], 50);
                            
                            $this->info("  - Procesando " . count($lotes) . " lotes");
                            
                            foreach ($lotes as $index => $lote) {
                                try {
                                    // Construir sentencia INSERT para este lote
                                    $insertQuery = "INSERT INTO `{$tabla}` (`" . implode("`, `", $columnasCompatibles) . "`) VALUES ";
                                    
                                    $valuesArray = [];
                                    foreach ($lote as $values) {
                                        // Extraer los valores individuales respetando las comillas
                                        $valoresIndividuales = $this->parseValues($values);
                                        
                                        // Seleccionar solo los valores para las columnas compatibles
                                        $valoresCompatibles = [];
                                        foreach ($columnasCompatibles as $columna) {
                                            $posicionEnBackup = array_search($columna, $columnasBackup);
                                            if ($posicionEnBackup !== false && isset($valoresIndividuales[$posicionEnBackup])) {
                                                $valoresCompatibles[] = $this->formatearValorSQL($valoresIndividuales[$posicionEnBackup]);
                                            } else {
                                                $valoresCompatibles[] = 'NULL';
                                            }
                                        }
                                        
                                        $valuesArray[] = "(" . implode(", ", $valoresCompatibles) . ")";
                                    }
                                    
                                    $insertQuery .= implode(", ", $valuesArray);
                                    
                                    // Ejecutar la inserción
                                    DB::statement($insertQuery);
                                    $insercionesExitosas += count($lote);
                                    
                                    // Mostrar progreso
                                    if (($index + 1) % 10 == 0 || $index == count($lotes) - 1) {
                                        $this->info("    - Lote " . ($index + 1) . "/" . count($lotes) . " completado");
                                    }
                                    
                                    // Liberar memoria
                                    unset($valuesArray);
                                    unset($lote);
                                    gc_collect_cycles();
                                    
                                } catch (\Exception $e) {
                                    $insercionesFallidas += count($lote);
                                    $this->error("Error al insertar en {$tabla}: " . $e->getMessage());
                                    Log::error("Error al insertar en {$tabla}", [
                                        'error' => $e->getMessage(),
                                        'trace' => $e->getTraceAsString(),
                                        'query' => $insertQuery ?? 'No disponible'
                                    ]);
                                }
                            }
                        } else {
                            $this->warn("No hay columnas compatibles para la tabla {$tabla}");
                        }
                    }
                }
                
                $this->info('Restauración de datos completada');
                $this->info("Tablas limpiadas: " . count($tablasLimpiadas));
                $this->info("Inserciones exitosas: {$insercionesExitosas}");
                
                if ($insercionesFallidas > 0) {
                    $this->warn("Inserciones fallidas: {$insercionesFallidas}");
                }
                
            } finally {
                // Reactivar restricciones de clave foránea
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
            
            Log::info('Restauración de datos completada', [
                'tablas_limpiadas' => count($tablasLimpiadas),
                'inserciones_exitosas' => $insercionesExitosas,
                'inserciones_fallidas' => $insercionesFallidas
            ]);
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error al restaurar los datos: ' . $e->getMessage());
            Log::error('Error al restaurar los datos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
    
    /**
     * Procesa los valores para asegurarse de que estén correctamente formateados para SQL
     */
    private function formatearValorSQL($valor)
    {
        // Eliminar comillas simples al inicio y final si existen
        $valor = trim($valor, "'");
        
        // Si es NULL, devolverlo como está
        if (strtoupper($valor) === 'NULL') {
            return 'NULL';
        }
        
        // Si es un número, devolverlo como está
        if (is_numeric($valor)) {
            return $valor;
        }
        
        // Si es una fecha o datetime en formato ISO, mantener las comillas simples
        if (preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', $valor)) {
            return "'" . $valor . "'";
        }
        
        // Para cualquier otro valor, escapar las comillas simples y añadir comillas
        return "'" . str_replace("'", "''", $valor) . "'";
    }

    /**
     * Parsea una cadena de valores respetando las comillas
     */
    private function parseValues($values)
    {
        // Eliminar los paréntesis externos
        $values = trim($values, '()');
        
        $valoresIndividuales = [];
        $valorActual = '';
        $comillasAbiertas = false;
        
        for ($i = 0; $i < strlen($values); $i++) {
            $caracter = $values[$i];
            
            if ($caracter == "'" && ($i == 0 || $values[$i-1] != '\\')) {
                $comillasAbiertas = !$comillasAbiertas;
                $valorActual .= $caracter;
            } elseif ($caracter == ',' && !$comillasAbiertas) {
                $valoresIndividuales[] = trim($valorActual);
                $valorActual = '';
            } else {
                $valorActual .= $caracter;
            }
        }
        
        if (!empty($valorActual)) {
            $valoresIndividuales[] = trim($valorActual);
        }
        
        return $valoresIndividuales;
    }
}
