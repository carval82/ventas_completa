<?php

namespace App\Services\Modules;

use App\Contracts\FacturacionElectronicaInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SiigoModule implements FacturacionElectronicaInterface
{
    protected $baseUrl;
    protected $username;
    protected $accessKey;

    public function __construct()
    {
        $this->baseUrl = config('siigo.base_url', 'https://api.siigo.com/v1');
        $this->username = config('siigo.username');
        $this->accessKey = config('siigo.access_key');
    }

    public function enviarFactura(array $facturaData): array
    {
        try {
            if (!$this->validarConfiguracion()) {
                return [
                    'success' => false,
                    'error' => 'Configuración de Siigo incompleta',
                    'proveedor' => 'siigo'
                ];
            }

            // Preparar datos para Siigo
            $payload = $this->prepararFacturaParaSiigo($facturaData);

            $response = Http::withHeaders([
                'Authorization' => $this->username . ':' . $this->accessKey,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/invoices', $payload);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'mensaje' => 'Factura enviada exitosamente a Siigo',
                    'numero_factura' => $data['number'] ?? null,
                    'id_siigo' => $data['id'] ?? null,
                    'proveedor' => 'siigo'
                ];
            } else {
                throw new \Exception('Error en respuesta de Siigo: ' . $response->body());
            }

        } catch (\Exception $e) {
            Log::error('Error en SiigoModule::enviarFactura', [
                'error' => $e->getMessage(),
                'factura_id' => $facturaData['id'] ?? null
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'proveedor' => 'siigo'
            ];
        }
    }

    public function consultarEstado(string $identificador): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->username . ':' . $this->accessKey
            ])->get($this->baseUrl . '/invoices/' . $identificador);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'estado' => $data['status'] ?? 'unknown',
                    'numero_factura' => $data['number'] ?? null,
                    'proveedor' => 'siigo'
                ];
            }

            return [
                'success' => false,
                'error' => 'No se pudo consultar el estado',
                'proveedor' => 'siigo'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'proveedor' => 'siigo'
            ];
        }
    }

    public function getConfiguracion(): array
    {
        return [
            'username' => $this->username,
            'access_key' => $this->accessKey ? '***' : null,
            'base_url' => $this->baseUrl,
            'activo' => $this->validarConfiguracion()
        ];
    }

    public function validarConfiguracion(): bool
    {
        return !empty($this->username) && !empty($this->accessKey);
    }

    public function getNombreProveedor(): string
    {
        return 'Siigo';
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
                    'account_group' => 361,
                    'type' => 'Product',
                    'stock_control' => true,
                    'tax_classification' => 'Taxed',
                    'tax_included' => false,
                    'tax_consumption_value' => 0,
                    'taxes' => [
                        [
                            'id' => 13156
                        ]
                    ],
                    'prices' => [
                        [
                            'currency_code' => 'COP',
                            'price_list' => [
                                [
                                    'position' => 1,
                                    'value' => $producto['precio'] ?? 0
                                ]
                            ]
                        ]
                    ]
                ];

                $response = Http::withHeaders([
                    'Authorization' => $this->username . ':' . $this->accessKey,
                    'Content-Type' => 'application/json'
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
                'proveedor' => 'siigo'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'proveedor' => 'siigo'
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
                    'type' => 'Customer',
                    'person_type' => 'Person',
                    'id_type' => 'CC',
                    'identification' => $cliente['documento'] ?? '',
                    'name' => [$cliente['nombre'] ?? ''],
                    'commercial_name' => $cliente['nombre'] ?? '',
                    'branch_office' => 0,
                    'active' => true
                ];

                $response = Http::withHeaders([
                    'Authorization' => $this->username . ':' . $this->accessKey,
                    'Content-Type' => 'application/json'
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
                'proveedor' => 'siigo'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'proveedor' => 'siigo'
            ];
        }
    }

    private function prepararFacturaParaSiigo(array $facturaData): array
    {
        return [
            'document' => [
                'id' => 24446
            ],
            'date' => $facturaData['fecha'] ?? date('Y-m-d'),
            'customer' => [
                'identification' => $facturaData['cliente']['documento'] ?? '',
                'branch_office' => 0
            ],
            'cost_center' => 235,
            'seller' => 629,
            'observations' => 'Factura generada desde sistema de ventas',
            'items' => $this->prepararItemsParaSiigo($facturaData['detalles'] ?? []),
            'payments' => [
                [
                    'id' => 5636,
                    'value' => $facturaData['total'] ?? 0,
                    'due_date' => date('Y-m-d')
                ]
            ]
        ];
    }

    private function prepararItemsParaSiigo(array $detalles): array
    {
        $items = [];
        foreach ($detalles as $detalle) {
            $items[] = [
                'code' => $detalle['producto']['codigo'] ?? '',
                'description' => $detalle['producto']['nombre'] ?? '',
                'quantity' => $detalle['cantidad'] ?? 1,
                'price' => $detalle['precio_unitario'] ?? 0,
                'discount' => 0,
                'taxes' => [
                    [
                        'id' => 13156
                    ]
                ]
            ];
        }
        return $items;
    }
}
