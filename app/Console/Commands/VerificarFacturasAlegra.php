<?php

namespace App\Console\Commands;

use App\Models\Venta;
use App\Services\AlegraService;
use Illuminate\Console\Command;

class VerificarFacturasAlegra extends Command
{
    protected $signature = 'alegra:check-invoices';
    protected $description = 'Verifica el estado de las facturas en Alegra';

    public function handle(AlegraService $alegra)
    {
        $ventas = Venta::whereNotNull('factura_alegra_id')
            ->where('estado_factura_dian', '!=', 'AUTORIZADO')
            ->get();

        $this->info("Verificando {$ventas->count()} facturas...");

        foreach ($ventas as $venta) {
            $response = $alegra->obtenerEstadoFactura($venta->factura_alegra_id);
            
            if ($response['success']) {
                $estado = $response['data']['status'] ?? null;
                $venta->estado_factura_dian = $estado;
                $venta->save();
                
                $this->info("Factura {$venta->numero_factura}: {$estado}");
            } else {
                $this->error("Error al verificar factura {$venta->numero_factura}: {$response['error']}");
            }
        }
    }
} 