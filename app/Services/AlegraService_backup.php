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
     * Probar la conexiÃ³n con Alegra
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
            $response = $this->http->get('company');
            
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

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error ' . $response->status()
            ];
        } catch (\Exception $e) {
            Log::error('Error en test de conexiÃ³n Alegra', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
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
                'identification' => $cliente->cedula,
                'email' => $cliente->email ?: 'sin@email.com',
                'phonePrimary' => $cliente->telefono ?: '0000000000',
                'address' => [
                    'address' => $cliente->direccion ?? 'Sin direcciÃ³n'
                ],
                'type' => 'client',
                'kindOfPerson' => 'PERSON_ENTITY',
                'regime' => 'SIMPLIFIED_REGIME'
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
            // Configurar cliente HTTP
            $this->configurarClienteHttp();
            
            // Realizar la solicitud para obtener los detalles de la factura
            $response = $this->getHttpClient()->get("invoices/{$idFactura}", [
                'query' => [
                    'expand' => 'items,client,payments,attachments,observations,metadata'
                ]
            ]);
            
            $data = $response->json();
            $statusCode = $response->status();
            
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
            
            // Configurar cliente HTTP
            $this->configurarClienteHttp();
            
            // Realizar la solicitud para descargar el PDF
            $response = Http::withBasicAuth($this->user, $this->token)
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
                'error' => $error
            ]);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                
                // Filtrar las plantillas segÃºn el tipo solicitado
                $plantillasActivas = array_filter($data, function($plantilla) use ($tipo) {
                    $esElectronica = isset($plantilla['isElectronic']) && $plantilla['isElectronic'] === true;
                    $estaActiva = isset($plantilla['status']) && $plantilla['status'] === 'active';
                    
                    if ($tipo === 'electronica') {
                        return $esElectronica && $estaActiva;
                    } else {
                        return !$esElectronica && $estaActiva;
                    }
                });
                
                // Si hay plantillas activas, devolver la primera
                if (!empty($plantillasActivas)) {
                    $plantilla = reset($plantillasActivas);
                    
                    Log::info('ResoluciÃ³n preferida encontrada', [
                        'id' => $plantilla['id'],
                        'nombre' => $plantilla['name'] ?? 'Sin nombre',
                        'tipo' => $tipo
                    ]);
                    
                    return [
                        'success' => true,
                        'message' => 'ResoluciÃ³n preferida encontrada',
                        'plantilla' => $plantilla,
                        'plantillas' => $plantillasActivas
                    ];
                }
                
                Log::warning('No se encontraron plantillas de numeraciÃ³n activas', [
                    'tipo' => $tipo
                ]);
                
                return [
                    'success' => false,
                    'message' => 'No se encontraron plantillas de numeraciÃ³n activas',
                    'plantillas' => []
                ];
            }
            
            Log::error('Error al obtener plantillas de numeraciÃ³n', [
                'http_code' => $httpCode,
                'response' => $response,
                'error' => $error
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al obtener plantillas de numeraciÃ³n: ' . $response,
                'error' => $error
            ];
        } catch (\Exception $e) {
            Log::error('ExcepciÃ³n al obtener resoluciÃ³n preferida: ' . $e->getMessage(), [
                'tipo' => $tipo
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al obtener resoluciÃ³n preferida: ' . $e->getMessage()
            ];
        }
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
        try {
            // Intentar obtener las credenciales de la empresa
            $empresa = \App\Models\Empresa::first();
            
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
                return [
                    'success' => false,
                    'message' => 'Credenciales de Alegra no configuradas'
                ];
            }
            
            return [
                'success' => true,
                'email' => $email,
                'token' => $token
            ];
        } catch (\Exception $e) {
            Log::error('Error al obtener credenciales de Alegra: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener credenciales de Alegra: ' . $e->getMessage()
            ];
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
            
            // Configurar cURL
            $ch = curl_init();
            $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/open";
            
            // Datos con el formato correcto para abrir la factura
            $datos = json_encode([
                'paymentForm' => 'CASH',
                'paymentMethod' => 'CASH'
            ]);
            
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
            
            // Ejecutar la solicitud
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            // Registrar la respuesta
            Log::info('Respuesta de apertura de factura con cURL', [
                'http_code' => $httpCode,
                'response' => $response,
                'error' => $error
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
                'message' => 'Error al abrir la factura',
                'error' => $response
            ];
        } catch (\Exception $e) {
            Log::error('Excepción al abrir factura directamente: ' . $e->getMessage(), [
                'id_factura' => $idFactura
            ]);
            
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
