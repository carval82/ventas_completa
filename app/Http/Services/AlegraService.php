<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AlegraService
{
    protected $baseUrl;
    protected $auth;
    protected $http;
    protected $credencialesValidas;
    
    /**
     * Número máximo de reintentos para conexiones fallidas
     */
    protected $maxReintentos = 3;
    
    /**
     * Tiempo de espera entre reintentos (en segundos)
     */
    protected $tiempoEsperaReintento = 2;

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
            $empresa = \App\Models\Empresa::first();
            
            if ($empresa && !empty($empresa->alegra_email) && !empty($empresa->alegra_token)) {
                // Usar credenciales de la empresa
                $email = $empresa->alegra_email;
                $token = $empresa->alegra_token;
                Log::info('Usando credenciales de Alegra configuradas en la empresa');
            } else {
                // Si no hay credenciales en la base de datos, intentar obtenerlas de la configuración
                $email = config('alegra.user');
                $token = config('alegra.token');
                
                if (!empty($email) && !empty($token)) {
                    Log::info('Usando credenciales de Alegra configuradas en el archivo .env');
                } else {
                    // Si no hay credenciales configuradas, marcar el servicio como no inicializado correctamente
                    Log::warning('No se encontraron credenciales de Alegra válidas. Algunas funciones no estarán disponibles.');
                    $email = '';
                    $token = '';
                    $this->credencialesValidas = false;
                }
            }
        }
        
        $this->credencialesValidas = !empty($email) && !empty($token);
        
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
            
            // No hacer una petición de prueba automáticamente al construir el objeto
            // para evitar errores en la inicialización
        } else {
            // Crear un cliente HTTP sin autenticación para evitar errores
            $this->http = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->baseUrl($this->baseUrl);
        }
    }

    /**
     * Verificar conexión con Alegra
     */
    private function testConnection()
    {
        if (!$this->credencialesValidas) {
            Log::warning('No se puede probar la conexión: credenciales no válidas');
            return false;
        }
        
        try {
            $response = $this->http->get('/company');
            
            Log::info('Test de conexión Alegra', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            if (!$response->successful()) {
                Log::warning('Falló el test de conexión Alegra', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error en test de conexión Alegra', [
                'error' => $e->getMessage()
            ]);
            return false;
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
            // Convertir los datos a formato JSON para depuración
            $jsonData = json_encode($datos, JSON_PRETTY_PRINT);
            
            // Log completo de los datos recibidos
            Log::info('Datos recibidos para crear factura en Alegra', [
                'datos_completos' => $jsonData
            ]);
            
            // Preparar los datos para la API de Alegra - FORMATO EXACTO SEGÚN DOCUMENTACIÓN
            $requestData = [
                'date' => $datos['date'] ?? now()->format('Y-m-d'),
                'dueDate' => $datos['dueDate'] ?? now()->format('Y-m-d'),
                'client' => $datos['client']
            ];
            
            // Agregar los ítems exactamente como vienen
            $requestData['items'] = $datos['items'];
            
            // Campos requeridos para factura electrónica
            $requestData['paymentForm'] = $datos['paymentForm'] ?? 'CASH';
            $requestData['paymentMethod'] = $datos['paymentMethod'] ?? 'CASH';
            $requestData['term'] = $datos['term'] ?? 'De contado';
            
            // CLAVE: Crear directamente como OPEN para evitar problemas de apertura
            $requestData['status'] = 'open';
            
            // Agregar el método de pago
            if (isset($datos['payment'])) {
                $requestData['payment'] = $datos['payment'];
            }
            
            // Agregar facturación electrónica
            if (isset($datos['useElectronicInvoice'])) {
                $requestData['useElectronicInvoice'] = $datos['useElectronicInvoice'];
            }
            
            // Agregar observaciones si existen
            if (isset($datos['observations'])) {
                $requestData['observations'] = $datos['observations'];
            }
            
            // Agregar impuestos a nivel de factura si existen
            if (isset($datos['tax'])) {
                $requestData['tax'] = $datos['tax'];
            }
            
            // Convertir a JSON para enviar a Alegra
            $jsonRequestData = json_encode($requestData);
            
            // Log de los datos que se enviarán a Alegra
            Log::info('Datos que se enviarán a Alegra', [
                'json_request' => $jsonRequestData
            ]);
            
            // Realizar la petición a la API de Alegra
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->auth,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/invoices', $requestData);
            
            // Registrar la respuesta completa de Alegra
            Log::info('Respuesta completa de Alegra', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['detail'] ?? $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Error en servicio Alegra', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener lista de productos
     */
    public function obtenerProductos()
    {
        try {
            $reintentos = 0;
            $maxReintentos = $this->maxReintentos;
            $tiempoEsperaReintento = $this->tiempoEsperaReintento;
            
            while ($reintentos <= $maxReintentos) {
                try {
                    $response = $this->http->get('/api/v1/items');
                    return $response->successful() 
                        ? ['success' => true, 'data' => $response->json()]
                        : ['success' => false, 'error' => $response->json()['message'] ?? 'Error'];
                } catch (\Exception $e) {
                    Log::error('Error en servicio Alegra', [
                        'error' => $e->getMessage()
                    ]);
                    
                    $reintentos++;
                    
                    if ($reintentos <= $maxReintentos) {
                        Log::info('Reintento de conexión Alegra', [
                            'reintentos' => $reintentos,
                            'max_reintentos' => $maxReintentos
                        ]);
                        
                        sleep($tiempoEsperaReintento);
                    } else {
                        return ['success' => false, 'error' => $e->getMessage()];
                    }
                }
            }
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
     * Cambiar el estado de una factura de borrador a definitiva
     * 
     * @param int $facturaId ID de la factura en Alegra
     * @return array Resultado de la operación
     */
    public function cambiarEstadoFacturaADefinitiva($facturaId)
    {
        try {
            Log::info('Iniciando cambio de estado de factura a definitiva', [
                'factura_id' => $facturaId
            ]);
            
            // Verificar primero el estado actual de la factura
            $facturaResponse = $this->http->get("/api/v1/invoices/{$facturaId}");
            
            if (!$facturaResponse->successful()) {
                return [
                    'success' => false,
                    'mensaje' => 'Error al obtener información de la factura',
                    'error' => $facturaResponse->json()['message'] ?? $facturaResponse->json()['detail'] ?? 'Error desconocido'
                ];
            }
            
            $factura = $facturaResponse->json();
            $estadoActual = $factura['status'] ?? '';
            
            // Si ya está en estado definitivo, no necesitamos hacer nada
            if ($estadoActual === 'open') {
                return [
                    'success' => true,
                    'mensaje' => 'La factura ya está en estado definitivo',
                    'data' => $factura
                ];
            }
            
            // Si está en borrador, cambiarla a definitiva
            if ($estadoActual === 'draft') {
                Log::info('Cambiando estado de factura de borrador a definitiva', [
                    'factura_id' => $facturaId
                ]);
                
                // Endpoint para cambiar estado de la factura
                $response = $this->http->put("/api/v1/invoices/{$facturaId}/status", [
                    'status' => 'open'
                ]);
                
                if ($response->successful()) {
                    return [
                        'success' => true,
                        'mensaje' => 'Estado de factura cambiado a definitivo correctamente',
                        'data' => $response->json()
                    ];
                }
                
                return [
                    'success' => false,
                    'mensaje' => 'Error al cambiar estado de factura a definitivo',
                    'error' => $response->json()['message'] ?? $response->json()['detail'] ?? 'Error desconocido'
                ];
            }
            
            // Si está en otro estado, reportarlo
            return [
                'success' => false,
                'mensaje' => "La factura está en un estado inesperado: {$estadoActual}",
                'data' => $factura
            ];
            
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado de factura a definitivo', [
                'factura_id' => $facturaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'mensaje' => 'Error al cambiar estado de factura a definitivo',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Emitir factura electrónica
     * 
     * @param string $facturaId ID de la factura en Alegra
     * @param int $maxIntentos Número máximo de intentos para abrir la factura
     * @param int $tiempoEspera Tiempo de espera entre intentos en segundos
     * @return array Resultado de la operación
     */
    public function emitirFacturaElectronica($facturaId, $maxIntentos = 3, $tiempoEspera = 2)
    {
        try {
            Log::info('Iniciando emisión de factura electrónica', [
                'factura_id' => $facturaId,
                'max_intentos' => $maxIntentos,
                'tiempo_espera' => $tiempoEspera
            ]);
            
            // Verificar el estado actual de la factura
            $estadoActual = $this->consultarEstadoFactura($facturaId);
            
            if (!$estadoActual['success']) {
                Log::error('Error al consultar estado de la factura', [
                    'factura_id' => $facturaId,
                    'error' => $estadoActual['message'] ?? 'Error desconocido'
                ]);
                return $estadoActual;
            }
            
            // Si la factura ya está en estado 'open', no es necesario abrirla
            if ($estadoActual['data']['status'] === 'open') {
                Log::info('La factura ya está en estado open, procediendo directamente a enviar a DIAN', [
                    'factura_id' => $facturaId
                ]);
            } else {
                // Intentar abrir la factura con el método mejorado
                Log::info('Intentando abrir factura', ['factura_id' => $facturaId]);
                
                $intentos = 0;
                $exito = false;
                
                while (!$exito && $intentos < $maxIntentos) {
                    $intentos++;
                    
                    Log::info("Intento {$intentos} de {$maxIntentos} para abrir factura", ['factura_id' => $facturaId]);
                    
                    try {
                        // Alternar entre formulario estándar y vacío en cada intento
                        $usarFormularioVacio = ($intentos % 2 == 0);
                        
                        $resultadoApertura = $this->abrirFacturaDirecto($facturaId, $usarFormularioVacio);
                        $exito = $resultadoApertura['success'];
                        
                        if ($exito) {
                            Log::info("Factura abierta exitosamente en el intento {$intentos}", [
                                'factura_id' => $facturaId,
                                'formato_exitoso' => $resultadoApertura['formato_exitoso'] ?? 'desconocido'
                            ]);
                            break;
                        }
                    } catch (\Exception $e) {
                        Log::error("Error en el intento {$intentos} para abrir factura", [
                            'factura_id' => $facturaId,
                            'error' => $e->getMessage()
                        ]);
                    }
                    
                    if (!$exito && $intentos < $maxIntentos) {
                        Log::info("Esperando {$tiempoEspera} segundos antes del siguiente intento", ['factura_id' => $facturaId]);
                        sleep($tiempoEspera);
                    }
                }
                
                if (!$exito) {
                    Log::error("No se pudo abrir la factura después de {$maxIntentos} intentos", ['factura_id' => $facturaId]);
                    return [
                        'success' => false,
                        'message' => "No se pudo abrir la factura después de {$maxIntentos} intentos"
                    ];
                }
            }
            
            // Verificar nuevamente el estado de la factura antes de enviar a DIAN
            $estadoFinal = $this->consultarEstadoFactura($facturaId);
            
            if (!$estadoFinal['success']) {
                Log::error('Error al verificar estado final de la factura', [
                    'factura_id' => $facturaId,
                    'error' => $estadoFinal['message'] ?? 'Error desconocido'
                ]);
                return $estadoFinal;
            }
            
            if ($estadoFinal['data']['status'] !== 'open') {
                Log::error('La factura no está en estado open después de los intentos de apertura', [
                    'factura_id' => $facturaId,
                    'estado_actual' => $estadoFinal['data']['status']
                ]);
                return [
                    'success' => false,
                    'message' => 'La factura no está en estado open, no se puede enviar a DIAN',
                    'estado_actual' => $estadoFinal['data']['status']
                ];
            }
            
            // Si llegamos aquí, la factura está en estado 'open', ahora enviar a DIAN
            Log::info('Factura en estado open, enviando a DIAN', ['factura_id' => $facturaId]);
            
            // Enviar a DIAN
            $resultadoDian = $this->enviarFacturaADian($facturaId);
            
            return $resultadoDian;
        } catch (\Exception $e) {
            Log::error('Excepción al emitir factura electrónica', [
                'factura_id' => $facturaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Verificar el estado de una factura electrónica en la DIAN
     * 
     * @param int $facturaId ID de la factura en Alegra
     * @return array Resultado de la operación con el estado actual
     */
    public function verificarEstadoFacturaElectronica($facturaId)
    {
        try {
            // Obtener detalles de la factura incluyendo estado de facturación electrónica
            $response = $this->http->get("/api/v1/invoices/{$facturaId}?expand=dianStatus");
            
            if ($response->successful()) {
                $factura = $response->json();
                $estadoDian = $factura['dianStatus'] ?? null;
                
                Log::info('Estado de factura electrónica obtenido', [
                    'factura_id' => $facturaId,
                    'estado_dian' => $estadoDian
                ]);
                
                return [
                    'success' => true,
                    'estado' => $estadoDian,
                    'factura' => $factura
                ];
            }
            
            return [
                'success' => false,
                'mensaje' => 'Error al verificar estado de factura electrónica',
                'error' => $response->json()['message'] ?? $response->json()['detail'] ?? 'Error desconocido'
            ];
            
        } catch (\Exception $e) {
            Log::error('Error al verificar estado de factura electrónica', [
                'factura_id' => $facturaId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'mensaje' => 'Error al verificar estado de factura electrónica',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener lista de clientes
     */
    public function obtenerClientes()
    {
        try {
            Log::info('Obteniendo lista de clientes de Alegra');

            $response = $this->http->get('/contacts', [
                'query' => [
                    'type' => 'client',
                    'metadata' => 'true'
                ]
            ]);
            
            Log::info('Respuesta obtención clientes', [
                'status' => $response->status(),
                'body_sample' => substr(json_encode($response->json()), 0, 200) . '...'
            ]);

            return $response->successful() 
                ? ['success' => true, 'data' => $response->json()]
                : ['success' => false, 'error' => $response->json()['message'] ?? 'Error al obtener clientes'];
        } catch (\Exception $e) {
            Log::error('Error al obtener clientes de Alegra', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crear cliente en Alegra a partir de un modelo Cliente
     */
    public function crearClienteAlegra($cliente)
    {
        try {
            Log::info('Creando cliente en Alegra desde modelo', [
                'cliente_id' => $cliente->id,
                'cedula' => $cliente->cedula ?? $cliente->identification ?? 'N/A'
            ]);

            // Construir nombre completo de forma segura
            $nombres = $cliente->nombres ?? $cliente->name ?? '';
            $apellidos = $cliente->apellidos ?? '';
            $nombreCompleto = trim($nombres . ' ' . $apellidos);
            
            // Si no hay nombre, usar un valor por defecto
            if (empty($nombreCompleto)) {
                $nombreCompleto = 'Cliente ' . ($cliente->cedula ?? $cliente->identification ?? $cliente->id);
            }

            // Determinar el tipo de identificación
            $tipoIdentificacion = 'CC'; // Por defecto Cédula de Ciudadanía
            if (isset($cliente->tipo_documento)) {
                $tipoIdentificacion = strtoupper($cliente->tipo_documento);
            } elseif (isset($cliente->identificationType)) {
                $tipoIdentificacion = strtoupper($cliente->identificationType);
            } else {
                // Determinar automáticamente basado en la longitud del documento
                $identificacion = $cliente->cedula ?? $cliente->identification ?? '';
                if (strlen($identificacion) >= 9) {
                    $tipoIdentificacion = 'NIT'; // Probablemente es NIT si tiene 9+ dígitos
                }
            }

            // Preparar dirección con departamento válido
            $direccion = $cliente->direccion ?? $cliente->address ?? '';
            $departamento = $cliente->departamento ?? 'Antioquia'; // Departamento por defecto
            $ciudad = $cliente->ciudad ?? 'Medellín'; // Ciudad por defecto
            
            // Determinar tipo de persona basado en el tipo de identificación (valores válidos de Alegra)
            // Probando con valores numéricos que son comunes en APIs colombianas
            $tipPersona = ($tipoIdentificacion === 'NIT') ? '2' : '1';
            
            // LÓGICA CORREGIDA: Basarse SIEMPRE en el tipo de documento
            // CC, CE, TI, PP, RC = Persona natural
            // NIT = Persona jurídica
            if ($tipoIdentificacion === 'NIT') {
                $tipoPersona = 'Persona jurídica';
            } else {
                // Para CC, CE, TI, PP, RC siempre es persona natural
                $tipoPersona = 'Persona natural';
            }
            
            Log::info('Tipo de persona determinado', [
                'tipo_identificacion' => $tipoIdentificacion,
                'tipo_persona_calculado' => $tipoPersona,
                'tipo_persona_bd' => $cliente->tipo_persona ?? 'no_definido'
            ]);
            
            // Usando valores REALES encontrados en Alegra
            $tipoPersonaValor = ($tipoIdentificacion === 'NIT') ? 'LEGAL_ENTITY' : 'PERSON_ENTITY';
            
            $clienteData = [
                'name' => $nombreCompleto,
                'nameObject' => [
                    'firstName' => $cliente->nombres ?? explode(' ', $nombreCompleto)[0] ?? '',
                    'lastName' => $cliente->apellidos ?? (isset(explode(' ', $nombreCompleto)[1]) ? implode(' ', array_slice(explode(' ', $nombreCompleto), 1)) : '')
                ],
                'identification' => $cliente->cedula ?? $cliente->identification ?? '',
                'identificationObject' => [
                    'type' => $tipoIdentificacion,
                    'number' => $cliente->cedula ?? $cliente->identification ?? ''
                ],
                'kindOfPerson' => $tipoPersonaValor,
                'email' => $cliente->email ?? null,
                'phone' => $cliente->telefono ?? $cliente->phone ?? null,
                'address' => [
                    'address' => $direccion,
                    'city' => $ciudad,
                    'department' => $departamento
                ],
                'type' => 'client'
            ];
            
            Log::info('Usando valores REALES de Alegra', [
                'kindOfPerson' => $tipoPersonaValor,
                'tipo_identificacion' => $tipoIdentificacion
            ]);

            Log::info('Creando cliente en Alegra', ['datos' => $clienteData]);

            return $this->crearCliente($clienteData);

        } catch (\Exception $e) {
            Log::error('Error al crear cliente', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
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

            // Determinar el formato de datos que se está usando
            $clienteData = [];
            
            if (isset($datos['name'])) {
                // Formato nuevo (desde crearClienteAlegra)
                // Separar nombres para nameObject
                $nombreCompleto = $datos['name'];
                $partesNombre = explode(' ', $nombreCompleto, 2);
                
                $clienteData = [
                    'name' => $datos['name'],
                    'nameObject' => [
                        'firstName' => $partesNombre[0] ?? '',
                        'lastName' => $partesNombre[1] ?? ''
                    ],
                    'identification' => $datos['identification'] ?? '',
                    'identificationObject' => $datos['identificationObject'] ?? [
                        'type' => 'CC',
                        'number' => $datos['identification'] ?? ''
                    ],
                    'kindOfPerson' => $datos['kindOfPerson'] ?? 'PERSON_ENTITY',
                    'email' => $datos['email'] ?? null,
                    'phone' => $datos['phone'] ?? null,
                    'address' => $datos['address'] ?? ['address' => ''],
                    'type' => $datos['type'] ?? 'client'
                ];
            } else {
                // Formato antiguo (compatibilidad)
                $tipoDoc = $datos['tipo_documento'] ?? 'CC';
                if (strlen($datos['cedula'] ?? '') >= 9) {
                    $tipoDoc = 'NIT';
                }
                
                $tipPersonaAntiguo = ($tipoDoc === 'NIT') ? 'LEGAL_ENTITY' : 'PERSON_ENTITY';
                
                $clienteData = [
                    'name' => ($datos['nombres'] ?? '') . ' ' . ($datos['apellidos'] ?? ''),
                    'nameObject' => [
                        'firstName' => $datos['nombres'] ?? '',
                        'lastName' => $datos['apellidos'] ?? ''
                    ],
                    'identification' => $datos['cedula'] ?? '',
                    'identificationObject' => [
                        'type' => strtoupper($tipoDoc),
                        'number' => $datos['cedula'] ?? ''
                    ],
                    'kindOfPerson' => $tipPersonaAntiguo,
                    'email' => $datos['email'] ?? null,
                    'phone' => $datos['telefono'] ?? null,
                    'address' => [
                        'address' => $datos['direccion'] ?? '',
                        'city' => $datos['ciudad'] ?? 'Medellín',
                        'department' => $datos['departamento'] ?? 'Antioquia'
                    ],
                    'type' => 'client'
                ];
            }

            Log::info('Datos finales para Alegra', ['clienteData' => $clienteData]);
            
            // Verificar específicamente que kindOfPerson esté presente
            Log::info('Verificando campo kindOfPerson', [
                'kindOfPerson_presente' => isset($clienteData['kindOfPerson']),
                'kindOfPerson_valor' => $clienteData['kindOfPerson'] ?? 'NO_DEFINIDO'
            ]);

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
     * Crear producto en Alegra a partir de un modelo Producto
     */
    public function crearProductoAlegra($producto)
    {
        try {
            Log::info('Creando producto en Alegra desde modelo', [
                'producto_id' => $producto->id,
                'codigo' => $producto->codigo
            ]);

            // Obtener el porcentaje de IVA del producto
            $ivaProducto = (float)$producto->iva;
            
            // Verificar si la empresa tiene habilitada la característica de múltiples impuestos
            $empresa = \App\Models\Empresa::first();
            $enviarImpuestos = $empresa && $empresa->alegra_multiples_impuestos ?? false;
            
            $productoData = [
                'name' => $producto->nombre,
                'reference' => $producto->codigo,
                'description' => $producto->descripcion ?? '',
                'price' => (float)$producto->precio_venta,
                'inventory' => [
                    'unit' => 'unit',
                    'unitCost' => (float)$producto->precio_compra ?? 0,
                    'initialQuantity' => (float)$producto->stock ?? 0
                ]
            ];
            
            // Solo agregar información de impuestos si la empresa lo soporta
            if ($enviarImpuestos) {
                $productoData['tax'] = [
                    'id' => 1, // ID estándar del IVA en Alegra
                    'percentage' => $ivaProducto > 0 ? $ivaProducto : 19
                ];
                
                Log::info('Enviando información de impuestos a Alegra', [
                    'iva_producto' => $ivaProducto
                ]);
            } else {
                Log::info('Omitiendo información de impuestos (empresa no soporta múltiples impuestos)', [
                    'iva_producto' => $ivaProducto
                ]);
            }
            
            Log::info('Datos de producto para Alegra', [
                'producto_id' => $producto->id,
                'iva_producto' => $ivaProducto,
                'datos_alegra' => $productoData
            ]);

            $response = $this->http->post('/items', $productoData);

            Log::info('Respuesta creación producto', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            return $response->successful()
                ? ['success' => true, 'data' => $response->json()]
                : ['success' => false, 'error' => $response->json()['message'] ?? 'Error al crear producto'];

        } catch (\Exception $e) {
            Log::error('Error al crear producto desde modelo', [
                'producto_id' => $producto->id,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Actualizar un producto existente en Alegra con su porcentaje de IVA correcto
     * @param \App\Models\Producto $producto
     * @return array
     */
    public function actualizarProductoAlegra($producto)
    {
        try {
            // Verificar que el producto tenga ID de Alegra
            if (!$producto->id_alegra) {
                Log::warning('Intentando actualizar producto sin ID de Alegra', [
                    'producto_id' => $producto->id
                ]);
                return ['success' => false, 'error' => 'El producto no tiene ID de Alegra'];
            }
            
            Log::info('Actualizando producto en Alegra', [
                'producto_id' => $producto->id,
                'alegra_id' => $producto->id_alegra,
                'iva' => $producto->iva
            ]);

            // Obtener el porcentaje de IVA del producto
            $ivaProducto = (float)$producto->iva;
            
            // Verificar si la empresa tiene habilitada la característica de múltiples impuestos
            $empresa = \App\Models\Empresa::first();
            $enviarImpuestos = $empresa && $empresa->alegra_multiples_impuestos ?? false;
            
            // Datos para actualizar
            $productoData = [
                'name' => $producto->nombre,
                'reference' => $producto->codigo,
                'description' => $producto->descripcion ?? '',
                'price' => (float)$producto->precio_venta
            ];
            
            // Solo agregar información de impuestos si la empresa lo soporta
            if ($enviarImpuestos) {
                $productoData['tax'] = [
                    'id' => 1, // ID estándar del IVA en Alegra
                    'percentage' => $ivaProducto > 0 ? $ivaProducto : 19
                ];
            }
            
            // Actualizar el producto en Alegra
            $response = $this->http->put('/items/' . $producto->id_alegra, $productoData);
            
            Log::info('Respuesta actualización producto', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);
            
            return $response->successful()
                ? ['success' => true, 'data' => $response->json()]
                : ['success' => false, 'error' => $response->json()['message'] ?? 'Error al actualizar producto'];
                
        } catch (\Exception $e) {
            Log::error('Error al actualizar producto en Alegra', [
                'producto_id' => $producto->id,
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
    
    /**
     * Determina si un error es de conexión y debe reintentarse
     * 
     * @param string $mensajeError Mensaje de error
     * @return bool True si es un error de conexión, false en caso contrario
     */
    protected function esErrorDeConexion($mensajeError)
    {
        $patronesErrorConexion = [
            'cURL error 28', // Timeout
            'cURL error 6',  // Could not resolve host
            'cURL error 7',  // Failed to connect
            'Connection refused',
            'Connection timed out',
            'Operation timed out',
            'Network is unreachable',
            'SSL connection timeout',
            'Connection reset',
            'Failed to connect',
            'Could not resolve host',
            'Resolving timed out'
        ];
        
        foreach ($patronesErrorConexion as $patron) {
            if (stripos($mensajeError, $patron) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Abrir una factura directamente usando cURL (cambiar estado de 'draft' a 'open')
     * Este método utiliza cURL directamente en lugar del cliente HTTP de Laravel
     * para evitar problemas con el cambio de estado
     * 
     * @param string $facturaId ID de la factura en Alegra
     * @param bool $usarFormularioVacio Si es true, envía un objeto JSON vacío
     * @return array Resultado de la operación
     */
    public function abrirFacturaDirecto($facturaId, $usarFormularioVacio = false)
    {
        try {
            Log::info('Iniciando apertura directa de factura con cURL', [
                'factura_id' => $facturaId,
                'usar_formulario_vacio' => $usarFormularioVacio
            ]);
            
            // Obtener credenciales
            $empresa = \App\Models\Empresa::first();
            
            if (!$empresa || empty($empresa->alegra_email) || empty($empresa->alegra_token)) {
                Log::error('No se encontraron credenciales de Alegra para abrir factura');
                return ['success' => false, 'message' => 'No se encontraron credenciales de Alegra'];
            }
            
            $email = $empresa->alegra_email;
            $token = $empresa->alegra_token;
            
            // Verificar el estado actual de la factura antes de intentar abrirla
            $estadoActual = $this->consultarEstadoFactura($facturaId);
            
            if (!$estadoActual['success']) {
                Log::error('Error al consultar estado actual de la factura', [
                    'factura_id' => $facturaId,
                    'error' => $estadoActual['error'] ?? 'Error desconocido'
                ]);
                return $estadoActual;
            }
            
            // Si la factura ya está en estado 'open', no es necesario cambiarla
            if ($estadoActual['data']['status'] === 'open') {
                Log::info('La factura ya está en estado open, no es necesario cambiarla', [
                    'factura_id' => $facturaId
                ]);
                return [
                    'success' => true,
                    'message' => 'La factura ya está en estado open',
                    'data' => $estadoActual['data']
                ];
            }
            
            // Si la factura no está en estado 'draft', puede haber problemas al cambiarla
            if ($estadoActual['data']['status'] !== 'draft') {
                Log::warning('La factura no está en estado draft, puede haber problemas al cambiarla', [
                    'factura_id' => $facturaId,
                    'estado_actual' => $estadoActual['data']['status']
                ]);
            }
            
            // Intentar abrir la factura con diferentes formatos de payload
            $formatos = [
                // Primero intentar con el formato especificado por el parámetro
                $usarFormularioVacio ? 'vacio' : 'completo',
                // Luego intentar con el formato alternativo
                $usarFormularioVacio ? 'completo' : 'vacio',
                // Finalmente intentar con el formato mínimo
                'minimo'
            ];
            
            // Eliminar duplicados
            $formatos = array_unique($formatos);
            
            foreach ($formatos as $formato) {
                Log::info('Intentando abrir factura con formato', [
                    'factura_id' => $facturaId,
                    'formato' => $formato
                ]);
                
                // Preparar los datos según el formato
                switch ($formato) {
                    case 'vacio':
                        $datos = json_encode([]);
                        break;
                    case 'minimo':
                        $datos = json_encode([
                            'paymentForm' => 'CASH'
                        ]);
                        break;
                    case 'completo':
                    default:
                        $datos = json_encode([
                            'paymentForm' => 'CASH',
                            'paymentMethod' => 'CASH'
                        ]);
                        break;
                }
                
                // Configurar cURL
                $ch = curl_init();
                $url = "https://api.alegra.com/api/v1/invoices/{$facturaId}/open";
                
                // Configurar opciones de cURL
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $datos);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15); // 15 segundos máximo
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 5 segundos para conectar
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
                    'factura_id' => $facturaId,
                    'formato' => $formato,
                    'http_code' => $httpCode,
                    'error' => $error,
                    'response' => $response
                ]);
                
                // Si la solicitud fue exitosa, verificar el estado actual
                if ($httpCode >= 200 && $httpCode < 300) {
                    // Esperar un momento para que el cambio se aplique
                    sleep(1);
                    
                    // Verificar el estado actual de la factura
                    $nuevoEstado = $this->consultarEstadoFactura($facturaId);
                    
                    if ($nuevoEstado['success'] && $nuevoEstado['data']['status'] === 'open') {
                        Log::info('Factura abierta correctamente', [
                            'factura_id' => $facturaId,
                            'formato_exitoso' => $formato
                        ]);
                        
                        return [
                            'success' => true,
                            'message' => 'Factura abierta correctamente',
                            'data' => $nuevoEstado['data'],
                            'formato_exitoso' => $formato
                        ];
                    }
                    
                    Log::warning('La solicitud fue exitosa pero la factura no cambió a estado open', [
                        'factura_id' => $facturaId,
                        'formato' => $formato,
                        'estado_actual' => $nuevoEstado['data']['status'] ?? 'desconocido'
                    ]);
                }
            }
            
            // Si llegamos aquí, ninguno de los formatos funcionó
            Log::error('No se pudo abrir la factura con ninguno de los formatos', [
                'factura_id' => $facturaId,
                'formatos_intentados' => $formatos
            ]);
            
            return [
                'success' => false,
                'message' => 'No se pudo abrir la factura con ninguno de los formatos intentados',
                'formatos_intentados' => $formatos
            ];
        } catch (\Exception $e) {
            Log::error('Excepción al abrir factura directamente', [
                'factura_id' => $facturaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Consultar el estado actual de una factura en Alegra
     * 
     * @param string $facturaId ID de la factura en Alegra
     * @return array Resultado de la operación con los datos de la factura
     */
    public function consultarEstadoFactura($facturaId)
    {
        try {
            Log::info('Consultando estado de factura', ['factura_id' => $facturaId]);
            
            // Obtener credenciales
            $empresa = \App\Models\Empresa::first();
            
            if (!$empresa || empty($empresa->alegra_email) || empty($empresa->alegra_token)) {
                Log::error('No se encontraron credenciales de Alegra para consultar factura');
                return ['success' => false, 'message' => 'No se encontraron credenciales de Alegra'];
            }
            
            $email = $empresa->alegra_email;
            $token = $empresa->alegra_token;
            
            // Configurar cURL
            $ch = curl_init();
            $url = "https://api.alegra.com/api/v1/invoices/{$facturaId}";
            
            // Configurar opciones de cURL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
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
            Log::info('Respuesta de consulta de factura', [
                'factura_id' => $facturaId,
                'http_code' => $httpCode,
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
            
            return [
                'success' => false,
                'message' => 'Error al consultar la factura',
                'error' => $response,
                'http_code' => $httpCode
            ];
        } catch (\Exception $e) {
            Log::error('Excepción al consultar estado de factura', [
                'factura_id' => $facturaId,
                'error' => $e->getMessage()
            ]);
            
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Obtener estado actual de una factura en Alegra
     */
    public function obtenerEstadoFactura($facturaId)
    {
        try {
            Log::info('Obteniendo estado de factura desde Alegra', [
                'factura_id' => $facturaId
            ]);

            $response = $this->http->get("/invoices/{$facturaId}");
            
            Log::info('Respuesta estado factura', [
                'status' => $response->status(),
                'body_sample' => substr(json_encode($response->json()), 0, 500) . '...'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Extraer información relevante
                $estadoInfo = [
                    'id' => $data['id'] ?? null,
                    'status' => $data['status'] ?? null,
                    'cufe' => null,
                    'qrCode' => null,
                    'legalStatus' => null,
                    'numberTemplate' => $data['numberTemplate'] ?? null
                ];
                
                // Si hay información de sello electrónico (stamp)
                if (isset($data['stamp'])) {
                    $estadoInfo['cufe'] = $data['stamp']['cufe'] ?? null;
                    $estadoInfo['qrCode'] = $data['stamp']['barCodeContent'] ?? null;
                    $estadoInfo['legalStatus'] = $data['stamp']['legalStatus'] ?? null;
                }
                
                return [
                    'success' => true,
                    'data' => $estadoInfo
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al obtener estado de factura'
            ];
        } catch (\Exception $e) {
            Log::error('Error al obtener estado de factura', [
                'factura_id' => $facturaId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener detalles completos de una factura para generación de PDF
     * y extraer CUFE/QR de la misma forma que el flujo automático espera.
     */
    public function obtenerDetalleFacturaCompleto($facturaId)
    {
        try {
            Log::info('Obteniendo detalles completos de factura desde Alegra', [
                'factura_id' => $facturaId
            ]);

            $response = $this->http->get("/invoices/{$facturaId}");
            
            $statusCode = $response->status();
            $data = $response->json();

            Log::info('Respuesta detalles completos factura', [
                'status' => $statusCode,
                'body_sample' => substr(json_encode($data), 0, 500) . '...'
            ]);

            if ($response->successful()) {
                $cufe = null;
                $qrCode = null;
                $pdfUrl = null;

                // 1) Buscar en metadata si existe
                if (isset($data['metadata']) && is_array($data['metadata'])) {
                    foreach ($data['metadata'] as $metadata) {
                        if (isset($metadata['key']) && $metadata['key'] === 'cufe') {
                            $cufe = $metadata['value'];
                        }
                        if (isset($metadata['key']) && in_array($metadata['key'], ['qrCode', 'qr_data'])) {
                            $qrCode = $metadata['value'];
                        }
                    }
                }

                // 2) Buscar en dianStatus si está disponible allí
                if (isset($data['dianStatus']) && is_array($data['dianStatus'])) {
                    if (empty($cufe) && !empty($data['dianStatus']['cufe'])) {
                        $cufe = $data['dianStatus']['cufe'];
                    }
                    if (empty($qrCode) && !empty($data['dianStatus']['qrCode'])) {
                        $qrCode = $data['dianStatus']['qrCode'];
                    }
                }

                // 3) Tomar directamente del stamp (lo que ya usa la tirilla)
                if (isset($data['stamp']) && is_array($data['stamp'])) {
                    if (empty($cufe) && !empty($data['stamp']['cufe'])) {
                        $cufe = $data['stamp']['cufe'];
                    }
                    if (empty($qrCode) && !empty($data['stamp']['barCodeContent'])) {
                        $qrCode = $data['stamp']['barCodeContent'];
                    }
                }

                // 4) Buscar PDF en adjuntos (por si se necesita para descarga)
                if (isset($data['attachments']) && is_array($data['attachments'])) {
                    foreach ($data['attachments'] as $attachment) {
                        if (isset($attachment['name']) && strpos($attachment['name'], '.pdf') !== false) {
                            $pdfUrl = $attachment['downloadLink'];
                        }
                    }
                }

                return [
                    'success' => true,
                    'data' => $data,   // Todos los datos para PDF/tirilla
                    'cufe' => $cufe,   // CUFE resumido para guardarlo en Venta
                    'qrCode' => $qrCode, // Cadena que genera el QR DIAN
                    'pdfUrl' => $pdfUrl
                ];
            }

            return [
                'success' => false,
                'error' => $data['message'] ?? 'Error al obtener detalles de factura'
            ];
        } catch (\Exception $e) {
            Log::error('Error al obtener detalles completos de factura', [
                'factura_id' => $facturaId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Enviar factura a DIAN (simplificado para facturas ya abiertas)
     */
    public function enviarFacturaADian($facturaId)
    {
        try {
            Log::info('Enviando factura a DIAN', [
                'factura_id' => $facturaId
            ]);

            // Verificar estado actual de la factura
            $estadoActual = $this->obtenerEstadoFactura($facturaId);
            
            if (!$estadoActual['success']) {
                return [
                    'success' => false,
                    'error' => 'No se pudo verificar el estado de la factura'
                ];
            }
            
            $status = $estadoActual['data']['status'];
            Log::info('Estado actual de la factura', [
                'factura_id' => $facturaId,
                'status' => $status
            ]);
            
            // Si la factura ya está abierta, enviar directamente a DIAN
            if ($status === 'open') {
                Log::info('Factura ya está abierta, enviando directamente a DIAN');
                
                // Enviar a DIAN usando el endpoint masivo (más confiable)
                $stampData = [
                    'ids' => [$facturaId],
                    'paymentForm' => 'CASH',
                    'paymentMethod' => 'CASH',
                    'term' => 'De contado'
                ];
                
                $responseEmitir = $this->http->post("/invoices/stamp", $stampData);
            } else {
                // Si aún está en draft, primero abrirla
                Log::info('Factura en draft, abriendo primero');
                
                $openData = [
                    'paymentForm' => 'CASH',
                    'paymentMethod' => 'CASH',
                    'term' => 'De contado'
                ];
                
                $responseAbrir = $this->http->put("/invoices/{$facturaId}/open", $openData);
                
                if (!$responseAbrir->successful()) {
                    return [
                        'success' => false,
                        'error' => 'Error al abrir la factura: ' . ($responseAbrir->json()['message'] ?? 'Error desconocido')
                    ];
                }
                
                // Ahora enviar a DIAN usando el endpoint masivo
                $stampData = [
                    'ids' => [$facturaId],
                    'paymentForm' => 'CASH',
                    'paymentMethod' => 'CASH',
                    'term' => 'De contado'
                ];
                $responseEmitir = $this->http->post("/invoices/stamp", $stampData);
            }
            
            Log::info('Respuesta de envío a DIAN', [
                'factura_id' => $facturaId,
                'status' => $responseEmitir->status(),
                'body_sample' => substr(json_encode($responseEmitir->json()), 0, 500) . '...'
            ]);

            if ($responseEmitir->successful()) {
                $data = $responseEmitir->json();
                
                return [
                    'success' => true,
                    'data' => [
                        'status' => $data['status'] ?? 'sent',
                        'stamp' => $data['stamp'] ?? null,
                        'message' => 'Factura enviada a DIAN exitosamente'
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => $responseEmitir->json()['message'] ?? 'Error al enviar a DIAN'
            ];
            
        } catch (\Exception $e) {
            Log::error('Error al enviar factura a DIAN', [
                'factura_id' => $facturaId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener métodos de pago disponibles en Alegra
     */
    public function obtenerMetodosPago()
    {
        try {
            Log::info('Obteniendo métodos de pago disponibles');
            
            // Intentar diferentes endpoints para métodos de pago
            $response = $this->http->get('/accounts');
            
            if (!$response->successful()) {
                // Intentar endpoint alternativo
                $response = $this->http->get('/payment-methods');
            }
            
            if ($response->successful()) {
                $metodos = $response->json();
                Log::info('Métodos de pago obtenidos', ['metodos' => $metodos]);
                return [
                    'success' => true,
                    'data' => $metodos
                ];
            }
            
            return [
                'success' => false,
                'error' => 'No se pudieron obtener los métodos de pago'
            ];
            
        } catch (\Exception $e) {
            Log::error('Error al obtener métodos de pago', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Método alternativo para abrir factura usando PATCH
     */
    public function abrirFacturaAlternativo($facturaId)
    {
        try {
            Log::info('Abriendo factura con método alternativo (PATCH)', [
                'factura_id' => $facturaId
            ]);

            // Método 1: Usar PATCH en lugar de PUT
            $response = $this->http->patch("/invoices/{$facturaId}", [
                'status' => 'open',
                'paymentForm' => 'CASH',
                'paymentMethod' => 'CASH',
                'term' => 'De contado'
            ]);
            
            Log::info('Respuesta PATCH', [
                'factura_id' => $facturaId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            // Método 2: Usar POST a endpoint específico
            $response2 = $this->http->post("/invoices/{$facturaId}/open");
            
            Log::info('Respuesta POST open', [
                'factura_id' => $facturaId,
                'status' => $response2->status(),
                'body' => $response2->body()
            ]);

            if ($response2->successful()) {
                return [
                    'success' => true,
                    'data' => $response2->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Métodos alternativos fallaron: ' . $response2->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Error en método alternativo', [
                'factura_id' => $facturaId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Método directo para abrir factura (sin actualización previa)
     */
    public function abrirFacturaDirecta($facturaId)
    {
        try {
            Log::info('Abriendo factura directamente', [
                'factura_id' => $facturaId
            ]);

            // Intentar abrir sin datos adicionales primero
            $response = $this->http->put("/invoices/{$facturaId}/open");
            
            Log::info('Respuesta de apertura directa', [
                'factura_id' => $facturaId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            // Si falla, intentar con datos de pago
            $response2 = $this->http->put("/invoices/{$facturaId}/open", [
                'paymentForm' => 'CASH',
                'paymentMethod' => 'CASH'
            ]);
            
            Log::info('Respuesta de apertura con datos', [
                'factura_id' => $facturaId,
                'status' => $response2->status(),
                'body' => $response2->body()
            ]);

            if ($response2->successful()) {
                return [
                    'success' => true,
                    'data' => $response2->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'No se pudo abrir la factura: ' . $response2->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Error al abrir factura directamente', [
                'factura_id' => $facturaId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Método de prueba para stamp masivo
     */
    public function probarStampMasivo($facturaId)
    {
        try {
            $response = $this->http->post('/invoices/stamp', [
                'ids' => [$facturaId], // Corregido: usar 'ids' en lugar de 'invoices'
                'paymentForm' => 'CASH',
                'paymentMethod' => 'CASH',
                'term' => 'De contado'
            ]);
            
            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->body()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Método de prueba para stamp individual
     */
    public function probarStampIndividual($facturaId)
    {
        try {
            $response = $this->http->put("/invoices/{$facturaId}/stamp", [
                'paymentForm' => 'CASH',
                'paymentMethod' => 'CASH',
                'term' => 'De contado'
            ]);
            
            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->body()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Método de prueba para open con stamp
     */
    public function probarOpenConStamp($facturaId)
    {
        try {
            $response = $this->http->post("/invoices/{$facturaId}/open", [
                'stamp' => true
            ]);
            
            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->body()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener URL del PDF de factura desde Alegra
     */
    public function obtenerPdfFactura($facturaId)
    {
        try {
            Log::info('Obteniendo PDF de factura', [
                'factura_id' => $facturaId
            ]);

            // Método 1: Intentar obtener PDF desde el endpoint estándar
            $response = $this->http->get("/invoices/{$facturaId}");
            
            if ($response->successful()) {
                $facturaData = $response->json();
                
                // Buscar URL del PDF en diferentes campos posibles
                $pdfUrl = $facturaData['pdf'] ?? 
                         $facturaData['pdfUrl'] ?? 
                         $facturaData['url_pdf'] ?? 
                         null;
                
                if ($pdfUrl) {
                    Log::info('URL del PDF obtenida', [
                        'factura_id' => $facturaId,
                        'pdf_url' => $pdfUrl
                    ]);
                    
                    return [
                        'success' => true,
                        'pdf_url' => $pdfUrl,
                        'data' => $facturaData
                    ];
                }
                
                // Si no hay PDF directo, construir URL manualmente
                $baseUrl = config('services.alegra.base_url', 'https://api.alegra.com/api/v1');
                $pdfUrl = $baseUrl . "/invoices/{$facturaId}/pdf";
                
                Log::info('Usando URL de PDF construida', [
                    'factura_id' => $facturaId,
                    'pdf_url' => $pdfUrl
                ]);
                
                return [
                    'success' => true,
                    'pdf_url' => $pdfUrl,
                    'data' => $facturaData
                ];
            }
            
            return [
                'success' => false,
                'error' => 'No se pudo obtener la factura'
            ];
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo PDF de factura', [
                'factura_id' => $facturaId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Enviar factura por email usando la API de Alegra
     */
    public function enviarFacturaPorEmail($facturaId, $emailCliente, $mensaje = null)
    {
        try {
            Log::info('Enviando factura por email', [
                'factura_id' => $facturaId,
                'email' => $emailCliente
            ]);

            $emailData = [
                'emails' => [$emailCliente],
                'subject' => 'Factura Electrónica',
                'message' => $mensaje ?? 'Adjunto encontrará su factura electrónica. Gracias por su compra.'
            ];
            
            $response = $this->http->post("/invoices/{$facturaId}/email", $emailData);
            
            if ($response->successful()) {
                Log::info('Factura enviada por email exitosamente', [
                    'factura_id' => $facturaId,
                    'email' => $emailCliente
                ]);
                
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }
            
            Log::warning('Error enviando factura por email', [
                'factura_id' => $facturaId,
                'email' => $emailCliente,
                'response' => $response->body()
            ]);
            
            return [
                'success' => false,
                'error' => 'No se pudo enviar el email: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Excepción enviando factura por email', [
                'factura_id' => $facturaId,
                'email' => $emailCliente,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
}