<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use App\Services\BackupService;
use App\Models\Setting;

use Carbon\Carbon;

class BackupController extends Controller
{
    protected $backupPath;
    protected $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->middleware('auth');
        $this->backupPath = storage_path('app/backups');
        $this->backupService = $backupService;
    }

    public function index()
    {
        try {
            $backupsPath = storage_path('app/backups');
            $metadataPath = storage_path('app/backups/metadata.json');
            
            // Asegurar que el directorio existe
            if (!File::exists($backupsPath)) {
                File::makeDirectory($backupsPath, 0755, true);
            }
    
            // Cargar metadatos
            $metadata = [];
            if (File::exists($metadataPath)) {
                $metadata = json_decode(File::get($metadataPath), true) ?? [];
            }
    
            // Obtener archivos del directorio (excluir metadata.json)
            $files = File::files($backupsPath);
            $backupFiles = array_filter($files, function($file) {
                $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
                return in_array($extension, ['sql', 'zip']);
            });
    
            $backups = collect($backupFiles)->map(function($file) use ($metadata) {
                $filename = $file->getFilename();
                $fileMetadata = $metadata[$filename] ?? null;
                
                return [
                    'filename' => $filename,
                    'size' => $this->formatBytes($file->getSize()),
                    'date' => Carbon::createFromTimestamp($file->getMTime())->format('d/m/Y'),
                    'time' => Carbon::createFromTimestamp($file->getMTime())->format('H:i:s'),
                    'age' => Carbon::createFromTimestamp($file->getMTime())->diffForHumans(),
                    'path' => $file->getPathname(),
                    'type' => pathinfo($filename, PATHINFO_EXTENSION) === 'zip' ? 'compressed' : 'sql',
                    'checksum' => $fileMetadata['checksum'] ?? null,
                    'has_metadata' => $fileMetadata !== null,
                    'created_at' => $fileMetadata['created_at'] ?? null
                ];
            })->sortByDesc(function($backup) {
                return Carbon::createFromFormat('d/m/Y', $backup['date'])->timestamp;
            });
    
            Log::info('Backups encontrados', [
                'count' => $backups->count(),
                'files' => $backups->pluck('filename')->toArray()
            ]);
            
            // Obtener configuraciones
            $backupEmail = Setting::where('key', 'backup_email')->first();
            $backupEmail = $backupEmail ? $backupEmail->value : '';
            
            $backupAutoEnabled = Setting::where('key', 'backup_auto_enabled')->first();
            $backupAutoEnabled = $backupAutoEnabled ? (bool)$backupAutoEnabled->value : false;
    
            return view('backup.index', compact('backups', 'backupEmail', 'backupAutoEnabled'));
    
        } catch (\Exception $e) {
            Log::error('Error al listar backups', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return view('backup.index', [
                'backups' => collect([]), 
                'backupEmail' => '', 
                'backupAutoEnabled' => false
            ])->with('error', 'Error al listar backups: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            // Obtener tablas usando una consulta directa
            $tables = DB::select('SHOW TABLES');
            $tables = array_map(function($table) {
                return array_values((array)$table)[0];
            }, $tables);

            if (empty($tables)) {
                throw new \Exception('No se pudieron obtener las tablas de la base de datos');
            }

            return view('backup.create', compact('tables'));
        
        } catch (\Exception $e) {
            Log::error('Error obteniendo tablas para backup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        
            return redirect()->route('backup.index')
                ->with('error', 'Error al preparar el formulario de backup: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            // Validación básica
            $request->validate([
                'backup_name' => 'nullable|string',
                'send_email' => 'nullable|in:on,1,true,0,false'
            ]);

            // Determinar si se debe enviar por correo (manejar checkbox y strings)
            $sendEmailValue = $request->input('send_email');
            $sendEmail = in_array($sendEmailValue, ['on', '1', 'true', true], true) ? '--send-email' : '';
            
            // Ejecutar el comando de backup
            $output = [];
            $returnVar = 0;
            
            $command = "php " . base_path('artisan') . " backup:database {$sendEmail}";
            exec($command, $output, $returnVar);
            
            // Verificar si el comando se ejecutó correctamente
            if ($returnVar !== 0) {
                return redirect()->route('backup.index')
                    ->with('error', 'Error al crear el backup: ' . implode("\n", $output));
            }
            
            return redirect()->route('backup.index')
                ->with('success', 'Backup creado con éxito' . ($sendEmail ? ' y enviado por correo electrónico' : ''));
        } catch (\Exception $e) {
            Log::error('Error al crear backup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('backup.index')
                ->with('error', 'Error al crear backup: ' . $e->getMessage());
        }
    }

    public function download($filename)
    {
        try {
            $backupPath = storage_path('app/backups/' . $filename);
            
            if (!File::exists($backupPath)) {
                return redirect()->route('backup.index')
                    ->with('error', 'Archivo de backup no encontrado: ' . $filename);
            }
            
            return response()->download($backupPath);
            
        } catch (\Exception $e) {
            Log::error('Error al descargar backup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('backup.index')
                ->with('error', 'Error al descargar backup: ' . $e->getMessage());
        }
    }

    /**
     * Restaura un backup
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function restore($filename)
    {
        try {
            // Verificar que el archivo existe
            $backupPath = storage_path('app/backups/' . $filename);
            
            if (!File::exists($backupPath)) {
                return redirect()->route('backup.index')
                    ->with('error', 'Archivo de backup no encontrado: ' . $filename);
            }
            
            // Ejecutar el comando de restauración
            Artisan::call('backup:restore', [
                'file' => $filename
            ]);
            
            return redirect()->route('backup.index')
                ->with('success', 'Base de datos restaurada con éxito');
        } catch (\Exception $e) {
            Log::error('Excepción en restauración de backup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('backup.index')
                ->with('error', 'Error en la restauración: ' . $e->getMessage());
        }
    }
    
    /**
     * Restaura solo los datos de un backup sin afectar la estructura
     *
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    public function restoreDataOnly($filename)
    {
        try {
            // Verificar que el archivo existe
            $backupPath = storage_path('app/backups/' . $filename);
            
            if (!File::exists($backupPath)) {
                Log::error('Archivo de backup no encontrado', [
                    'filename' => $filename,
                    'path' => $backupPath
                ]);
                return redirect()->route('backup.index')
                    ->with('error', 'Archivo de backup no encontrado: ' . $filename);
            }
            
            // Ejecutar el comando de restauración selectiva mejorado
            $output = Artisan::call('backup:restore-data-improved', [
                'file' => $filename,
                '--force' => true
            ]);

            if ($output !== 0) {
                $outputText = Artisan::output();
                Log::error('Error en la restauración de datos', [
                    'output' => $outputText
                ]);
                throw new \Exception($outputText);
            }

            return redirect()->route('backup.index')
                ->with('success', 'Datos restaurados con éxito. Se han preservado las estructuras de tablas.');
        } catch (\Exception $e) {
            Log::error('Error al restaurar los datos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('backup.index')
                ->with('error', 'Error al restaurar los datos: ' . $e->getMessage());
        }
    }

    // Crea un backup previo a la restauración
    public function crearBackupPrevio($backupAntes)
    {
        try {
            // Ruta de mysqldump en XAMPP
            $mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';

            // Verificar que existe mysqldump
            if (!file_exists($mysqldump)) {
                throw new \Exception('No se encontró mysqldump en la ruta especificada');
            }

            // Configuración de la base de datos
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');

            // Construir el comando
            $command = "\"{$mysqldump}\" --user={$username} --password={$password} --databases {$database} --result-file=\"{$backupAntes}\" 2>&1";
        
            Log::info('Ejecutando comando de backup previo', [
                'command' => str_replace($password, '****', $command)
            ]);

            // Ejecutar comando
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                Log::error('Error en mysqldump para backup previo', [
                    'output' => $output,
                    'code' => $returnCode
                ]);
                throw new \Exception('Error al crear backup previo: ' . implode("\n", $output));
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error al crear backup previo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    public function delete($filename)
    {
        try {
            $backupPath = storage_path('app/backups/' . $filename);
            
            if (!File::exists($backupPath)) {
                return redirect()->route('backup.index')
                    ->with('error', 'Archivo de backup no encontrado: ' . $filename);
            }
            
            File::delete($backupPath);
            
            return redirect()->route('backup.index')
                ->with('success', 'Backup eliminado con éxito');
            
        } catch (\Exception $e) {
            Log::error('Error al eliminar backup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('backup.index')
                ->with('error', 'Error al eliminar backup: ' . $e->getMessage());
        }
    }

    /**
     * Analiza el contenido de un archivo de backup
     *
     * @param string $filename Nombre del archivo de backup
     * @return \Illuminate\Http\JsonResponse
     */
    public function analizar($filename)
    {
        try {
            $filePath = storage_path('app/backups/' . $filename);
            
            if (!File::exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo de backup no existe'
                ], 404);
            }
            
            // Obtener información del archivo
            $fileInfo = [
                'nombre' => $filename,
                'tamano' => $this->formatBytes(File::size($filePath)),
                'fecha' => Carbon::createFromTimestamp(File::lastModified($filePath))->format('d/m/Y H:i:s')
            ];
            
            // Analizar el contenido del backup
            $analisis = $this->backupService->analizarBackup($filePath);
            
            // Añadir información del archivo
            $analisis['archivo'] = $fileInfo;
            
            return response()->json([
                'success' => true,
                'data' => $analisis
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al analizar backup', [
                'filename' => $filename,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al analizar el backup: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Configurar el correo electrónico para envío de backups
     */
    public function configureEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
        
        // Guardar la configuración en la base de datos
        $setting = Setting::updateOrCreate(
            ['key' => 'backup_email'],
            ['value' => $request->email]
        );
        
        return redirect()->route('backup.index')
            ->with('success', 'Configuración de correo electrónico guardada con éxito');
    }
    
    /**
     * Configurar backups automáticos
     */
    public function configureAutoBackup(Request $request)
    {
        $request->validate([
            'auto_enabled' => 'required|boolean',
        ]);
        
        // Guardar la configuración en la base de datos
        Setting::updateOrCreate(
            ['key' => 'backup_auto_enabled'],
            ['value' => $request->auto_enabled]
        );
        
        $message = $request->auto_enabled 
            ? 'Backups automáticos habilitados' 
            : 'Backups automáticos deshabilitados';
        
        return redirect()->route('backup.index')
            ->with('success', $message);
    }
    
    /**
     * Validar integridad de un backup específico
     */
    public function validateBackup($filename)
    {
        try {
            $output = [];
            $returnVar = 0;
            
            $command = "php " . base_path('artisan') . " backup:validate --file={$filename}";
            exec($command, $output, $returnVar);
            
            if ($returnVar === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Backup válido - integridad verificada',
                    'output' => implode("\n", $output)
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup corrupto o con problemas de integridad',
                    'output' => implode("\n", $output)
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Error al validar backup', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al validar backup: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Validar todos los backups
     */
    public function validateAllBackups()
    {
        try {
            $output = [];
            $returnVar = 0;
            
            $command = "php " . base_path('artisan') . " backup:validate --all --repair";
            exec($command, $output, $returnVar);
            
            return response()->json([
                'success' => true,
                'message' => 'Validación completada',
                'output' => implode("\n", $output),
                'has_errors' => $returnVar !== 0
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al validar todos los backups', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al validar backups: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener estadísticas de backups
     */
    public function getStats()
    {
        try {
            $backupsPath = storage_path('app/backups');
            $metadataPath = storage_path('app/backups/metadata.json');
            
            if (!File::exists($backupsPath)) {
                return response()->json([
                    'total_backups' => 0,
                    'total_size' => 0,
                    'compressed_backups' => 0,
                    'last_backup' => null
                ]);
            }
            
            // Obtener archivos
            $files = File::files($backupsPath);
            $backupFiles = array_filter($files, function($file) {
                $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
                return in_array($extension, ['sql', 'zip']);
            });
            
            $totalSize = 0;
            $compressedCount = 0;
            $lastBackup = null;
            $lastModified = 0;
            
            foreach ($backupFiles as $file) {
                $totalSize += $file->getSize();
                
                if (pathinfo($file->getFilename(), PATHINFO_EXTENSION) === 'zip') {
                    $compressedCount++;
                }
                
                if ($file->getMTime() > $lastModified) {
                    $lastModified = $file->getMTime();
                    $lastBackup = [
                        'filename' => $file->getFilename(),
                        'date' => Carbon::createFromTimestamp($file->getMTime())->format('d/m/Y H:i:s'),
                        'size' => $this->formatBytes($file->getSize())
                    ];
                }
            }
            
            return response()->json([
                'total_backups' => count($backupFiles),
                'total_size' => $this->formatBytes($totalSize),
                'total_size_bytes' => $totalSize,
                'compressed_backups' => $compressedCount,
                'last_backup' => $lastBackup
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas de backups', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas'
            ], 500);
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