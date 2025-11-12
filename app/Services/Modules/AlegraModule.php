<?php

namespace App\Services\Modules;

use App\Contracts\FacturacionElectronicaInterface;
use App\Services\AlegraService;
use Illuminate\Support\Facades\Log;

class AlegraModule implements FacturacionElectronicaInterface
{
    protected $alegraService;

    public function __construct()
    {
        $this->alegraService = new AlegraService();
    }

    public function enviarFactura(array $facturaData): array
    {
        try {
            // Usar el método existente de AlegraService
            $resultado = $this->alegraService->abrirFacturaDirecto($facturaData['id']);
            
            return [
                'success' => $resultado['success'] ?? false,
                'mensaje' => $resultado['mensaje'] ?? 'Factura procesada',
                'numero_factura' => $resultado['numero_factura'] ?? null,
                'proveedor' => 'alegra'
            ];
        } catch (\Exception $e) {
            Log::error('Error en AlegraModule::enviarFactura', [
                'error' => $e->getMessage(),
                'factura_id' => $facturaData['id'] ?? null
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'proveedor' => 'alegra'
            ];
        }
    }

    public function consultarEstado(string $identificador): array
    {
        // Implementar consulta de estado en Alegra
        return [
            'success' => true,
            'estado' => 'enviado',
            'proveedor' => 'alegra'
        ];
    }

    public function getConfiguracion(): array
    {
        return [
            'usuario' => config('alegra.usuario'),
            'token' => config('alegra.token') ? '***' : null,
            'url_base' => config('alegra.url_base'),
            'activo' => !empty(config('alegra.usuario')) && !empty(config('alegra.token'))
        ];
    }

    public function validarConfiguracion(): bool
    {
        return !empty(config('alegra.usuario')) && !empty(config('alegra.token'));
    }

    public function getNombreProveedor(): string
    {
        return 'Alegra';
    }

    public function sincronizarProductos(array $productos): array
    {
        try {
            $resultados = [];
            foreach ($productos as $producto) {
                $resultado = $this->alegraService->sincronizarProducto($producto);
                $resultados[] = $resultado;
            }

            return [
                'success' => true,
                'sincronizados' => count($resultados),
                'resultados' => $resultados,
                'proveedor' => 'alegra'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'proveedor' => 'alegra'
            ];
        }
    }

    public function sincronizarClientes(array $clientes): array
    {
        try {
            $resultados = [];
            foreach ($clientes as $cliente) {
                $resultado = $this->alegraService->sincronizarCliente($cliente);
                $resultados[] = $resultado;
            }

            return [
                'success' => true,
                'sincronizados' => count($resultados),
                'resultados' => $resultados,
                'proveedor' => 'alegra'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'proveedor' => 'alegra'
            ];
        }
    }
}
