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

use Carbon\Carbon;

class BackupController extends Controller
{
    protected $backupPath;

    public function __construct()
    {
        $this->middleware('auth');
        $this->backupPath = storage_path('app/backups');
    }

    public function index()
    {
        try {
            $backupsPath = storage_path('app/backups');
            
            // Asegurar que el directorio existe
            if (!File::exists($backupsPath)) {
                File::makeDirectory($backupsPath, 0755, true);
            }
    
            // Obtener archivos del directorio
            $files = File::files($backupsPath);
    
            $backups = collect($files)->map(function($file) {
                return [
                    'filename' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'date' => Carbon::createFromTimestamp($file->getMTime()),
                    'path' => $file->getPathname()
                ];
            })->sortByDesc('date');
    
            Log::info('Backups encontrados', [
                'count' => $backups->count(),
                'files' => $backups->pluck('filename')->toArray()
            ]);
    
            return view('backup.index', compact('backups'));
    
        } catch (\Exception $e) {
            Log::error('Error al listar backups', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return view('backup.index', ['backups' => collect([])])
                ->with('error', 'Error al listar backups: ' . $e->getMessage());
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
            'backup_name' => 'required|string',
            'description' => 'required|string'
        ]);

        // Ruta de mysqldump en XAMPP
        $mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';

        // Verificar que existe mysqldump
        if (!file_exists($mysqldump)) {
            throw new \Exception('No se encontró mysqldump en la ruta especificada');
        }

        // Preparar directorio y archivo
        $backupsPath = storage_path('app/backups');
        if (!File::exists($backupsPath)) {
            File::makeDirectory($backupsPath, 0755, true);
        }

        // Nombre del archivo
        $filename = date('Y-m-d_H-i-s') . '_backup.sql';
        $filePath = $backupsPath . '/' . $filename;

        // Configuración de la base de datos
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        // Construir el comando
        $command = "\"{$mysqldump}\" --user={$username} --password={$password} --databases {$database} --result-file=\"{$filePath}\" 2>&1";
        
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

        // Registrar en la base de datos
        DB::table('backups')->insert([
            'filename' => $filename,
            'description' => $request->description,
            'type' => 'full',
            'created_by' => Auth::id(),
            'size' => File::size($filePath),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return redirect()->route('backup.index')
            ->with('success', 'Backup creado exitosamente');

    } catch (\Exception $e) {
        Log::error('Error en backup', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        if (isset($filePath) && File::exists($filePath)) {
            File::delete($filePath);
        }

        return back()
            ->with('error', 'Error: ' . $e->getMessage())
            ->withInput();
    }
}

    public function download($filename)
    {
        try {
            $path = storage_path('app/backups/' . $filename);
            
            if (!File::exists($path)) {
                throw new \Exception('Archivo no encontrado');
            }

            // Forzar la descarga del archivo
            return response()->download($path, $filename, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al descargar backup', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Error descargando backup: ' . $e->getMessage());
        }
    }

    public function restore(Request $request)
    {
        try {
            $request->validate([
                'backup_file' => 'required|string'
            ]);
    
            $filename = $request->backup_file;
            $filePath = storage_path('app/backups/' . $filename);
    
            Log::info('Iniciando restauración de backup', [
                'filename' => $filename,
                'path' => $filePath
            ]);
    
            if (!File::exists($filePath)) {
                throw new \Exception('Archivo de backup no encontrado: ' . $filePath);
            }
    
            // Configuración de la base de datos
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');
    
            // Comando para MySQL
            $mysql = 'C:\\xampp\\mysql\\bin\\mysql.exe';
            
            if (!file_exists($mysql)) {
                throw new \Exception('No se encontró mysql.exe en la ruta especificada');
            }
    
            Log::info('Creando backup previo a restauración');
    
            // Crear backup antes de restaurar
            $backupAntes = storage_path('app/backups/before_restore_' . date('Y-m-d_H-i-s') . '.sql');
            $comandoBackup = sprintf(
                '"%s" --host=%s --user=%s --password=%s %s > "%s"',
                'C:\\xampp\\mysql\\bin\\mysqldump.exe',
                $host,
                $username,
                $password,
                $database,
                $backupAntes
            );
            
            exec($comandoBackup, $outputBackup, $returnVarBackup);
            
            if ($returnVarBackup !== 0) {
                throw new \Exception('Error al crear backup previo: ' . implode("\n", $outputBackup));
            }
    
            Log::info('Ejecutando restauración');
    
            // Restaurar el backup seleccionado
            $command = sprintf(
                '"%s" --host=%s --user=%s --password=%s %s < "%s" 2>&1',
                $mysql,
                $host,
                $username,
                $password,
                $database,
                $filePath
            );
    
            exec($command, $output, $returnVar);
    
            if ($returnVar !== 0) {
                $errorMsg = implode("\n", $output);
                Log::error('Error en la restauración', [
                    'output' => $output,
                    'returnVar' => $returnVar
                ]);
                throw new \Exception('Error al restaurar: ' . $errorMsg);
            }
    
            // Actualizar zona horaria
            DB::statement("SET time_zone = '-05:00'");
                
            Log::info('Restauración completada exitosamente');
    
            return redirect()->route('backup.index')
                ->with('success', 'Base de datos restaurada exitosamente');
    
        } catch (\Exception $e) {
            Log::error('Error al restaurar backup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Error restaurando backup: ' . $e->getMessage());
        }
    }

    public function delete($filename)
    {
        try {
            if (Storage::delete('backups/' . $filename)) {
                return redirect()->route('backup.index')
                    ->with('success', 'Backup eliminado exitosamente');
            }

            throw new \Exception('No se pudo eliminar el archivo');

        } catch (\Exception $e) {
            return back()->with('error', 'Error eliminando backup: ' . $e->getMessage());
        }
    }
}