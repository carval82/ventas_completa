<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\TestAlegraSimple::class,
        Commands\RestoreDataImproved::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * These schedules are used to run the console commands.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // BACKUPS AUTOMÁTICOS DESHABILITADOS PARA DISTRIBUCIÓN
        // Los backups se pueden crear manualmente desde la interfaz web
        
        /*
        // Backup diario a las 2:00 AM
        $schedule->command('backup:schedule --frequency=daily --send-email')
                 ->dailyAt('02:00')
                 ->name('backup-diario')
                 ->withoutOverlapping()
                 ->runInBackground();
        
        // Backup semanal los domingos a las 3:00 AM
        $schedule->command('backup:schedule --frequency=weekly --send-email')
                 ->weeklyOn(0, '03:00')
                 ->name('backup-semanal')
                 ->withoutOverlapping()
                 ->runInBackground();
        
        // Backup mensual el primer día del mes a las 4:00 AM
        $schedule->command('backup:schedule --frequency=monthly --send-email')
                 ->monthlyOn(1, '04:00')
                 ->name('backup-mensual')
                 ->withoutOverlapping()
                 ->runInBackground();
        */
        
        // Limpiar logs antiguos semanalmente (mantener activo)
        $schedule->command('log:clear')
                 ->weekly()
                 ->name('limpiar-logs')
                 ->runInBackground();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
} 