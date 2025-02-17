<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AlegraService
{
    protected $baseUrl;
    protected $auth;
    protected $http;

    public function __construct()
    {
        $this->baseUrl = config('alegra.base_url');
        
        if (empty($this->baseUrl)) {
            throw new \RuntimeException('La URL base de Alegra no está configurada');
        }
        
        $user = config('alegra.user');
        $token = config('alegra.token');
        
        if (empty($user) || empty($token)) {
            throw new \RuntimeException('Las credenciales de Alegra no están configuradas');
        }
        
        // Alegra usa Basic Auth con email:token
        $this->auth = base64_encode($user . ':' . $token);
        
        Log::info('Configurando cliente HTTP Alegra', [
            'base_url' => $this->baseUrl,
            'auth_header' => 'Basic ' . $this->auth
        ]);

        $this->http = Http::withHeaders([
            'Authorization' => 'Basic ' . $this->auth,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->baseUrl($this->baseUrl);

        // Verificar conexión
        $this->testConnection();
    }

    /**
     * Verificar conexión con Alegra
     */
    private function testConnection()
    {
        try {
            $response = $this->http->get('/company');
            
            Log::info('Test de conexión Alegra', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException(
                    'Error al conectar con Alegra: ' . 
                    ($response->json()['message'] ?? 'Error ' . $response->status())
                );
            }
        } catch (\Exception $e) {
            Log::error('Error en test de conexión Alegra', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Crear una factura en Alegra
     * @param array $datos Datos de la factura
     * @return array
     */
    public function crearFactura(array $datos)
    {
        try {
            $response = Http::post('http://localhost:8000/invoices', [
                'date' => now()->format('Y-m-d'),
                'dueDate' => now()->format('Y-m-d'),
                'client_id' => $datos['client']['id'],
                'items' => array_map(function($item) {
                    return [
                        'id' => $item['id'],
                        'price' => floatval($item['price']),
                        'quantity' => intval($item['quantity']),
                        'description' => $item['description']
                    ];
                }, $datos['items'])
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['detail'] ?? 'Error desconocido'
            ];

        } catch (\Exception $e) {
            Log::error('Error en servicio Python', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtener lista de productos
     */
    public function obtenerProductos()
    {
        try {
            $response = $this->http->get('/api/v1/items');
            return $response->successful() 
                ? ['success' => true, 'data' => $response->json()]
                : ['success' => false, 'error' => $response->json()['message'] ?? 'Error'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crear un producto
     */
    public function crearProducto($data)
    {
        try {
            $response = $this->http->post('/api/v1/items', $data);
            return $response->successful() 
                ? ['success' => true, 'data' => $response->json()]
                : ['success' => false, 'error' => $response->json()['message'] ?? 'Error'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtener una factura específica
     */
    public function obtenerFactura($id)
    {
        try {
            $response = $this->http->get("/api/v1/invoices/{$id}");
            return $response->successful() 
                ? ['success' => true, 'data' => $response->json()]
                : ['success' => false, 'error' => $response->json()['message'] ?? 'Error'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Verifica si la facturación electrónica está habilitada y configurada
     */
    public function verificarFacturacionElectronica()
    {
        try {
            $response = $this->http->get('/company');
            
            if ($response->successful()) {
                $company = $response->json();
                
                // Verificar configuración de FE
                $settings = $company['settings'] ?? [];
                
                return [
                    'success' => true,
                    'habilitada' => $settings['electronicInvoicing'] ?? false,
                    'configurada' => ($settings['electronicInvoicingWizardProgress'] ?? '') === 'completed',
                    'version' => $settings['electronicInvoicingVersion'] ?? null,
                    'settings' => $settings
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al verificar facturación electrónica'
            ];

        } catch (\Exception $e) {
            Log::error('Error verificando facturación electrónica', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Buscar cliente por identificación
     */
    public function buscarCliente($identificacion)
    {
        try {
            Log::info('Buscando cliente en Alegra', [
                'identificacion' => $identificacion
            ]);

            // Buscar cliente exacto por identificación
            $response = $this->http->get('/contacts', [
                'query' => [
                    'identification' => $identificacion,
                    'metadata' => 'true', // Para obtener más detalles
                    'format' => 'plain' // Para búsqueda exacta
                ]
            ]);

            Log::info('Respuesta búsqueda cliente', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            if ($response->successful()) {
                $clientes = $response->json();
                
                // Buscar coincidencia exacta
                $cliente = collect($clientes)->first(function($c) use ($identificacion) {
                    return $c['identification'] === $identificacion;
                });

                if ($cliente) {
                    return ['success' => true, 'data' => $cliente];
                }

                // Si no existe, retornamos error para crear nuevo
                return ['success' => false, 'error' => 'Cliente no encontrado'];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al buscar cliente'
            ];

        } catch (\Exception $e) {
            Log::error('Error al buscar cliente', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crear cliente en Alegra
     */
    public function crearCliente($datos)
    {
        try {
            Log::info('Creando cliente en Alegra', [
                'datos' => $datos
            ]);

            $clienteData = [
                'name' => $datos['nombres'] . ' ' . ($datos['apellidos'] ?? ''),
                'identification' => $datos['cedula'],
                'email' => $datos['email'],
                'phone' => $datos['telefono'],
                'address' => [
                    'address' => $datos['direccion']
                ],
                'type' => 'client'
            ];

            $response = $this->http->post('/contacts', $clienteData);

            Log::info('Respuesta creación cliente', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            return $response->successful()
                ? ['success' => true, 'data' => $response->json()]
                : ['success' => false, 'error' => $response->json()['message'] ?? 'Error al crear cliente'];

        } catch (\Exception $e) {
            Log::error('Error al crear cliente', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtener numeraciones activas
     */
    public function obtenerNumeraciones()
    {
        try {
            Log::info('Obteniendo numeraciones de Alegra');

            $response = $this->http->get('/number-templates');
            
            Log::info('Respuesta numeraciones', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            if ($response->successful()) {
                $numeraciones = $response->json();
                
                // Buscar numeración electrónica activa
                $numeracionFE = collect($numeraciones)->first(function($numeracion) {
                    return ($numeracion['status'] ?? '') === 'active' && 
                           ($numeracion['documentType'] ?? '') === 'invoice' &&
                           ($numeracion['isElectronic'] ?? false) === true;
                });

                if (!$numeracionFE) {
                    Log::warning('No se encontró numeración FE activa', [
                        'numeraciones' => collect($numeraciones)
                            ->filter(fn($n) => ($n['status'] ?? '') === 'active')
                            ->map(fn($n) => [
                                'id' => $n['id'],
                                'name' => $n['name'],
                                'documentType' => $n['documentType'],
                                'isElectronic' => $n['isElectronic'] ?? false,
                                'status' => $n['status']
                            ])
                            ->toArray()
                    ]);

                    return [
                        'success' => false,
                        'error' => 'No se encontró una numeración activa para factura electrónica'
                    ];
                }

                Log::info('Numeración FE encontrada', [
                    'numeracion' => $numeracionFE
                ]);

                return [
                    'success' => true,
                    'data' => $numeracionFE
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al obtener numeraciones'
            ];

        } catch (\Exception $e) {
            Log::error('Error al obtener numeraciones', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtener última factura electrónica exitosa
     */
    public function obtenerUltimaFactura()
    {
        try {
            Log::info('Consultando factura específica #93');
            
            // Consulta directa a la factura #93
            $response = $this->http->get('/invoices/93');

            if ($response->successful()) {
                $factura = $response->json();
                
                // Log detallado de toda la estructura
                Log::info('Estructura completa de factura', $factura);
                
                // Log específico del paymentForm
                if (isset($factura['paymentForm'])) {
                    Log::info('PaymentForm encontrado', [
                        'paymentForm' => $factura['paymentForm']
                    ]);
                } else {
                    Log::warning('Factura no tiene paymentForm');
                }

                return [
                    'success' => true,
                    'data' => $factura
                ];
            }

            Log::error('Error al obtener factura', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al consultar factura'
            ];

        } catch (\Exception $e) {
            Log::error('Error en consulta', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}