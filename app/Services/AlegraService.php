<?php

namespace App\Services;

use App\Models\Venta;
use App\Models\Empresa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\AlegraErrorHelper;

class AlegraService
{
    protected $baseUrl;
    protected $auth;
    protected $http;
    protected $credencialesValidas;
    protected $user;
    protected $token;
    protected $httpClient;
    protected $empresaCache; // Cache de empresa para evitar N+1 queries
    protected $credencialesCache; // Cache de credenciales
    
    // Endpoints de Alegra
    const ENDPOINT_CLIENTES = '/contacts';
    const ENDPOINT_PRODUCTOS = '/items';

    public function __construct($customEmail = null, $customToken = null)
    {
        $this->baseUrl = config('alegra.base_url');
        
        if (empty($this->baseUrl)) {
            $this->baseUrl = 'https://api.alegra.com/api/v1';
            Log::info('URL base de Alegra no configurada, usando valor por defecto', [
                'base_url' => $this->baseUrl
            ]);
        }
        
        // Verificar si se proporcionaron credenciales personalizadas
        if (!empty($customEmail) && !empty($customToken)) {
            // Usar credenciales personalizadas proporcionadas en el constructor
            $email = $customEmail;
            $token = $customToken;
            Log::info('Usando credenciales de Alegra personalizadas proporcionadas en el constructor', [
                'email' => $email,
                'token_partial' => substr($token, 0, 3) . '...' . substr($token, -3)
            ]);
        } else {
            // Obtener las credenciales de la empresa
            $empresa = Empresa::first();
            
            if ($empresa && !empty($empresa->alegra_email) && !empty($empresa->alegra_token)) {
                // Usar credenciales de la empresa
                $email = $empresa->alegra_email;
                $token = $empresa->alegra_token;
                Log::info('Usando credenciales de Alegra configuradas en la empresa');
            } else {
                // Si no hay credenciales en la base de datos, intentar obtenerlas de la configuraciÃ³n
                $email = config('alegra.user');
                $token = config('alegra.token');
                
                if (!empty($email) && !empty($token)) {
                    Log::info('Usando credenciales de Alegra configuradas en el archivo .env');
                } else {
                    // Si no hay credenciales configuradas, marcar el servicio como no inicializado correctamente
                    Log::warning('No se encontraron credenciales de Alegra vÃ¡lidas. Algunas funciones no estarÃ¡n disponibles.');
                    $email = '';
                    $token = '';
                    $this->credencialesValidas = false;
                }
            }
        }
        
        $this->credencialesValidas = !empty($email) && !empty($token);
        $this->user = $email;
        $this->token = $token;
        
        // Alegra usa Basic Auth con email:token
        $this->auth = $this->credencialesValidas ? base64_encode($email . ':' . $token) : '';
        
        Log::info('Configurando cliente HTTP Alegra', [
            'base_url' => $this->baseUrl,
            'credenciales_validas' => $this->credencialesValidas,
            'email_usado' => $email ?: 'No configurado'
        ]);
        
        if ($this->credencialesValidas) {
            $this->http = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->auth,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->baseUrl($this->baseUrl);
            
            // No hacer una peticiÃ³n de prueba automÃ¡ticamente al construir el objeto
            // para evitar errores en la inicializaciÃ³n
        } else {
            // Crear un cliente HTTP sin autenticaciÃ³n para evitar errores
            $this->http = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->baseUrl($this->baseUrl);
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
            // Validar datos mínimos requeridos
            if (!isset($datos['client']) || !isset($datos['items']) || empty($datos['items'])) {
                return [
                    'success' => false,
                    'message' => 'Faltan datos requeridos: client, items'
                ];
            }
            
            // Preparar datos para la API de Alegra
            $datosFactura = [
                'date' => $datos['date'] ?? date('Y-m-d'),
                'dueDate' => $datos['dueDate'] ?? date('Y-m-d'),
                'client' => $datos['client'],
                'items' => $datos['items']
            ];
            
            // Agregar campos opcionales si están presentes
            if (isset($datos['warehouse'])) {
                $datosFactura['warehouse'] = $datos['warehouse'];
            }
            
            if (isset($datos['numberTemplate'])) {
                $datosFactura['numberTemplate'] = $datos['numberTemplate'];
            }
            
            // Configurar el pago según el formato correcto
            if (isset($datos['payment'])) {
                // Si ya viene en formato correcto, usarlo directamente
                $datosFactura['payment'] = $datos['payment'];
            } else {
                // Construir el objeto payment con el formato correcto
                $datosFactura['payment'] = [
                    'paymentMethod' => [
                        'id' => isset($datos['paymentMethod']) && is_numeric($datos['paymentMethod']) 
                            ? intval($datos['paymentMethod']) 
                            : 10 // ID 10 = CASH por defecto
                    ],
                    'account' => [
                        'id' => isset($datos['account']) && is_numeric($datos['account']) 
                            ? intval($datos['account']) 
                            : 1 // ID 1 = cuenta por defecto
                    ]
                ];
            }
            
            // Obtener credenciales
            $credenciales = $this->obtenerCredencialesAlegra();
            if (!$credenciales['success']) {
                return $credenciales;
            }
            
            $email = $credenciales['email'];
            $token = $credenciales['token'];
            
            // Configurar cURL
            $ch = curl_init();
            $url = "https://api.alegra.com/api/v1/invoices";
            
            // Configurar opciones de cURL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datosFactura));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($email . ':' . $token)
            ]);
            
            // Ejecutar la solicitud
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            // Registrar la respuesta
            Log::info('Respuesta de creación de factura', [
                'http_code' => $httpCode,
                'response' => $response,
                'error' => $error
            ]);
            
            // Procesar la respuesta
            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                return [
                    'success' => true,
                    'message' => 'Factura creada correctamente',
                    'data' => $data
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Error al crear la factura: ' . $response,
                'error' => $error
            ];
        } catch (\Exception $e) {
            Log::error('Excepción al crear factura: ' . $e->getMessage(), [
                'datos' => $datos
            ]);
            
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Mapear forma de pago de nuestro sistema a Alegra
     * @param string $formaPago
     * @return array
     */
    public function mapearFormaPago($formaPago)
    {
        Log::info('Mapeando forma de pago', ['forma_pago' => $formaPago]);
        
        // Obtener mÃ©todos de pago de la configuraciÃ³n
        $metodosPago = config('alegra.payment_methods', []);
        
        // Normalizar la forma de pago (minÃºsculas, sin espacios)
        $formaPagoNormalizada = strtolower(trim($formaPago));
        
        // Si existe en la configuraciÃ³n, usar esa configuraciÃ³n
        if (isset($metodosPago[$formaPagoNormalizada])) {
            $metodoPago = $metodosPago[$formaPagoNormalizada];
            return [
                'paymentMethod' => ['id' => $metodoPago['id']],
                'account' => ['id' => $metodoPago['account_id']]
            ];
        }
        
        // Mapeo alternativo si no estÃ¡ en la configuraciÃ³n
        switch ($formaPagoNormalizada) {
            case 'tarjeta':
            case 'tarjeta_credito':
                return [
                    'paymentMethod' => ['id' => 3],  // Tarjeta de crÃ©dito
                    'account' => ['id' => 1]         // Cuenta por defecto
                ];
            case 'tarjeta_debito':
                return [
                    'paymentMethod' => ['id' => 4],  // Tarjeta de dÃ©bito
                    'account' => ['id' => 1]         // Cuenta por defecto
                ];
            case 'transferencia':
            case 'transferencia_bancaria':
                return [
                    'paymentMethod' => ['id' => 1],  // Transferencia bancaria
                    'account' => ['id' => 1]         // Cuenta por defecto
                ];
            default:
                // Por defecto, usar efectivo
                return [
                    'paymentMethod' => ['id' => 10], // Efectivo por defecto
                    'account' => ['id' => 1]         // Cuenta por defecto
                ];
        }
    }

    /**
     * Prueba la conexiÃ³n con Alegra
     * 
     * @return array
     */
    public function probarConexion()
    {
        if (!$this->credencialesValidas) {
            return [
                'success' => false,
                'error' => 'Las credenciales de Alegra no estÃ¡n configuradas'
            ];
        }
        
        try {
            // Registrar las credenciales que estamos usando (sin mostrar el token completo)
            Log::info('Intentando conexión con Alegra', [
                'email' => $this->user,
                'token_parcial' => substr($this->token, 0, 3) . '...' . substr($this->token, -3)
            ]);
            
            // Crear un cliente HTTP con las credenciales actuales
            $client = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->user . ':' . $this->token),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->baseUrl($this->baseUrl);
            
            $response = $client->get('company');
            
            Log::info('Test de conexiÃ³n Alegra', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            // Si la conexión falla, intentar con credenciales alternativas
            if ($response->status() == 401) {
                Log::warning('Credenciales no válidas, intentando con credenciales alternativas');
                
                // Credenciales alternativas (las correctas proporcionadas por el usuario)
                $altEmail = "anitasape1982@gmail.com";
                $altToken = "b8ca29dbb62a079643f5";
                
                // Crear un cliente HTTP con las credenciales alternativas
                $altClient = Http::withHeaders([
                    'Authorization' => 'Basic ' . base64_encode($altEmail . ':' . $altToken),
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])->baseUrl($this->baseUrl);
                
                $altResponse = $altClient->get('company');
                
                Log::info('Test de conexión Alegra con credenciales alternativas', [
                    'status' => $altResponse->status(),
                    'body' => $altResponse->json()
                ]);
                
                if ($altResponse->successful()) {
                    // Actualizar las credenciales en el objeto actual
                    $this->user = $altEmail;
                    $this->token = $altToken;
                    $this->auth = base64_encode($altEmail . ':' . $altToken);
                    $this->credencialesValidas = true;
                    
                    // Actualizar el cliente HTTP
                    $this->http = $altClient;
                    
                    // Actualizar las credenciales en la base de datos
                    $empresa = Empresa::first();
                    if ($empresa) {
                        $empresa->alegra_email = $altEmail;
                        $empresa->alegra_token = $altToken;
                        $empresa->save();
                        
                        Log::info('Credenciales de Alegra actualizadas en la base de datos');
                    }
                    
                    return [
                        'success' => true,
                        'data' => $altResponse->json(),
                        'message' => 'Conexión exitosa con credenciales alternativas'
                    ];
                }
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error ' . $response->status(),
                'message' => 'No se pudo conectar con Alegra. Verifique sus credenciales.'
            ];
        } catch (\Exception $e) {
            Log::error('Error en test de conexiÃ³n Alegra', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error al conectar con Alegra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Prueba la conexiÃ³n con Alegra (alias para mantener compatibilidad)
     * 
     * @return array
     */
    public function testConnection()
    {
        return $this->probarConexion();
    }

    /**
     * Crear un cliente en Alegra
     * @param \App\Models\Cliente $cliente
     * @return array
     */
    public function crearClienteAlegra($cliente)
    {
        try {
            Log::info('Iniciando creaciÃ³n de cliente en Alegra', [
                'cliente_id' => $cliente->id,
                'cedula' => $cliente->cedula
            ]);

            // Preparar datos del cliente para Alegra
            $datos = [
                'name' => $cliente->nombres . ' ' . $cliente->apellidos,
                'nameObject' => [
                    'firstName' => $cliente->nombres,
                    'lastName' => $cliente->apellidos,
                ],
                'identificationObject' => [
                    'type' => $cliente->tipo_documento ?? 'CC',
                    'number' => $cliente->cedula,
                ],
                'email' => $cliente->email ?: 'sin@email.com',
                'phonePrimary' => $cliente->telefono ?: '0000000000',
                'address' => [
                    'address' => $cliente->direccion ?? 'Sin dirección',
                    'city' => $cliente->ciudad ?? 'Bogotá D.C.',
                    'department' => $cliente->departamento ?? 'Bogotá'
                ],
                'type' => 'client',
                'kindOfPerson' => $cliente->tipo_persona ?? 'PERSON_ENTITY',
                'regime' => $cliente->regimen ?? 'SIMPLIFIED_REGIME'
            ];

            Log::info('Datos del cliente para Alegra', [
                'datos' => $datos
            ]);

            $response = $this->http->post('contacts', $datos);
            
            if ($response->successful()) {
                Log::info('Cliente creado exitosamente en Alegra', [
                    'cliente_id' => $cliente->id,
                    'alegra_id' => $response->json()['id'],
                    'response' => $response->json()
                ]);
                
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::error('Error al crear cliente en Alegra', [
                'error' => $response->json(),
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al crear cliente'
            ];

        } catch (\Exception $e) {
            Log::error('ExcepciÃ³n al crear cliente en Alegra', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Crear un producto en Alegra
     * @param \App\Models\Producto $producto
     * @return array
     */
    public function crearProductoAlegra($producto)
    {
        try {
            Log::info('Iniciando creaciÃ³n de producto en Alegra', [
                'producto_id' => $producto->id,
                'nombre' => $producto->nombre
            ]);

            // Preparar datos del producto para Alegra
            $datos = [
                'name' => $producto->nombre,
                'description' => $producto->descripcion ?? $producto->nombre,
                'reference' => $producto->codigo,
                'price' => (float)$producto->precio_venta,
                'type' => 'product', // Especificar que es un producto, no un servicio
                'inventory' => [
                    'unit' => $producto->unidad_medida ?? 'unit',
                    'availableQuantity' => (int)$producto->stock,
                    'unitCost' => (float)$producto->precio_compra,
                    'negativeSale' => true, // Permitir ventas en negativo
                    'warehouses' => [
                        [
                            'id' => 1, // Bodega principal por defecto
                            'initialQuantity' => (int)$producto->stock
                        ]
                    ]
                ]
            ];

            Log::info('Datos del producto para Alegra', [
                'datos' => $datos
            ]);

            $response = $this->http->post('items', $datos);
            
            if ($response->successful()) {
                Log::info('Producto creado exitosamente en Alegra', [
                    'producto_id' => $producto->id,
                    'alegra_id' => $response->json()['id'],
                    'response' => $response->json()
                ]);
                
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::error('Error al crear producto en Alegra', [
                'error' => $response->json(),
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al crear producto'
            ];

        } catch (\Exception $e) {
            Log::error('ExcepciÃ³n al crear producto en Alegra', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar un producto en Alegra
     * @param \App\Models\Producto $producto
     * @return array
     */
    public function actualizarProductoAlegra($producto)
    {
        try {
            if (!$producto->id_alegra) {
                return [
                    'success' => false,
                    'error' => 'El producto no tiene un ID de Alegra asignado'
                ];
            }

            Log::info('Iniciando actualizaciÃ³n de producto en Alegra', [
                'producto_id' => $producto->id,
                'alegra_id' => $producto->id_alegra
            ]);

            // Preparar datos del producto para Alegra
            $datos = [
                'name' => $producto->nombre,
                'description' => $producto->descripcion ?? $producto->nombre,
                'reference' => $producto->codigo,
                'price' => (float)$producto->precio_venta,
                'inventory' => [
                    'unit' => $producto->unidad_medida ?? 'unit',
                    'unitCost' => (float)$producto->precio_compra,
                ]
            ];

            Log::info('Datos del producto para actualizar en Alegra', [
                'datos' => $datos
            ]);

            $response = $this->http->put("items/{$producto->id_alegra}", $datos);
            
            if ($response->successful()) {
                Log::info('Producto actualizado exitosamente en Alegra', [
                    'producto_id' => $producto->id,
                    'alegra_id' => $producto->id_alegra,
                    'response' => $response->json()
                ]);
                
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::error('Error al actualizar producto en Alegra', [
                'error' => $response->json(),
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al actualizar producto'
            ];

        } catch (\Exception $e) {
            Log::error('ExcepciÃ³n al actualizar producto en Alegra', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
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
            Log::error('ExcepciÃ³n al obtener productos de Alegra', [
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
            Log::error('ExcepciÃ³n al obtener clientes de Alegra', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtiene el estado actual de una factura en Alegra usando cURL
     * @param string $idFactura ID de la factura en Alegra
     * @return array
     */
    public function obtenerEstadoFactura($idFactura)
    {
        try {
            Log::info('Consultando estado de factura en Alegra con cURL', [
                'id_factura' => $idFactura
            ]);
            
            // Obtener credenciales
            $credenciales = $this->obtenerCredencialesAlegra();
            if (!$credenciales['success']) {
                return $credenciales;
            }
            
            $email = $credenciales['email'];
            $token = $credenciales['token'];
            
            // Configurar cURL
            $ch = curl_init();
            $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}?expand=items,client,payments,attachments,observations,metadata";
            
            // Configurar opciones de cURL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($email . ':' . $token)
            ]);
            
            // Ejecutar la solicitud
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            // Registrar la respuesta
            Log::info('Respuesta de consulta de estado de factura con cURL', [
                'http_code' => $httpCode,
                'error' => $error
            ]);
            
            // Procesar la respuesta
            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                
                // Extraer informaciÃ³n del CUFE si existe
                $cufe = null;
                $qrCode = null;
                $pdfUrl = null;
                
                // Buscar el CUFE en los metadatos
                if (isset($data['metadata']) && is_array($data['metadata'])) {
                    foreach ($data['metadata'] as $metadata) {
                        if (isset($metadata['key']) && $metadata['key'] === 'cufe') {
                            $cufe = $metadata['value'];
                        }
                        if (isset($metadata['key']) && $metadata['key'] === 'qrCode') {
                            $qrCode = $metadata['value'];
                        }
                    }
                }
                
                // Buscar el PDF en los adjuntos
                if (isset($data['attachments']) && is_array($data['attachments'])) {
                    foreach ($data['attachments'] as $attachment) {
                        if (isset($attachment['name']) && strpos($attachment['name'], '.pdf') !== false) {
                            $pdfUrl = $attachment['downloadLink'];
                        }
                    }
                }
                
                return [
                    'success' => true,
                    'data' => $data,
                    'cufe' => $cufe,
                    'qrCode' => $qrCode,
                    'pdfUrl' => $pdfUrl
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Error al obtener detalles de la factura',
                'error_details' => json_decode($response, true)
            ];
        } catch (\Exception $e) {
            Log::error('Error al obtener detalles de la factura: ' . $e->getMessage(), ['id_factura' => $idFactura]);
            return [
                'success' => false,
                'message' => 'Error al obtener detalles de la factura: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Descarga el PDF de una factura de Alegra
     * @param string $facturaId
     * @return array
     */
    public function descargarPDFFactura($facturaId, $rutaDestino = null)
    {
        // MÃ©todo actualizado que usa la API directamente en lugar del script Node.js
        return $this->descargarPdfFacturaDirecto($facturaId, $rutaDestino);
    }
    
    /**
     * Obtiene la informaciÃ³n completa de una factura, incluyendo el CUFE
     * @param string $idFactura ID de la factura en Alegra
     * @return array
     */
    public function obtenerDetalleFacturaCompleto($idFactura)
    {
        try {
            // Obtener credenciales
            $credenciales = $this->obtenerCredencialesAlegra();
            if (!$credenciales['success']) {
                return $credenciales;
            }
            
            $email = $credenciales['email'];
            $token = $credenciales['token'];
            
            // Realizar la solicitud para obtener los detalles de la factura
            $response = Http::withBasicAuth($email, $token)
                ->get("https://api.alegra.com/api/v1/invoices/{$idFactura}", [
                    'expand' => 'items,client,payments,attachments,observations,metadata'
                ]);
            
            $data = $response->json();
            $statusCode = $response->status();
            
            // Log para debugging
            Log::info('Respuesta completa de Alegra para factura', [
                'id_factura' => $idFactura,
                'status_code' => $statusCode,
                'tiene_attachments' => isset($data['attachments']),
                'attachments' => $data['attachments'] ?? 'No disponible',
                'tiene_metadata' => isset($data['metadata']),
                'metadata' => $data['metadata'] ?? 'No disponible'
            ]);
            
            if ($statusCode >= 200 && $statusCode < 300) {
                // Extraer informaciÃ³n del CUFE si existe
                $cufe = null;
                $qrCode = null;
                $pdfUrl = null;
                
                // Buscar el CUFE en los metadatos
                if (isset($data['metadata']) && is_array($data['metadata'])) {
                    foreach ($data['metadata'] as $metadata) {
                        if (isset($metadata['key']) && $metadata['key'] === 'cufe') {
                            $cufe = $metadata['value'];
                        }
                        if (isset($metadata['key']) && $metadata['key'] === 'qrCode') {
                            $qrCode = $metadata['value'];
                        }
                    }
                }
                
                // Buscar el PDF en los adjuntos
                if (isset($data['attachments']) && is_array($data['attachments'])) {
                    foreach ($data['attachments'] as $attachment) {
                        if (isset($attachment['name']) && strpos($attachment['name'], '.pdf') !== false) {
                            $pdfUrl = $attachment['downloadLink'];
                        }
                    }
                }
                
                return [
                    'success' => true,
                    'data' => $data,
                    'cufe' => $cufe,
                    'qrCode' => $qrCode,
                    'pdfUrl' => $pdfUrl
                ];
            } else {
                Log::warning('Error al obtener detalles de la factura', [
                    'id_factura' => $idFactura,
                    'status_code' => $statusCode,
                    'response' => $data
                ]);
                
                return [
                    'success' => false,
                    'message' => isset($data['message']) ? $data['message'] : 'Error al obtener detalles de la factura',
                    'error' => $data
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error al obtener detalles de la factura: ' . $e->getMessage(), ['id_factura' => $idFactura]);
            return [
                'success' => false,
                'message' => 'Error al obtener detalles de la factura: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Descarga el PDF de una factura electrÃ³nica usando la API directamente
     * @param string $idFactura ID de la factura en Alegra
     * @param string|null $rutaDestino Ruta donde guardar el PDF (opcional)
     * @return array
     */
    public function descargarPdfFacturaDirecto($idFactura, $rutaDestino = null)
    {
        try {
            // Primero obtenemos los detalles de la factura para encontrar la URL del PDF
            $detalles = $this->obtenerDetalleFacturaCompleto($idFactura);
            
            if (!$detalles['success']) {
                return $detalles;
            }
            
            if (empty($detalles['pdfUrl'])) {
                return [
                    'success' => false,
                    'message' => 'No se encontrÃ³ el PDF de la factura'
                ];
            }
            
            // Obtener credenciales
            $credenciales = $this->obtenerCredencialesAlegra();
            if (!$credenciales['success']) {
                return $credenciales;
            }
            
            $email = $credenciales['email'];
            $token = $credenciales['token'];
            
            // Realizar la solicitud para descargar el PDF
            $response = Http::withBasicAuth($email, $token)
                ->get($detalles['pdfUrl']);
            
            $statusCode = $response->status();
            
            if ($statusCode >= 200 && $statusCode < 300) {
                $contenidoPdf = $response->body();
                
                // Si se especificÃ³ una ruta de destino, guardar el archivo
                if ($rutaDestino) {
                    // Asegurarse de que la carpeta exista
                    $directorio = dirname($rutaDestino);
                    if (!file_exists($directorio)) {
                        mkdir($directorio, 0755, true);
                    }
                    
                    // Guardar el PDF
                    file_put_contents($rutaDestino, $contenidoPdf);
                    
                    return [
                        'success' => true,
                        'message' => 'PDF descargado correctamente',
                        'ruta_archivo' => $rutaDestino
                    ];
                }
                
                // Si no se especificÃ³ ruta, devolver el contenido del PDF
                return [
                    'success' => true,
                    'message' => 'PDF obtenido correctamente',
                    'contenido' => $contenidoPdf,
                    'content_type' => 'application/pdf'
                ];
            } else {
                Log::warning('Error al descargar el PDF de la factura', [
                    'id_factura' => $idFactura,
                    'status_code' => $statusCode
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Error al descargar el PDF de la factura'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error al descargar el PDF de la factura: ' . $e->getMessage(), ['id_factura' => $idFactura]);
            return [
                'success' => false,
                'message' => 'Error al descargar el PDF de la factura: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Proceso completo de facturaciÃ³n electrÃ³nica: crear, abrir, emitir y obtener PDF
     * @param array $datosFactura Datos para crear la factura
     * @param string|null $rutaDestinoPdf Ruta donde guardar el PDF (opcional)
     * @return array
     */
    public function procesarFacturaElectronica($datosFactura, $rutaDestinoPdf = null)
    {
        try {
            // Paso 1: Crear la factura
            $resultadoCreacion = $this->crearFactura($datosFactura);
            
            if (!$resultadoCreacion['success']) {
                return [
                    'success' => false,
                    'message' => 'Error al crear la factura: ' . $resultadoCreacion['message'],
                    'etapa' => 'creacion'
                ];
            }
            
            $idFactura = $resultadoCreacion['data']['id'];
            
            // Paso 2: Abrir la factura
            $resultadoApertura = $this->abrirFacturaDirecto($idFactura);
            
            if (!$resultadoApertura['success']) {
                return [
                    'success' => false,
                    'message' => 'Error al abrir la factura: ' . $resultadoApertura['message'],
                    'etapa' => 'apertura',
                    'id_factura' => $idFactura
                ];
            }
            
            // Paso 3: Emitir la factura electrÃ³nicamente
            $resultadoEmision = $this->enviarFacturaADian($idFactura);
            
            if (!$resultadoEmision['success']) {
                return [
                    'success' => false,
                    'message' => 'Error al emitir la factura: ' . $resultadoEmision['message'],
                    'etapa' => 'emision',
                    'id_factura' => $idFactura
                ];
            }
            
            // Paso 4: Obtener detalles de la factura con CUFE
            $detallesFactura = $this->obtenerDetalleFacturaCompleto($idFactura);
            
            if (!$detallesFactura['success']) {
                return [
                    'success' => true, // La factura se emitiÃ³ correctamente, solo fallÃ³ al obtener detalles
                    'message' => 'La factura se emitiÃ³ correctamente, pero no se pudieron obtener todos los detalles',
                    'etapa' => 'detalles',
                    'id_factura' => $idFactura,
                    'error_detalles' => $detallesFactura['message']
                ];
            }
            
            // Paso 5: Descargar PDF si se solicitÃ³
            $pdfFactura = null;
            if ($rutaDestinoPdf) {
                $pdfFactura = $this->descargarPdfFacturaDirecto($idFactura, $rutaDestinoPdf);
                
                if (!$pdfFactura['success']) {
                    return [
                        'success' => true, // La factura se emitiÃ³ correctamente, solo fallÃ³ al descargar PDF
                        'message' => 'La factura se emitiÃ³ correctamente, pero no se pudo descargar el PDF',
                        'etapa' => 'pdf',
                        'id_factura' => $idFactura,
                        'cufe' => $detallesFactura['cufe'],
                        'error_pdf' => $pdfFactura['message']
                    ];
                }
            }
            
            // Todo el proceso se completÃ³ correctamente
            return [
                'success' => true,
                'message' => 'Factura electrÃ³nica procesada correctamente',
                'id_factura' => $idFactura,
                'cufe' => $detallesFactura['cufe'],
                'qrCode' => $detallesFactura['qrCode'],
                'pdfUrl' => $detallesFactura['pdfUrl'],
                'pdf_descargado' => $pdfFactura ? $pdfFactura['ruta_archivo'] : null,
                'detalles_factura' => $detallesFactura['data']
            ];
        } catch (\Exception $e) {
            Log::error('Error en el proceso de facturaciÃ³n electrÃ³nica: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error en el proceso de facturaciÃ³n electrÃ³nica: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Procesa una factura electrÃ³nica existente: verifica estado, abre si es necesario y emite
     * @param string $idFactura ID de la factura en Alegra
     * @return array
     */
    public function procesarFacturaExistente($idFactura)
    {
        try {
            Log::info('Iniciando procesamiento de factura electrÃ³nica', [
                'id_factura' => $idFactura
            ]);
            
            // Paso 1: Verificar el estado actual de la factura
            $estadoFactura = $this->obtenerEstadoFactura($idFactura);
            
            if (!$estadoFactura['success']) {
                Log::error('Error al consultar estado de la factura', [
                    'id_factura' => $idFactura,
                    'error' => $estadoFactura['message']
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Error al consultar estado de la factura: ' . $estadoFactura['message'],
                    'etapa' => 'verificacion'
                ];
            }
            
            $estadoActual = $estadoFactura['data']['status'];
            Log::info('Estado actual de la factura', [
                'id_factura' => $idFactura,
                'estado' => $estadoActual
            ]);
            
            // Paso 2: Si estÃ¡ en borrador, abrirla
            if ($estadoActual === 'draft') {
                Log::info('La factura estÃ¡ en estado borrador, intentando abrirla', [
                    'id_factura' => $idFactura
                ]);
                
                $resultadoApertura = $this->abrirFacturaDirecto($idFactura);
                
                if (!$resultadoApertura['success']) {
                    Log::error('Error al abrir la factura', [
                        'id_factura' => $idFactura,
                        'error' => $resultadoApertura['message']
                    ]);
                    
                    return [
                        'success' => false,
                        'message' => 'Error al abrir la factura: ' . $resultadoApertura['message'],
                        'etapa' => 'apertura'
                    ];
                }
                
                Log::info('Factura abierta exitosamente', [
                    'id_factura' => $idFactura
                ]);
                
                // Verificar nuevamente el estado despuÃ©s de abrir
                $estadoFactura = $this->obtenerEstadoFactura($idFactura);
                
                if (!$estadoFactura['success'] || $estadoFactura['data']['status'] !== 'open') {
                    Log::error('La factura no cambiÃ³ a estado abierto despuÃ©s de intentar abrirla', [
                        'id_factura' => $idFactura,
                        'estado_actual' => $estadoFactura['data']['status'] ?? 'desconocido'
                    ]);
                    
                    return [
                        'success' => false,
                        'message' => 'La factura no cambiÃ³ a estado abierto despuÃ©s de intentar abrirla',
                        'etapa' => 'apertura'
                    ];
                }
            } else if ($estadoActual !== 'open') {
                Log::error('La factura no estÃ¡ en estado borrador ni abierto, no se puede procesar', [
                    'id_factura' => $idFactura,
                    'estado_actual' => $estadoActual
                ]);
                
                return [
                    'success' => false,
                    'message' => 'La factura estÃ¡ en estado ' . $estadoActual . ', no se puede procesar',
                    'etapa' => 'verificacion'
                ];
            }
            
            // Paso 3: Emitir la factura electrÃ³nicamente
            Log::info('Intentando emitir la factura electrÃ³nicamente', [
                'id_factura' => $idFactura
            ]);
            
            $resultadoEmision = $this->enviarFacturaADian($idFactura);
            
            if (!$resultadoEmision['success']) {
                Log::error('Error al emitir la factura electrÃ³nicamente', [
                    'id_factura' => $idFactura,
                    'error' => $resultadoEmision['message']
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Error al emitir la factura electrÃ³nicamente: ' . $resultadoEmision['message'],
                    'etapa' => 'emision'
                ];
            }
            
            Log::info('Factura emitida electrÃ³nicamente con Ã©xito', [
                'id_factura' => $idFactura
            ]);
            
            return [
                'success' => true,
                'message' => 'Factura procesada y emitida electrÃ³nicamente con Ã©xito',
                'id_factura' => $idFactura
            ];
        } catch (\Exception $e) {
            Log::error('ExcepciÃ³n al procesar factura electrÃ³nica: ' . $e->getMessage(), [
                'id_factura' => $idFactura
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al procesar la factura electrÃ³nica: ' . $e->getMessage(),
                'etapa' => 'general'
            ];
        }
    }

    /**
     * Obtiene la resoluciÃ³n preferida para facturaciÃ³n electrÃ³nica o fÃ­sica
     * @param string $tipo Tipo de resoluciÃ³n: 'electronica' o 'fisica'
     * @return array
     */
    public function obtenerResolucionPreferida($tipo = 'electronica')
    {
        try {
            Log::info('Buscando resoluciÃ³n preferida', [
                'tipo' => $tipo
            ]);
            
            // Obtener credenciales
            $credenciales = $this->obtenerCredencialesAlegra();
            if (!$credenciales['success']) {
                return $credenciales;
            }
            
            $email = $credenciales['email'];
            $token = $credenciales['token'];
            
            // Registrar las credenciales que se están usando (sin mostrar el token completo)
            Log::info('Usando credenciales para obtener resolución', [
                'email' => $email,
                'token_parcial' => substr($token, 0, 3) . '...' . substr($token, -3)
            ]);
            
            // Configurar cURL
            $ch = curl_init();
            $url = "https://api.alegra.com/api/v1/number-templates";
            
            // Configurar opciones de cURL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($email . ':' . $token)
            ]);
            
            // Ejecutar la solicitud
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            // Registrar la respuesta
            Log::info('Respuesta de obtener plantillas de numeraciÃ³n', [
                'http_code' => $httpCode,
                'error' => $error,
                'response' => substr($response, 0, 500) // Limitar el tamaño del log
            ]);
            
            // Si hay error de autenticación, intentar con credenciales alternativas
            if ($httpCode == 401) {
                Log::warning('Credenciales no válidas para obtener plantillas, intentando con credenciales alternativas');
                
                // Credenciales alternativas (usar las del config)
                $altEmail = config('alegra.user');
                $altToken = config('alegra.token');
                
                // Configurar nuevo cURL con credenciales alternativas
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization: Basic ' . base64_encode($altEmail . ':' . $altToken)
                ]);
                
                // Ejecutar la solicitud
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                
                curl_close($ch);
                
                // Registrar la respuesta
                Log::info('Respuesta con credenciales alternativas', [
                    'http_code' => $httpCode,
                    'error' => $error,
                    'response' => substr($response, 0, 500) // Limitar el tamaño del log
                ]);
                
                // Si la conexión es exitosa, solo actualizar las credenciales en el objeto actual
                if ($httpCode >= 200 && $httpCode < 300) {
                    Log::info('Usando credenciales alternativas exitosamente para esta consulta');
                    
                    // Actualizar las credenciales en el objeto actual solo para esta consulta
                    $this->user = $altEmail;
                    $this->token = $altToken;
                    $this->auth = base64_encode($altEmail . ':' . $altToken);
                    $this->credencialesValidas = true;
                    
                    // Actualizar el cliente HTTP
                    $this->http = Http::withHeaders([
                        'Authorization' => 'Basic ' . $this->auth,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ])->baseUrl($this->baseUrl);
                }
            }
            
            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                
                // Si no hay datos o no es un array, crear una resolución predeterminada
                if (empty($data) || !is_array($data)) {
                    Log::warning('No se obtuvieron datos de plantillas de numeración, usando valores predeterminados');
                    
                    // Crear una resolución predeterminada basada en los datos proporcionados
                    return [
                        'success' => true,
                        'message' => 'Usando resolución predeterminada',
                        'data' => $this->getResolucionPredeterminada()
                    ];
                }
                
                // Filtrar las plantillas según el tipo solicitado
                $plantillasActivas = array_filter($data, function($plantilla) use ($tipo) {
                    $esElectronica = isset($plantilla['isElectronic']) && $plantilla['isElectronic'] === true;
                    $estaActiva = isset($plantilla['status']) && $plantilla['status'] === 'active';
                    
                    if ($tipo === 'electronica') {
                        return $esElectronica && $estaActiva;
                    } else {
                        return !$esElectronica && $estaActiva;
                    }
                });
                
                // Si hay plantillas activas, buscar la resolución preferida
                if (!empty($plantillasActivas)) {
                    // Primero buscar por prefijo FEV (Factura Electrónica de Venta)
                    $plantillaFEV = null;
                    foreach ($plantillasActivas as $plantilla) {
                        if (isset($plantilla['prefix']) && $plantilla['prefix'] === 'FEV') {
                            $plantillaFEV = $plantilla;
                            break;
                        }
                    }
                    
                    // Si encontramos una plantilla con prefijo FEV, usarla
                    if ($plantillaFEV) {
                        Log::info('Resolución con prefijo FEV encontrada', [
                            'id' => $plantillaFEV['id'],
                            'nombre' => $plantillaFEV['name'] ?? 'Sin nombre',
                            'prefijo' => $plantillaFEV['prefix']
                        ]);
                        
                        return [
                            'success' => true,
                            'message' => 'Resolución con prefijo FEV encontrada',
                            'data' => $plantillaFEV
                        ];
                    }
                    
                    // Si no hay con prefijo FEV, buscar por nombre "FACTURA ELECTRÓNICA DE VENTA" (ignorando mayúsculas/minúsculas)
                    $plantillaPorNombre = null;
                    foreach ($plantillasActivas as $plantilla) {
                        if (isset($plantilla['name']) && 
                            (strtoupper($plantilla['name']) === 'FACTURA ELECTRÓNICA DE VENTA' || 
                             strtoupper($plantilla['name']) === 'FACTURA ELECTRONICA DE VENTA')) {
                            $plantillaPorNombre = $plantilla;
                            break;
                        }
                    }
                    
                    // Si encontramos una plantilla con el nombre correcto, usarla
                    if ($plantillaPorNombre) {
                        Log::info('Resolución con nombre "FACTURA ELECTRÓNICA DE VENTA" encontrada', [
                            'id' => $plantillaPorNombre['id'],
                            'nombre' => $plantillaPorNombre['name'],
                            'prefijo' => $plantillaPorNombre['prefix'] ?? 'Sin prefijo'
                        ]);
                        
                        return [
                            'success' => true,
                            'message' => 'Resolución con nombre "FACTURA ELECTRÓNICA DE VENTA" encontrada',
                            'data' => $plantillaPorNombre
                        ];
                    }
                    
                    // Si no encontramos ninguna específica, usar la primera plantilla activa
                    $plantilla = reset($plantillasActivas);
                    
                    Log::info('Usando primera resolución electrónica activa disponible', [
                        'id' => $plantilla['id'],
                        'nombre' => $plantilla['name'] ?? 'Sin nombre',
                        'prefijo' => $plantilla['prefix'] ?? 'Sin prefijo',
                        'tipo' => $tipo
                    ]);
                    
                    return [
                        'success' => true,
                        'message' => 'Usando primera resolución electrónica activa disponible',
                        'data' => $plantilla
                    ];
                }
                
                // Si no hay plantillas activas del tipo solicitado, crear una predeterminada
                Log::warning('No se encontraron plantillas de numeración activas, usando valores predeterminados', [
                    'tipo' => $tipo
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Usando resolución predeterminada',
                    'data' => $this->getResolucionPredeterminada()
                ];
            }
            
            Log::error('Error al obtener plantillas de numeración', [
                'http_code' => $httpCode,
                'response' => $response,
                'error' => $error
            ]);
            
            // Si hay un error, devolver una resolución predeterminada
            return [
                'success' => true,
                'message' => 'Usando resolución predeterminada debido a error en API',
                'data' => $this->getResolucionPredeterminada()
            ];
        } catch (\Exception $e) {
            Log::error('Excepción al obtener resolución preferida: ' . $e->getMessage(), [
                'tipo' => $tipo
            ]);
            
            // En caso de excepción, devolver una resolución predeterminada
            return [
                'success' => true,
                'message' => 'Usando resolución predeterminada debido a excepción',
                'data' => $this->getResolucionPredeterminada()
            ];
        }
    }
    
    /**
     * Obtiene una resolución predeterminada para usar cuando no se puede obtener de Alegra
     * @return array
     */
    private function getResolucionPredeterminada()
    {
        // Intentar obtener la resolución de la base de datos primero
        $empresa = Empresa::first();
        if ($empresa && !empty($empresa->resolucion_facturacion)) {
            $resolucionGuardada = json_decode($empresa->resolucion_facturacion, true);
            if ($resolucionGuardada && isset($resolucionGuardada['id'])) {
                Log::info('Usando resolución guardada en la base de datos');
                
                // Convertir la resolución guardada al formato esperado por Alegra
                return [
                    'id' => $resolucionGuardada['id'],
                    'prefix' => $resolucionGuardada['prefijo'],
                    'name' => 'Factura Electrónica',
                    'status' => 'active',
                    'isElectronic' => true,
                    'initialNumber' => 501,
                    'finalNumber' => 2000,
                    'currentNumber' => 501,
                    'resolution' => [
                        'number' => $resolucionGuardada['numero_resolucion'] ?? '18764098256287',
                        'date' => $resolucionGuardada['fecha_inicio'] ?? '2025-09-05',
                        'expirationDate' => $resolucionGuardada['fecha_fin'] ?? '2026-03-05'
                    ]
                ];
            }
        }
        
        // Si no hay resolución en la base de datos, devolver un array vacío para indicar que no hay resolución configurada
        Log::warning('No hay resolución configurada en la base de datos ni se pudo obtener de Alegra');
        
        return [
            'id' => '',
            'prefix' => '',
            'name' => 'NO CONFIGURADA',
            'status' => 'inactive',
            'isElectronic' => true,
            'initialNumber' => 0,
            'finalNumber' => 0,
            'currentNumber' => 0,
            'resolution' => [
                'number' => '',
                'date' => '',
                'expirationDate' => ''
            ]
        ];
    }

    /**
     * EnvÃ­a una factura a la DIAN para emisiÃ³n electrÃ³nica usando cURL
     * @param string $idFactura ID de la factura en Alegra
     * @return array
     */
    public function enviarFacturaADian($idFactura)
    {
        try {
            Log::info('Iniciando envÃ­o de factura a DIAN con cURL', [
                'id_factura' => $idFactura
            ]);
            
            // Obtener credenciales
            $credenciales = $this->obtenerCredencialesAlegra();
            if (!$credenciales['success']) {
                return $credenciales;
            }
            
            $email = $credenciales['email'];
            $token = $credenciales['token'];
            
            // Configurar cURL
            $ch = curl_init();
            $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/stamp";
            
            // Datos para la solicitud
            $datos = json_encode([
                'generateStamp' => true,
                'generateQrCode' => true
            ]);
            
            // Configurar opciones de cURL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $datos);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($email . ':' . $token)
            ]);
            
            // Ejecutar la solicitud
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            // Registrar la respuesta
            Log::info('Respuesta de envío de factura a DIAN con cURL', [
                'http_code' => $httpCode,
                'response' => $response,
                'error' => $error
            ]);
            
            // Procesar la respuesta
            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                return [
                    'success' => true,
                    'data' => $data
                ];
            }
            
            // Procesar error
            $errorData = json_decode($response, true);
            $errorMessage = isset($errorData['message']) ? $errorData['message'] : 'Error al enviar la factura a la DIAN';
            
            return [
                'success' => false,
                'message' => $errorMessage,
                'error_details' => $errorData
            ];
        } catch (\Exception $e) {
            Log::error('Excepción al enviar factura a DIAN: ' . $e->getMessage(), [
                'id_factura' => $idFactura
            ]);
            
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Obtiene las credenciales de Alegra desde la empresa o el archivo .env
     * @return array
     */
    protected function obtenerCredencialesAlegra()
    {
        // Si ya tenemos las credenciales en caché, retornarlas
        if ($this->credencialesCache !== null) {
            return $this->credencialesCache;
        }
        
        try {
            // Si no tenemos la empresa en caché, obtenerla una sola vez
            if ($this->empresaCache === null) {
                $this->empresaCache = \App\Models\Empresa::first();
            }
            
            $empresa = $this->empresaCache;
            
            if ($empresa && $empresa->alegra_email && $empresa->alegra_token) {
                // Usar credenciales de la empresa
                $email = $empresa->alegra_email;
                $token = $empresa->alegra_token;
                Log::info('Usando credenciales de Alegra configuradas en la empresa');
            } else {
                // Usar credenciales del archivo .env como respaldo
                $email = config('alegra.user');
                $token = config('alegra.token');
                Log::info('Usando credenciales de Alegra del archivo .env');
            }
            
            if (empty($email) || empty($token)) {
                Log::error('Credenciales de Alegra vacías');
                $this->credencialesCache = [
                    'success' => false,
                    'message' => 'Credenciales de Alegra no configuradas'
                ];
                return $this->credencialesCache;
            }
            
            $this->credencialesCache = [
                'success' => true,
                'email' => $email,
                'token' => $token
            ];
            
            return $this->credencialesCache;
        } catch (\Exception $e) {
            Log::error('Error al obtener credenciales de Alegra: ' . $e->getMessage());
            $this->credencialesCache = [
                'success' => false,
                'message' => 'Error al obtener credenciales de Alegra: ' . $e->getMessage()
            ];
            return $this->credencialesCache;
        }
    }

    /**
     * Abre una factura directamente usando la API de Alegra con cURL
     * @param string $idFactura ID de la factura en Alegra
     * @return array
     */
    public function abrirFacturaDirecto($idFactura)
    {
        try {
            Log::info('Iniciando apertura directa de factura con cURL', [
                'id_factura' => $idFactura
            ]);
            
            // Obtener credenciales
            $credenciales = $this->obtenerCredencialesAlegra();
            if (!$credenciales['success']) {
                return $credenciales;
            }
            
            $email = $credenciales['email'];
            $token = $credenciales['token'];
            
            // Intentar múltiples formatos para abrir la factura
            $formatosApertura = [
                // Formato 1: Básico con efectivo
                [
                    'paymentForm' => 'CASH',
                    'paymentMethod' => 'CASH',
                    'paymentDueDate' => date('Y-m-d')
                ],
                // Formato 2: Con observaciones vacías
                [
                    'paymentForm' => 'CASH',
                    'paymentMethod' => 'CASH',
                    'paymentDueDate' => date('Y-m-d'),
                    'observations' => ''
                ],
                // Formato 3: Solo con forma de pago
                [
                    'paymentForm' => 'CASH'
                ],
                // Formato 4: Formato mínimo
                []
            ];
            
            foreach ($formatosApertura as $index => $formato) {
                Log::info("Intentando formato de apertura #{$index}", [
                    'formato' => $formato
                ]);
                
                $resultado = $this->intentarAperturaConFormato($idFactura, $formato, $email, $token);
                
                if ($resultado['success']) {
                    Log::info("Apertura exitosa con formato #{$index}");
                    return $resultado;
                }
                
                Log::warning("Formato #{$index} falló", [
                    'error' => $resultado['message']
                ]);
            }
            
            // Si todos los formatos fallan, intentar envío directo a DIAN
            Log::info('Todos los formatos de apertura fallaron, intentando envío directo a DIAN');
            return $this->enviarFacturaDianDirecto($idFactura);
            
        } catch (\Exception $e) {
            Log::error('Excepción al abrir factura directamente: ' . $e->getMessage(), [
                'id_factura' => $idFactura
            ]);
            
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Intenta abrir una factura con un formato específico
     */
    private function intentarAperturaConFormato($idFactura, $formato, $email, $token)
    {
        try {
            // Configurar cURL
            $ch = curl_init();
            $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/open";
            
            $datos = json_encode($formato);
            
            // Configurar opciones de cURL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $datos);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($email . ':' . $token)
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            
            // Ejecutar la solicitud
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            // Registrar la respuesta
            Log::info('Respuesta de apertura de factura', [
                'http_code' => $httpCode,
                'response' => $response,
                'error' => $error,
                'formato_usado' => $formato
            ]);
            
            // Procesar la respuesta
            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                return [
                    'success' => true,
                    'message' => 'Factura abierta correctamente',
                    'data' => $data
                ];
            }
            
            return [
                'success' => false,
                'message' => "HTTP {$httpCode}: " . $response,
                'error' => $response
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Envía la factura directamente a la DIAN como último recurso
     */
    private function enviarFacturaDianDirecto($idFactura)
    {
        try {
            Log::info('Intentando envío directo a DIAN', [
                'id_factura' => $idFactura
            ]);
            
            // Obtener credenciales
            $credenciales = $this->obtenerCredencialesAlegra();
            if (!$credenciales['success']) {
                return $credenciales;
            }
            
            $email = $credenciales['email'];
            $token = $credenciales['token'];
            
            // Configurar cURL para envío a DIAN
            $ch = curl_init();
            $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/send-dian";
            
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($email . ':' . $token)
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            
            // Ejecutar la solicitud
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            Log::info('Respuesta de envío directo a DIAN', [
                'http_code' => $httpCode,
                'response' => $response,
                'error' => $error
            ]);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                return [
                    'success' => true,
                    'message' => 'Factura enviada directamente a DIAN',
                    'data' => $data
                ];
            }
            
            return [
                'success' => false,
                'message' => "Error en envío directo a DIAN: HTTP {$httpCode}: " . $response,
                'error' => $response
            ];
            
        } catch (\Exception $e) {
            Log::error('Error en envío directo a DIAN: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error en envío directo a DIAN: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene el cliente genérico (consumidor final) de Alegra
     * @return array
     */
    public function obtenerClienteGenerico()
    {
        try {
            Log::info('Buscando cliente genérico (consumidor final) en Alegra');
            
            // Obtener credenciales
            $credenciales = $this->obtenerCredencialesAlegra();
            if (!$credenciales['success']) {
                return [
                    'success' => false,
                    'message' => 'No se pudieron obtener las credenciales de Alegra: ' . $credenciales['message'],
                    'data' => null
                ];
            }
            
            // Configurar cURL
            $ch = curl_init();
            $url = "https://api.alegra.com/api/v1/contacts?identification=222222222222&type=client";
            
            // Configurar opciones de cURL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($credenciales['email'] . ':' . $credenciales['token'])
            ]);
            
            // Ejecutar la solicitud
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            // Registrar la respuesta
            Log::info('Respuesta de búsqueda de cliente genérico por cédula 222222222222', [
                'http_code' => $httpCode,
                'error' => $error,
                'response' => substr($response, 0, 500) // Limitar el tamaño del log
            ]);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                
                // Si no hay datos o no es un array, devolver error
                if (empty($data) || !is_array($data)) {
                    Log::warning('No se encontró el cliente genérico en Alegra con cédula 222222222222');
                    
                    return [
                        'success' => false,
                        'message' => 'No se encontró el cliente genérico en Alegra con cédula 222222222222',
                        'data' => null
                    ];
                }
                
                // Buscar el cliente con cédula "222222222222"
                $clienteGenerico = null;
                foreach ($data as $cliente) {
                    if (isset($cliente['identification']) && $cliente['identification'] === '222222222222') {
                        $clienteGenerico = $cliente;
                        break;
                    }
                }
                
                if ($clienteGenerico) {
                    Log::info('Cliente genérico encontrado por cédula', [
                        'id' => $clienteGenerico['id'],
                        'nombre' => $clienteGenerico['name'],
                        'cedula' => $clienteGenerico['identification']
                    ]);
                    
                    return [
                        'success' => true,
                        'message' => 'Cliente genérico encontrado',
                        'data' => $clienteGenerico
                    ];
                } else {
                    Log::warning('No se encontró el cliente genérico en Alegra con cédula 222222222222');
                    
                    return [
                        'success' => false,
                        'message' => 'No se encontró el cliente genérico en Alegra con cédula 222222222222',
                        'data' => null
                    ];
                }
            }
            
            Log::error('Error al buscar el cliente genérico en Alegra', [
                'http_code' => $httpCode,
                'response' => $response,
                'error' => $error
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al buscar el cliente genérico en Alegra: ' . $error,
                'data' => null
            ];
        } catch (\Exception $e) {
            Log::error('Excepción al buscar el cliente genérico en Alegra: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al buscar el cliente genérico en Alegra: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Obtiene los detalles de una factura emitida en Alegra, incluyendo el PDF y los datos para impresión
     * @param string $idFactura ID de la factura en Alegra
     * @return array
     */
    public function obtenerDetallesFacturaEmitida($idFactura)
    {
        try {
            Log::info('Obteniendo detalles de factura emitida', [
                'id_factura' => $idFactura
            ]);
            
            // Obtener credenciales
            $credenciales = $this->obtenerCredencialesAlegra();
            if (!$credenciales['success']) {
                return [
                    'success' => false,
                    'message' => 'No se pudieron obtener las credenciales de Alegra: ' . $credenciales['message'],
                    'data' => null
                ];
            }
            
            // Configurar cURL para obtener los detalles de la factura
            $ch = curl_init();
            $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}?metadata=true";
            
            // Configurar opciones de cURL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($credenciales['email'] . ':' . $credenciales['token'])
            ]);
            
            // Ejecutar la solicitud
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            // Registrar la respuesta
            Log::info('Respuesta de obtener detalles de factura', [
                'http_code' => $httpCode,
                'error' => $error,
                'response_length' => strlen($response)
            ]);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                $facturaData = json_decode($response, true);
                
                // Verificar si la factura existe y tiene datos
                if (empty($facturaData) || !isset($facturaData['id'])) {
                    Log::warning('No se encontraron datos de la factura', [
                        'id_factura' => $idFactura
                    ]);
                    
                    return [
                        'success' => false,
                        'message' => 'No se encontraron datos de la factura',
                        'data' => null
                    ];
                }
                
                // Obtener el PDF de la factura
                $pdfUrl = null;
                $dianStatus = null;
                
                // Verificar si la factura tiene metadatos
                if (isset($facturaData['metadata']) && is_array($facturaData['metadata'])) {
                    foreach ($facturaData['metadata'] as $metadata) {
                        // Buscar el enlace al PDF
                        if (isset($metadata['key']) && $metadata['key'] === 'dian_document_pdf') {
                            $pdfUrl = $metadata['value'];
                        }
                        
                        // Buscar el estado DIAN
                        if (isset($metadata['key']) && $metadata['key'] === 'dian_status') {
                            $dianStatus = $metadata['value'];
                        }
                    }
                }
                
                // Preparar los datos para devolver
                $resultado = [
                    'success' => true,
                    'message' => 'Detalles de factura obtenidos correctamente',
                    'data' => [
                        'factura' => $facturaData,
                        'pdf_url' => $pdfUrl,
                        'dian_status' => $dianStatus
                    ]
                ];
                
                Log::info('Detalles de factura obtenidos correctamente', [
                    'id_factura' => $idFactura,
                    'tiene_pdf' => !empty($pdfUrl),
                    'dian_status' => $dianStatus
                ]);
                
                return $resultado;
            }
            
            Log::error('Error al obtener detalles de factura', [
                'http_code' => $httpCode,
                'error' => $error
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al obtener detalles de factura: ' . $error,
                'data' => null
            ];
        } catch (\Exception $e) {
            Log::error('Excepción al obtener detalles de factura: ' . $e->getMessage(), [
                'id_factura' => $idFactura
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al obtener detalles de factura: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Obtener clientes de Alegra
     * @param array $params Parámetros de consulta (limit, start, etc.)
     * @return array
     */
    public function obtenerClientes($params = [])
    {
        try {
            // Obtener credenciales
            $credenciales = $this->obtenerCredencialesAlegra();
            if (!$credenciales['success']) {
                return $credenciales;
            }
            
            $email = $credenciales['email'];
            $token = $credenciales['token'];
            
            // Configurar cURL
            $ch = curl_init();
            $url = $this->baseUrl . self::ENDPOINT_CLIENTES;
            
            // Añadir parámetros de consulta si existen
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            
            // Configurar opciones de cURL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($email . ':' . $token)
            ]);
            
            // Ejecutar la solicitud
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            // Registrar la respuesta
            Log::info('Respuesta de obtención de clientes', [
                'http_code' => $httpCode,
                'error' => $error
            ]);
            
            // Procesar la respuesta
            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                return [
                    'success' => true,
                    'message' => 'Clientes obtenidos correctamente',
                    'data' => $data
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Error al obtener clientes: ' . $response,
                'error' => $error
            ];
        } catch (\Exception $e) {
            Log::error('Excepción al obtener clientes: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Excepción al obtener clientes: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene TODOS los clientes de Alegra usando paginación
     * 
     * @return array
     */
    public function obtenerTodosLosClientesPaginados()
    {
        try {
            $todosLosClientes = [];
            $start = 0;
            $limit = 30; // Máximo por página según API de Alegra
            $totalObtenidos = 0;
            
            \Log::info('Iniciando obtención paginada de clientes de Alegra');
            
            do {
                // Obtener página actual
                $params = [
                    'start' => $start,
                    'limit' => $limit
                ];
                
                \Log::info("Obteniendo clientes - Página desde {$start} hasta " . ($start + $limit));
                
                $respuesta = $this->obtenerClientes($params);
                
                if (!$respuesta['success']) {
                    return [
                        'success' => false,
                        'message' => 'Error al obtener página de clientes: ' . ($respuesta['message'] ?? 'Error desconocido'),
                        'data' => []
                    ];
                }
                
                $clientesPagina = $respuesta['data'];
                $cantidadPagina = count($clientesPagina);
                
                // Agregar clientes de esta página al total
                $todosLosClientes = array_merge($todosLosClientes, $clientesPagina);
                $totalObtenidos += $cantidadPagina;
                
                \Log::info("Página obtenida: {$cantidadPagina} clientes. Total acumulado: {$totalObtenidos}");
                
                // Preparar para siguiente página
                $start += $limit;
                
                // Continuar mientras la página actual tenga clientes
                // Si una página tiene menos clientes que el límite, es la última página
            } while ($cantidadPagina == $limit);
            
            \Log::info("Obtención paginada de clientes completada. Total de clientes obtenidos: {$totalObtenidos}");
            
            return [
                'success' => true,
                'message' => "Se obtuvieron {$totalObtenidos} clientes de Alegra",
                'data' => $todosLosClientes,
                'total' => $totalObtenidos
            ];
            
        } catch (\Exception $e) {
            \Log::error('Error en obtención paginada de clientes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al obtener clientes paginados: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Obtiene TODOS los productos de Alegra usando paginación
     * 
     * @return array
     */
    public function obtenerTodosLosProductosPaginados()
    {
        try {
            $todosLosProductos = [];
            $start = 0;
            $limit = 30; // Máximo por página según API de Alegra (límite real es 30)
            $totalObtenidos = 0;
            
            \Log::info('Iniciando obtención paginada de productos de Alegra');
            
            do {
                // Obtener página actual
                $params = [
                    'start' => $start,
                    'limit' => $limit
                ];
                
                \Log::info("Obteniendo productos - Página desde {$start} hasta " . ($start + $limit));
                
                $respuesta = $this->obtenerProductos($params);
                
                if (!$respuesta['success']) {
                    return [
                        'success' => false,
                        'message' => 'Error al obtener página de productos: ' . ($respuesta['message'] ?? 'Error desconocido'),
                        'data' => []
                    ];
                }
                
                $productosPagina = $respuesta['data'];
                $cantidadPagina = count($productosPagina);
                
                // Agregar productos de esta página al total
                $todosLosProductos = array_merge($todosLosProductos, $productosPagina);
                $totalObtenidos += $cantidadPagina;
                
                \Log::info("Página obtenida: {$cantidadPagina} productos. Total acumulado: {$totalObtenidos}");
                
                // Preparar para siguiente página
                $start += $limit;
                
                // Continuar mientras la página actual tenga productos
                // Si una página tiene menos productos que el límite, es la última página
            } while ($cantidadPagina == $limit);
            
            \Log::info("Sincronización paginada completada. Total de productos obtenidos: {$totalObtenidos}");
            
            return [
                'success' => true,
                'message' => "Se obtuvieron {$totalObtenidos} productos de Alegra",
                'data' => $todosLosProductos,
                'total' => $totalObtenidos
            ];
            
        } catch (\Exception $e) {
            \Log::error('Error en obtención paginada de productos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al obtener productos paginados: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Obtiene productos de Alegra con parámetros específicos
     * 
     * @param array $params Parámetros de consulta (limit, start, etc.)
     * @return array
     */
    public function obtenerProductos($params = [])
    {
        try {
            // Obtener credenciales
            $credenciales = $this->obtenerCredencialesAlegra();
            if (!$credenciales['success']) {
                return $credenciales;
            }
            
            $email = $credenciales['email'];
            $token = $credenciales['token'];
            
            // Configurar cURL
            $ch = curl_init();
            $url = $this->baseUrl . self::ENDPOINT_PRODUCTOS;
            
            // Añadir parámetros de consulta si existen
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            
            // Configurar opciones de cURL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout de 30 segundos
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Timeout de conexión 10 segundos
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($email . ':' . $token)
            ]);
            
            // Ejecutar la solicitud
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            // Registrar la respuesta
            Log::info('Respuesta de obtención de productos', [
                'http_code' => $httpCode,
                'error' => $error
            ]);
            
            // Procesar la respuesta
            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                return [
                    'success' => true,
                    'message' => 'Productos obtenidos correctamente',
                    'data' => $data
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Error al obtener productos: ' . $response,
                'error' => $error
            ];
        } catch (\Exception $e) {
            Log::error('Excepción al obtener productos: ' . $e->getMessage());
            
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // El método crearClienteAlegra ya está implementado anteriormente en el archivo
    
    // El método crearProductoAlegra ya está implementado anteriormente en el archivo
    
    /**
     * Sincroniza todos los clientes de Alegra con la base de datos local
     * 
     * @return array
     */
    public function sincronizarTodosLosClientes()
    {
        try {
            \Log::info('Iniciando sincronización de clientes con Alegra');
            
            // Obtener TODOS los clientes de Alegra usando paginación
            $clientesAlegra = $this->obtenerTodosLosClientesPaginados();
            
            if (!$clientesAlegra['success']) {
                return [
                    'success' => false,
                    'error' => 'Error al obtener clientes de Alegra: ' . ($clientesAlegra['message'] ?? 'Error desconocido')
                ];
            }
            
            $clientesData = $clientesAlegra['data'];
            $total = count($clientesData);
            $sincronizados = 0;
            $errores = 0;
            
            \Log::info('Se encontraron ' . $total . ' clientes en Alegra');
            
            // Recorrer cada cliente de Alegra
            foreach ($clientesData as $clienteAlegra) {
                try {
                    // Verificar si ya existe un cliente con ese ID de Alegra
                    $clienteExistente = \App\Models\Cliente::where('id_alegra', $clienteAlegra['id'])->first();
                    
                    // Si no existe por ID, buscar por cédula/identificación
                    if (!$clienteExistente && isset($clienteAlegra['identificationObject']['number'])) {
                        $clienteExistente = \App\Models\Cliente::where('cedula', $clienteAlegra['identificationObject']['number'])->first();
                    }
                    
                    // Preparar datos del cliente
                    $datosCliente = [
                        'nombres' => $clienteAlegra['nameObject']['firstName'] ?? $clienteAlegra['name'] ?? 'Sin nombre',
                        'apellidos' => $clienteAlegra['nameObject']['lastName'] ?? '',
                        'cedula' => $clienteAlegra['identificationObject']['number'] ?? '',
                        'telefono' => $clienteAlegra['phonePrimary'] ?? '',
                        'email' => $clienteAlegra['email'] ?? '',
                        'direccion' => $clienteAlegra['address']['address'] ?? '',
                        'ciudad' => $clienteAlegra['address']['city'] ?? 'Bogotá D.C.',
                        'departamento' => $clienteAlegra['address']['department'] ?? 'Bogotá',
                        'tipo_documento' => $clienteAlegra['identificationObject']['type'] ?? 'CC',
                        'tipo_persona' => $clienteAlegra['kindOfPerson'] ?? 'PERSON_ENTITY',
                        'regimen' => $clienteAlegra['regime'] ?? 'SIMPLIFIED_REGIME',
                        'id_alegra' => $clienteAlegra['id'],
                        'estado' => 1
                    ];
                    
                    if ($clienteExistente) {
                        // Actualizar cliente existente
                        $clienteExistente->update($datosCliente);
                        \Log::info('Cliente actualizado: ' . $clienteExistente->id . ' - ' . $clienteExistente->nombres);
                    } else {
                        // Crear nuevo cliente
                        $nuevoCliente = \App\Models\Cliente::create($datosCliente);
                        \Log::info('Cliente creado: ' . $nuevoCliente->id . ' - ' . $nuevoCliente->nombres);
                    }
                    
                    $sincronizados++;
                } catch (\Exception $e) {
                    \Log::error('Error al sincronizar cliente de Alegra', [
                        'cliente_id' => $clienteAlegra['id'] ?? 'No disponible',
                        'error' => $e->getMessage()
                    ]);
                    $errores++;
                }
            }
            
            return [
                'success' => true,
                'message' => 'Sincronización de clientes completada',
                'data' => [
                    'total' => $total,
                    'sincronizados' => $sincronizados,
                    'errores' => $errores
                ]
            ];
            
        } catch (\Exception $e) {
            \Log::error('Excepción al sincronizar clientes con Alegra', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'Error al sincronizar clientes: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Sincroniza todos los productos de Alegra con la base de datos local
     * 
     * @return array
     */
    public function sincronizarTodosLosProductos()
    {
        try {
            \Log::info('Iniciando sincronización de productos con Alegra');
            
            // Obtener TODOS los productos de Alegra usando paginación
            $todosLosProductos = $this->obtenerTodosLosProductosPaginados();
            
            if (!$todosLosProductos['success']) {
                return [
                    'success' => false,
                    'error' => 'Error al obtener productos de Alegra: ' . ($todosLosProductos['message'] ?? 'Error desconocido')
                ];
            }
            
            $productosData = $todosLosProductos['data'];
            $total = count($productosData);
            $sincronizados = 0;
            $errores = 0;
            
            \Log::info('Se encontraron ' . $total . ' productos en Alegra');
            
            // Recorrer cada producto de Alegra
            foreach ($productosData as $productoAlegra) {
                try {
                    // Verificar si ya existe un producto con ese ID de Alegra
                    $productoExistente = \App\Models\Producto::where('id_alegra', $productoAlegra['id'])->first();
                    
                    // Si no existe por ID, buscar por código/referencia
                    if (!$productoExistente && isset($productoAlegra['reference'])) {
                        $productoExistente = \App\Models\Producto::where('codigo', $productoAlegra['reference'])->first();
                    }
                    
                    // Preparar datos del producto con valores seguros
                    $precioVenta = floatval($productoAlegra['price'] ?? 0);
                    $precioCompra = floatval($productoAlegra['inventory']['unitCost'] ?? 0);
                    $stockAlegra = intval($productoAlegra['inventory']['availableQuantity'] ?? 0);
                    
                    // Si no hay stock en Alegra, asignar un mínimo de 5 unidades
                    $stock = $stockAlegra > 0 ? $stockAlegra : 5;
                    
                    \Log::info("Stock procesado para producto {$productoAlegra['id']}", [
                        'stock_alegra' => $stockAlegra,
                        'stock_asignado' => $stock,
                        'aplicado_minimo' => $stockAlegra == 0
                    ]);
                    
                    // Obtener el régimen tributario de la empresa para calcular IVA correcto
                    $empresa = \App\Models\Empresa::first();
                    $valorIva = 0.00; // Por defecto para no responsables de IVA
                    $precioFinal = $precioVenta;
                    
                    if ($empresa && $empresa->regimen_tributario === 'responsable_iva') {
                        // Para responsables de IVA, calcular el 19%
                        $valorIva = $precioVenta * 0.19;
                        $precioFinal = $precioVenta + $valorIva;
                    } elseif ($empresa && $empresa->regimen_tributario === 'regimen_simple') {
                        // Para régimen simple, también manejar IVA
                        $valorIva = $precioVenta * 0.19;
                        $precioFinal = $precioVenta + $valorIva;
                    }
                    // Para 'no_responsable_iva' se mantiene valor_iva = 0.00
                    
                    $datosProducto = [
                        'nombre' => $productoAlegra['name'] ?? 'Sin nombre',
                        'descripcion' => $productoAlegra['description'] ?? null,
                        'codigo' => $productoAlegra['reference'] ?? 'ALG-' . $productoAlegra['id'],
                        'precio_venta' => $precioVenta,
                        'precio_compra' => $precioCompra,
                        'precio_final' => $precioFinal, // Precio con IVA incluido según régimen
                        'valor_iva' => $valorIva, // IVA calculado según régimen tributario
                        'porcentaje_ganancia' => $precioCompra > 0 ? (($precioVenta - $precioCompra) / $precioCompra) * 100 : 0,
                        'stock' => $stock,
                        'stock_minimo' => 1, // Stock mínimo por defecto
                        'unidad_medida' => $productoAlegra['inventory']['unit'] ?? 'unidad',
                        'peso_bulto' => null,
                        'es_producto_base' => true,
                        'permite_conversiones' => false,
                        'id_alegra' => $productoAlegra['id'],
                        'estado' => 'activo'
                    ];
                    
                    if ($productoExistente) {
                        // Actualizar producto existente
                        $productoExistente->update($datosProducto);
                        \Log::info('Producto actualizado: ' . $productoExistente->id . ' - ' . $productoExistente->nombre);
                    } else {
                        // Crear nuevo producto
                        $nuevoProducto = \App\Models\Producto::create($datosProducto);
                        \Log::info('Producto creado: ' . $nuevoProducto->id . ' - ' . $nuevoProducto->nombre);
                    }
                    
                    $sincronizados++;
                } catch (\Exception $e) {
                    \Log::error('Error al sincronizar producto de Alegra', [
                        'producto_id' => $productoAlegra['id'] ?? 'No disponible',
                        'error' => $e->getMessage()
                    ]);
                    $errores++;
                }
            }
            
            return [
                'success' => true,
                'message' => 'Sincronización de productos completada',
                'data' => [
                    'total' => $total,
                    'sincronizados' => $sincronizados,
                    'errores' => $errores
                ]
            ];
            
        } catch (\Exception $e) {
            \Log::error('Excepción al sincronizar productos con Alegra', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'Error al sincronizar productos: ' . $e->getMessage()
            ];
        }
    }
}
