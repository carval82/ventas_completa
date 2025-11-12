<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RestoreDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:restore {file : Nombre del archivo de backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restaura la base de datos desde un archivo de backup';

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
                return 1;
            }
            
            // Crear un backup previo por seguridad
            $this->call('backup:database');
            
            $this->info('Iniciando restauración completa de la base de datos...');
            Log::info('Iniciando restauración completa', ['filename' => $filename]);
            
            // Determinar si es un archivo ZIP o SQL
            $extension = pathinfo($backupPath, PATHINFO_EXTENSION);
            $sqlFilePath = $backupPath;
            $tempSqlFile = null;
            
            if ($extension === 'zip') {
                $this->info('Detectado archivo ZIP, extrayendo contenido SQL...');
                
                $zip = new \ZipArchive();
                if ($zip->open($backupPath) === TRUE) {
                    // Buscar el archivo SQL dentro del ZIP
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $zipFilename = $zip->getNameIndex($i);
                        if (pathinfo($zipFilename, PATHINFO_EXTENSION) === 'sql') {
                            // Crear archivo temporal
                            $tempSqlFile = storage_path('app/backups/temp_restore_' . time() . '.sql');
                            $sqlContent = $zip->getFromIndex($i);
                            File::put($tempSqlFile, $sqlContent);
                            $sqlFilePath = $tempSqlFile;
                            break;
                        }
                    }
                    $zip->close();
                    
                    if (!$tempSqlFile) {
                        throw new \Exception('No se encontró archivo SQL dentro del ZIP');
                    }
                } else {
                    throw new \Exception('No se pudo abrir el archivo ZIP');
                }
            }
            
            // Configuración de la base de datos
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');
            
            // Ruta de mysql en XAMPP
            $mysql = 'C:\\xampp\\mysql\\bin\\mysql.exe';
            
            // Verificar que existe mysql
            if (!file_exists($mysql)) {
                throw new \Exception('No se encontró mysql en la ruta especificada');
            }
            
            // Construir el comando usando cmd para manejar redirección en Windows
            $command = "cmd /c \"\"{$mysql}\" --host={$host} --user={$username} --password={$password} {$database} < \"{$sqlFilePath}\"\"";
            
            $this->info('Ejecutando comando de restauración...');
            Log::info('Ejecutando comando de restauración', [
                'command' => str_replace($password, '****', $command),
                'sql_file' => $sqlFilePath
            ]);
            
            // Ejecutar comando
            exec($command, $output, $returnCode);
            
            // Limpiar archivo temporal si se creó
            if ($tempSqlFile && File::exists($tempSqlFile)) {
                File::delete($tempSqlFile);
                $this->info('Archivo temporal eliminado');
            }
            
            if ($returnCode !== 0) {
                Log::error('Error en mysql', [
                    'output' => $output,
                    'code' => $returnCode
                ]);
                throw new \Exception('Error al restaurar la base de datos: ' . implode("\n", $output));
            }
            
            $this->info('Base de datos restaurada exitosamente');
            Log::info('Base de datos restaurada exitosamente', ['filename' => $filename]);
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error al restaurar la base de datos: ' . $e->getMessage());
            Log::error('Error al restaurar la base de datos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
}
