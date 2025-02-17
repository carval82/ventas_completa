<?php

namespace App\Services;

use App\Models\Venta;
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
        
        // Alegra usa Basic Auth con email:token
        $this->auth = base64_encode(
            config('alegra.user') . ':' . config('alegra.token')
        );
        
        $this->http = Http::withHeaders([
            'Authorization' => 'Basic ' . $this->auth,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->baseUrl($this->baseUrl);
    }

    /**
     * Crear una factura en Alegra
     * @param array $datos Datos de la factura
     * @return array
     */
    public function crearFactura(array $datos)
    {
        try {
            Log::info('Iniciando creación de factura en Alegra', [
                'data' => $datos
            ]);

            // Si es factura electrónica, agregar configuración específica
            if ($datos['useElectronicInvoice'] ?? false) {
                Log::info('Configurando factura electrónica');
                
                $datos['numberTemplate'] = [
                    'id' => config('alegra.fe_template_id'),
                    'prefix' => 'FE'
                ];
                
                $datos['stamp'] = [
                    'generateStamp' => true,
                    'generateQrCode' => true
                ];
            }

            $response = $this->http->post('/api/v1/invoices', $datos);
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::error('Error en respuesta de Alegra', [
                'error' => $response->json(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al crear factura'
            ];

        } catch (\Exception $e) {
            Log::error('Excepción en servicio Alegra', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function testConnection()
    {
        try {
            Log::info('Intentando conexión con Alegra', [
                'url' => $this->baseUrl . '/company',
                'auth_header' => 'Basic ' . $this->auth
            ]);

            $response = $this->http->get('/company');
            
            Log::info('Respuesta de Alegra', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $response->successful() 
                ? ['success' => true, 'data' => $response->json()]
                : ['success' => false, 'error' => $response->json()['message'] ?? 'Error'];
        } catch (\Exception $e) {
            Log::error('Error en conexión con Alegra', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function verificarResolucion($tipoFactura)
    {
        try {
            $response = $this->http->get('/number-templates');
            
            if (!$response->successful()) {
                Log::error('Error al consultar resoluciones de Alegra', [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return [
                    'success' => false,
                    'error' => 'Error al consultar resoluciones'
                ];
            }

            $resoluciones = $response->json();
            
            // Log para ver qué resoluciones nos está devolviendo Alegra
            Log::info('Resoluciones obtenidas de Alegra', [
                'tipo_factura_solicitada' => $tipoFactura,
                'resoluciones' => $resoluciones
            ]);

            // Buscar resolución activa según tipo de factura
            $resolucionActiva = collect($resoluciones)->first(function($resolucion) use ($tipoFactura) {
                $esElectronica = $tipoFactura === 'electronica';
                $esPOS = $tipoFactura === 'pos';
                
                // Log para cada resolución que evaluamos
                Log::info('Evaluando resolución', [
                    'resolucion' => $resolucion,
                    'tipo_factura' => $tipoFactura,
                    'es_electronica' => $esElectronica,
                    'es_pos' => $esPOS,
                    'status' => $resolucion['status'] ?? 'no_status',
                    'type' => $resolucion['type'] ?? 'no_type'
                ]);
                
                $estaActiva = ($resolucion['status'] ?? '') === 'active';
                
                return $estaActiva && 
                       (($esElectronica && ($resolucion['type'] ?? '') === 'electronic-invoice') ||
                        ($esPOS && ($resolucion['type'] ?? '') === 'POS') ||
                        (!$esElectronica && !$esPOS && ($resolucion['type'] ?? '') === 'standard'));
            });

            if (!$resolucionActiva) {
                Log::warning('No se encontró resolución activa', [
                    'tipo_factura' => $tipoFactura,
                    'resoluciones_disponibles' => $resoluciones
                ]);
                return [
                    'success' => false,
                    'error' => "No hay resolución activa para facturas tipo: {$tipoFactura}"
                ];
            }

            return [
                'success' => true,
                'data' => $resolucionActiva
            ];

        } catch (\Exception $e) {
            Log::error('Error al verificar resolución', [
                'error' => $e->getMessage(),
                'tipo_factura' => $tipoFactura,
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => 'Error al verificar resolución: ' . $e->getMessage()
            ];
        }
    }

    protected function mapearFormaPago($metodo_pago)
    {
        switch ($metodo_pago) {
            case 'efectivo':
                return [
                    "id" => 1,             // ID de la forma de pago (1 = Contado)
                    "paymentMethod" => 10,  // 10 = Efectivo según DIAN
                    "dueDate" => now()->format('Y-m-d')
                ];
            case 'credito':
                return [
                    "id" => 2,             // ID de la forma de pago (2 = Crédito)
                    "paymentMethod" => 1,   // 1 = Instrumento no definido según DIAN
                    "dueDate" => now()->addDays(30)->format('Y-m-d')
                ];
            default:
                return [
                    "id" => 1,
                    "paymentMethod" => 10,
                    "dueDate" => now()->format('Y-m-d')
                ];
        }
    }

    private function obtenerPlantilla($tipoFactura, $plantilla)
    {
        switch ($tipoFactura) {
            case 'electronica':
                return config('alegra.plantilla_electronica');
            case 'pos':
                return config('alegra.plantilla_pos');
            default:
                return config('alegra.plantilla_normal');
        }
    }

    public function obtenerProductosAlegra()
    {
        try {
            $response = $this->http->get('/items');
            
            if ($response->successful()) {
                Log::info('Productos obtenidos de Alegra', [
                    'count' => count($response->json())
                ]);
                return ['success' => true, 'data' => $response->json()];
            }

            Log::error('Error al obtener productos de Alegra', [
                'response' => $response->json()
            ]);
            return ['success' => false, 'error' => $response->json()['message'] ?? 'Error al obtener productos'];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener productos de Alegra', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function crearProductoAlegra($producto)
    {
        try {
            $response = $this->http->post('/items', [
                'name' => $producto->nombre,
                'description' => $producto->descripcion ?? $producto->nombre,
                'reference' => $producto->codigo,
                'price' => $producto->precio_venta
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $producto->codigo_alegra = $data['id'];
                $producto->save();

                Log::info('Producto creado en Alegra', [
                    'producto_id' => $producto->id,
                    'alegra_id' => $data['id']
                ]);
                return ['success' => true, 'data' => $data];
            }

            Log::error('Error al crear producto en Alegra', [
                'producto_id' => $producto->id,
                'response' => $response->json()
            ]);
            return ['success' => false, 'error' => $response->json()['message'] ?? 'Error al crear producto'];

        } catch (\Exception $e) {
            Log::error('Excepción al crear producto en Alegra', [
                'producto_id' => $producto->id,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function obtenerClientesAlegra()
    {
        try {
            $response = $this->http->get('/contacts?type=client');
            
            if ($response->successful()) {
                Log::info('Clientes obtenidos de Alegra', [
                    'count' => count($response->json())
                ]);
                return ['success' => true, 'data' => $response->json()];
            }

            Log::error('Error al obtener clientes de Alegra', [
                'response' => $response->json()
            ]);
            return ['success' => false, 'error' => $response->json()['message'] ?? 'Error al obtener clientes'];

        } catch (\Exception $e) {
            Log::error('Excepción al obtener clientes de Alegra', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function crearClienteAlegra($cliente)
    {
        try {
            $nombres = explode(' ', trim($cliente->nombres));
            $apellidos = explode(' ', trim($cliente->apellidos));

            $data = [
                'nameObject' => [
                    'firstName' => ucwords(strtolower($nombres[0] ?? '')),
                    'secondName' => ucwords(strtolower($nombres[1] ?? '')),
                    'lastName' => ucwords(strtolower($apellidos[0] ?? '')),
                    'secondLastName' => ucwords(strtolower($apellidos[1] ?? ''))
                ],
                'identificationObject' => [
                    'type' => 'CC',
                    'number' => $cliente->cedula,
                    'nationalityKindOfPerson' => 'NATIONAL'
                ],
                'email' => $cliente->email ?: 'sin@email.com',
                'phonePrimary' => $cliente->telefono ?: '0000000',
                'type' => 'client',
                'kindOfPerson' => 'PERSON_ENTITY',
                'regime' => 'SIMPLIFIED_REGIME'
            ];

            Log::info('Intentando crear cliente en Alegra', [
                'cliente' => $cliente->toArray(),
                'data_alegra' => $data
            ]);

            $response = $this->http->post('/contacts', $data);

            if ($response->successful()) {
                $data = $response->json();
                $cliente->codigo_alegra = $data['id'];
                $cliente->save();

                Log::info('Cliente creado en Alegra', [
                    'cliente_id' => $cliente->id,
                    'alegra_id' => $data['id']
                ]);
                return ['success' => true, 'data' => $data];
            }

            Log::error('Error al crear cliente en Alegra', [
                'cliente_id' => $cliente->id,
                'response' => $response->json()
            ]);
            return ['success' => false, 'error' => $response->json()['message'] ?? 'Error al crear cliente'];

        } catch (\Exception $e) {
            Log::error('Excepción al crear cliente en Alegra', [
                'cliente_id' => $cliente->id,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function obtenerEstadoFactura($facturaId)
    {
        try {
            $response = $this->http->get("/invoices/{$facturaId}/status");
            return $response->successful() 
                ? ['success' => true, 'data' => $response->json()]
                : ['success' => false, 'error' => $response->json()['message'] ?? 'Error'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function enviarFacturaADian($facturaId)
    {
        try {
            $response = $this->http->post("/invoices/{$facturaId}/email", [
                'sendDian' => true
            ]);
            
            return $response->successful() 
                ? ['success' => true, 'data' => $response->json()]
                : ['success' => false, 'error' => $response->json()['message'] ?? 'Error'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function descargarPDFFactura($facturaId)
    {
        try {
            $response = $this->http->get("/invoices/{$facturaId}/pdf");
            return $response->successful() 
                ? ['success' => true, 'data' => $response->body()]
                : ['success' => false, 'error' => $response->json()['message'] ?? 'Error'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function obtenerResolucionPreferida($tipo_factura = null)
    {
        try {
            // Cambiamos el endpoint al correcto para obtener resoluciones
            $response = $this->http->get('/number-templates');
            
            if (!$response->successful()) {
                Log::error('Error al obtener resoluciones', [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return [
                    'success' => false,
                    'error' => 'Error al obtener resoluciones'
                ];
            }

            $resoluciones = $response->json();

            // Buscamos la resolución electrónica activa
            foreach ($resoluciones as $resolucion) {
                if ($resolucion['documentType'] === 'invoice' && 
                    $resolucion['status'] === 'active' && 
                    $resolucion['isElectronic'] === true &&
                    $resolucion['prefix'] === 'FEVP') {
                    
                    Log::info('Resolución encontrada', [
                        'id' => $resolucion['id'],
                        'prefix' => $resolucion['prefix'],
                        'number' => $resolucion['nextInvoiceNumber']
                    ]);

                    return [
                        'success' => true,
                        'data' => [
                            'id' => $resolucion['id'],
                            'prefix' => $resolucion['prefix']
                        ]
                    ];
                }
            }

            Log::error('No se encontró resolución electrónica activa');
            return [
                'success' => false,
                'error' => 'No se encontró resolución electrónica activa'
            ];

        } catch (\Exception $e) {
            Log::error('Error al obtener resolución: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function obtenerUltimaFactura()
    {
        try {
            Log::info('Consultando última factura electrónica');
            
            // Consultamos las últimas facturas, filtrando por electrónicas
            $response = $this->http->get('/invoices', [
                'query' => [
                    'type' => 'electronic',
                    'limit' => 1,
                    'order_direction' => 'DESC',
                    'order_field' => 'date'
                ]
            ]);

            if ($response->successful()) {
                $factura = $response->json()[0] ?? null;
                
                if ($factura) {
                    Log::info('Última factura encontrada', [
                        'id' => $factura['id'],
                        'number' => $factura['numberTemplate'] ?? null,
                        'resolution' => $factura['resolution'] ?? null,
                        'status' => $factura['status']
                    ]);
                    
                    return [
                        'success' => true,
                        'data' => $factura
                    ];
                }
            }

            Log::error('Error al consultar última factura', [
                'response' => $response->json()
            ]);

            return [
                'success' => false,
                'error' => 'No se encontraron facturas electrónicas'
            ];

        } catch (\Exception $e) {
            Log::error('Error consultando última factura: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function obtenerFacturaEjemplo()
    {
        try {
            $response = $this->http->get('/invoices', [
                'query' => ['limit' => 1]
            ]);
            
            Log::info('Factura de ejemplo de Alegra', [
                'response' => $response->json()
            ]);
            
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Error obteniendo factura ejemplo', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function obtenerResolucionFacturacion()
    {
        try {
            Log::info('Intentando obtener resolución de Alegra');
            
            // Cambiamos la ruta según la documentación de Alegra
            $response = $this->http->get('resolution');  // Quitamos /api/v1/ ya que ya está en la URL base
            
            Log::info('Respuesta de Alegra recibida', [
                'status' => $response->status(),
                'body' => $response->json(),
                'url' => $this->baseUrl . '/resolution', // Agregamos log de la URL completa
                'headers' => $this->http->getOptions()   // Verificamos headers
            ]);
            
            if ($response->successful()) {
                Log::info('Resolución obtenida exitosamente', [
                    'data' => $response->json()
                ]);
                return ['success' => true, 'data' => $response->json()];
            }
            
            Log::error('Error al obtener resolución de Alegra', [
                'status' => $response->status(),
                'error' => $response->json()['message'] ?? 'Error desconocido'
            ]);
            
            return [
                'success' => false, 
                'error' => $response->json()['message'] ?? 'Error'
            ];
        } catch (\Exception $e) {
            Log::error('Excepción al obtener resolución de Alegra', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'url' => $this->baseUrl . '/resolution'
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
} 