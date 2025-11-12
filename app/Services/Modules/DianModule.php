<?php

namespace App\Services\Modules;

use App\Contracts\FacturacionElectronicaInterface;
use App\Services\DianService;
use Illuminate\Support\Facades\Log;

class DianModule implements FacturacionElectronicaInterface
{
    protected $dianService;

    public function __construct()
    {
        $this->dianService = new DianService();
    }

    public function enviarFactura(array $facturaData): array
    {
        try {
            $resultado = $this->dianService->enviarFacturaElectronica($facturaData);
            
            return [
                'success' => $resultado['success'],
                'mensaje' => $resultado['mensaje'] ?? 'Factura enviada a DIAN',
                'cufe' => $resultado['cufe'] ?? null,
                'numero_factura' => $resultado['numero_factura'] ?? null,
                'proveedor' => 'dian'
            ];
        } catch (\Exception $e) {
            Log::error('Error en DianModule::enviarFactura', [
                'error' => $e->getMessage(),
                'factura_id' => $facturaData['id'] ?? null
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'proveedor' => 'dian'
            ];
        }
    }

    public function consultarEstado(string $identificador): array
    {
        return [
            'success' => true,
            'estado' => 'enviado',
            'cufe' => $identificador,
            'proveedor' => 'dian'
        ];
    }

    public function getConfiguracion(): array
    {
        return [
            'nit_empresa' => config('dian.nit_empresa'),
            'username' => config('dian.username'),
            'password' => config('dian.password') ? '***' : null,
            'test_mode' => config('dian.test_mode'),
            'activo' => $this->validarConfiguracion()
        ];
    }

    public function validarConfiguracion(): bool
    {
        return !empty(config('dian.nit_empresa')) && 
               !empty(config('dian.username')) && 
               !empty(config('dian.password'));
    }

    public function getNombreProveedor(): string
    {
        return 'DIAN Directo';
    }

    public function sincronizarProductos(array $productos): array
    {
        return [
            'success' => true,
            'mensaje' => 'DIAN no requiere sincronización de productos',
            'proveedor' => 'dian'
        ];
    }

    public function sincronizarClientes(array $clientes): array
    {
        return [
            'success' => true,
            'mensaje' => 'DIAN no requiere sincronización de clientes',
            'proveedor' => 'dian'
        ];
    }
}
