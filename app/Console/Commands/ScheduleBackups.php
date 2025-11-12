<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class ScheduleBackups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:schedule 
                            {--frequency=daily : Frecuencia del backup (daily, weekly, monthly)}
                            {--send-email : Enviar backup por correo electrónico}
                            {--force : Forzar ejecución independientemente de la configuración}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta backups programados según la configuración establecida';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $frequency = $this->option('frequency');
            $sendEmail = $this->option('send-email');
            $force = $this->option('force');
            
            $this->info("Iniciando backup programado - Frecuencia: {$frequency}");
            
            // Verificar si los backups automáticos están habilitados
            if (!$force) {
                $backupEnabled = Setting::where('key', 'backup_auto_enabled')->first();
                if (!$backupEnabled || !$backupEnabled->value) {
                    $this->info('Los backups automáticos están deshabilitados');
                    return 0;
                }
            }
            
            // Verificar si es momento de hacer backup según la frecuencia
            if (!$force && !$this->shouldRunBackup($frequency)) {
                $this->info("No es momento de ejecutar backup con frecuencia: {$frequency}");
                return 0;
            }
            
            // Ejecutar el backup
            $exitCode = Artisan::call('backup:database', [
                '--send-email' => $sendEmail
            ]);
            
            if ($exitCode === 0) {
                $this->info('Backup programado ejecutado exitosamente');
                
                // Actualizar la fecha del último backup
                Setting::updateOrCreate(
                    ['key' => "backup_last_{$frequency}"],
                    ['value' => now()->toDateTimeString()]
                );
                
                Log::info('Backup programado ejecutado', [
                    'frequency' => $frequency,
                    'send_email' => $sendEmail,
                    'timestamp' => now()
                ]);
            } else {
                $this->error('Error al ejecutar backup programado');
                Log::error('Error en backup programado', [
                    'frequency' => $frequency,
                    'exit_code' => $exitCode
                ]);
            }
            
            return $exitCode;
            
        } catch (\Exception $e) {
            $this->error('Error en backup programado: ' . $e->getMessage());
            Log::error('Excepción en backup programado', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
    
    /**
     * Determina si debe ejecutarse el backup según la frecuencia
     */
    private function shouldRunBackup($frequency)
    {
        $lastBackup = Setting::where('key', "backup_last_{$frequency}")->first();
        
        if (!$lastBackup) {
            // Si nunca se ha hecho backup, ejecutar
            return true;
        }
        
        $lastBackupDate = \Carbon\Carbon::parse($lastBackup->value);
        $now = now();
        
        switch ($frequency) {
            case 'daily':
                return $now->diffInDays($lastBackupDate) >= 1;
                
            case 'weekly':
                return $now->diffInWeeks($lastBackupDate) >= 1;
                
            case 'monthly':
                return $now->diffInMonths($lastBackupDate) >= 1;
                
            default:
                return false;
        }
    }
}
