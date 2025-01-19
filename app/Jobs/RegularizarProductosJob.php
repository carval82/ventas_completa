<?php

namespace App\Jobs;

use App\Models\Producto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RegularizarProductosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3; // Número de intentos
    public $timeout = 300; // Tiempo máximo de ejecución (5 minutos)
    protected $producto_id;

    /**
     * Create a new job instance.
     */
    public function __construct($producto_id = null)
    {
        $this->producto_id = $producto_id;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info('Iniciando job de regularización', [
                'producto_id' => $this->producto_id
            ]);

            if ($this->producto_id) {
                // Regularizar solo el producto específico
                $producto = Producto::findOrFail($this->producto_id);
                $producto->regularizarStock();
                
                Log::info('Regularización completada para producto específico', [
                    'producto_id' => $this->producto_id
                ]);
            } else {
                // Regularizar todos los productos
                Producto::regularizarProductos();
                
                Log::info('Regularización completada para todos los productos');
            }
        } catch (\Exception $e) {
            Log::error('Error en regularización', [
                'mensaje' => $e->getMessage(),
                'producto_id' => $this->producto_id,
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e; // Re-lanzar la excepción para que el job se marque como fallido
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Falló el job de regularización', [
            'producto_id' => $this->producto_id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}