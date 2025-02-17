<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Services\AlegraService;
use Illuminate\Console\Command;

class SincronizarClientesAlegra extends Command
{
    protected $signature = 'alegra:sync-clientes';
    protected $description = 'Sincroniza clientes con Alegra';

    public function handle(AlegraService $alegra)
    {
        $this->info('Sincronizando clientes con Alegra...');

        // Obtener clientes de Alegra
        $response = $alegra->obtenerClientesAlegra();
        
        if (!$response['success']) {
            $this->error('Error al obtener clientes de Alegra: ' . $response['error']);
            return 1;
        }

        $clientesAlegra = collect($response['data']);
        $this->info('Clientes encontrados en Alegra: ' . $clientesAlegra->count());

        // Sincronizar clientes locales
        $clientes = Cliente::whereNull('codigo_alegra')->get();
        $this->info('Clientes locales sin vincular: ' . $clientes->count());

        $bar = $this->output->createProgressBar($clientes->count());
        $bar->start();

        foreach ($clientes as $cliente) {
            $result = $alegra->crearClienteAlegra($cliente);
            if (!$result['success']) {
                $this->error("\nError al sincronizar cliente {$cliente->nombres} {$cliente->apellidos}: {$result['error']}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->info("\n¡Sincronización completada!");
    }
} 