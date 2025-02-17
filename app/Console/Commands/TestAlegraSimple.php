<?php

namespace App\Console\Commands;

use App\Services\AlegraService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestAlegraSimple extends Command
{
    protected $signature = 'alegra:test';
    protected $description = 'Prueba la conexión con Alegra API';

    public function handle()
    {
        $this->info('Probando conexión con Alegra API...');
        
        // Leer directamente el archivo .env
        $envFile = file_get_contents(base_path('.env'));
        $this->info('Contenido del .env relacionado con Alegra:');
        foreach(explode("\n", $envFile) as $line) {
            if(strpos($line, 'ALEGRA_') !== false) {
                $this->info($line);
            }
        }
        
        $this->info("\nConfiguración actual:");
        $this->table(
            ['Variable', 'Valor'],
            [
                ['URL', config('alegra.url')],
                ['Usuario', config('alegra.user')],
                ['Token', substr(config('alegra.token'), 0, 5) . '...'],
                ['ENV_USER', env('ALEGRA_USER')],
                ['ENV_TOKEN', substr(env('ALEGRA_TOKEN'), 0, 5) . '...'],
                ['Archivo .env existe', file_exists(base_path('.env')) ? 'Sí' : 'No'],
                ['.env es legible', is_readable(base_path('.env')) ? 'Sí' : 'No']
            ]
        );

        try {
            $alegraService = new AlegraService();
            $response = $alegraService->testConnection();

            if ($response['success']) {
                $this->info('✅ Conexión exitosa!');
                $this->info('Información de la empresa:');
                $this->table(
                    ['Campo', 'Valor'],
                    collect($response['data'])
                        ->map(fn($value, $key) => [$key, is_array($value) ? json_encode($value) : $value])
                        ->toArray()
                );
            } else {
                $this->error('❌ Error de conexión');
                $this->error($response['error']);
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('Error al probar conexión con Alegra', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
} 