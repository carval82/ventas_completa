<?php

namespace App\Console\Commands;

use App\Models\Producto;
use App\Services\AlegraService;
use Illuminate\Console\Command;

class SincronizarProductosAlegra extends Command
{
    protected $signature = 'alegra:sync-productos';
    protected $description = 'Sincroniza productos con Alegra';

    public function handle(AlegraService $alegra)
    {
        $this->info('Sincronizando productos con Alegra...');

        // Obtener productos de Alegra
        $response = $alegra->obtenerProductosAlegra();
        
        if (!$response['success']) {
            $this->error('Error al obtener productos de Alegra: ' . $response['error']);
            return 1;
        }

        $productosAlegra = collect($response['data']);
        $this->info('Productos encontrados en Alegra: ' . $productosAlegra->count());

        // Sincronizar productos locales
        $productos = Producto::whereNull('codigo_alegra')->get();
        $this->info('Productos locales sin vincular: ' . $productos->count());

        $bar = $this->output->createProgressBar($productos->count());
        $bar->start();

        foreach ($productos as $producto) {
            $result = $alegra->crearProductoAlegra($producto);
            if (!$result['success']) {
                $this->error("\nError al sincronizar producto {$producto->nombre}: {$result['error']}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->info("\n¡Sincronización completada!");
    }
} 