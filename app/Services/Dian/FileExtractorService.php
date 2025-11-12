<?php

namespace App\Services\Dian;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class FileExtractorService
{
    private $extensionesPermitidas = ['zip', 'rar', '7z', 'xml', 'pdf'];
    private $carpetaExtraccion;

    public function __construct()
    {
        $this->carpetaExtraccion = 'facturas_extraidas/' . date('Y/m/d');
    }

    /**
     * Extraer archivos de una lista de archivos
     */
    public function extraerArchivos(array $archivos): array
    {
        $archivosExtraidos = [];
        
        foreach ($archivos as $archivo) {
            try {
                $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
                
                switch ($extension) {
                    case 'zip':
                        $extraidos = $this->extraerZip($archivo);
                        $archivosExtraidos = array_merge($archivosExtraidos, $extraidos);
                        break;
                        
                    case 'rar':
                        $extraidos = $this->extraerRar($archivo);
                        $archivosExtraidos = array_merge($archivosExtraidos, $extraidos);
                        break;
                        
                    case '7z':
                        $extraidos = $this->extraer7z($archivo);
                        $archivosExtraidos = array_merge($archivosExtraidos, $extraidos);
                        break;
                        
                    case 'xml':
                    case 'pdf':
                        // Archivos ya descomprimidos
                        $archivosExtraidos[] = $archivo;
                        break;
                        
                    default:
                        Log::warning("Extensión no soportada: {$extension} para archivo {$archivo}");
                }
                
            } catch (\Exception $e) {
                Log::error("Error extrayendo archivo {$archivo}: " . $e->getMessage());
            }
        }

        return $archivosExtraidos;
    }

    /**
     * Extraer archivo ZIP
     */
    private function extraerZip(string $archivoZip): array
    {
        $archivosExtraidos = [];
        
        try {
            $zip = new ZipArchive();
            $resultado = $zip->open($archivoZip);
            
            if ($resultado !== TRUE) {
                throw new \Exception("No se pudo abrir el archivo ZIP: {$archivoZip}");
            }

            // Crear carpeta de extracción
            $carpetaDestino = $this->crearCarpetaExtraccion(basename($archivoZip, '.zip'));
            
            // Extraer todos los archivos
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $nombreArchivo = $zip->getNameIndex($i);
                $contenido = $zip->getFromIndex($i);
                
                if ($contenido !== false && !empty(trim($nombreArchivo))) {
                    $rutaDestino = $carpetaDestino . '/' . basename($nombreArchivo);
                    
                    if (Storage::put($rutaDestino, $contenido)) {
                        $archivosExtraidos[] = storage_path('app/' . $rutaDestino);
                        Log::info("Extraído de ZIP: {$rutaDestino}");
                    }
                }
            }
            
            $zip->close();
            
        } catch (\Exception $e) {
            Log::error("Error extrayendo ZIP {$archivoZip}: " . $e->getMessage());
        }

        return $archivosExtraidos;
    }

    /**
     * Extraer archivo RAR
     */
    private function extraerRar(string $archivoRar): array
    {
        $archivosExtraidos = [];
        
        try {
            // Verificar si la extensión RAR está disponible
            if (!extension_loaded('rar')) {
                // Usar comando del sistema como alternativa
                return $this->extraerRarComando($archivoRar);
            }

            $rar = rar_open($archivoRar);
            
            if (!$rar) {
                throw new \Exception("No se pudo abrir el archivo RAR: {$archivoRar}");
            }

            $carpetaDestino = $this->crearCarpetaExtraccion(basename($archivoRar, '.rar'));
            $entradas = rar_list($rar);
            
            foreach ($entradas as $entrada) {
                if (!$entrada->isDirectory()) {
                    $nombreArchivo = $entrada->getName();
                    $rutaDestino = $carpetaDestino . '/' . basename($nombreArchivo);
                    
                    $stream = $entrada->getStream();
                    if ($stream) {
                        $contenido = stream_get_contents($stream);
                        fclose($stream);
                        
                        if (Storage::put($rutaDestino, $contenido)) {
                            $archivosExtraidos[] = storage_path('app/' . $rutaDestino);
                            Log::info("Extraído de RAR: {$rutaDestino}");
                        }
                    }
                }
            }
            
            rar_close($rar);
            
        } catch (\Exception $e) {
            Log::error("Error extrayendo RAR {$archivoRar}: " . $e->getMessage());
            // Intentar con comando del sistema
            return $this->extraerRarComando($archivoRar);
        }

        return $archivosExtraidos;
    }

    /**
     * Extraer RAR usando comando del sistema
     */
    private function extraerRarComando(string $archivoRar): array
    {
        $archivosExtraidos = [];
        
        try {
            $carpetaDestino = $this->crearCarpetaExtraccion(basename($archivoRar, '.rar'));
            $rutaCompleta = storage_path('app/' . $carpetaDestino);
            
            // Crear directorio si no existe
            if (!is_dir($rutaCompleta)) {
                mkdir($rutaCompleta, 0755, true);
            }
            
            // Intentar con diferentes comandos RAR
            $comandos = [
                "unrar x \"{$archivoRar}\" \"{$rutaCompleta}\"",
                "7z x \"{$archivoRar}\" -o\"{$rutaCompleta}\"",
                "winrar x \"{$archivoRar}\" \"{$rutaCompleta}\""
            ];
            
            foreach ($comandos as $comando) {
                $output = [];
                $returnCode = 0;
                
                exec($comando . ' 2>&1', $output, $returnCode);
                
                if ($returnCode === 0) {
                    // Buscar archivos extraídos
                    $archivosExtraidos = $this->buscarArchivosEnCarpeta($rutaCompleta);
                    Log::info("RAR extraído exitosamente con comando: {$comando}");
                    break;
                }
            }
            
        } catch (\Exception $e) {
            Log::error("Error extrayendo RAR con comando: " . $e->getMessage());
        }

        return $archivosExtraidos;
    }

    /**
     * Extraer archivo 7Z
     */
    private function extraer7z(string $archivo7z): array
    {
        $archivosExtraidos = [];
        
        try {
            $carpetaDestino = $this->crearCarpetaExtraccion(basename($archivo7z, '.7z'));
            $rutaCompleta = storage_path('app/' . $carpetaDestino);
            
            if (!is_dir($rutaCompleta)) {
                mkdir($rutaCompleta, 0755, true);
            }
            
            $comando = "7z x \"{$archivo7z}\" -o\"{$rutaCompleta}\"";
            $output = [];
            $returnCode = 0;
            
            exec($comando . ' 2>&1', $output, $returnCode);
            
            if ($returnCode === 0) {
                $archivosExtraidos = $this->buscarArchivosEnCarpeta($rutaCompleta);
                Log::info("7Z extraído exitosamente: {$archivo7z}");
            } else {
                Log::error("Error extrayendo 7Z: " . implode("\n", $output));
            }
            
        } catch (\Exception $e) {
            Log::error("Error extrayendo 7Z {$archivo7z}: " . $e->getMessage());
        }

        return $archivosExtraidos;
    }

    /**
     * Crear carpeta de extracción única
     */
    private function crearCarpetaExtraccion(string $nombreBase): string
    {
        $carpeta = $this->carpetaExtraccion . '/' . $nombreBase . '_' . time();
        Storage::makeDirectory($carpeta);
        return $carpeta;
    }

    /**
     * Buscar archivos en una carpeta
     */
    private function buscarArchivosEnCarpeta(string $carpeta): array
    {
        $archivos = [];
        
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($carpeta)
            );
            
            foreach ($iterator as $archivo) {
                if ($archivo->isFile()) {
                    $extension = strtolower($archivo->getExtension());
                    
                    if (in_array($extension, $this->extensionesPermitidas)) {
                        $archivos[] = $archivo->getPathname();
                    }
                }
            }
            
        } catch (\Exception $e) {
            Log::error("Error buscando archivos en carpeta {$carpeta}: " . $e->getMessage());
        }

        return $archivos;
    }

    /**
     * Limpiar archivos temporales antiguos
     */
    public function limpiarArchivosTemporales(int $diasAntiguedad = 7): void
    {
        try {
            $carpetaBase = 'facturas_extraidas';
            $fechaLimite = now()->subDays($diasAntiguedad);
            
            $directorios = Storage::directories($carpetaBase);
            
            foreach ($directorios as $directorio) {
                $fechaDirectorio = Storage::lastModified($directorio);
                
                if ($fechaDirectorio < $fechaLimite->timestamp) {
                    Storage::deleteDirectory($directorio);
                    Log::info("Directorio temporal eliminado: {$directorio}");
                }
            }
            
        } catch (\Exception $e) {
            Log::error("Error limpiando archivos temporales: " . $e->getMessage());
        }
    }

    /**
     * Obtener información de un archivo
     */
    public function obtenerInfoArchivo(string $rutaArchivo): array
    {
        try {
            $info = [
                'nombre' => basename($rutaArchivo),
                'extension' => strtolower(pathinfo($rutaArchivo, PATHINFO_EXTENSION)),
                'tamaño' => filesize($rutaArchivo),
                'fecha_modificacion' => filemtime($rutaArchivo),
                'es_comprimido' => false,
                'es_xml' => false,
                'es_pdf' => false
            ];
            
            $info['es_comprimido'] = in_array($info['extension'], ['zip', 'rar', '7z']);
            $info['es_xml'] = $info['extension'] === 'xml';
            $info['es_pdf'] = $info['extension'] === 'pdf';
            
            return $info;
            
        } catch (\Exception $e) {
            Log::error("Error obteniendo info de archivo {$rutaArchivo}: " . $e->getMessage());
            return [];
        }
    }
}
