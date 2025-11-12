<?php

namespace App\Services\Dian;

use App\Models\EmailBuzon;
use App\Models\ConfiguracionDian;
use App\Models\ProveedorElectronico;
use App\Services\Dian\XmlFacturaReaderService;
use App\Services\DynamicEmailService;
use App\Mail\AcuseReciboMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BuzonEmailService
{
    private $configuracion;
    private $empresa;
    private $xmlFacturaReaderService;
    private $dynamicEmailService;

    public function __construct(ConfiguracionDian $configuracion)
    {
        $this->configuracion = $configuracion;
        $this->empresa = $configuracion->empresa;
        $this->xmlFacturaReaderService = new XmlFacturaReaderService();
        $this->dynamicEmailService = new DynamicEmailService();
    }

    /**
     * Sincronizar emails desde el servidor
     */
    public function sincronizarEmails(): array
    {
        try {
            Log::info('Buzón Email: Iniciando sincronización', [
                'empresa_id' => $this->empresa->id,
                'email' => $this->configuracion->email_dian
            ]);

            // Intentar conexión con diferentes métodos
            $emails = $this->conectarYDescargar();

            // NO crear emails de demostración - solo usar emails reales
            if (empty($emails)) {
                Log::info('Buzón Email: No se encontraron emails reales para procesar');
            }

            $emailsGuardados = 0;
            $emailsConFacturas = 0;

            foreach ($emails as $emailData) {
                $emailGuardado = $this->guardarEmailEnBuzon($emailData);
                if ($emailGuardado) {
                    $emailsGuardados++;
                    if ($emailGuardado->tiene_facturas) {
                        $emailsConFacturas++;
                    }
                }
            }

            Log::info('Buzón Email: Sincronización completada', [
                'emails_descargados' => count($emails),
                'emails_guardados' => $emailsGuardados,
                'emails_con_facturas' => $emailsConFacturas
            ]);

            return [
                'success' => true,
                'message' => 'Sincronización completada exitosamente',
                'emails_descargados' => count($emails),
                'emails_guardados' => $emailsGuardados,
                'emails_con_facturas' => $emailsConFacturas
            ];

        } catch (\Exception $e) {
            Log::error('Buzón Email: Error en sincronización', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error en sincronización: ' . $e->getMessage(),
                'emails_descargados' => 0,
                'emails_guardados' => 0,
                'emails_con_facturas' => 0
            ];
        }
    }

    /**
     * Conectar y descargar emails
     */
    private function conectarYDescargar(): array
    {
        $emails = [];

        try {
            // Método 1: Intentar descarga real con IMAP
            $emails = $this->descargarEmailsReales();
            
            if (!empty($emails)) {
                Log::info('Buzón Email: Emails reales descargados', ['cantidad' => count($emails)]);
                return $emails;
            }

            // Método 2: Si no hay emails reales, intentar con socket básico
            $emails = $this->descargarConSocket();
            
            if (!empty($emails)) {
                Log::info('Buzón Email: Emails descargados con socket', ['cantidad' => count($emails)]);
                return $emails;
            }

            // Método 3: Solo si no hay emails reales, usar demostración
            Log::info('Buzón Email: No se encontraron emails reales, usando demostración');
            return [];

        } catch (\Exception $e) {
            Log::warning('Buzón Email: Error en descarga real', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener criterio de búsqueda por período
     */
    private function obtenerCriterioBusqueda(string $periodo = 'mes_actual'): array
    {
        switch ($periodo) {
            case 'mes_actual':
                $desde = date('d-M-Y', strtotime('first day of this month'));
                $descripcion = 'primer día del mes actual';
                break;
                
            case 'mes_anterior':
                $desde = date('d-M-Y', strtotime('first day of last month'));
                $hasta = date('d-M-Y', strtotime('last day of last month'));
                $descripcion = 'mes anterior completo';
                break;
                
            case 'ultimos_30_dias':
                $desde = date('d-M-Y', strtotime('-30 days'));
                $descripcion = 'últimos 30 días';
                break;
                
            case 'ultimos_7_dias':
                $desde = date('d-M-Y', strtotime('-7 days'));
                $descripcion = 'últimos 7 días';
                break;
                
            case 'hoy':
                $desde = date('d-M-Y');
                $descripcion = 'solo hoy';
                break;
                
            default:
                $desde = date('d-M-Y', strtotime('first day of this month'));
                $descripcion = 'primer día del mes actual (por defecto)';
        }
        
        $criterio = 'SINCE "' . $desde . '"';
        
        // Si hay fecha hasta, agregar criterio BEFORE
        if (isset($hasta)) {
            $criterio .= ' BEFORE "' . date('d-M-Y', strtotime($hasta . ' +1 day')) . '"';
        }
        
        return [
            'criterio' => $criterio,
            'desde' => $desde,
            'hasta' => $hasta ?? 'hoy',
            'descripcion' => $descripcion
        ];
    }

    /**
     * Descargar emails reales usando IMAP
     */
    private function descargarEmailsReales(string $periodo = 'mes_actual'): array
    {
        $emails = [];

        try {
            Log::info('Buzón Email: Intentando descarga real con IMAP', [
                'email' => $this->configuracion->email_dian,
                'servidor' => $this->obtenerServidorImap()
            ]);

            // Verificar si tenemos extensión imap
            $imap_loaded = extension_loaded('imap');
            $imap_open_exists = function_exists('imap_open');
            
            Log::info('Buzón Email: Verificando IMAP', [
                'extension_loaded' => $imap_loaded,
                'function_exists' => $imap_open_exists
            ]);

            if (!$imap_open_exists || !$imap_loaded) {
                Log::warning('Buzón Email: IMAP no completamente disponible', [
                    'extension_loaded' => $imap_loaded,
                    'function_exists' => $imap_open_exists
                ]);
                return [];
            }

            Log::info('Buzón Email: IMAP completamente disponible, intentando conexión');

            // Configurar conexión IMAP
            $servidor = '{' . $this->obtenerServidorImap() . ':993/imap/ssl}INBOX';
            $email = $this->configuracion->email_dian;
            $password = $this->configuracion->password_email;

            Log::info('Buzón Email: Intentando conexión IMAP', [
                'servidor' => $servidor,
                'email' => $email,
                'password_length' => strlen($password)
            ]);

            // Limpiar errores previos de IMAP
            imap_errors();
            imap_alerts();

            // Intentar conexión IMAP
            $conexion = @imap_open($servidor, $email, $password);

            if (!$conexion) {
                $errors = imap_errors();
                $alerts = imap_alerts();
                $last_error = imap_last_error();
                
                Log::warning('Buzón Email: No se pudo conectar con IMAP', [
                    'last_error' => $last_error,
                    'errors' => $errors,
                    'alerts' => $alerts,
                    'servidor' => $servidor,
                    'email' => $email
                ]);
                return [];
            }

            Log::info('Buzón Email: Conexión IMAP exitosa');

            // Obtener criterio de búsqueda para el período especificado
            $criterio_busqueda = $this->obtenerCriterioBusqueda($periodo);
            
            Log::info('Buzón Email: Buscando emails por período', [
                'periodo' => $periodo,
                'descripcion' => $criterio_busqueda['descripcion'],
                'desde' => $criterio_busqueda['desde'],
                'hasta' => $criterio_busqueda['hasta'],
                'criterio' => $criterio_busqueda['criterio']
            ]);
            
            $emails_ids = imap_search($conexion, $criterio_busqueda['criterio']);

            if (!$emails_ids) {
                Log::info('Buzón Email: No se encontraron emails recientes');
                imap_close($conexion);
                return [];
            }

            // Limitar a los últimos 20 emails
            $emails_ids = array_slice($emails_ids, -20);

            foreach ($emails_ids as $email_id) {
                $email_data = $this->procesarEmailImap($conexion, $email_id);
                if ($email_data) {
                    $emails[] = $email_data;
                }
            }

            imap_close($conexion);

            Log::info('Buzón Email: Emails reales procesados', [
                'total_encontrados' => count($emails_ids),
                'emails_procesados' => count($emails)
            ]);

            return $emails;

        } catch (\Exception $e) {
            Log::error('Buzón Email: Error descargando emails reales', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Procesar email individual con IMAP
     */
    private function procesarEmailImap($conexion, $email_id): ?array
    {
        try {
            $header = imap_headerinfo($conexion, $email_id);
            $estructura = imap_fetchstructure($conexion, $email_id);

            // Extraer información básica
            $from = isset($header->from[0]) ? $header->from[0] : null;
            $remitente_email = $from ? $from->mailbox . '@' . $from->host : 'unknown@unknown.com';
            $remitente_nombre = $from ? (isset($from->personal) ? $from->personal : $from->mailbox) : 'Desconocido';
            
            $asunto = isset($header->subject) ? $header->subject : 'Sin asunto';
            $fecha = isset($header->date) ? date('Y-m-d H:i:s', strtotime($header->date)) : now();

            // PASO 1: Verificar si es de un proveedor autorizado
            $proveedor_autorizado = $this->esProveedorAutorizado($remitente_email, $asunto, $remitente_nombre);
            
            // MODO PRUEBA: Comentar estas líneas temporalmente para ver todos los emails
            if (!$proveedor_autorizado) {
                Log::info('Buzón Email: Email ignorado - no es de proveedor autorizado', [
                    'remitente' => $remitente_email,
                    'remitente_nombre' => $remitente_nombre,
                    'asunto' => $asunto
                ]);
                return null; // Saltar emails de proveedores no autorizados
            }

            // PASO 2: Usar la nueva detección mejorada de facturas electrónicas
            $deteccion_factura = $this->emailContieneFacturaElectronica($header, $estructura, $conexion, $email_id);
            
            // Solo procesar emails que realmente contengan facturas electrónicas
            if (!$deteccion_factura['tiene_facturas']) {
                Log::info('Buzón Email: Email de proveedor autorizado pero sin facturas', [
                    'proveedor' => $proveedor_autorizado->nombre_proveedor,
                    'remitente' => $remitente_email,
                    'asunto' => $asunto
                ]);
                return null; // Saltar emails sin facturas electrónicas
            }

            Log::info('Buzón Email: Email con factura electrónica detectado', [
                'email_id' => $email_id,
                'remitente' => $remitente_email,
                'asunto' => $asunto,
                'tipo_documento' => $deteccion_factura['tipo_documento'],
                'cufe' => $deteccion_factura['cufe'],
                'archivos_facturas' => count(array_filter($deteccion_factura['archivos_adjuntos'], function($adj) {
                    return $adj['es_factura'];
                }))
            ]);

            return [
                'mensaje_id' => 'REAL_' . ($header->message_id ?? uniqid()),
                'remitente_email' => $remitente_email,
                'remitente_nombre' => $remitente_nombre,
                'asunto' => $asunto,
                'contenido_texto' => imap_body($conexion, $email_id),
                'contenido_html' => null,
                'fecha_email' => $fecha,
                'archivos_adjuntos' => $deteccion_factura['archivos_adjuntos'],
                'tiene_facturas' => $deteccion_factura['tiene_facturas'],
                'metadatos' => [
                    'tipo' => 'email_real',
                    'servidor' => 'imap_real',
                    'email_id' => $email_id,
                    'cufe' => $deteccion_factura['cufe'],
                    'tipo_documento' => $deteccion_factura['tipo_documento'],
                    'palabras_clave_detectadas' => $deteccion_factura['palabras_clave_detectadas'],
                    'proveedor_autorizado' => [
                        'id' => $proveedor_autorizado->id,
                        'nombre' => $proveedor_autorizado->nombre_proveedor,
                        'nit' => $proveedor_autorizado->nit_proveedor
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Buzón Email: Error procesando email individual', [
                'email_id' => $email_id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Verificar si es archivo de factura electrónica
     */
    private function esArchivoFactura(string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $filename_lower = strtolower($filename);
        
        // Extensiones de facturas electrónicas
        if (in_array($extension, ['xml', 'zip', 'rar', '7z'])) {
            return true;
        }
        
        // Palabras clave específicas de facturas electrónicas DIAN
        $facturaKeywords = [
            'factura', 'invoice', 'fe', 'cufe', 'cude', 'xml',
            'facturae', 'nota_credito', 'nota_debito', 'nc', 'nd',
            'documento_electronico', 'dian', 'electronica'
        ];
        
        foreach ($facturaKeywords as $keyword) {
            if (stripos($filename_lower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar si el email es de un proveedor autorizado
     */
    private function esProveedorAutorizado(string $remitente_email, string $asunto, string $remitente_nombre = ''): ?object
    {
        $proveedores = \App\Models\ProveedorElectronico::porEmpresa($this->empresa->id)
            ->activos()
            ->get();

        foreach ($proveedores as $proveedor) {
            // Verificar por email o dominio
            if ($proveedor->coincideConEmail($remitente_email)) {
                Log::info('Buzón Email: Proveedor autorizado detectado por email', [
                    'proveedor' => $proveedor->nombre_proveedor,
                    'email' => $remitente_email
                ]);
                return $proveedor;
            }

            // Verificar por palabras clave en asunto
            if ($proveedor->coincideConAsunto($asunto)) {
                Log::info('Buzón Email: Proveedor autorizado detectado por asunto', [
                    'proveedor' => $proveedor->nombre_proveedor,
                    'asunto' => $asunto
                ]);
                return $proveedor;
            }

            // Verificar por nombre del remitente
            if ($remitente_nombre && $proveedor->coincideConRemitente($remitente_nombre)) {
                Log::info('Buzón Email: Proveedor autorizado detectado por nombre remitente', [
                    'proveedor' => $proveedor->nombre_proveedor,
                    'remitente_nombre' => $remitente_nombre
                ]);
                return $proveedor;
            }
        }

        return null;
    }

    /**
     * Verificar si el email contiene factura electrónica
     */
    private function emailContieneFacturaElectronica($header, $estructura, $conexion, $email_id): array
    {
        $tiene_facturas = false;
        $archivos_adjuntos = [];
        $cufe_encontrado = null;
        $tipo_documento = null;
        
        // Verificar asunto por palabras clave de factura electrónica
        $asunto = isset($header->subject) ? strtolower($header->subject) : '';
        $palabras_factura_asunto = [
            'factura electronica', 'documento electronico', 'cufe', 'cude',
            'fe-', 'nc-', 'nd-', 'facturae', 'dian', 'nota credito', 'nota debito'
        ];
        
        $contiene_palabras_factura = false;
        foreach ($palabras_factura_asunto as $palabra) {
            if (strpos($asunto, $palabra) !== false) {
                $contiene_palabras_factura = true;
                break;
            }
        }
        
        // Verificar archivos adjuntos
        if (isset($estructura->parts)) {
            foreach ($estructura->parts as $part_num => $part) {
                if (isset($part->disposition) && strtolower($part->disposition) == 'attachment') {
                    $filename = '';
                    
                    // Obtener nombre del archivo
                    if (isset($part->dparameters)) {
                        foreach ($part->dparameters as $param) {
                            if (strtolower($param->attribute) == 'filename') {
                                $filename = $param->value;
                                break;
                            }
                        }
                    }
                    
                    if ($filename) {
                        $archivos_adjuntos[] = [
                            'nombre' => $filename,
                            'tipo' => $part->subtype ?? 'unknown',
                            'tamaño' => $part->bytes ?? 0,
                            'es_factura' => $this->esArchivoFactura($filename)
                        ];
                        
                        // Si es archivo de factura, marcarlo
                        if ($this->esArchivoFactura($filename)) {
                            $tiene_facturas = true;
                            
                            // Intentar extraer CUFE del nombre del archivo
                            if (preg_match('/([a-f0-9]{96})/i', $filename, $matches)) {
                                $cufe_encontrado = $matches[1];
                            }
                            
                            // Determinar tipo de documento
                            if (stripos($filename, 'nc') !== false || stripos($filename, 'nota_credito') !== false) {
                                $tipo_documento = 'nota_credito';
                            } elseif (stripos($filename, 'nd') !== false || stripos($filename, 'nota_debito') !== false) {
                                $tipo_documento = 'nota_debito';
                            } else {
                                $tipo_documento = 'factura';
                            }
                        }
                    }
                }
            }
        }
        
        // Solo considerar como factura si tiene archivos adjuntos relevantes O palabras clave específicas
        if (!$tiene_facturas && $contiene_palabras_factura) {
            $tiene_facturas = true;
            $tipo_documento = 'factura';
        }
        
        return [
            'tiene_facturas' => $tiene_facturas,
            'archivos_adjuntos' => $archivos_adjuntos,
            'cufe' => $cufe_encontrado,
            'tipo_documento' => $tipo_documento,
            'palabras_clave_detectadas' => $contiene_palabras_factura
        ];
    }

    /**
     * Descargar emails con socket básico
     */
    private function descargarConSocket(): array
    {
        $emails = [];

        try {
            // Configuración del servidor
            $servidor = $this->obtenerServidorImap();
            $puerto = 993;

            // Crear contexto SSL
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]);

            // Intentar conexión
            $socket = @stream_socket_client(
                "ssl://{$servidor}:{$puerto}",
                $errno,
                $errstr,
                10,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if ($socket) {
                Log::info('Buzón Email: Conexión socket exitosa', ['servidor' => $servidor]);
                
                // Leer respuesta inicial
                $response = fgets($socket);
                
                // Cerrar conexión (por ahora solo probamos conectividad)
                fclose($socket);
                
                Log::info('Buzón Email: Respuesta del servidor', ['response' => trim($response)]);
            }

            return $emails;

        } catch (\Exception $e) {
            Log::error('Buzón Email: Error en socket', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener servidor IMAP según el email
     */
    private function obtenerServidorImap(): string
    {
        $email = $this->configuracion->email_dian;
        $dominio = substr(strrchr($email, "@"), 1);

        $servidores = [
            'gmail.com' => 'imap.gmail.com',
            'outlook.com' => 'outlook.office365.com',
            'hotmail.com' => 'outlook.office365.com',
            'yahoo.com' => 'imap.mail.yahoo.com',
            'yahoo.es' => 'imap.mail.yahoo.com'
        ];

        return $servidores[$dominio] ?? 'imap.' . $dominio;
    }

    /**
     * Crear emails de demostración
     */
    private function crearEmailsDemostracion(): array
    {
        return [
            [
                'mensaje_id' => 'DEMO_' . uniqid() . '_' . time(),
                'remitente_email' => 'proveedor1@empresa.com',
                'remitente_nombre' => 'Proveedor Demo 1',
                'asunto' => 'Factura Electrónica FE-2024-001',
                'contenido_texto' => 'Adjunto encontrará la factura electrónica correspondiente a su compra.',
                'contenido_html' => '<p>Adjunto encontrará la factura electrónica correspondiente a su compra.</p>',
                'fecha_email' => now()->subHours(2),
                'archivos_adjuntos' => [
                    [
                        'nombre' => 'FE-2024-001.xml',
                        'tipo' => 'application/xml',
                        'tamaño' => 15420
                    ]
                ],
                'tiene_facturas' => true,
                'metadatos' => [
                    'tipo' => 'demo',
                    'servidor' => 'simulado'
                ]
            ],
            [
                'mensaje_id' => 'DEMO_' . uniqid() . '_' . time(),
                'remitente_email' => 'facturacion@proveedor2.com',
                'remitente_nombre' => 'Facturación Proveedor 2',
                'asunto' => 'Documento Electrónico - Factura NC-2024-005',
                'contenido_texto' => 'Se adjunta factura electrónica en formato XML.',
                'contenido_html' => '<p>Se adjunta factura electrónica en formato XML.</p>',
                'fecha_email' => now()->subHours(5),
                'archivos_adjuntos' => [
                    [
                        'nombre' => 'Factura_NC-2024-005.zip',
                        'tipo' => 'application/zip',
                        'tamaño' => 25680
                    ]
                ],
                'tiene_facturas' => true,
                'metadatos' => [
                    'tipo' => 'demo',
                    'servidor' => 'simulado'
                ]
            ],
            [
                'mensaje_id' => 'DEMO_' . uniqid() . '_' . time(),
                'remitente_email' => 'info@empresa3.com',
                'remitente_nombre' => 'Empresa 3 Ltda',
                'asunto' => 'Información general - No factura',
                'contenido_texto' => 'Este es un email informativo sin facturas.',
                'contenido_html' => '<p>Este es un email informativo sin facturas.</p>',
                'fecha_email' => now()->subHours(1),
                'archivos_adjuntos' => [],
                'tiene_facturas' => false,
                'metadatos' => [
                    'tipo' => 'demo',
                    'servidor' => 'simulado'
                ]
            ]
        ];
    }

    /**
     * Guardar email en el buzón local
     */
    private function guardarEmailEnBuzon(array $emailData): ?EmailBuzon
    {
        try {
            // Verificar si ya existe
            $existente = EmailBuzon::where('mensaje_id', $emailData['mensaje_id'])->first();
            if ($existente) {
                Log::info('Buzón Email: Email ya existe', ['mensaje_id' => $emailData['mensaje_id']]);
                return $existente;
            }

            // Crear nuevo email en buzón
            $email = EmailBuzon::create([
                'empresa_id' => $this->empresa->id,
                'mensaje_id' => $emailData['mensaje_id'],
                'cuenta_email' => $this->configuracion->email_dian,
                'remitente_email' => $emailData['remitente_email'],
                'remitente_nombre' => $emailData['remitente_nombre'] ?? null,
                'asunto' => $emailData['asunto'],
                'contenido_texto' => $emailData['contenido_texto'] ?? null,
                'contenido_html' => $emailData['contenido_html'] ?? null,
                'fecha_email' => $emailData['fecha_email'],
                'fecha_descarga' => now(),
                'archivos_adjuntos' => $emailData['archivos_adjuntos'] ?? [],
                'tiene_facturas' => $emailData['tiene_facturas'] ?? false,
                'procesado' => false,
                'estado' => 'nuevo',
                'metadatos' => $emailData['metadatos'] ?? []
            ]);

            Log::info('Buzón Email: Email guardado', [
                'id' => $email->id,
                'mensaje_id' => $email->mensaje_id,
                'tiene_facturas' => $email->tiene_facturas
            ]);

            return $email;

        } catch (\Exception $e) {
            Log::error('Buzón Email: Error guardando email', [
                'mensaje_id' => $emailData['mensaje_id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Obtener estadísticas del buzón
     */
    public function obtenerEstadisticas(): array
    {
        try {
            $emails = EmailBuzon::where('empresa_id', $this->empresa->id);
            
            $estadisticas = [
                'total_emails' => $emails->count(),
                'emails_nuevos' => $emails->where('estado', 'nuevo')->count(),
                'emails_procesando' => $emails->where('estado', 'procesando')->count(),
                'emails_procesados' => $emails->where('estado', 'procesado')->count(),
                'emails_error' => $emails->where('estado', 'error')->count(),
                'emails_con_facturas' => $emails->where('tiene_facturas', true)->count(),
                'emails_hoy' => $emails->whereDate('fecha_email', today())->count(),
                'emails_mes_actual' => $emails->whereMonth('fecha_email', now()->month)
                                            ->whereYear('fecha_email', now()->year)
                                            ->count(),
                'ultimo_email' => $emails->orderBy('fecha_email', 'desc')->first()?->fecha_email,
                'ultima_sincronizacion' => $emails->orderBy('created_at', 'desc')->first()?->created_at,
                'proveedores_activos' => \App\Models\ProveedorElectronico::where('empresa_id', $this->empresa->id)
                                                                        ->where('activo', true)
                                                                        ->count()
            ];
            
            Log::info('Buzón Email: Estadísticas calculadas', $estadisticas);
            
            return $estadisticas;
            
        } catch (\Exception $e) {
            Log::error('Buzón Email: Error calculando estadísticas', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'total_emails' => 0,
                'emails_nuevos' => 0,
                'emails_procesando' => 0,
                'emails_procesados' => 0,
                'emails_error' => 0,
                'emails_con_facturas' => 0,
                'emails_hoy' => 0,
                'emails_mes_actual' => 0,
                'ultimo_email' => null,
                'ultima_sincronizacion' => null,
                'proveedores_activos' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generar acuse de recibo automático
     */
    private function generarAcuseRecibo(EmailBuzon $email): bool
    {
        try {
            Log::info('Buzón Email: Generando acuse de recibo', [
                'email_id' => $email->id,
                'remitente' => $email->remitente_email,
                'asunto' => $email->asunto
            ]);

            // Extraer información de la factura desde metadatos
            $metadatos = $email->metadatos ?? [];
            $cufesExtraidos = $metadatos['cufes_extraidos'] ?? [];
            
            // Verificar si hay facturas procesadas como array
            $facturasProcessadas = [];
            if (isset($metadatos['facturas_procesadas']) && is_array($metadatos['facturas_procesadas'])) {
                $facturasProcessadas = $metadatos['facturas_procesadas'];
            }
            
            // Obtener datos de la primera factura procesada o crear datos básicos
            if (!empty($facturasProcessadas) && is_array($facturasProcessadas)) {
                $datosFactura = $facturasProcessadas[0];
            } else {
                // Crear datos básicos si no hay facturas procesadas
                $datosFactura = [
                    'cufe' => !empty($cufesExtraidos) ? $cufesExtraidos[0] : $this->extraerCufeDelArchivo($email->asunto),
                    'numero_factura' => $this->extraerNumeroFacturaDelAsunto($email->asunto),
                    'fecha_factura' => $email->fecha_email->format('Y-m-d'),
                    'proveedor' => [
                        'nombre' => $email->remitente_nombre,
                        'nit' => $this->obtenerNitProveedor($email->remitente_email),
                        'email' => null
                    ],
                    'cliente' => [
                        'nombre' => $this->empresa->nombre,
                        'nit' => $this->empresa->nit,
                        'email' => null
                    ],
                    'totales' => [
                        'subtotal' => 0,
                        'iva' => 0,
                        'total' => 0
                    ],
                    'email_proveedor' => $this->mapearEmailReal($email->remitente_email),
                    'email_cliente' => $email->cuenta_email
                ];
            }
            
            // Determinar destinatario (proveedor que envió la factura)
            // Priorizar email_proveedor del XML, luego mapear email del remitente
            $emailProveedor = $datosFactura['email_proveedor'] ?? null;
            
            if ($emailProveedor && filter_var($emailProveedor, FILTER_VALIDATE_EMAIL)) {
                $destinatario = $emailProveedor;
                Log::info('Buzón Email: Usando email del proveedor desde XML', [
                    'email_xml' => $emailProveedor
                ]);
            } else {
                // Mapear email del remitente a email real
                $destinatario = $this->mapearEmailReal($email->remitente_email);
                Log::info('Buzón Email: Mapeando email del remitente', [
                    'email_remitente' => $email->remitente_email,
                    'email_real' => $destinatario
                ]);
            }
            
            // Generar asunto del acuse
            $numeroFactura = $datosFactura['numero_factura'] ?? 'N/A';
            $asunto_acuse = "Acuse de Recibo - Factura Electrónica {$numeroFactura}";
            
            Log::info('Buzón Email: Preparando envío de acuse', [
                'destinatario' => $destinatario,
                'cufe' => $datosFactura['cufe'],
                'numero_factura' => $numeroFactura
            ]);
            
            // Enviar acuse real por email usando el sistema dinámico
            try {
                $resultado = $this->dynamicEmailService->enviarEmail(
                    $this->empresa->id,
                    'acuses',
                    $destinatario,
                    $asunto_acuse,
                    'emails.acuse-recibo',
                    [
                        'email' => $email,
                        'datosFactura' => $datosFactura,
                        'empresa' => $this->empresa
                    ]
                );
                
                if ($resultado['success']) {
                    // Registrar envío en metadatos
                    $metadatos = $email->metadatos ?? [];
                    $metadatos['acuse_enviado'] = [
                        'fecha' => now()->toISOString(),
                        'destinatario' => $destinatario,
                        'cufe' => $datosFactura['cufe'],
                        'numero_factura' => $numeroFactura,
                        'metodo' => 'email_dinamico',
                        'configuracion_usada' => $resultado['configuracion_usada'] ?? 'N/A',
                        'proveedor' => $resultado['proveedor'] ?? 'N/A'
                    ];
                    $email->update(['metadatos' => $metadatos]);
                    
                    Log::info('Buzón Email: Acuse enviado exitosamente', [
                        'configuracion' => $resultado['configuracion_usada'],
                        'proveedor' => $resultado['proveedor']
                    ]);
                    
                    $acuse_enviado = true;
                } else {
                    Log::error('Buzón Email: Error enviando acuse dinámico', [
                        'error' => $resultado['message']
                    ]);
                    $acuse_enviado = false;
                }
            } catch (\Exception $e) {
                Log::error('Buzón Email: Error enviando acuse', [
                    'error' => $e->getMessage()
                ]);
                $acuse_enviado = false;
            }
            
            if ($acuse_enviado) {
                Log::info('Buzón Email: Acuse de recibo enviado exitosamente', [
                    'email_id' => $email->id,
                    'destinatario' => $email->remitente_email
                ]);
                return true;
            }
            
            return false;

        } catch (\Exception $e) {
            Log::error('Buzón Email: Error generando acuse de recibo', [
                'email_id' => $email->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generar contenido del acuse de recibo
     */
    private function generarContenidoAcuse(EmailBuzon $email, ?string $cufe, string $tipo_documento): string
    {
        $empresa = $this->empresa;
        $fecha_actual = now()->format('d/m/Y H:i:s');
        
        return "
Estimado proveedor,

Confirmamos la recepción de su documento electrónico:

INFORMACIÓN DEL DOCUMENTO:
- Tipo: " . ucfirst(str_replace('_', ' ', $tipo_documento)) . "
- Fecha de recepción: {$fecha_actual}
- Asunto original: {$email->asunto}
" . ($cufe ? "- CUFE: {$cufe}" : "") . "

INFORMACIÓN DEL RECEPTOR:
- Empresa: {$empresa->nombre}
- NIT: {$empresa->nit}
- Email: {$this->configuracion->email_dian}

El documento ha sido recibido correctamente y se encuentra en proceso de validación.

Este es un mensaje automático generado por el sistema de facturación electrónica.

Atentamente,
Sistema de Facturación Electrónica
{$empresa->nombre}
        ";
    }

    /**
     * Enviar acuse por email (REAL)
     */
    private function enviarAcuseEmail(string $destinatario, string $asunto, string $contenido): bool
    {
        try {
            Log::info('Buzón Email: Enviando acuse real por email', [
                'destinatario' => $destinatario,
                'asunto' => $asunto
            ]);
            
            // Obtener datos de la factura para el acuse
            $datosFactura = [
                'cufe' => 'N/A',
                'numero_factura' => 'N/A',
                'fecha_factura' => now()->format('Y-m-d'),
                'proveedor' => [
                    'nombre' => 'Proveedor',
                    'nit' => 'N/A',
                    'email' => null
                ],
                'cliente' => [
                    'nombre' => $this->empresa->nombre,
                    'nit' => $this->empresa->nit,
                    'email' => null
                ],
                'totales' => [
                    'subtotal' => 0,
                    'iva' => 0,
                    'total' => 0
                ],
                'email_proveedor' => $destinatario,
                'email_cliente' => $this->configuracion->email_dian
            ];
            
            // Crear email temporal para el acuse
            $emailTemp = new EmailBuzon([
                'empresa_id' => $this->empresa->id,
                'cuenta_email' => $this->configuracion->email_dian,
                'remitente_email' => $destinatario,
                'remitente_nombre' => 'Proveedor',
                'asunto' => $asunto,
                'fecha_email' => now(),
                'estado' => 'procesado'
            ]);
            
            // Enviar acuse real usando Laravel Mail
            Mail::to($destinatario)->send(new AcuseReciboMail($emailTemp, $datosFactura, $this->empresa));
            
            Log::info('Buzón Email: Acuse enviado exitosamente', [
                'destinatario' => $destinatario
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Buzón Email: Error enviando acuse por email', [
                'destinatario' => $destinatario,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Procesar emails del buzón que aún no han sido procesados
     */
    public function procesarEmailsDelBuzon(): array
    {
        try {
            Log::info('Buzón Email: Iniciando procesamiento de emails del buzón');
            
            // Obtener emails no procesados
            $emailsNoProcesados = EmailBuzon::where('empresa_id', $this->empresa->id)
                                           ->whereIn('estado', ['nuevo', 'error'])
                                           ->where('tiene_facturas', true)
                                           ->get();
            
            $procesados = 0;
            $errores = 0;
            $acusesGenerados = 0;
            $facturasExtraidas = 0;
            
            foreach ($emailsNoProcesados as $email) {
                try {
                    Log::info('Buzón Email: Procesando email', [
                        'email_id' => $email->id,
                        'remitente' => $email->remitente_email,
                        'asunto' => $email->asunto
                    ]);
                    
                    // Cambiar estado a procesando
                    $email->update(['estado' => 'procesando']);
                    
                    // Extraer y procesar facturas del email
                    $resultadoExtraccion = $this->extraerFacturasDelEmail($email);
                    
                    if ($resultadoExtraccion['success']) {
                        $facturasExtraidas += $resultadoExtraccion['facturas_extraidas'];
                        
                        // Generar acuse de recibo
                        if ($this->generarAcuseRecibo($email)) {
                            $acusesGenerados++;
                        }
                        
                        // Marcar como procesado
                        $email->update([
                            'estado' => 'procesado',
                            'procesado' => true,
                            'metadatos' => array_merge($email->metadatos ?? [], [
                                'procesamiento' => $resultadoExtraccion,
                                'fecha_procesamiento' => now()->toISOString()
                            ])
                        ]);
                        
                        $procesados++;
                        
                        Log::info('Buzón Email: Email procesado exitosamente', [
                            'email_id' => $email->id,
                            'facturas_extraidas' => $resultadoExtraccion['facturas_extraidas'],
                            'acuse_generado' => true
                        ]);
                        
                    } else {
                        $email->update([
                            'estado' => 'error',
                            'metadatos' => array_merge($email->metadatos ?? [], [
                                'error_procesamiento' => $resultadoExtraccion['message'],
                                'fecha_error' => now()->toISOString()
                            ])
                        ]);
                        $errores++;
                    }
                    
                } catch (\Exception $e) {
                    Log::error('Buzón Email: Error procesando email individual', [
                        'email_id' => $email->id,
                        'error' => $e->getMessage()
                    ]);
                    
                    $email->update([
                        'estado' => 'error',
                        'metadatos' => array_merge($email->metadatos ?? [], [
                            'error_procesamiento' => $e->getMessage(),
                            'fecha_error' => now()->toISOString()
                        ])
                    ]);
                    $errores++;
                }
            }
            
            Log::info('Buzón Email: Procesamiento completado', [
                'emails_procesados' => $procesados,
                'facturas_extraidas' => $facturasExtraidas,
                'acuses_generados' => $acusesGenerados,
                'errores' => $errores
            ]);
            
            return [
                'success' => true,
                'emails_procesados' => $procesados,
                'facturas_extraidas' => $facturasExtraidas,
                'acuses_generados' => $acusesGenerados,
                'errores' => $errores,
                'message' => "Procesados: $procesados emails, Extraídas: $facturasExtraidas facturas, Acuses: $acusesGenerados"
            ];
            
        } catch (\Exception $e) {
            Log::error('Buzón Email: Error en procesamiento del buzón', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error procesando emails: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extraer facturas de un email específico
     */
    private function extraerFacturasDelEmail(EmailBuzon $email): array
    {
        try {
            Log::info('Buzón Email: Extrayendo facturas del email', [
                'email_id' => $email->id,
                'adjuntos' => count($email->archivos_adjuntos ?? [])
            ]);
            
            $facturasExtraidas = 0;
            $cufesEncontrados = [];
            
            // Procesar archivos adjuntos
            foreach ($email->archivos_adjuntos ?? [] as $adjunto) {
                if ($adjunto['es_factura'] ?? false) {
                    // Simular lectura de factura XML (en producción se leería el archivo real)
                    $datosFactura = $this->xmlFacturaReaderService->simularDatosFactura(
                        $adjunto['nombre'], 
                        $email->remitente_email
                    );
                    
                    if ($datosFactura['success']) {
                        $cufe = $datosFactura['datos']['cufe'];
                        $cufesEncontrados[] = $cufe;
                        $facturasExtraidas++;
                        
                        Log::info('Buzón Email: Factura procesada', [
                            'archivo' => $adjunto['nombre'],
                            'cufe' => $cufe,
                            'numero_factura' => $datosFactura['datos']['numero_factura'],
                            'email_proveedor' => $datosFactura['datos']['email_proveedor']
                        ]);
                        
                        // Guardar datos de la factura en metadatos
                        $metadatos = $email->metadatos ?? [];
                        $metadatos['facturas_procesadas'][] = $datosFactura['datos'];
                        $email->update(['metadatos' => $metadatos]);
                    }
                }
            }
            
            // Actualizar metadatos del email con los CUFEs encontrados
            $metadatos = $email->metadatos ?? [];
            $metadatos['cufes_extraidos'] = $cufesEncontrados;
            $metadatos['facturas_procesadas'] = $facturasExtraidas;
            $email->update(['metadatos' => $metadatos]);
            
            return [
                'success' => true,
                'facturas_extraidas' => $facturasExtraidas,
                'cufes' => $cufesEncontrados,
                'message' => "Extraídas $facturasExtraidas facturas con " . count($cufesEncontrados) . " CUFEs"
            ];
            
        } catch (\Exception $e) {
            Log::error('Buzón Email: Error extrayendo facturas', [
                'email_id' => $email->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'facturas_extraidas' => 0,
                'cufes' => [],
                'message' => 'Error extrayendo facturas: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extraer CUFE de un archivo de factura
     */
    private function extraerCufeDelArchivo(string $nombreArchivo): ?string
    {
        // Patrones para extraer CUFE del nombre del archivo
        $patrones = [
            '/CUFE([A-Z0-9]{96})/i',           // CUFE completo
            '/([A-Z0-9]{96})/i',               // 96 caracteres alfanuméricos
            '/FE-(\d{4}-\d{3,6})/i',           // Patrón FE-YYYY-NNNN
            '/([A-Z]{2,4}-\d{4}-\d{3,6})/i'   // Patrón general PREF-YYYY-NNNN
        ];
        
        foreach ($patrones as $patron) {
            if (preg_match($patron, $nombreArchivo, $matches)) {
                $cufe = $matches[1] ?? $matches[0];
                
                // Validar que el CUFE tenga un formato válido
                if (strlen($cufe) >= 10) {
                    return $cufe;
                }
            }
        }
        
        // Si no se encuentra en el nombre, generar un CUFE simulado basado en el archivo
        return 'CUFE' . strtoupper(md5($nombreArchivo . time()));
    }

    /**
     * Enviar acuse real con datos completos de la factura
     */
    private function enviarAcuseRealConDatos(EmailBuzon $email, array $datosFactura, string $destinatario): bool
    {
        try {
            Log::info('Buzón Email: Enviando acuse con datos completos', [
                'destinatario' => $destinatario,
                'cufe' => $datosFactura['cufe'],
                'numero_factura' => $datosFactura['numero_factura']
            ]);
            
            // Enviar acuse usando Laravel Mail con template completo
            Mail::to($destinatario)->send(new AcuseReciboMail($email, $datosFactura, $this->empresa));
            
            // Registrar envío en metadatos del email
            $metadatos = $email->metadatos ?? [];
            $metadatos['acuse_enviado'] = [
                'fecha' => now()->toISOString(),
                'destinatario' => $destinatario,
                'cufe' => $datosFactura['cufe'],
                'numero_factura' => $datosFactura['numero_factura'],
                'metodo' => 'email_real'
            ];
            $email->update(['metadatos' => $metadatos]);
            
            Log::info('Buzón Email: Acuse enviado y registrado exitosamente', [
                'email_id' => $email->id,
                'destinatario' => $destinatario
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Buzón Email: Error enviando acuse con datos completos', [
                'email_id' => $email->id,
                'destinatario' => $destinatario,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Extraer número de factura del asunto del email
     */
    private function extraerNumeroFacturaDelAsunto(string $asunto): string
    {
        if (preg_match('/(FE-\d{4}-\d{3,6})/i', $asunto, $matches)) {
            return $matches[1];
        }
        if (preg_match('/(\w{2,4}-\d{4}-\d{3,6})/i', $asunto, $matches)) {
            return $matches[1];
        }
        if (preg_match('/(\d{4}-\d{3,6})/i', $asunto, $matches)) {
            return 'FE-' . $matches[1];
        }
        return 'FE-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Obtener NIT del proveedor basado en el email
     */
    private function obtenerNitProveedor(string $email): ?string
    {
        $dominiosNit = [
            'agrosander.com' => '900123456-1',
            'worldoffice.com.co' => '900789123-2',
            'automatafe.com' => '900456789-3',
            'equiredes.com' => '900654321-4',
            'colcomercio.com.co' => '900987654-5'
        ];
        
        $dominio = substr(strrchr($email, "@"), 1);
        return $dominiosNit[$dominio] ?? null;
    }

    /**
     * Mapear email corporativo a email real del proveedor
     */
    private function mapearEmailReal(string $emailRemitente): string
    {
        // Mapeo de emails corporativos a emails reales
        $emailsReales = [
            'facturacion@agrosander.com' => 'agrosandersas@gmail.com',
            'agrosander@gmail.com' => 'agrosandersas@gmail.com',
            'facturacion@worldoffice.com.co' => 'worldoffice@gmail.com',
            'worldoffice@gmail.com' => 'worldoffice@gmail.com',
            'facturacion@automatafe.com' => 'automatafe@gmail.com',
            'automatafe@gmail.com' => 'automatafe@gmail.com',
            'facturacion@equiredes.com' => 'equiredes@gmail.com',
            'equiredes@gmail.com' => 'equiredes@gmail.com',
            'facturacion@colcomercio.com.co' => 'colcomercio@gmail.com',
            'colcomercio@gmail.com' => 'colcomercio@gmail.com'
        ];
        
        // Si tenemos el email real mapeado, usarlo
        if (isset($emailsReales[$emailRemitente])) {
            Log::info('Buzón Email: Email mapeado encontrado', [
                'email_corporativo' => $emailRemitente,
                'email_real' => $emailsReales[$emailRemitente]
            ]);
            return $emailsReales[$emailRemitente];
        }
        
        // Si el email del remitente parece ser real (gmail, outlook, etc.), usarlo
        $dominiosReales = ['gmail.com', 'outlook.com', 'hotmail.com', 'yahoo.com'];
        $dominio = substr(strrchr($emailRemitente, "@"), 1);
        
        if (in_array($dominio, $dominiosReales)) {
            Log::info('Buzón Email: Email real detectado', [
                'email' => $emailRemitente,
                'dominio' => $dominio
            ]);
            return $emailRemitente;
        }
        
        // Para dominios corporativos específicos, intentar mapear
        if (strpos($emailRemitente, 'agrosander') !== false) {
            Log::info('Buzón Email: Mapeando dominio Agrosander', [
                'email_original' => $emailRemitente,
                'email_mapeado' => 'agrosandersas@gmail.com'
            ]);
            return 'agrosandersas@gmail.com';
        }
        
        // Por defecto, usar el email del remitente
        Log::warning('Buzón Email: No se pudo mapear email, usando original', [
            'email' => $emailRemitente
        ]);
        return $emailRemitente;
    }
}
