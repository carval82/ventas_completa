<?php

namespace App\Services\Dian;

use App\Models\ConfiguracionDian;
use App\Models\FacturaDianProcesada;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmailProcessorService
{
    private $configuracion;
    private $imapConnection;

    public function __construct(ConfiguracionDian $configuracion)
    {
        $this->configuracion = $configuracion;
    }

    /**
     * Obtener configuraciones predefinidas comunes
     */
    public static function obtenerConfiguracionesPredefinidas(): array
    {
        return [
            'gmail' => [
                'nombre' => 'Gmail',
                'servidor_imap' => 'imap.gmail.com',
                'puerto_imap' => 993,
                'ssl_enabled' => true,
                'ejemplo_email' => 'tu_email@gmail.com',
                'instrucciones' => 'Usa tu email de Gmail y contraseña de aplicación (si tienes 2FA activado)'
            ],
            'outlook' => [
                'nombre' => 'Outlook/Hotmail',
                'servidor_imap' => 'outlook.office365.com',
                'puerto_imap' => 993,
                'ssl_enabled' => true,
                'ejemplo_email' => 'tu_email@outlook.com',
                'instrucciones' => 'Usa tu email de Outlook y contraseña normal'
            ],
            'yahoo' => [
                'nombre' => 'Yahoo Mail',
                'servidor_imap' => 'imap.mail.yahoo.com',
                'puerto_imap' => 993,
                'ssl_enabled' => true,
                'ejemplo_email' => 'tu_email@yahoo.com',
                'instrucciones' => 'Activa "Aplicaciones menos seguras" en configuración de Yahoo'
            ],
            'personalizado' => [
                'nombre' => 'Servidor Personalizado',
                'servidor_imap' => '',
                'puerto_imap' => 993,
                'ssl_enabled' => true,
                'ejemplo_email' => 'tu_email@tudominio.com',
                'instrucciones' => 'Consulta con tu proveedor de email los datos del servidor IMAP'
            ]
        ];
    }

    /**
     * Detectar configuración de email desde variables de entorno (opcional)
     */
    public static function detectarConfiguracionExistente(): array
    {
        $config = [
            'email_detectado' => null,
            'servidor_detectado' => null,
            'puerto_detectado' => null,
            'ssl_detectado' => true,
            'configuracion_encontrada' => false,
            'fuente' => null
        ];

        try {
            // Intentar obtener configuración desde .env (opcional)
            $mailHost = env('MAIL_HOST');
            $mailUsername = env('MAIL_USERNAME');
            $mailPassword = env('MAIL_PASSWORD');

            if ($mailHost && $mailUsername && $mailPassword) {
                // Detectar tipo de proveedor
                if (strpos($mailHost, 'gmail') !== false) {
                    $config['email_detectado'] = $mailUsername;
                    $config['servidor_detectado'] = 'imap.gmail.com';
                    $config['puerto_detectado'] = 993;
                    $config['ssl_detectado'] = true;
                    $config['configuracion_encontrada'] = true;
                    $config['fuente'] = 'Variables de entorno (Gmail)';
                }
                elseif (strpos($mailHost, 'outlook') !== false) {
                    $config['email_detectado'] = $mailUsername;
                    $config['servidor_detectado'] = 'outlook.office365.com';
                    $config['puerto_detectado'] = 993;
                    $config['ssl_detectado'] = true;
                    $config['configuracion_encontrada'] = true;
                    $config['fuente'] = 'Variables de entorno (Outlook)';
                }
            }

        } catch (\Exception $e) {
            Log::warning("Error detectando configuración existente: " . $e->getMessage());
        }

        return $config;
    }

    /**
     * Procesar emails de facturas DIAN
     */
    public function procesarEmails(): array
    {
        Log::info('DIAN EmailProcessor: Iniciando procesamiento de emails', [
            'empresa_id' => $this->configuracion->empresa_id,
            'email_dian' => $this->configuracion->email_dian,
            'servidor_imap' => $this->configuracion->servidor_imap
        ]);

        $resultados = [
            'emails_procesados' => 0,
            'facturas_encontradas' => 0,
            'errores' => [],
            'facturas_procesadas' => []
        ];

        try {
            // Conectar al servidor IMAP
            Log::info('DIAN EmailProcessor: Intentando conectar al servidor IMAP');
            if (!$this->conectarIMAP()) {
                throw new \Exception('No se pudo conectar al servidor de email');
            }
            Log::info('DIAN EmailProcessor: Conexión IMAP exitosa');

            // Obtener emails no leídos
            $emails = $this->obtenerEmailsNoLeidos();
            
            Log::info('DIAN EmailProcessor: Emails obtenidos', [
                'total_emails' => count($emails)
            ]);
            
            foreach ($emails as $emailId => $email) {
                try {
                    Log::info('DIAN EmailProcessor: Procesando email', [
                        'email_id' => $emailId,
                        'asunto' => $email['header']->subject ?? 'Sin asunto'
                    ]);

                    $resultadoProcesamiento = $this->procesarEmail($emailId, $email);
                    
                    if ($resultadoProcesamiento['success']) {
                        $resultados['emails_procesados']++;
                        $resultados['facturas_encontradas'] += $resultadoProcesamiento['facturas_count'];
                        $resultados['facturas_procesadas'][] = $resultadoProcesamiento['factura'];
                        
                        Log::info('DIAN EmailProcessor: Email procesado exitosamente', [
                            'email_id' => $emailId,
                            'facturas_count' => $resultadoProcesamiento['facturas_count']
                        ]);
                    } else {
                        $resultados['errores'][] = $resultadoProcesamiento['error'];
                        Log::warning('DIAN EmailProcessor: Error procesando email', [
                            'email_id' => $emailId,
                            'error' => $resultadoProcesamiento['error']
                        ]);
                    }
                    
                } catch (\Exception $e) {
                    $error = "Error procesando email {$emailId}: " . $e->getMessage();
                    $resultados['errores'][] = $error;
                    Log::error('DIAN EmailProcessor: Excepción procesando email', [
                        'email_id' => $emailId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            $this->cerrarConexionIMAP();

        } catch (\Exception $e) {
            $resultados['errores'][] = "Error general: " . $e->getMessage();
            Log::error("Error en procesamiento de emails DIAN: " . $e->getMessage());
        }

        return $resultados;
    }

    /**
     * Conectar al servidor IMAP
     */
    private function conectarIMAP(): bool
    {
        try {
            $servidor = "{{$this->configuracion->servidor_imap}:{$this->configuracion->puerto_imap}";
            
            if ($this->configuracion->ssl_enabled) {
                $servidor .= "/imap/ssl";
            }
            
            $servidor .= "}INBOX";

            Log::info('DIAN IMAP: Intentando conexión', [
                'servidor' => $servidor,
                'email' => $this->configuracion->email_dian,
                'ssl_enabled' => $this->configuracion->ssl_enabled
            ]);

            $this->imapConnection = imap_open(
                $servidor,
                $this->configuracion->email_dian,
                $this->configuracion->password_email
            );

            if (!$this->imapConnection) {
                $error = imap_last_error();
                Log::error("DIAN IMAP: Error de conexión", [
                    'error' => $error,
                    'servidor' => $servidor,
                    'email' => $this->configuracion->email_dian
                ]);
                return false;
            }

            Log::info('DIAN IMAP: Conexión exitosa', [
                'servidor' => $servidor,
                'email' => $this->configuracion->email_dian
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("DIAN IMAP: Excepción en conexión", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'servidor' => $servidor ?? 'no definido',
                'email' => $this->configuracion->email_dian
            ]);
            return false;
        }
    }

    /**
     * Obtener emails no leídos
     */
    private function obtenerEmailsNoLeidos(): array
    {
        $emails = [];
        
        try {
            // Buscar emails no leídos de hoy
            $fechaHoy = date('d-M-Y');
            $busqueda = "UNSEEN SINCE \"{$fechaHoy}\"";
            
            $emailIds = imap_search($this->imapConnection, $busqueda);
            
            if (!$emailIds) {
                return $emails;
            }

            foreach ($emailIds as $emailId) {
                $header = imap_headerinfo($this->imapConnection, $emailId);
                $estructura = imap_fetchstructure($this->imapConnection, $emailId);
                
                // Filtrar solo emails que parezcan facturas
                if ($this->esEmailDeFactura($header)) {
                    $emails[$emailId] = [
                        'header' => $header,
                        'estructura' => $estructura
                    ];
                }
            }

        } catch (\Exception $e) {
            Log::error("Error obteniendo emails: " . $e->getMessage());
        }

        return $emails;
    }

    /**
     * Verificar si el email parece contener una factura
     */
    private function esEmailDeFactura($header): bool
    {
        $asunto = $header->subject ?? '';
        $remitente = $header->from[0]->mailbox ?? '';
        
        // Palabras clave que indican facturas electrónicas
        $palabrasClave = [
            'factura',
            'invoice',
            'cufe',
            'dian',
            'electronica',
            'fe_',
            'facturacion'
        ];

        $asuntoLower = strtolower($asunto);
        
        foreach ($palabrasClave as $palabra) {
            if (strpos($asuntoLower, $palabra) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Procesar un email específico
     */
    private function procesarEmail(int $emailId, array $email): array
    {
        try {
            $header = $email['header'];
            $estructura = $email['estructura'];

            // Crear registro de factura procesada
            $factura = FacturaDianProcesada::create([
                'empresa_id' => $this->configuracion->empresa_id,
                'mensaje_id' => $header->message_id,
                'asunto_email' => $header->subject,
                'remitente_email' => $header->from[0]->mailbox . '@' . $header->from[0]->host,
                'fecha_email' => date('Y-m-d H:i:s', $header->udate),
                'archivos_adjuntos' => [],
                'estado' => 'procesando'
            ]);

            // Procesar archivos adjuntos
            $archivosDescargados = $this->procesarAdjuntos($emailId, $estructura, $factura);
            
            if (empty($archivosDescargados)) {
                $factura->marcarComoError('No se encontraron archivos adjuntos');
                return ['success' => false, 'error' => 'Sin archivos adjuntos'];
            }

            // Extraer archivos comprimidos y buscar XML
            $extractor = new FileExtractorService();
            $archivosExtraidos = $extractor->extraerArchivos($archivosDescargados);
            
            // Buscar y procesar XML de factura
            $xmlProcessor = new XmlFacturaService();
            $datosFactura = $xmlProcessor->procesarFacturas($archivosExtraidos);
            
            if ($datosFactura) {
                $factura->marcarComoProcesada($datosFactura);
                
                // Marcar email como leído
                imap_setflag_full($this->imapConnection, $emailId, "\\Seen");
                
                return [
                    'success' => true,
                    'facturas_count' => 1,
                    'factura' => $factura
                ];
            } else {
                $factura->marcarComoError('No se pudo extraer información de la factura');
                return ['success' => false, 'error' => 'Error procesando XML'];
            }

        } catch (\Exception $e) {
            Log::error("Error procesando email {$emailId}: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Procesar archivos adjuntos del email
     */
    private function procesarAdjuntos(int $emailId, $estructura, FacturaDianProcesada $factura): array
    {
        $archivosDescargados = [];
        
        try {
            if (isset($estructura->parts)) {
                foreach ($estructura->parts as $partNum => $part) {
                    if ($this->esAdjunto($part)) {
                        $archivo = $this->descargarAdjunto($emailId, $partNum + 1, $part, $factura);
                        if ($archivo) {
                            $archivosDescargados[] = $archivo;
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error("Error procesando adjuntos: " . $e->getMessage());
        }

        return $archivosDescargados;
    }

    /**
     * Verificar si una parte del email es un adjunto
     */
    private function esAdjunto($part): bool
    {
        return isset($part->disposition) && 
               strtolower($part->disposition) === 'attachment';
    }

    /**
     * Descargar un archivo adjunto
     */
    private function descargarAdjunto(int $emailId, int $partNum, $part, FacturaDianProcesada $factura): ?string
    {
        try {
            $nombreArchivo = 'sin_nombre';
            
            // Obtener nombre del archivo
            if (isset($part->dparameters)) {
                foreach ($part->dparameters as $param) {
                    if (strtolower($param->attribute) === 'filename') {
                        $nombreArchivo = $param->value;
                        break;
                    }
                }
            }

            // Crear directorio de descarga
            $carpetaDescarga = $this->configuracion->carpeta_descarga . '/' . $factura->id;
            Storage::makeDirectory($carpetaDescarga);

            // Descargar archivo
            $contenido = imap_fetchbody($this->imapConnection, $emailId, $partNum);
            
            // Decodificar según la codificación
            if ($part->encoding === 3) { // Base64
                $contenido = base64_decode($contenido);
            } elseif ($part->encoding === 4) { // Quoted-printable
                $contenido = quoted_printable_decode($contenido);
            }

            // Guardar archivo
            $rutaArchivo = $carpetaDescarga . '/' . $nombreArchivo;
            Storage::put($rutaArchivo, $contenido);

            Log::info("Archivo descargado: {$rutaArchivo}");
            
            return storage_path('app/' . $rutaArchivo);

        } catch (\Exception $e) {
            Log::error("Error descargando adjunto: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cerrar conexión IMAP
     */
    private function cerrarConexionIMAP(): void
    {
        if ($this->imapConnection) {
            imap_close($this->imapConnection);
        }
    }
}
