<?php

namespace App\Console\Commands;

use App\Http\Services\AlegraService;
use Illuminate\Console\Command;

class TestAlegraConnection extends Command
{
    protected $signature = 'alegra:test';
    protected $description = 'Test Alegra API connection';

    public function handle()
    {
        $this->info('Probando conexión con Alegra API...');

        try {
            $alegraService = new AlegraService();
            $response = $alegraService->obtenerProductos();

            if ($response['success']) {
                $this->info('✓ Conexión exitosa!');
                $this->info('Productos encontrados: ' . count($response['data']));
                
                // Mostrar algunos productos de ejemplo
                if (!empty($response['data'])) {
                    $this->table(
                        ['ID', 'Nombre', 'Referencia'],
                        collect($response['data'])->take(5)->map(fn($item) => [
                            $item['id'],
                            $item['name'],
                            $item['reference'] ?? 'N/A'
                        ])
                    );
                }
            } else {
                $this->error('✗ Error de conexión: ' . ($response['error'] ?? 'Error desconocido'));
            }
        } catch (\Exception $e) {
            $this->error('✗ Error: ' . $e->getMessage());
        }
    }
}