<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Services\DynamicEmailService;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database {--send-email : Enviar el backup por correo electrónico}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea un backup de la base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('Iniciando proceso de backup...');
            
            // Preparar directorio
            $backupsPath = storage_path('app/backups');
            if (!File::exists($backupsPath)) {
                File::makeDirectory($backupsPath, 0755, true);
            }
            
            // Nombre del archivo
            $filename = date('Y-m-d_H-i-s') . '_backup.sql';
            $filePath = $backupsPath . '/' . $filename;
            $compressedFilename = date('Y-m-d_H-i-s') . '_backup.zip';
            $compressedPath = $backupsPath . '/' . $compressedFilename;
            
            // Configuración de la base de datos
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            
            // Ruta de mysqldump en XAMPP
            $mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
            
            // Verificar que existe mysqldump
            if (!file_exists($mysqldump)) {
                throw new \Exception('No se encontró mysqldump en la ruta especificada');
            }
            
            // Construir el comando mejorado para evitar errores de directorio temporal
            $command = "\"{$mysqldump}\" --single-transaction --routines --triggers --user={$username} --password={$password} --databases {$database} --result-file=\"{$filePath}\" 2>&1";
            
            $this->info('Ejecutando comando de backup...');
            Log::info('Ejecutando comando de backup', [
                'command' => str_replace($password, '****', $command)
            ]);
            
            // Ejecutar comando
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                Log::error('Error en mysqldump', [
                    'output' => $output,
                    'code' => $returnCode
                ]);
                throw new \Exception('Error al crear backup: ' . implode("\n", $output));
            }
            
            // Verificar el archivo
            if (!File::exists($filePath)) {
                throw new \Exception('No se pudo crear el archivo de backup');
            }
            
            if (File::size($filePath) === 0) {
                File::delete($filePath);
                throw new \Exception('El archivo de backup está vacío');
            }
            
            $fileSize = $this->formatBytes(File::size($filePath));
            $this->info("Backup SQL creado exitosamente: {$filename} ({$fileSize})");
            
            // Comprimir el backup
            $this->info('Comprimiendo backup...');
            $compressed = $this->createCompressedBackup($filePath, $compressedPath, $filename);
            
            if ($compressed) {
                $compressedSize = $this->formatBytes(File::size($compressedPath));
                $compressionRatio = round((1 - File::size($compressedPath) / File::size($filePath)) * 100, 1);
                
                $this->info("Backup comprimido creado: {$compressedFilename} ({$compressedSize}) - Compresión: {$compressionRatio}%");
                
                // Generar checksum para validación de integridad
                $checksum = $this->generateChecksum($compressedPath);
                $this->saveBackupMetadata($compressedFilename, $compressedSize, $checksum);
                
                Log::info('Backup creado y comprimido exitosamente', [
                    'sql_filename' => $filename,
                    'sql_size' => $fileSize,
                    'compressed_filename' => $compressedFilename,
                    'compressed_size' => $compressedSize,
                    'compression_ratio' => $compressionRatio . '%',
                    'checksum' => $checksum
                ]);
                
                // Eliminar archivo SQL original para ahorrar espacio
                File::delete($filePath);
                $this->info('Archivo SQL original eliminado para ahorrar espacio');
                
                // Enviar por correo si se solicita (usar archivo comprimido)
                if ($this->option('send-email')) {
                    $this->sendBackupByEmail($compressedPath, $compressedFilename);
                }
                
                $finalFilename = $compressedFilename;
            } else {
                $this->warn('No se pudo comprimir el backup, manteniendo archivo SQL original');
                
                // Generar checksum para el archivo SQL
                $checksum = $this->generateChecksum($filePath);
                $this->saveBackupMetadata($filename, $fileSize, $checksum);
                
                Log::info('Backup creado exitosamente (sin compresión)', [
                    'filename' => $filename,
                    'size' => $fileSize,
                    'checksum' => $checksum
                ]);
                
                // Enviar por correo si se solicita
                if ($this->option('send-email')) {
                    $this->sendBackupByEmail($filePath, $filename);
                }
                
                $finalFilename = $filename;
            }
            
            // Limpiar backups antiguos (mantener solo los últimos 10)
            $this->cleanOldBackups();
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error al crear backup: ' . $e->getMessage());
            Log::error('Error al crear backup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
    
    /**
     * Enviar backup por correo electrónico usando sistema dinámico
     */
    private function sendBackupByEmail($filePath, $filename)
    {
        // Obtener el correo electrónico configurado (fallback)
        $backupEmail = Setting::where('key', 'backup_email')->first();
        
        if (!$backupEmail || empty($backupEmail->value)) {
            $this->warn('No hay un correo electrónico configurado para enviar el backup');
            return;
        }
        
        $email = $backupEmail->value;
        
        try {
            $this->info('Enviando backup por correo electrónico a: ' . $email);
            
            // Verificar el tamaño del archivo (límite de 25MB para la mayoría de proveedores)
            $fileSize = File::size($filePath);
            $maxSize = 25 * 1024 * 1024; // 25MB
            
            if ($fileSize > $maxSize) {
                // Si el archivo es muy grande, comprimir primero
                $compressedPath = $this->compressBackup($filePath, $filename);
                if ($compressedPath && File::size($compressedPath) <= $maxSize) {
                    $attachmentPath = $compressedPath;
                    $attachmentName = str_replace('.sql', '.zip', $filename);
                } else {
                    $this->warn('El backup es demasiado grande para enviar por correo (' . $this->formatBytes($fileSize) . ')');
                    return;
                }
            } else {
                $attachmentPath = $filePath;
                $attachmentName = $filename;
            }
            
            // Intentar envío con sistema dinámico primero
            $dynamicEmailService = new DynamicEmailService();
            
            // Obtener empresa_id del usuario actual o usar empresa por defecto
            // En comandos de consola, auth()->user() puede ser null
            $empresaId = auth()->check() ? auth()->user()->empresa_id : 2; // Usar empresa ID 2 por defecto
            
            $resultado = $dynamicEmailService->enviarEmail(
                $empresaId,
                'backup',
                $email,
                'Backup de Base de Datos - ' . date('d/m/Y H:i:s'),
                'emails.backup',
                [
                    'filename' => $filename,
                    'size' => $this->formatBytes(File::size($filePath)),
                    'date' => date('d/m/Y H:i:s')
                ],
                [
                    [
                        'path' => $attachmentPath,
                        'options' => [
                            'as' => $attachmentName,
                            'mime' => 'application/octet-stream'
                        ]
                    ]
                ]
            );
            
            if ($resultado['success']) {
                $this->info('✅ Backup enviado usando configuración dinámica');
                $this->info('📧 Configuración: ' . ($resultado['configuracion_usada'] ?? 'N/A'));
                $this->info('🚀 Proveedor: ' . ($resultado['proveedor'] ?? 'N/A'));
                
                Log::info('Backup enviado por correo dinámico', [
                    'email' => $email,
                    'filename' => $filename,
                    'size' => $this->formatBytes($fileSize),
                    'configuracion' => $resultado['configuracion_usada'],
                    'proveedor' => $resultado['proveedor']
                ]);
            } else {
                $this->warn('⚠️ Sistema dinámico falló, usando método tradicional');
                $this->warn('Error: ' . $resultado['message']);
                
                // Fallback al método tradicional
                Mail::send('emails.backup', [
                    'filename' => $filename,
                    'size' => $this->formatBytes(File::size($filePath)),
                    'date' => date('d/m/Y H:i:s')
                ], function ($message) use ($email, $attachmentPath, $attachmentName) {
                    $message->to($email)
                            ->subject('Backup de Base de Datos - ' . date('d/m/Y H:i:s'))
                            ->attach($attachmentPath, [
                                'as' => $attachmentName,
                                'mime' => 'application/octet-stream'
                            ]);
                });
                
                $this->info('📧 Backup enviado usando método tradicional');
                
                Log::info('Backup enviado por correo tradicional', [
                    'email' => $email,
                    'filename' => $filename,
                    'size' => $this->formatBytes($fileSize),
                    'metodo' => 'fallback_tradicional'
                ]);
            }
            
            // Limpiar archivo comprimido temporal si se creó
            if (isset($compressedPath) && File::exists($compressedPath)) {
                File::delete($compressedPath);
            }
            
            $this->info('Backup enviado por correo electrónico exitosamente');
            Log::info('Backup enviado por correo', [
                'email' => $email,
                'filename' => $filename,
                'size' => $this->formatBytes($fileSize)
            ]);
            
        } catch (\Exception $e) {
            $this->error('Error al enviar backup por correo: ' . $e->getMessage());
            Log::error('Error al enviar backup por correo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Crear backup comprimido principal
     */
    private function createCompressedBackup($sqlPath, $zipPath, $sqlFilename)
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
                $zip->addFile($sqlPath, $sqlFilename);
                $zip->setCompressionName($sqlFilename, \ZipArchive::CM_DEFLATE, 9); // Máxima compresión
                $zip->close();
                
                return File::exists($zipPath) && File::size($zipPath) > 0;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Error al crear backup comprimido', [
                'error' => $e->getMessage(),
                'sql_path' => $sqlPath,
                'zip_path' => $zipPath
            ]);
            return false;
        }
    }
    
    /**
     * Comprimir backup para envío por correo (método legacy)
     */
    private function compressBackup($filePath, $filename)
    {
        try {
            $zipPath = str_replace('.sql', '.zip', $filePath);
            
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
                $zip->addFile($filePath, $filename);
                $zip->setCompressionName($filename, \ZipArchive::CM_DEFLATE, 9);
                $zip->close();
                
                if (File::exists($zipPath)) {
                    $this->info('Backup comprimido para envío: ' . $this->formatBytes(File::size($zipPath)));
                    return $zipPath;
                }
            }
            
            return false;
        } catch (\Exception $e) {
            $this->error('Error al comprimir backup: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generar checksum MD5 para validación de integridad
     */
    private function generateChecksum($filePath)
    {
        try {
            return md5_file($filePath);
        } catch (\Exception $e) {
            Log::error('Error al generar checksum', [
                'error' => $e->getMessage(),
                'file' => $filePath
            ]);
            return null;
        }
    }
    
    /**
     * Guardar metadatos del backup
     */
    private function saveBackupMetadata($filename, $size, $checksum)
    {
        try {
            $metadataPath = storage_path('app/backups/metadata.json');
            
            // Cargar metadatos existentes
            $metadata = [];
            if (File::exists($metadataPath)) {
                $metadata = json_decode(File::get($metadataPath), true) ?? [];
            }
            
            // Añadir nuevo backup
            $metadata[$filename] = [
                'filename' => $filename,
                'size' => $size,
                'checksum' => $checksum,
                'created_at' => now()->toDateTimeString(),
                'type' => pathinfo($filename, PATHINFO_EXTENSION) === 'zip' ? 'compressed' : 'sql'
            ];
            
            // Guardar metadatos actualizados
            File::put($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));
            
        } catch (\Exception $e) {
            Log::error('Error al guardar metadatos del backup', [
                'error' => $e->getMessage(),
                'filename' => $filename
            ]);
        }
    }
    
    /**
     * Limpiar backups antiguos (mantener solo los últimos 10)
     */
    private function cleanOldBackups()
    {
        $backupsPath = storage_path('app/backups');
        $files = File::files($backupsPath);
        
        // Ordenar por fecha de modificación (más antiguos primero)
        usort($files, function ($a, $b) {
            return $a->getMTime() - $b->getMTime();
        });
        
        // Si hay más de 10 archivos, eliminar los más antiguos
        if (count($files) > 10) {
            $filesToDelete = array_slice($files, 0, count($files) - 10);
            
            foreach ($filesToDelete as $file) {
                $filename = $file->getFilename();
                File::delete($file);
                $this->info("Backup antiguo eliminado: {$filename}");
                Log::info('Backup antiguo eliminado', ['filename' => $filename]);
            }
        }
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
