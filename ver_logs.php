<?php

// Cargar el framework Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Verificar los logs
echo "Revisando los últimos logs relacionados con Alegra:\n";
$logFiles = [
    __DIR__ . '/storage/logs/laravel.log',
    __DIR__ . '/storage/logs/alegra_api.log'
];

foreach ($logFiles as $logFile) {
    if (file_exists($logFile)) {
        echo "\nContenido de " . basename($logFile) . ":\n";
        $logs = file_get_contents($logFile);
        $logLines = explode("\n", $logs);
        
        // Buscar líneas relacionadas con errores en la DIAN
        $dianErrors = [];
        foreach ($logLines as $line) {
            if (stripos($line, 'dian') !== false && stripos($line, 'error') !== false) {
                $dianErrors[] = $line;
            }
        }
        
        // Mostrar los últimos 10 errores relacionados con la DIAN
        $lastDianErrors = array_slice($dianErrors, -10);
        if (!empty($lastDianErrors)) {
            echo "Últimos errores relacionados con la DIAN:\n";
            foreach ($lastDianErrors as $line) {
                echo $line . "\n";
            }
        } else {
            echo "No se encontraron errores específicos de la DIAN.\n";
        }
        
        // Mostrar las últimas 20 líneas del log
        $lastLogs = array_slice($logLines, -20);
        echo "\nÚltimas 20 líneas del log:\n";
        foreach ($lastLogs as $line) {
            if (!empty(trim($line))) {
                echo $line . "\n";
            }
        }
    } else {
        echo "No se encontró el archivo de log: " . basename($logFile) . "\n";
    }
}
