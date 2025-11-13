<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

class BackupService
{
    /**
     * Restaura solo los datos de un backup, manteniendo la estructura actual de la base de datos
     *
     * @param string $filePath Ruta al archivo SQL de backup
     * @return array Resultado de la operación
     */
    public function restaurarSoloDatos($filePath)
    {
        try {
            Log::info('Iniciando restauración inteligente de backup', [
                'path' => $filePath
            ]);

            if (!File::exists($filePath)) {
                throw new \Exception('Archivo de backup no encontrado: ' . $filePath);
            }

            // Aumentar límites para operaciones largas
            ini_set('memory_limit', '512M');
            set_time_limit(600); // 10 minutos
            
            // Obtener el contenido SQL (puede ser ZIP o SQL directo)
            $sqlContent = $this->extraerContenidoSQL($filePath);
            
            // Obtener las tablas actuales de la base de datos
            $tablasActuales = $this->obtenerTablasActuales();
            Log::info('Tablas actuales en la base de datos', ['tablas' => $tablasActuales]);

            // Desactivar restricciones de clave foránea para toda la operación
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            try {
                // Limpiar tablas principales antes de la restauración
                $this->limpiarTablasPrincipales();
                
                // Intentar restaurar productos directamente
                $resultadoProductos = $this->restaurarProductosDirectamente($sqlContent);
                
                // Procesar TODOS los datos usando el método robusto
                $resultado = $this->restaurarDatosRobusto($sqlContent, $tablasActuales);
                
                // Combinar resultados
                $resultado['inserciones_exitosas'] += $resultadoProductos['inserciones_exitosas'];
                $resultado['inserciones_fallidas'] += $resultadoProductos['inserciones_fallidas'];
                $resultado['errores'] = array_merge($resultado['errores'], $resultadoProductos['errores']);
    
                // Actualizar secuencias de autoincremento si es necesario
                $this->actualizarSecuencias();
                
                // Verificar que la restauración funcionó
                $verificacion = $this->verificarRestauracion();
                $resultado['verificacion'] = $verificacion;
                
            } finally {
                // Asegurarse de reactivar las restricciones de clave foránea
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            // Liberar memoria
            unset($sqlContent);
            gc_collect_cycles();
            
            Log::info('Restauración completada exitosamente', [
                'verificacion' => $verificacion ?? []
            ]);
            
            return [
                'success' => true,
                'message' => 'Restauración inteligente completada con éxito',
                'detalles' => $resultado
            ];

        } catch (\Exception $e) {
            Log::error('Error en restauración inteligente', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error en la restauración: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Restaura directamente los productos usando expresiones regulares
     * 
     * @param string $sqlContent Contenido del archivo SQL
     * @return array Resultado de la operación
     */
    private function restaurarProductosDirectamente($sqlContent)
    {
        $resultado = [
            'inserciones_exitosas' => 0,
            'inserciones_fallidas' => 0,
            'errores' => []
        ];
        
        try {
            // Verificar si la tabla productos existe
            if (!Schema::hasTable('productos')) {
                $resultado['errores'][] = "La tabla 'productos' no existe en la base de datos actual";
                return $resultado;
            }
            
            // Obtener la estructura de la tabla productos
            $columnasProductos = Schema::getColumnListing('productos');
            
            // Limpiar la tabla de productos antes de insertar
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('productos')->delete();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            Log::info('Tabla productos limpiada para restauración directa');
            
            // Extraer las inserciones de productos
            $inserciones = [];
            if (preg_match_all('/INSERT INTO `productos` \(([^)]+)\) VALUES\s+(.+?);/is', $sqlContent, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    try {
                        $columnasStr = $match[1];
                        $valoresStr = $match[2];
                        
                        // Extraer columnas
                        $columnasBackup = array_map(function($col) {
                            return trim($col, '` ');
                        }, explode(',', $columnasStr));
                        
                        // Filtrar solo las columnas que existen en la tabla actual
                        $columnasComunes = array_intersect($columnasBackup, $columnasProductos);
                        
                        if (empty($columnasComunes)) {
                            $resultado['errores'][] = "No hay columnas comunes para la tabla productos";
                            continue;
                        }
                        
                        // Extraer conjuntos de valores
                        preg_match_all('/\((.*?)\)(?:,|$)/s', $valoresStr, $valoresMatches);
                        
                        if (isset($valoresMatches[1]) && !empty($valoresMatches[1])) {
                            foreach ($valoresMatches[1] as $conjuntoValores) {
                                try {
                                    // Dividir los valores respetando las comillas
                                    $valores = $this->dividirValoresRespetandoComillas($conjuntoValores);
                                    
                                    if (count($valores) !== count($columnasBackup)) {
                                        $resultado['errores'][] = "Número de valores no coincide con número de columnas en tabla productos: " . 
                                            count($valores) . " vs " . count($columnasBackup);
                                        continue;
                                    }
                                    
                                    // Crear array asociativo para la inserción
                                    $datosInsercion = [];
                                    foreach ($columnasComunes as $columna) {
                                        $posicion = array_search($columna, $columnasBackup);
                                        if ($posicion !== false && isset($valores[$posicion])) {
                                            $datosInsercion[$columna] = $this->limpiarValorSQL($valores[$posicion]);
                                        }
                                    }
                                    
                                    if (!empty($datosInsercion)) {
                                        $inserciones[] = $datosInsercion;
                                    }
                                } catch (\Exception $e) {
                                    $resultado['inserciones_fallidas']++;
                                    $resultado['errores'][] = "Error procesando conjunto de valores: " . $e->getMessage();
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        $resultado['errores'][] = "Error procesando inserción de productos: " . $e->getMessage();
                    }
                }
            } else {
                $resultado['errores'][] = "No se encontraron inserciones para la tabla productos";
            }
            
            // Insertar todos los productos en lotes pequeños
            if (!empty($inserciones)) {
                $this->insertarProductosEnLotes($inserciones, $resultado);
            }
            
            Log::info('Restauración directa de productos completada', [
                'exitosas' => $resultado['inserciones_exitosas'],
                'fallidas' => $resultado['inserciones_fallidas']
            ]);
            
        } catch (\Exception $e) {
            $resultado['errores'][] = "Error general restaurando productos: " . $e->getMessage();
            Log::error('Error restaurando productos directamente', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return $resultado;
    }
    
    /**
     * Inserta productos en lotes pequeños para evitar problemas de memoria y sesión
     * 
     * @param array $productos Lista de productos a insertar
     * @param array &$resultado Resultado acumulado de la operación
     */
    private function insertarProductosEnLotes($productos, &$resultado)
    {
        $totalProductos = count($productos);
        $loteSize = 50; // Tamaño de lote más pequeño para evitar problemas
        $totalLotes = ceil($totalProductos / $loteSize);
        
        Log::info("Iniciando inserción de {$totalProductos} productos en {$totalLotes} lotes");
        
        for ($i = 0; $i < $totalProductos; $i += $loteSize) {
            try {
                $lote = array_slice($productos, $i, $loteSize);
                $numLote = ceil(($i + 1) / $loteSize);
                
                Log::info("Procesando lote {$numLote} de {$totalLotes} ({$i}-" . min($i + $loteSize, $totalProductos) . ")");
                
                // Insertar cada producto individualmente para mejor control de errores
                foreach ($lote as $producto) {
                    try {
                        DB::table('productos')->insert($producto);
                        $resultado['inserciones_exitosas']++;
                    } catch (\Exception $e) {
                        $resultado['inserciones_fallidas']++;
                        
                        // Registrar solo los primeros 20 errores para evitar logs demasiado grandes
                        if (count($resultado['errores']) < 20) {
                            $errorMsg = "Error al insertar producto: " . $e->getMessage();
                            if (isset($producto['codigo'])) {
                                $errorMsg .= " (Código: " . $producto['codigo'] . ")";
                            }
                            $resultado['errores'][] = $errorMsg;
                        }
                    }
                }
                
                // Liberar memoria explícitamente
                unset($lote);
                gc_collect_cycles();
                
            } catch (\Exception $e) {
                $resultado['inserciones_fallidas'] += count(array_slice($productos, $i, $loteSize));
                $resultado['errores'][] = "Error procesando lote de productos: " . $e->getMessage();
                Log::error("Error procesando lote de productos", [
                    'lote' => $numLote,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Procesa el backup excepto productos, adaptando a la estructura actual
     *
     * @param string $sqlContent Contenido del archivo SQL
     * @param array $tablasActuales Tablas actuales con sus columnas
     * @return array Resultado del procesamiento
     */
    private function procesarBackupExceptoProductos($sqlContent, $tablasActuales)
    {
        $resultado = [
            'tablas_procesadas' => 0,
            'inserciones_exitosas' => 0,
            'inserciones_fallidas' => 0,
            'tablas_no_encontradas' => [],
            'errores' => []
        ];

        // Dividir el contenido en líneas
        $lineas = explode("\n", $sqlContent);
        
        // Desactivar restricciones de clave foránea temporalmente
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Primero, extraer todas las sentencias INSERT
        $inserciones = [];
        $tablasEncontradas = [];
        
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            
            // Ignorar líneas vacías o comentarios
            if (empty($linea) || strpos($linea, '--') === 0) {
                continue;
            }
            
            // Detectar tabla en INSERT INTO
            if (preg_match('/^INSERT INTO `([^`]+)`/', $linea, $matches)) {
                $tabla = $matches[1];
                
                // Ignorar la tabla productos
                if ($tabla === 'productos') {
                    continue;
                }
                
                // Verificar si la tabla existe en la estructura actual
                if (!isset($tablasActuales[$tabla])) {
                    if (!in_array($tabla, $resultado['tablas_no_encontradas'])) {
                        $resultado['tablas_no_encontradas'][] = $tabla;
                    }
                    continue;
                }
                
                // Registrar la tabla encontrada para limpiarla después
                if (!in_array($tabla, $tablasEncontradas)) {
                    $tablasEncontradas[] = $tabla;
                }
                
                // Acumular la inserción
                if (!isset($inserciones[$tabla])) {
                    $inserciones[$tabla] = [];
                }
                $inserciones[$tabla][] = $linea;
            }
        }
        
        // Limpiar las tablas encontradas antes de insertar
        foreach ($tablasEncontradas as $tabla) {
            try {
                if (Schema::hasTable($tabla)) {
                    $count = DB::table($tabla)->count();
                    DB::table($tabla)->delete();
                    Log::info("Tabla {$tabla} limpiada para restauración", ['registros_eliminados' => $count]);
                }
            } catch (\Exception $e) {
                Log::warning("Error al limpiar tabla {$tabla}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Ahora, procesar las inserciones tabla por tabla
        foreach ($inserciones as $tabla => $sentencias) {
            try {
                Log::info("Procesando tabla {$tabla} con " . count($sentencias) . " sentencias");
                
                // Obtener la estructura de la tabla
                $columnasTabla = $tablasActuales[$tabla];
                
                // Procesar cada sentencia INSERT
                foreach ($sentencias as $sentencia) {
                    try {
                        // Extraer columnas si están especificadas
                        $columnasBackup = [];
                        if (preg_match('/^INSERT INTO `[^`]+` \(([^)]+)\)/', $sentencia, $matches)) {
                            $columnasStr = $matches[1];
                            $columnasBackup = array_map(function($col) {
                                return trim($col, '` ');
                            }, explode(',', $columnasStr));
                        } else {
                            // Si no hay columnas especificadas, usar todas las columnas de la tabla
                            $columnasBackup = $columnasTabla;
                        }
                        
                        // Filtrar solo las columnas que existen en la tabla actual
                        $columnasComunes = array_intersect($columnasBackup, $columnasTabla);
                        
                        if (empty($columnasComunes)) {
                            $resultado['errores'][] = "No hay columnas comunes para la tabla {$tabla}";
                            continue;
                        }
                        
                        // Extraer la parte VALUES del INSERT
                        if (preg_match('/VALUES\s+(.+?);$/is', $sentencia, $matches)) {
                            $valoresStr = $matches[1];
                            
                            // Método alternativo: usar expresiones regulares para extraer valores entre paréntesis
                            preg_match_all('/\((.*?)\)(?:,|$)/s', $valoresStr, $matches);
                            
                            if (isset($matches[1]) && !empty($matches[1])) {
                                $batchSize = 100; // Procesar en lotes para evitar problemas de memoria
                                $batchInserts = [];
                                $batchCount = 0;
                                
                                foreach ($matches[1] as $conjuntoValores) {
                                    try {
                                        // Dividir los valores respetando las comillas
                                        $valores = $this->dividirValoresRespetandoComillas($conjuntoValores);
                                        
                                        if (count($valores) !== count($columnasBackup)) {
                                            $resultado['errores'][] = "Número de valores no coincide con número de columnas en tabla {$tabla}: " . 
                                                count($valores) . " vs " . count($columnasBackup);
                                            continue;
                                        }
                                        
                                        // Crear array asociativo para la inserción
                                        $datosInsercion = [];
                                        foreach ($columnasComunes as $columna) {
                                            $posicion = array_search($columna, $columnasBackup);
                                            if ($posicion !== false && isset($valores[$posicion])) {
                                                $datosInsercion[$columna] = $this->limpiarValorSQL($valores[$posicion]);
                                            }
                                        }
                                        
                                        if (!empty($datosInsercion)) {
                                            $batchInserts[] = $datosInsercion;
                                            $batchCount++;
                                            
                                            // Cuando alcanzamos el tamaño del lote, insertamos
                                            if ($batchCount >= $batchSize) {
                                                $this->insertarLoteRegistros($tabla, $batchInserts, $resultado);
                                                $batchInserts = [];
                                                $batchCount = 0;
                                            }
                                        }
                                    } catch (\Exception $e) {
                                        $resultado['inserciones_fallidas']++;
                                        if (count($resultado['errores']) < 50) {
                                            $resultado['errores'][] = "Error procesando valores para {$tabla}: " . $e->getMessage();
                                        }
                                    }
                                }
                                
                                // Insertar cualquier lote restante
                                if (!empty($batchInserts)) {
                                    $this->insertarLoteRegistros($tabla, $batchInserts, $resultado);
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        $resultado['errores'][] = "Error procesando sentencia en tabla {$tabla}: " . $e->getMessage();
                        Log::error("Error procesando sentencia SQL", [
                            'tabla' => $tabla,
                            'error' => $e->getMessage(),
                            'sentencia' => substr($sentencia, 0, 200) . '...' // Solo mostrar parte de la sentencia para evitar logs enormes
                        ]);
                    }
                }
                
                $resultado['tablas_procesadas']++;
                
            } catch (\Exception $e) {
                $resultado['errores'][] = "Error procesando tabla {$tabla}: " . $e->getMessage();
                Log::error("Error procesando tabla", [
                    'tabla' => $tabla,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        // Reactivar restricciones de clave foránea
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        return $resultado;
    }
    
    /**
     * Inserta un lote de registros en la tabla especificada
     * 
     * @param string $tabla Nombre de la tabla
     * @param array $lote Lote de registros a insertar
     * @param array &$resultado Resultado acumulado de la operación
     */
    private function insertarLoteRegistros($tabla, $lote, &$resultado)
    {
        if (empty($lote)) {
            return;
        }
        
        try {
            // Intentar inserción por lotes
            DB::beginTransaction();
            
            foreach ($lote as $registro) {
                try {
                    // Intentar insertar cada registro individualmente
                    DB::table($tabla)->insert($registro);
                    $resultado['inserciones_exitosas']++;
                } catch (\Exception $e) {
                    $resultado['inserciones_fallidas']++;
                    
                    // Registrar solo los primeros 50 errores para evitar logs demasiado grandes
                    if (count($resultado['errores']) < 50) {
                        $errorMsg = "Error al insertar en {$tabla}: " . $e->getMessage();
                        if (isset($registro['id'])) {
                            $errorMsg .= " (ID: " . $registro['id'] . ")";
                        }
                        $resultado['errores'][] = $errorMsg;
                    }
                    
                    // Registrar detalles adicionales en el log
                    Log::warning("Error al insertar en {$tabla}", [
                        'error' => $e->getMessage(),
                        'registro' => $registro
                    ]);
                }
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $resultado['inserciones_fallidas'] += count($lote);
            $resultado['errores'][] = "Error al insertar lote en {$tabla}: " . $e->getMessage();
            
            Log::error("Error al insertar lote en {$tabla}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Procesa el archivo de backup para extraer e insertar datos
     *
     * @param string $sqlContent Contenido del archivo SQL
     * @param array $tablasActuales Tablas actuales con sus columnas
     * @return array Resultado del procesamiento
     */
    private function procesarBackup($sqlContent, $tablasActuales)
    {
        $resultado = [
            'tablas_procesadas' => 0,
            'inserciones_exitosas' => 0,
            'inserciones_fallidas' => 0,
            'tablas_no_encontradas' => [],
            'errores' => []
        ];

        // Dividir el contenido en líneas
        $lineas = explode("\n", $sqlContent);
        
        // Desactivar restricciones de clave foránea temporalmente
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Procesar cada tabla por separado para mejor control
        $tablaActual = null;
        $tablasLimpiadas = [];
        $columnasTabla = [];
        
        // Primero, extraer todas las sentencias INSERT
        $inserciones = [];
        
        foreach ($lineas as $linea) {
            // Detectar cambio de tabla en CREATE TABLE
            if (preg_match('/^CREATE TABLE `([^`]+)`/', $linea, $matches)) {
                $tablaActual = $matches[1];
                
                // Verificar si la tabla existe en la estructura actual
                if (!isset($tablasActuales[$tablaActual])) {
                    if (!in_array($tablaActual, $resultado['tablas_no_encontradas'])) {
                        $resultado['tablas_no_encontradas'][] = $tablaActual;
                    }
                    continue;
                }
                
                // Añadir a la lista de tablas limpiadas
                if (!in_array($tablaActual, $tablasLimpiadas)) {
                    $tablasLimpiadas[] = $tablaActual;
                }
            }
            
            // Detectar tabla en INSERT INTO
            if (preg_match('/^INSERT INTO `([^`]+)`/', $linea, $matches)) {
                $tabla = $matches[1];
                
                // Verificar si la tabla existe en la estructura actual
                if (!isset($tablasActuales[$tabla])) {
                    if (!in_array($tabla, $resultado['tablas_no_encontradas'])) {
                        $resultado['tablas_no_encontradas'][] = $tabla;
                    }
                    continue;
                }
                
                // Acumular la inserción
                if (!isset($inserciones[$tabla])) {
                    $inserciones[$tabla] = [];
                }
                $inserciones[$tabla][] = $linea;
            }
        }
        
        // Limpiar las tablas antes de insertar
        foreach ($tablasLimpiadas as $tabla) {
            try {
                if (Schema::hasTable($tabla)) {
                    $count = DB::table($tabla)->count();
                    DB::table($tabla)->delete();
                    Log::info("Tabla {$tabla} limpiada", ['registros_eliminados' => $count]);
                }
            } catch (\Exception $e) {
                Log::warning("Error al limpiar tabla {$tabla}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Ahora, procesar las inserciones tabla por tabla
        foreach ($inserciones as $tabla => $sentencias) {
            try {
                // Obtener la estructura de la tabla
                $columnasTabla = $tablasActuales[$tabla];
                
                // Procesar cada sentencia INSERT
                foreach ($sentencias as $sentencia) {
                    try {
                        // Extraer columnas si están especificadas
                        $columnasBackup = [];
                        if (preg_match('/^INSERT INTO `[^`]+` \(([^)]+)\)/', $sentencia, $matches)) {
                            $columnasStr = $matches[1];
                            $columnasBackup = array_map(function($col) {
                                return trim($col, '` ');
                            }, explode(',', $columnasStr));
                        } else {
                            // Si no hay columnas especificadas, usar todas las columnas de la tabla
                            $columnasBackup = $columnasTabla;
                        }
                        
                        // Filtrar solo las columnas que existen en la tabla actual
                        $columnasComunes = array_intersect($columnasBackup, $columnasTabla);
                        
                        if (empty($columnasComunes)) {
                            $resultado['errores'][] = "No hay columnas comunes para la tabla {$tabla}";
                            continue;
                        }
                        
                        // Extraer la parte VALUES del INSERT
                        if (preg_match('/VALUES\s+(.+?);$/is', $sentencia, $matches)) {
                            $valoresStr = $matches[1];
                            
                            // Método alternativo: usar expresiones regulares para extraer valores entre paréntesis
                            preg_match_all('/\((.*?)\)(?:,|$)/s', $valoresStr, $matches);
                            
                            if (isset($matches[1]) && !empty($matches[1])) {
                                foreach ($matches[1] as $conjuntoValores) {
                                    // Dividir los valores respetando las comillas
                                    $valores = $this->dividirValoresRespetandoComillas($conjuntoValores);
                                    
                                    if (count($valores) !== count($columnasBackup)) {
                                        $resultado['errores'][] = "Número de valores no coincide con número de columnas en tabla {$tabla}";
                                        continue;
                                    }
                                    
                                    // Crear array asociativo para la inserción
                                    $datosInsercion = [];
                                    foreach ($columnasComunes as $index => $columna) {
                                        $posicion = array_search($columna, $columnasBackup);
                                        if ($posicion !== false && isset($valores[$posicion])) {
                                            $datosInsercion[$columna] = $this->limpiarValorSQL($valores[$posicion]);
                                        }
                                    }
                                    
                                    if (!empty($datosInsercion)) {
                                        try {
                                            DB::table($tabla)->insert($datosInsercion);
                                            $resultado['inserciones_exitosas']++;
                                        } catch (\Exception $e) {
                                            $resultado['inserciones_fallidas']++;
                                            $resultado['errores'][] = "Error al insertar en {$tabla}: " . $e->getMessage();
                                        }
                                    }
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        $resultado['errores'][] = "Error procesando sentencia en tabla {$tabla}: " . $e->getMessage();
                    }
                }
                
                $resultado['tablas_procesadas']++;
                
            } catch (\Exception $e) {
                $resultado['errores'][] = "Error procesando tabla {$tabla}: " . $e->getMessage();
            }
        }
        
        // Reactivar restricciones de clave foránea
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        return $resultado;
    }
    
    /**
     * Método mejorado para restaurar datos adaptándolos a la estructura actual
     */
    private function restaurarDatosAdaptados($sqlContent, $tablasActuales)
    {
        $resultado = [
            'tablas_procesadas' => 0,
            'inserciones_exitosas' => 0,
            'inserciones_fallidas' => 0,
            'errores' => []
        ];
        
        // Extraer datos de las sentencias INSERT
        $datosExtraidos = $this->extraerDatosDeInserts($sqlContent);
        
        foreach ($datosExtraidos as $tabla => $registros) {
            // Saltar productos (se maneja por separado)
            if ($tabla === 'productos') {
                continue;
            }
            
            // Verificar si la tabla existe
            if (!Schema::hasTable($tabla)) {
                Log::warning("Tabla {$tabla} no existe en la estructura actual, saltando...");
                continue;
            }
            
            Log::info("Procesando tabla {$tabla}", ['registros' => count($registros)]);
            
            try {
                // Limpiar tabla antes de insertar
                DB::table($tabla)->truncate();
                
                // Obtener columnas actuales de la tabla
                $columnasActuales = Schema::getColumnListing($tabla);
                
                // Adaptar y insertar registros
                foreach ($registros as $registro) {
                    try {
                        $registroAdaptado = $this->adaptarRegistroAEstructuraActual($registro, $columnasActuales);
                        
                        if (!empty($registroAdaptado)) {
                            DB::table($tabla)->insert($registroAdaptado);
                            $resultado['inserciones_exitosas']++;
                        }
                    } catch (\Exception $e) {
                        $resultado['inserciones_fallidas']++;
                        $resultado['errores'][] = "Error insertando en {$tabla}: " . $e->getMessage();
                        Log::warning("Error insertando registro en {$tabla}", [
                            'error' => $e->getMessage(),
                            'registro' => $registro
                        ]);
                    }
                }
                
                $resultado['tablas_procesadas']++;
                Log::info("Tabla {$tabla} procesada exitosamente");
                
            } catch (\Exception $e) {
                $resultado['errores'][] = "Error procesando tabla {$tabla}: " . $e->getMessage();
                Log::error("Error procesando tabla {$tabla}", ['error' => $e->getMessage()]);
            }
        }
        
        return $resultado;
    }
    
    /**
     * Método simple para restaurar datos ejecutando directamente las sentencias SQL
     */
    private function restaurarDatosSimple($sqlContent, $tablasActuales)
    {
        $resultado = [
            'tablas_procesadas' => 0,
            'inserciones_exitosas' => 0,
            'inserciones_fallidas' => 0,
            'errores' => []
        ];
        
        $lineas = explode("\n", $sqlContent);
        $totalLineas = count($lineas);
        
        Log::info('Procesando backup con método simple', [
            'total_lineas' => $totalLineas
        ]);
        
        $tablasLimpiadas = [];
        
        // Reconstruir sentencias INSERT completas (pueden estar en múltiples líneas)
        $sentenciasCompletas = $this->reconstruirSentenciasINSERT($lineas);
        
        Log::info('Sentencias INSERT reconstruidas', [
            'total_sentencias' => count($sentenciasCompletas)
        ]);
        
        foreach ($sentenciasCompletas as $sentencia) {
            // Extraer nombre de tabla
            if (preg_match('/INSERT INTO\s+`?([^`\s]+)`?/i', $sentencia, $matches)) {
                $tabla = $matches[1];
                
                // Saltar productos (se maneja por separado)
                if ($tabla === 'productos') {
                    continue;
                }
                
                // Verificar si la tabla existe
                if (!Schema::hasTable($tabla)) {
                    Log::warning("Tabla {$tabla} no existe, saltando...");
                    continue;
                }
                
                // Limpiar tabla solo una vez
                if (!in_array($tabla, $tablasLimpiadas)) {
                    try {
                        DB::table($tabla)->truncate();
                        $tablasLimpiadas[] = $tabla;
                        Log::info("Tabla {$tabla} limpiada");
                    } catch (\Exception $e) {
                        Log::warning("No se pudo limpiar tabla {$tabla}: " . $e->getMessage());
                    }
                }
                
                // Ejecutar la sentencia INSERT completa
                try {
                    DB::statement($sentencia);
                    $resultado['inserciones_exitosas']++;
                    
                    if ($resultado['inserciones_exitosas'] % 10 === 0) {
                        Log::info("Progreso: {$resultado['inserciones_exitosas']} sentencias completadas");
                    }
                    
                } catch (\Exception $e) {
                    $resultado['inserciones_fallidas']++;
                    $error = "Error en tabla {$tabla}: " . $e->getMessage();
                    $resultado['errores'][] = $error;
                    
                    Log::warning("Error ejecutando INSERT", [
                        'tabla' => $tabla,
                        'error' => $e->getMessage(),
                        'sql_preview' => substr($sentencia, 0, 200) . '...'
                    ]);
                }
            }
        }
        
        $resultado['tablas_procesadas'] = count($tablasLimpiadas);
        
        Log::info('Restauración simple completada', [
            'tablas_procesadas' => $resultado['tablas_procesadas'],
            'inserciones_exitosas' => $resultado['inserciones_exitosas'],
            'inserciones_fallidas' => $resultado['inserciones_fallidas'],
            'tablas_limpiadas' => $tablasLimpiadas
        ]);
        
        return $resultado;
    }
    
    /**
     * Método robusto para restaurar TODOS los datos del backup
     */
    private function restaurarDatosRobusto($sqlContent, $tablasActuales)
    {
        $resultado = [
            'tablas_procesadas' => 0,
            'inserciones_exitosas' => 0,
            'inserciones_fallidas' => 0,
            'errores' => []
        ];
        
        Log::info('Iniciando restauración robusta de backup');
        
        try {
            // Paso 1: Limpiar TODAS las tablas de datos (excepto sistema)
            $this->limpiarTodasLasTablasDeDatos();
            
            // Paso 2: Desactivar TODAS las restricciones
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::statement('SET UNIQUE_CHECKS=0');
            DB::statement('SET AUTOCOMMIT=0');
            
            // Paso 3: Procesar el SQL por bloques para evitar problemas de memoria
            $bloquesSql = $this->dividirSQLEnBloques($sqlContent);
            
            Log::info('SQL dividido en bloques', ['total_bloques' => count($bloquesSql)]);
            
            foreach ($bloquesSql as $indice => $bloque) {
                try {
                    Log::info("Procesando bloque {$indice}");
                    
                    // Detectar si es tabla de ventas y procesarla especialmente
                    if (stripos($bloque, 'INSERT INTO `ventas`') !== false || stripos($bloque, 'INSERT INTO ventas') !== false) {
                        Log::info("Procesando tabla ventas con método especial");
                        $this->procesarVentasEspecial($bloque, $resultado);
                        continue;
                    }
                    
                    // Ejecutar el bloque completo
                    DB::unprepared($bloque);
                    $resultado['inserciones_exitosas']++;
                    
                } catch (\Exception $e) {
                    $errorMsg = $e->getMessage();
                    
                    // Si es error de duplicado, intentar con IGNORE
                    if (strpos($errorMsg, 'Duplicate entry') !== false || strpos($errorMsg, '1062') !== false) {
                        try {
                            Log::info("Reintentando bloque {$indice} con INSERT IGNORE");
                            $bloqueIgnore = str_replace('INSERT INTO', 'INSERT IGNORE INTO', $bloque);
                            DB::unprepared($bloqueIgnore);
                            $resultado['inserciones_exitosas']++;
                            continue;
                        } catch (\Exception $e2) {
                            Log::warning("INSERT IGNORE también falló para bloque {$indice}");
                        }
                    }
                    
                    $resultado['inserciones_fallidas']++;
                    $resultado['errores'][] = "Error en bloque {$indice}: " . $errorMsg;
                    
                    Log::warning("Error en bloque {$indice}", [
                        'error' => $errorMsg,
                        'bloque_preview' => substr($bloque, 0, 200) . '...'
                    ]);
                    
                    // Intentar procesar línea por línea si el bloque falla
                    $this->procesarBloqueLineaPorLinea($bloque, $resultado);
                }
            }
            
            // Paso 4: Reactivar restricciones
            DB::statement('COMMIT');
            DB::statement('SET AUTOCOMMIT=1');
            DB::statement('SET UNIQUE_CHECKS=1');
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            // Paso 5: Contar registros restaurados
            $resultado['tablas_procesadas'] = $this->contarTablasConDatos();
            
            // Contar registros por tabla para comparar con el backup original
            $conteoFinal = [];
            $totalFinal = 0;
            foreach (['productos', 'clientes', 'ventas', 'detalle_ventas', 'movimientos_contables', 'empresas', 'comprobantes', 'proveedores', 'categorias', 'marcas'] as $tabla) {
                if (Schema::hasTable($tabla)) {
                    $count = DB::table($tabla)->count();
                    $conteoFinal[$tabla] = $count;
                    $totalFinal += $count;
                }
            }
            
            $resultado['conteo_final'] = $conteoFinal;
            $resultado['total_registros_final'] = $totalFinal;
            
            Log::info('Restauración robusta completada', $resultado);
            Log::info('Conteo final de registros por tabla:', $conteoFinal);
            Log::info('Total de registros restaurados: ' . $totalFinal);
            
        } catch (\Exception $e) {
            // Restaurar configuración en caso de error
            DB::statement('ROLLBACK');
            DB::statement('SET AUTOCOMMIT=1');
            DB::statement('SET UNIQUE_CHECKS=1');
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            throw $e;
        }
        
        return $resultado;
    }
    
    /**
     * Limpia todas las tablas de datos (no de sistema)
     */
    private function limpiarTodasLasTablasDeDatos()
    {
        $tablasALimpiar = [
            // Orden importante: primero dependientes, luego principales
            'detalle_ventas',
            'detalle_compras', 
            'movimientos_contables',
            'movimientos_caja',
            'pagos_credito',
            'ventas',
            'compras',
            'productos',
            'clientes',
            'proveedores',
            'comprobantes',
            'cajas_diarias',
            'ubicaciones',
            'codigos_relacionados',
            'plan_cuentas',
            'configuracion_contable',
            'empresas'
        ];
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        foreach ($tablasALimpiar as $tabla) {
            if (Schema::hasTable($tabla)) {
                try {
                    DB::table($tabla)->truncate();
                    Log::info("Tabla {$tabla} limpiada");
                } catch (\Exception $e) {
                    Log::warning("No se pudo limpiar {$tabla}: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Divide el SQL en bloques manejables con orden correcto para foreign keys
     */
    private function dividirSQLEnBloques($sqlContent)
    {
        // Primero extraer todos los bloques INSERT (ahora devuelve arrays por tabla)
        $todosLosBloques = $this->extraerTodosLosBloques($sqlContent);
        
        // Luego ordenarlos según dependencias
        $bloquesOrdenados = $this->ordenarBloquesPorDependencias($todosLosBloques);
        
        // Aplanar el array: convertir arrays de bloques por tabla en un solo array
        $bloquesAplanados = [];
        $contadorBloques = 0;
        
        foreach ($bloquesOrdenados as $tabla => $bloquesTabla) {
            if (is_array($bloquesTabla)) {
                // Es un array de bloques para esta tabla
                Log::info("Tabla {$tabla} tiene " . count($bloquesTabla) . " bloques");
                foreach ($bloquesTabla as $indiceBloque => $bloque) {
                    // Contar registros aproximados en este bloque
                    $registrosAprox = substr_count($bloque, '),(') + 1;
                    Log::info("  - Bloque " . ($indiceBloque + 1) . " de {$tabla}: ~{$registrosAprox} registros");
                    $bloquesAplanados[] = $bloque;
                    $contadorBloques++;
                }
            } else {
                // Es un solo bloque (compatibilidad con código antiguo)
                Log::info("Tabla {$tabla} tiene 1 bloque (legacy)");
                $bloquesAplanados[] = $bloquesTabla;
                $contadorBloques++;
            }
        }
        
        Log::info('Bloques ordenados y aplanados', [
            'total_tablas' => count($bloquesOrdenados),
            'total_bloques' => $contadorBloques,
            'orden_tablas' => array_keys($bloquesOrdenados)
        ]);
        
        return $bloquesAplanados;
    }
    
    /**
     * Extrae todos los bloques INSERT del contenido SQL
     */
    private function extraerTodosLosBloques($sqlContent)
    {
        $bloques = [];
        $lineas = explode("\n", $sqlContent);
        $bloqueActual = '';
        $enInsert = false;
        $tablaActual = '';
        
        foreach ($lineas as $linea) {
            $lineaLimpia = trim($linea);
            
            // Ignorar comentarios y líneas vacías
            if (empty($lineaLimpia) || 
                strpos($lineaLimpia, '--') === 0 || 
                strpos($lineaLimpia, '/*') === 0 ||
                stripos($lineaLimpia, 'CREATE TABLE') === 0 ||
                stripos($lineaLimpia, 'DROP TABLE') === 0 ||
                stripos($lineaLimpia, 'ALTER TABLE') === 0 ||
                stripos($lineaLimpia, 'SET ') === 0) {
                continue;
            }
            
            // Detectar inicio de INSERT
            if (stripos($lineaLimpia, 'INSERT INTO') === 0) {
                // Guardar bloque anterior si existe
                if (!empty($bloqueActual) && !empty($tablaActual)) {
                    // CORREGIDO: Acumular bloques en lugar de sobrescribir
                    if (!isset($bloques[$tablaActual])) {
                        $bloques[$tablaActual] = [];
                    }
                    $bloques[$tablaActual][] = $bloqueActual;
                }
                
                // Extraer nombre de tabla
                if (preg_match('/INSERT INTO\s+`?([^`\s]+)`?/i', $lineaLimpia, $matches)) {
                    $tablaActual = $matches[1];
                    $bloqueActual = $linea . "\n";
                    $enInsert = true;
                } else {
                    $enInsert = false;
                }
            } elseif ($enInsert) {
                $bloqueActual .= $linea . "\n";
                
                // Detectar fin de bloque
                if (substr(rtrim($lineaLimpia), -1) === ';') {
                    if (!empty($tablaActual)) {
                        // CORREGIDO: Acumular bloques en lugar de sobrescribir
                        if (!isset($bloques[$tablaActual])) {
                            $bloques[$tablaActual] = [];
                        }
                        $bloques[$tablaActual][] = $bloqueActual;
                    }
                    $bloqueActual = '';
                    $tablaActual = '';
                    $enInsert = false;
                }
            }
        }
        
        // Agregar último bloque si existe
        if (!empty($bloqueActual) && !empty($tablaActual)) {
            // CORREGIDO: Acumular bloques en lugar de sobrescribir
            if (!isset($bloques[$tablaActual])) {
                $bloques[$tablaActual] = [];
            }
            $bloques[$tablaActual][] = $bloqueActual;
        }
        
        return $bloques;
    }
    
    /**
     * Ordena los bloques según las dependencias de foreign keys
     */
    private function ordenarBloquesPorDependencias($bloques)
    {
        // Definir el orden correcto basado en dependencias
        $ordenCorrecto = [
            // 1. Tablas base sin dependencias
            'users',
            'empresas',
            'ubicaciones',
            'plan_cuentas',
            'configuracion_contable',
            'configuracion_facturacion',
            
            // 2. Tablas de productos y clientes
            'productos',
            'clientes',
            'proveedores',
            'codigos_relacionados',
            
            // 3. Tablas de sistema
            'permissions',
            'roles',
            'role_has_permissions',
            'model_has_roles',
            'migrations',
            
            // 4. Tablas de caja y comprobantes
            'cajas_diarias',
            'comprobantes',
            
            // 5. Tablas transaccionales (dependen de las anteriores)
            'ventas',
            'compras',
            'detalle_ventas',
            'detalle_compras',
            'movimientos_contables',
            'movimientos_caja',
            'pagos_credito',
            
            // 6. Tablas de sesión y cache (al final)
            'sessions',
            'cache',
            'cache_locks'
        ];
        
        $bloquesOrdenados = [];
        
        // Primero agregar en el orden correcto
        foreach ($ordenCorrecto as $tabla) {
            if (isset($bloques[$tabla])) {
                $bloquesOrdenados[$tabla] = $bloques[$tabla];
                unset($bloques[$tabla]);
            }
        }
        
        // Agregar cualquier tabla restante al final
        foreach ($bloques as $tabla => $bloque) {
            $bloquesOrdenados[$tabla] = $bloque;
        }
        
        return $bloquesOrdenados;
    }
    
    /**
     * Procesa la tabla ventas de manera especial adaptando las columnas
     */
    private function procesarVentasEspecial($bloque, &$resultado)
    {
        try {
            Log::info('Procesando ventas con adaptación de columnas');
            
            // Obtener columnas actuales de la tabla ventas
            $columnasActuales = Schema::getColumnListing('ventas');
            
            // Extraer datos del INSERT (con o sin especificación de columnas)
            if (preg_match('/INSERT INTO\s+`?ventas`?\s*(?:\(([^)]+)\))?\s*VALUES\s*(.+);?$/is', $bloque, $matches)) {
                // Si no se especifican columnas, usar el orden exacto del backup
                if (empty($matches[1])) {
                    // Orden exacto de columnas según el CREATE TABLE del backup (30 valores)
                    $columnasBackup = [
                        'id', 'numero_factura', 'factura_alegra_id', 'estado_factura_dian',
                        'fecha_venta', 'subtotal', 'iva', 'total', 'metodo_pago',
                        'cliente_id', 'caja_id', 'user_id', 'created_at', 'updated_at',
                        'pago', 'devuelta', 'alegra_id', 'numero_factura_alegra',
                        'url_pdf_alegra', 'cufe', 'qr_code', 'estado_dian',
                        'xml_enviado', 'xml_respuesta', 'fecha_validacion', 'url_pdf',
                        'url_xml', 'fecha_envio_dian', 'respuesta_dian', 'numero_factura_electronica'
                    ];
                    Log::info('Usando orden exacto de columnas del backup (30 columnas)');
                } else {
                    $columnasBackup = array_map('trim', explode(',', str_replace('`', '', $matches[1])));
                }
                $valoresStr = rtrim($matches[2], ';');
                
                Log::info('Estructura de ventas en backup', [
                    'columnas_backup' => count($columnasBackup),
                    'columnas_actuales' => count($columnasActuales),
                    'columnas_backup_list' => $columnasBackup
                ]);
                
                // Extraer múltiples registros
                if (preg_match_all('/\(([^)]*(?:\([^)]*\)[^)]*)*)\)/', $valoresStr, $valoresMatches)) {
                    $registrosAdaptados = [];
                    
                    foreach ($valoresMatches[1] as $valores) {
                        $valoresArray = $this->parsearValoresSQL($valores);
                        
                        if (count($valoresArray) === count($columnasBackup)) {
                            // Crear registro con datos del backup
                            $registroBackup = array_combine($columnasBackup, $valoresArray);
                            
                            // Adaptar a estructura actual
                            $registroAdaptado = $this->adaptarVentaAEstructuraActual($registroBackup, $columnasActuales);
                            
                            if (!empty($registroAdaptado)) {
                                $registrosAdaptados[] = $registroAdaptado;
                            }
                        }
                    }
                    
                    // Insertar registros adaptados
                    if (!empty($registrosAdaptados)) {
                        DB::statement('SET FOREIGN_KEY_CHECKS=0');
                        
                        foreach ($registrosAdaptados as $registro) {
                            try {
                                DB::table('ventas')->insert($registro);
                                $resultado['inserciones_exitosas']++;
                            } catch (\Exception $e) {
                                Log::warning('Error insertando venta individual', [
                                    'error' => $e->getMessage(),
                                    'registro' => $registro
                                ]);
                                $resultado['inserciones_fallidas']++;
                            }
                        }
                        
                        DB::statement('SET FOREIGN_KEY_CHECKS=1');
                        
                        Log::info('Ventas procesadas', [
                            'total_registros' => count($registrosAdaptados),
                            'exitosos' => $resultado['inserciones_exitosas']
                        ]);
                    }
                }
            }
            
        } catch (\Exception $e) {
            $resultado['inserciones_fallidas']++;
            $resultado['errores'][] = "Error procesando ventas: " . $e->getMessage();
            
            Log::error('Error en procesamiento especial de ventas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Adapta un registro de venta del backup a la estructura actual
     */
    private function adaptarVentaAEstructuraActual($registroBackup, $columnasActuales)
    {
        $registroAdaptado = [];
        
        // Procesar cada columna actual
        foreach ($columnasActuales as $columnaActual) {
            $valor = null;
            
            // Mapeo directo si existe en el backup
            if (isset($registroBackup[$columnaActual])) {
                $valor = $registroBackup[$columnaActual];
            } else {
                // Mapeos especiales para columnas renombradas
                $mapeoEspecial = [
                    // No hay mapeos especiales necesarios ya que las columnas coinciden
                ];
                
                if (isset($mapeoEspecial[$columnaActual]) && isset($registroBackup[$mapeoEspecial[$columnaActual]])) {
                    $valor = $registroBackup[$mapeoEspecial[$columnaActual]];
                } else {
                    // Valor por defecto para columnas nuevas
                    $valor = $this->obtenerValorPorDefectoVenta($columnaActual);
                }
            }
            
            $registroAdaptado[$columnaActual] = $valor;
        }
        
        return $registroAdaptado;
    }
    
    /**
     * Obtiene valores por defecto para columnas nuevas en ventas
     */
    private function obtenerValorPorDefectoVenta($columna)
    {
        $valoresPorDefecto = [
            'comprobante_id' => null,
            'alegra_id' => null,
            'numero_factura_alegra' => null,
            'url_pdf_alegra' => null,
            'cufe' => null,
            'qr_code' => null,
            'estado_dian' => null,
            'xml_enviado' => null,
            'xml_respuesta' => null,
            'fecha_validacion' => null,
            'url_pdf' => null,
            'url_xml' => null,
            'fecha_envio_dian' => null,
            'respuesta_dian' => null,
            'numero_factura_electronica' => null,
        ];
        
        return $valoresPorDefecto[$columna] ?? null;
    }
    
    /**
     * Procesa un bloque línea por línea cuando falla como bloque completo
     */
    private function procesarBloqueLineaPorLinea($bloque, &$resultado)
    {
        $lineas = explode("\n", $bloque);
        
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            
            if (!empty($linea) && strpos($linea, '--') !== 0) {
                try {
                    DB::statement($linea);
                } catch (\Exception $e) {
                    // Ignorar errores individuales en modo de recuperación
                    Log::debug("Error en línea individual: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Cuenta las tablas que tienen datos después de la restauración
     */
    private function contarTablasConDatos()
    {
        $tablasConDatos = 0;
        $tablas = ['productos', 'clientes', 'ventas', 'detalle_ventas', 'movimientos_contables', 'empresas'];
        
        foreach ($tablas as $tabla) {
            if (Schema::hasTable($tabla)) {
                $count = DB::table($tabla)->count();
                if ($count > 0) {
                    $tablasConDatos++;
                }
            }
        }
        
        return $tablasConDatos;
    }
    
    /**
     * Reconstruye sentencias INSERT que pueden estar divididas en múltiples líneas
     */
    private function reconstruirSentenciasINSERT($lineas)
    {
        $sentencias = [];
        $sentenciaActual = '';
        $enInsert = false;
        
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            
            // Ignorar líneas vacías y comentarios
            if (empty($linea) || strpos($linea, '--') === 0 || strpos($linea, '/*') === 0) {
                continue;
            }
            
            // Detectar inicio de INSERT
            if (stripos($linea, 'INSERT INTO') === 0) {
                // Si había una sentencia anterior, guardarla
                if (!empty($sentenciaActual)) {
                    $sentencias[] = $sentenciaActual;
                }
                
                $sentenciaActual = $linea;
                $enInsert = true;
            } elseif ($enInsert) {
                // Continuar construyendo la sentencia
                $sentenciaActual .= ' ' . $linea;
            }
            
            // Detectar fin de sentencia (termina con ;)
            if ($enInsert && substr(rtrim($linea), -1) === ';') {
                $sentencias[] = $sentenciaActual;
                $sentenciaActual = '';
                $enInsert = false;
            }
        }
        
        // Agregar la última sentencia si no terminó con ;
        if (!empty($sentenciaActual)) {
            $sentencias[] = $sentenciaActual;
        }
        
        return $sentencias;
    }
    
    /**
     * Extrae datos de las sentencias INSERT del backup
     */
    private function extraerDatosDeInserts($sqlContent)
    {
        $datos = [];
        $lineas = explode("\n", $sqlContent);
        $totalLineas = count($lineas);
        
        Log::info('Analizando contenido SQL', [
            'total_lineas' => $totalLineas,
            'primeras_lineas' => array_slice($lineas, 0, 5)
        ]);
        
        $insertEncontrados = 0;
        
        foreach ($lineas as $numeroLinea => $linea) {
            $linea = trim($linea);
            
            // Ignorar líneas vacías, comentarios y comandos SQL
            if (empty($linea) || 
                strpos($linea, '--') === 0 || 
                strpos($linea, '/*') === 0 ||
                stripos($linea, 'CREATE TABLE') === 0 ||
                stripos($linea, 'DROP TABLE') === 0 ||
                stripos($linea, 'ALTER TABLE') === 0 ||
                stripos($linea, 'SET ') === 0) {
                continue;
            }
            
            // Buscar sentencias INSERT con diferentes formatos
            if (preg_match('/^INSERT INTO `?([^`\s]+)`?\s*\(([^)]+)\)\s*VALUES\s*(.+);?$/i', $linea, $matches)) {
                $tabla = $matches[1];
                $columnasStr = $matches[2];
                $valoresStr = rtrim($matches[3], ';');
                
                // Limpiar nombres de columnas
                $columnas = array_map(function($col) {
                    return trim(str_replace('`', '', $col));
                }, explode(',', $columnasStr));
                
                $insertEncontrados++;
                
                // Extraer múltiples conjuntos de valores
                if (preg_match_all('/\(([^)]*(?:\([^)]*\)[^)]*)*)\)/', $valoresStr, $valoresMatches)) {
                    foreach ($valoresMatches[1] as $valores) {
                        $valoresArray = $this->parsearValoresSQL($valores);
                        
                        if (count($valoresArray) === count($columnas)) {
                            $registro = array_combine($columnas, $valoresArray);
                            $datos[$tabla][] = $registro;
                        } else {
                            Log::warning('Desajuste en número de columnas', [
                                'tabla' => $tabla,
                                'columnas_esperadas' => count($columnas),
                                'valores_encontrados' => count($valoresArray),
                                'linea' => $numeroLinea + 1
                            ]);
                        }
                    }
                }
            }
        }
        
        Log::info('Extracción de datos completada', [
            'inserts_encontrados' => $insertEncontrados,
            'tablas_procesadas' => array_keys($datos),
            'total_registros' => array_sum(array_map('count', $datos))
        ]);
        
        return $datos;
    }
    
    /**
     * Parsea los valores de una sentencia SQL
     */
    private function parsearValoresSQL($valoresStr)
    {
        $valores = [];
        $enComillas = false;
        $valorActual = '';
        $i = 0;
        
        while ($i < strlen($valoresStr)) {
            $char = $valoresStr[$i];
            
            if ($char === "'" && ($i === 0 || $valoresStr[$i-1] !== '\\')) {
                $enComillas = !$enComillas;
                $valorActual .= $char;
            } elseif ($char === ',' && !$enComillas) {
                $valores[] = $this->limpiarValorSQL(trim($valorActual));
                $valorActual = '';
            } else {
                $valorActual .= $char;
            }
            $i++;
        }
        
        // Agregar el último valor
        if ($valorActual !== '') {
            $valores[] = $this->limpiarValorSQL(trim($valorActual));
        }
        
        return $valores;
    }
    
    /**
     * Limpia un valor SQL (quita comillas, maneja NULL, etc.)
     */
    private function limpiarValorSQL($valor)
    {
        $valor = trim($valor);
        
        if ($valor === 'NULL') {
            return null;
        }
        
        if (preg_match('/^\'(.*)\'$/', $valor, $matches)) {
            return str_replace("\\'", "'", $matches[1]);
        }
        
        return $valor;
    }
    
    /**
     * Adapta un registro a la estructura actual de la tabla
     */
    private function adaptarRegistroAEstructuraActual($registro, $columnasActuales)
    {
        $registroAdaptado = [];
        
        foreach ($columnasActuales as $columna) {
            if (isset($registro[$columna])) {
                $registroAdaptado[$columna] = $registro[$columna];
            } else {
                // Columna nueva que no existe en el backup
                // Usar valor por defecto según el tipo de columna
                $registroAdaptado[$columna] = $this->obtenerValorPorDefecto($columna);
            }
        }
        
        return $registroAdaptado;
    }
    
    /**
     * Obtiene un valor por defecto para columnas nuevas
     */
    private function obtenerValorPorDefecto($columna)
    {
        // Valores por defecto para columnas específicas conocidas
        $valoresPorDefecto = [
            'comprobante_id' => null,
            'numero_factura_alegra' => null,
            'alegra_id' => null,
            'cufe' => null,
            'qr_code' => null,
            'estado_dian' => null,
            'url_pdf_alegra' => null,
            'xml_enviado' => null,
            'xml_respuesta' => null,
            'fecha_validacion' => null,
            'url_pdf' => null,
            'url_xml' => null,
            'fecha_envio_dian' => null,
            'respuesta_dian' => null,
            'numero_factura_electronica' => null,
        ];
        
        return $valoresPorDefecto[$columna] ?? null;
    }
    
    /**
     * Extrae el contenido SQL de un archivo (ZIP o SQL directo)
     */
    private function extraerContenidoSQL($filePath)
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        
        if ($extension === 'zip') {
            Log::info('Procesando archivo ZIP', ['path' => $filePath]);
            
            $zip = new \ZipArchive();
            if ($zip->open($filePath) === TRUE) {
                // Buscar el archivo SQL dentro del ZIP
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    if (pathinfo($filename, PATHINFO_EXTENSION) === 'sql') {
                        Log::info('Archivo SQL encontrado en ZIP', ['sql_file' => $filename]);
                        
                        $sqlContent = $zip->getFromIndex($i);
                        $zip->close();
                        
                        if ($sqlContent === false) {
                            throw new \Exception('No se pudo extraer el contenido SQL del archivo ZIP');
                        }
                        
                        return $sqlContent;
                    }
                }
                
                $zip->close();
                throw new \Exception('No se encontró archivo SQL en el ZIP');
            } else {
                throw new \Exception('No se pudo abrir el archivo ZIP: ' . $filePath);
            }
        } else {
            // Archivo SQL directo
            Log::info('Procesando archivo SQL directo', ['path' => $filePath]);
            return File::get($filePath);
        }
    }
    
    /**
     * Limpia las tablas principales antes de la restauración
     */
    private function limpiarTablasPrincipales()
    {
        $tablasPrincipales = [
            'detalle_ventas',      // Primero las tablas dependientes
            'detalle_compras',
            'movimientos_contables',
            'comprobantes',        // Comprobantes contables
            'ventas',
            'compras',
            'productos',
            'categorias',
            'marcas',
            'clientes',
            'proveedores',
            'codigos_relacionados',
            'configuracion_contable',  // Configuración contable
            'plan_cuentas',       // Plan de cuentas
            'empresas'            // Limpiar empresas para permitir restauración
        ];
        
        Log::info('Limpiando tablas principales antes de la restauración', [
            'tablas' => $tablasPrincipales
        ]);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        foreach ($tablasPrincipales as $tabla) {
            try {
                if (Schema::hasTable($tabla)) {
                    $count = DB::table($tabla)->count();
                    DB::table($tabla)->truncate(); // Usar truncate en lugar de delete
                    Log::info("Tabla {$tabla} limpiada", ['registros_eliminados' => $count]);
                }
            } catch (\Exception $e) {
                Log::warning("Error al limpiar tabla {$tabla}", [
                    'error' => $e->getMessage()
                ]);
                // Si truncate falla, intentar con delete
                try {
                    if (Schema::hasTable($tabla)) {
                        DB::table($tabla)->delete();
                        Log::info("Tabla {$tabla} limpiada con delete como fallback");
                    }
                } catch (\Exception $e2) {
                    Log::error("Error crítico al limpiar tabla {$tabla}", [
                        'error' => $e2->getMessage()
                    ]);
                }
            }
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
    
    /**
     * Verifica que la restauración se haya completado correctamente
     */
    private function verificarRestauracion()
    {
        $tablasImportantes = [
            'productos' => 'Productos',
            'clientes' => 'Clientes', 
            'categorias' => 'Categorías',
            'marcas' => 'Marcas',
            'users' => 'Usuarios',
            'empresas' => 'Empresas'
        ];
        
        $resultado = [];
        
        foreach ($tablasImportantes as $tabla => $nombre) {
            try {
                if (Schema::hasTable($tabla)) {
                    $count = DB::table($tabla)->count();
                    $resultado[$nombre] = $count;
                    Log::info("Verificación: {$nombre}", ['registros' => $count]);
                } else {
                    $resultado[$nombre] = 'Tabla no existe';
                }
            } catch (\Exception $e) {
                $resultado[$nombre] = 'Error: ' . $e->getMessage();
            }
        }
        
        return $resultado;
    }
    
    /**
     * Actualiza las secuencias de autoincremento después de la restauración
     */
    private function actualizarSecuencias()
    {
        $tablasConAutoIncremento = [
            'productos',
            'categorias',
            'marcas',
            'clientes',
            'proveedores',
            'users',
            'ventas',
            'detalle_ventas',
            'codigos_relacionados'
        ];
        
        Log::info('Actualizando secuencias de autoincremento');
        
        foreach ($tablasConAutoIncremento as $tabla) {
            try {
                if (Schema::hasTable($tabla)) {
                    // Obtener el ID máximo
                    $maxId = DB::table($tabla)->max('id');
                    
                    if ($maxId) {
                        // Actualizar el autoincremento
                        DB::statement("ALTER TABLE {$tabla} AUTO_INCREMENT = " . ($maxId + 1));
                        Log::info("Secuencia de {$tabla} actualizada", ['nuevo_valor' => $maxId + 1]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Error al actualizar secuencia de {$tabla}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Obtiene las tablas actuales de la base de datos con sus columnas
     *
     * @return array Estructura de tablas y columnas
     */
    private function obtenerTablasActuales()
    {
        $tablas = [];
        $tablasDB = DB::select('SHOW TABLES');
        
        foreach ($tablasDB as $tabla) {
            $nombreTabla = reset($tabla);
            $columnas = Schema::getColumnListing($nombreTabla);
            $tablas[$nombreTabla] = $columnas;
        }
        
        return $tablas;
    }
    
    /**
     * Divide los valores respetando las comillas
     *
     * @param string $conjuntoValores Cadena con valores separados por comas
     * @return array Valores separados
     */
    private function dividirValoresRespetandoComillas($conjuntoValores)
    {
        $valores = [];
        $valor = '';
        $enComillas = false;
        $longitud = strlen($conjuntoValores);
        
        for ($i = 0; $i < $longitud; $i++) {
            $char = $conjuntoValores[$i];
            $prevChar = $i > 0 ? $conjuntoValores[$i-1] : '';
            
            // Manejar comillas (considerando escapes)
            if ($char === "'" && $prevChar !== "\\") {
                $enComillas = !$enComillas;
                $valor .= $char;
            }
            // Separador de valores (coma fuera de comillas)
            else if ($char === ',' && !$enComillas) {
                $valores[] = trim($valor);
                $valor = '';
            }
            // Cualquier otro carácter
            else {
                $valor .= $char;
            }
        }
        
        // Añadir el último valor
        if (!empty($valor)) {
            $valores[] = trim($valor);
        }
        
        return $valores;
    }
    
    /**
     * Analiza un archivo de backup y devuelve un resumen de su contenido
     *
     * @param string $filePath Ruta al archivo SQL de backup
     * @return array Resumen del contenido del backup
     */
    public function analizarBackup($filePath)
    {
        try {
            Log::info('Analizando archivo de backup', [
                'path' => $filePath
            ]);

            if (!File::exists($filePath)) {
                throw new \Exception('Archivo de backup no encontrado: ' . $filePath);
            }

            $sqlContent = '';
            
            // Verificar si es un archivo ZIP o SQL
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            
            if ($extension === 'zip') {
                // Manejar archivo ZIP
                $zip = new \ZipArchive();
                if ($zip->open($filePath) === TRUE) {
                    // Buscar el archivo SQL dentro del ZIP
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        if (pathinfo($filename, PATHINFO_EXTENSION) === 'sql') {
                            $sqlContent = $zip->getFromIndex($i);
                            break;
                        }
                    }
                    $zip->close();
                    
                    if (empty($sqlContent)) {
                        throw new \Exception('No se encontró archivo SQL dentro del ZIP');
                    }
                } else {
                    throw new \Exception('No se pudo abrir el archivo ZIP');
                }
            } else {
                // Leer directamente el archivo SQL
                $sqlContent = File::get($filePath);
            }
            
            // Extraer las tablas y contar registros
            $resumen = $this->extraerResumenTablas($sqlContent);
            
            // Devolver directamente el resumen para que coincida con el formato esperado por el modal
            return $resumen;

        } catch (\Exception $e) {
            Log::error('Error analizando backup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Extrae un resumen de las tablas y registros en el backup
     *
     * @param string $sqlContent Contenido del archivo SQL
     * @return array Resumen de tablas y registros
     */
    private function extraerResumenTablas($sqlContent)
    {
        $resumen = [
            'tablas' => [],
            'total_tablas' => 0,
            'total_registros' => 0,
            'tamano' => strlen($sqlContent),
            'fecha_creacion' => null,
            'version_mysql' => null
        ];
        
        // Extraer versión de MySQL si está disponible
        if (preg_match('/-- MySQL dump ([0-9.]+)/', $sqlContent, $matches)) {
            $resumen['version_mysql'] = $matches[1];
        }
        
        // Extraer fecha de creación
        if (preg_match('/-- Dump completed on ([0-9\-: ]+)/', $sqlContent, $matches)) {
            $resumen['fecha_creacion'] = $matches[1];
        }
        
        // Dividir el contenido en líneas
        $lineas = explode("\n", $sqlContent);
        $tablaActual = null;
        $conteoInserciones = [];
        
        // Buscar sentencias INSERT de forma más robusta
        $tablasConInserciones = [];
        
        // Patrón mejorado para capturar INSERT INTO con múltiples líneas
        $lines = explode("\n", $sqlContent);
        $currentInsertTable = null;
        $currentInsertContent = '';
        $inInsertStatement = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Detectar inicio de INSERT
            if (preg_match('/^INSERT INTO `([^`]+)`/', $line, $matches)) {
                $currentInsertTable = $matches[1];
                $currentInsertContent = $line;
                $inInsertStatement = true;
                
                // Inicializar contador para esta tabla
                if (!isset($tablasConInserciones[$currentInsertTable])) {
                    $tablasConInserciones[$currentInsertTable] = 0;
                }
            } elseif ($inInsertStatement) {
                $currentInsertContent .= ' ' . $line;
            }
            
            // Si la línea termina con ; y estamos en un INSERT, procesarlo
            if ($inInsertStatement && substr($line, -1) === ';') {
                // Contar registros en esta inserción
                $recordCount = $this->contarRegistrosEnLinea($currentInsertContent);
                $tablasConInserciones[$currentInsertTable] += $recordCount;
                $resumen['total_registros'] += $recordCount;
                
                // Reset para la siguiente inserción
                $inInsertStatement = false;
                $currentInsertContent = '';
                $currentInsertTable = null;
            }
        }
        
        foreach ($lineas as $linea) {
            // Detectar cambio de tabla en CREATE TABLE
            if (preg_match('/^CREATE TABLE `([^`]+)`/', $linea, $matches)) {
                $tablaActual = $matches[1];
                
                // Verificar si la tabla existe en la estructura actual
                if (!isset($resumen['tablas'][$tablaActual])) {
                    $resumen['tablas'][$tablaActual] = [
                        'nombre' => $tablaActual,
                        'registros' => isset($tablasConInserciones[$tablaActual]) ? $tablasConInserciones[$tablaActual] : 0,
                        'estructura' => true
                    ];
                    $resumen['total_tablas']++;
                }
            }
        }
        
        // Añadir tablas que solo tienen INSERT pero no CREATE TABLE
        foreach ($tablasConInserciones as $tabla => $registros) {
            if (!isset($resumen['tablas'][$tabla])) {
                $resumen['tablas'][$tabla] = [
                    'nombre' => $tabla,
                    'registros' => $registros,
                    'estructura' => false
                ];
                $resumen['total_tablas']++;
            }
        }
        
        // Ordenar tablas por cantidad de registros (descendente)
        uasort($resumen['tablas'], function($a, $b) {
            return $b['registros'] - $a['registros'];
        });
        
        // Convertir a array indexado para facilitar su uso en la vista
        $resumen['tablas'] = array_values($resumen['tablas']);
        
        // Categorizar tablas para la interfaz
        $resumen['categorias'] = $this->categorizarTablas($resumen['tablas']);
        
        return $resumen;
    }
    
    /**
     * Cuenta el número de registros en una línea de inserción
     *
     * @param string $linea Línea de inserción SQL
     * @return int Número de registros
     */
    private function contarRegistrosEnLinea($linea)
    {
        // Buscar la parte VALUES de la inserción
        if (preg_match('/VALUES\s*(.+);?$/i', $linea, $matches)) {
            $valuesContent = $matches[1];
            
            // Contar registros basándose en los separadores "),(" entre registros
            // Cada "),(" indica un nuevo registro
            $count = substr_count($valuesContent, '),(');
            
            // Si hay separadores, significa que hay count + 1 registros
            // Si no hay separadores pero hay VALUES, hay 1 registro
            return $count > 0 ? $count + 1 : 1;
        }
        
        return 0;
    }
    
    /**
     * Método legacy mantenido para compatibilidad
     */
    private function contarRegistrosEnInsercion($linea)
    {
        // Contar el número de apariciones de "),(" que indican múltiples registros
        $count = substr_count($linea, "),(");
        
        // Si hay al menos un registro, sumar 1 (para el primer registro)
        return $count > 0 ? $count + 1 : 1;
    }
    
    /**
     * Categoriza las tablas para mostrarlas de forma organizada
     *
     * @param array $tablas Lista de tablas con sus registros
     * @return array Tablas categorizadas
     */
    private function categorizarTablas($tablas)
    {
        $categorias = [
            'productos' => [
                'nombre' => 'Productos e Inventario',
                'icono' => 'fa-box',
                'color' => 'primary',
                'tablas' => [],
                'total_registros' => 0
            ],
            'ventas' => [
                'nombre' => 'Ventas y Transacciones',
                'icono' => 'fa-shopping-cart',
                'color' => 'success',
                'tablas' => [],
                'total_registros' => 0
            ],
            'usuarios' => [
                'nombre' => 'Usuarios y Seguridad',
                'icono' => 'fa-users',
                'color' => 'warning',
                'tablas' => [],
                'total_registros' => 0
            ],
            'configuracion' => [
                'nombre' => 'Configuración y Sistema',
                'icono' => 'fa-cogs',
                'color' => 'info',
                'tablas' => [],
                'total_registros' => 0
            ],
            'otros' => [
                'nombre' => 'Otras Tablas',
                'icono' => 'fa-table',
                'color' => 'secondary',
                'tablas' => [],
                'total_registros' => 0
            ]
        ];
        
        // Mapeo de tablas a categorías
        $mapeoTablas = [
            // Productos e Inventario
            'productos' => 'productos',
            'categorias' => 'productos',
            'marcas' => 'productos',
            'inventario' => 'productos',
            'stock' => 'productos',
            'codigos_relacionados' => 'productos',
            
            // Ventas y Transacciones
            'ventas' => 'ventas',
            'detalle_ventas' => 'ventas',
            'facturas' => 'ventas',
            'pagos' => 'ventas',
            'cajas' => 'ventas',
            'cajas_diarias' => 'ventas',
            'movimientos' => 'ventas',
            'gastos' => 'ventas',
            'clientes' => 'ventas',
            
            // Usuarios y Seguridad
            'users' => 'usuarios',
            'roles' => 'usuarios',
            'permissions' => 'usuarios',
            'role_has_permissions' => 'usuarios',
            'model_has_roles' => 'usuarios',
            'password_resets' => 'usuarios',
            
            // Configuración y Sistema
            'empresas' => 'configuracion',
            'configuraciones' => 'configuracion',
            'backups' => 'configuracion',
            'migrations' => 'configuracion',
            'settings' => 'configuracion',
            'logs' => 'configuracion'
        ];
        
        // Clasificar cada tabla
        foreach ($tablas as $tabla) {
            $nombreTabla = strtolower($tabla['nombre']);
            $categoria = 'otros'; // Categoría por defecto
            
            // Buscar coincidencias exactas
            if (isset($mapeoTablas[$nombreTabla])) {
                $categoria = $mapeoTablas[$nombreTabla];
            } else {
                // Buscar coincidencias parciales
                foreach ($mapeoTablas as $patron => $cat) {
                    if (strpos($nombreTabla, $patron) !== false) {
                        $categoria = $cat;
                        break;
                    }
                }
            }
            
            // Añadir a la categoría correspondiente
            $categorias[$categoria]['tablas'][] = $tabla;
            $categorias[$categoria]['total_registros'] += $tabla['registros'];
        }
        
        // Eliminar categorías vacías
        foreach ($categorias as $key => $categoria) {
            if (empty($categoria['tablas'])) {
                unset($categorias[$key]);
            }
        }
        
        return array_values($categorias);
    }
}
