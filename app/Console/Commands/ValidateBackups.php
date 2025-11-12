<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ValidateBackups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:validate 
                            {--file= : Validar un archivo específico}
                            {--all : Validar todos los backups}
                            {--repair : Intentar reparar metadatos faltantes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Valida la integridad de los archivos de backup usando checksums';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $backupsPath = storage_path('app/backups');
            $metadataPath = storage_path('app/backups/metadata.json');
            
            if (!File::exists($backupsPath)) {
                $this->error('El directorio de backups no existe');
                return 1;
            }
            
            // Cargar metadatos
            $metadata = [];
            if (File::exists($metadataPath)) {
                $metadata = json_decode(File::get($metadataPath), true) ?? [];
            }
            
            $specificFile = $this->option('file');
            $validateAll = $this->option('all');
            $repair = $this->option('repair');
            
            if ($specificFile) {
                return $this->validateSpecificFile($specificFile, $metadata, $repair);
            } elseif ($validateAll) {
                return $this->validateAllBackups($metadata, $repair);
            } else {
                $this->info('Especifique --file=nombre_archivo o --all para validar backups');
                return 0;
            }
            
        } catch (\Exception $e) {
            $this->error('Error al validar backups: ' . $e->getMessage());
            Log::error('Error en validación de backups', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
    
    /**
     * Validar un archivo específico
     */
    private function validateSpecificFile($filename, $metadata, $repair)
    {
        $backupsPath = storage_path('app/backups');
        $filePath = $backupsPath . '/' . $filename;
        
        if (!File::exists($filePath)) {
            $this->error("El archivo {$filename} no existe");
            return 1;
        }
        
        $this->info("Validando archivo: {$filename}");
        
        // Verificar si existe metadata
        if (!isset($metadata[$filename])) {
            $this->warn("No hay metadatos para {$filename}");
            
            if ($repair) {
                $this->info("Generando metadatos faltantes...");
                $checksum = md5_file($filePath);
                $size = $this->formatBytes(File::size($filePath));
                
                $metadata[$filename] = [
                    'filename' => $filename,
                    'size' => $size,
                    'checksum' => $checksum,
                    'created_at' => date('Y-m-d H:i:s', File::lastModified($filePath)),
                    'type' => pathinfo($filename, PATHINFO_EXTENSION) === 'zip' ? 'compressed' : 'sql'
                ];
                
                $this->saveMetadata($metadata);
                $this->info("Metadatos generados para {$filename}");
            }
            
            return 0;
        }
        
        // Validar checksum
        $storedChecksum = $metadata[$filename]['checksum'];
        $currentChecksum = md5_file($filePath);
        
        if ($storedChecksum === $currentChecksum) {
            $this->info("✅ {$filename} - Integridad verificada");
            return 0;
        } else {
            $this->error("❌ {$filename} - Archivo corrupto o modificado");
            $this->line("  Checksum esperado: {$storedChecksum}");
            $this->line("  Checksum actual:   {$currentChecksum}");
            return 1;
        }
    }
    
    /**
     * Validar todos los backups
     */
    private function validateAllBackups($metadata, $repair)
    {
        $backupsPath = storage_path('app/backups');
        $files = File::files($backupsPath);
        
        // Filtrar solo archivos de backup (excluir metadata.json)
        $backupFiles = array_filter($files, function($file) {
            $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
            return in_array($extension, ['sql', 'zip']);
        });
        
        if (empty($backupFiles)) {
            $this->info('No hay archivos de backup para validar');
            return 0;
        }
        
        $this->info('Validando ' . count($backupFiles) . ' archivos de backup...');
        $this->line('');
        
        $valid = 0;
        $invalid = 0;
        $missing_metadata = 0;
        
        foreach ($backupFiles as $file) {
            $filename = $file->getFilename();
            $filePath = $file->getPathname();
            
            // Verificar metadatos
            if (!isset($metadata[$filename])) {
                $this->warn("⚠️  {$filename} - Sin metadatos");
                $missing_metadata++;
                
                if ($repair) {
                    $checksum = md5_file($filePath);
                    $size = $this->formatBytes(File::size($filePath));
                    
                    $metadata[$filename] = [
                        'filename' => $filename,
                        'size' => $size,
                        'checksum' => $checksum,
                        'created_at' => date('Y-m-d H:i:s', File::lastModified($filePath)),
                        'type' => pathinfo($filename, PATHINFO_EXTENSION) === 'zip' ? 'compressed' : 'sql'
                    ];
                    
                    $this->info("  → Metadatos generados");
                }
                continue;
            }
            
            // Validar checksum
            $storedChecksum = $metadata[$filename]['checksum'];
            $currentChecksum = md5_file($filePath);
            
            if ($storedChecksum === $currentChecksum) {
                $this->info("✅ {$filename} - OK");
                $valid++;
            } else {
                $this->error("❌ {$filename} - CORRUPTO");
                $invalid++;
            }
        }
        
        // Guardar metadatos si se repararon
        if ($repair && $missing_metadata > 0) {
            $this->saveMetadata($metadata);
        }
        
        // Resumen
        $this->line('');
        $this->info('=== RESUMEN DE VALIDACIÓN ===');
        $this->info("Archivos válidos: {$valid}");
        
        if ($invalid > 0) {
            $this->error("Archivos corruptos: {$invalid}");
        }
        
        if ($missing_metadata > 0) {
            $this->warn("Archivos sin metadatos: {$missing_metadata}" . ($repair ? ' (reparados)' : ''));
        }
        
        return $invalid > 0 ? 1 : 0;
    }
    
    /**
     * Guardar metadatos
     */
    private function saveMetadata($metadata)
    {
        $metadataPath = storage_path('app/backups/metadata.json');
        File::put($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));
    }
    
    /**
     * Formatear bytes a un formato legible
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
