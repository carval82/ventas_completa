<?php

namespace App\Services\Modules;

use App\Contracts\FacturacionElectronicaInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WorldOfficeModule implements FacturacionElectronicaInterface
{
    protected $baseUrl;
    protected $apiKey;
    protected $companyId;

    public function __construct()
    {
        $this->baseUrl = config('worldoffice.base_url', 'https://api.worldoffice.com/v1');
        $this->apiKey = config('worldoffice.api_key');
        $this->companyId = config('worldoffice.company_id');
    }

    public function enviarFactura(array $facturaData): array
    {
        try {
            if (!$this->validarConfiguracion()) {
                return [
                    'success' => false,
                    'error' => 'Configuración de World Office incompleta',
                    'proveedor' => 'worldoffice'
                ];
            }

            // Preparar datos para World Office
            $payload = $this->prepararFacturaParaWorldOffice($facturaData);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'X-Company-ID' => $this->companyId
            ])->post($this->baseUrl . '/invoices', $payload);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'mensaje' => 'Factura enviada exitosamente a World Office',
                    'numero_factura' => $data['invoice_number'] ?? null,
                    'id_worldoffice' => $data['id'] ?? null,
                    'proveedor' => 'worldoffice'
                ];
            } else {
                throw new \Exception('Error en respuesta de World Office: ' . $response->body());
            }

        } catch (\Exception $e) {
            Log::error('Error en WorldOfficeModule::enviarFactura', [
                'error' => $e->getMessage(),
                'factura_id' => $facturaData['id'] ?? null
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'proveedor' => 'worldoffice'
            ];
        }
    }

    public function consultarEstado(string $identificador): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'X-Company-ID' => $this->companyId
            ])->get($this->baseUrl . '/invoices/' . $identificador);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'estado' => $data['status'] ?? 'unknown',
                    'numero_factura' => $data['invoice_number'] ?? null,
                    'proveedor' => 'worldoffice'
                ];
            }

            return [
                'success' => false,
                'error' => 'No se pudo consultar el estado',
                'proveedor' => 'worldoffice'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'proveedor' => 'worldoffice'
            ];
        }
    }

    public function getConfiguracion(): array
    {
        return [
            'api_key' => $this->apiKey ? '***' : null,
            'company_id' => $this->companyId,
            'base_url' => $this->baseUrl,
            'activo' => $this->validarConfiguracion()
        ];
    }

    public function validarConfiguracion(): bool
    {
        return !empty($this->apiKey) && !empty($this->companyId);
    }

    public function getNombreProveedor(): string
    {
        return 'World Office';
    }

    public function sincronizarProductos(array $productos): array
    {
        try {
            $sincronizados = 0;
            $errores = [];

            foreach ($productos as $producto) {
                $payload = [
                    'code' => $producto['codigo'] ?? '',
                    'name' => $producto['nombre'] ?? '',
                    'description' => $producto['descripcion'] ?? '',
                    'price' => $producto['precio'] ?? 0,
                    'tax_rate' => 19, // IVA por defecto
                    'category' => $producto['categoria'] ?? 'General',
                    'active' => true
                ];

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'X-Company-ID' => $this->companyId
                ])->post($this->baseUrl . '/products', $payload);

                if ($response->successful()) {
                    $sincronizados++;
                } else {
                    $errores[] = $response->body();
                }
            }

            return [
                'success' => true,
                'sincronizados' => $sincronizados,
                'errores' => $errores,
                'proveedor' => 'worldoffice'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'proveedor' => 'worldoffice'
            ];
        }
    }

    public function sincronizarClientes(array $clientes): array
    {
        try {
            $sincronizados = 0;
            $errores = [];

            foreach ($clientes as $cliente) {
                $payload = [
                    'document_type' => 'CC',
                    'document_number' => $cliente['documento'] ?? '',
                    'name' => $cliente['nombre'] ?? '',
                    'email' => $cliente['email'] ?? '',
                    'phone' => $cliente['telefono'] ?? '',
                    'address' => $cliente['direccion'] ?? '',
                    'city' => $cliente['ciudad'] ?? '',
                    'active' => true
                ];

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'X-Company-ID' => $this->companyId
                ])->post($this->baseUrl . '/customers', $payload);

                if ($response->successful()) {
                    $sincronizados++;
                } else {
                    $errores[] = $response->body();
                }
            }

            return [
                'success' => true,
                'sincronizados' => $sincronizados,
                'errores' => $errores,
                'proveedor' => 'worldoffice'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'proveedor' => 'worldoffice'
            ];
        }
    }

    private function prepararFacturaParaWorldOffice(array $facturaData): array
    {
        return [
            'invoice_date' => $facturaData['fecha'] ?? date('Y-m-d'),
            'due_date' => $facturaData['fecha_vencimiento'] ?? date('Y-m-d', strtotime('+30 days')),
            'customer' => [
                'document_number' => $facturaData['cliente']['documento'] ?? '',
                'name' => $facturaData['cliente']['nombre'] ?? '',
                'email' => $facturaData['cliente']['email'] ?? '',
                'address' => $facturaData['cliente']['direccion'] ?? ''
            ],
            'items' => $this->prepararItemsParaWorldOffice($facturaData['detalles'] ?? []),
            'payment_method' => 'cash',
            'notes' => 'Factura generada desde sistema de ventas',
            'currency' => 'COP'
        ];
    }

    private function prepararItemsParaWorldOffice(array $detalles): array
    {
        $items = [];
        foreach ($detalles as $detalle) {
            $items[] = [
                'product_code' => $detalle['producto']['codigo'] ?? '',
                'description' => $detalle['producto']['nombre'] ?? '',
                'quantity' => $detalle['cantidad'] ?? 1,
                'unit_price' => $detalle['precio_unitario'] ?? 0,
                'discount_percentage' => 0,
                'tax_rate' => 19 // IVA
            ];
        }
        return $items;
    }
}
