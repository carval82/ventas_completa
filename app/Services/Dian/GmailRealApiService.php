<?php

namespace App\Services\Dian;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GmailRealApiService
{
    private $client;
    private $gmail;
    private $email;
    private $credentialsPath;

    public function __construct(string $email)
    {
        $this->email = $email;
        $this->credentialsPath = storage_path('app/gmail_credentials.json');
        $this->initializeClient();
    }

    /**
     * Inicializar cliente de Google
     */
    private function initializeClient()
    {
        try {
            $this->client = new Client();
            $this->client->setApplicationName('Sistema Ventas DIAN');
            $this->client->setScopes([Gmail::GMAIL_READONLY, Gmail::GMAIL_MODIFY]);
            $this->client->setAuthConfig($this->getCredentialsConfig());
            $this->client->setAccessType('offline');
            $this->client->setPrompt('select_account consent');

            // Configurar token si existe
            $tokenPath = storage_path('app/gmail_token.json');
            if (file_exists($tokenPath)) {
                $accessToken = json_decode(file_get_contents($tokenPath), true);
                $this->client->setAccessToken($accessToken);
            }

            // Refrescar token si es necesario
            if ($this->client->isAccessTokenExpired()) {
                if ($this->client->getRefreshToken()) {
                    $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                    file_put_contents($tokenPath, json_encode($this->client->getAccessToken()));
                }
            }

            $this->gmail = new Gmail($this->client);

            Log::info('Gmail Real API: Cliente inicializado correctamente', [
                'email' => $this->email,
                'token_exists' => file_exists($tokenPath),
                'token_expired' => $this->client->isAccessTokenExpired()
            ]);

        } catch (\Exception $e) {
            Log::error('Gmail Real API: Error inicializando cliente', [
                'error' => $e->getMessage(),
                'email' => $this->email
            ]);
            throw $e;
        }
    }

    /**
     * Obtener configuración de credenciales
     */
    private function getCredentialsConfig(): array
    {
        // Por ahora usar credenciales básicas, luego se configurarán desde Google Console
        return [
            "web" => [
                "client_id" => env('GOOGLE_CLIENT_ID', ''),
                "project_id" => env('GOOGLE_PROJECT_ID', 'ventas-dian'),
                "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
                "token_uri" => "https://oauth2.googleapis.com/token",
                "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
                "client_secret" => env('GOOGLE_CLIENT_SECRET', ''),
                "redirect_uris" => [
                    url('/dian/oauth/callback')
                ]
            ]
        ];
    }

    /**
     * Obtener URL de autorización OAuth2
     */
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Procesar código de autorización OAuth2
     */
    public function processAuthCode(string $code): bool
    {
        try {
            $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);
            
            if (array_key_exists('error', $accessToken)) {
                Log::error('Gmail Real API: Error en autorización', [
                    'error' => $accessToken['error']
                ]);
                return false;
            }

            // Guardar token
            $tokenPath = storage_path('app/gmail_token.json');
            file_put_contents($tokenPath, json_encode($accessToken));

            Log::info('Gmail Real API: Autorización exitosa', [
                'email' => $this->email
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Gmail Real API: Error procesando código de autorización', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Verificar si está autorizado
     */
    public function isAuthorized(): bool
    {
        $tokenPath = storage_path('app/gmail_token.json');
        return file_exists($tokenPath) && !$this->client->isAccessTokenExpired();
    }

    /**
     * Procesar emails reales de Gmail
     */
    public function procesarEmailsReales(): array
    {
        try {
            if (!$this->isAuthorized()) {
                return [
                    'success' => false,
                    'message' => 'No autorizado. Necesita autenticación OAuth2.',
                    'auth_url' => $this->getAuthUrl(),
                    'emails_procesados' => 0,
                    'facturas_encontradas' => 0,
                    'errores' => ['No autorizado']
                ];
            }

            Log::info('Gmail Real API: Iniciando procesamiento real de emails');

            // Buscar emails con facturas electrónicas
            $query = 'has:attachment (subject:factura OR subject:invoice OR subject:CUFE OR subject:FE OR subject:NC OR subject:ND) newer_than:30d';
            
            $messages = $this->gmail->users_messages->listUsersMessages('me', [
                'q' => $query,
                'maxResults' => 50
            ]);

            $facturasProcesadas = [];
            $emailsProcesados = 0;

            foreach ($messages->getMessages() as $message) {
                $emailsProcesados++;
                $facturas = $this->procesarEmailIndividual($message->getId());
                $facturasProcesadas = array_merge($facturasProcesadas, $facturas);
            }

            Log::info('Gmail Real API: Procesamiento completado', [
                'emails_procesados' => $emailsProcesados,
                'facturas_encontradas' => count($facturasProcesadas)
            ]);

            return [
                'success' => true,
                'message' => 'Procesamiento real completado exitosamente.',
                'emails_procesados' => $emailsProcesados,
                'facturas_encontradas' => count($facturasProcesadas),
                'errores' => [],
                'facturas_procesadas' => $facturasProcesadas,
                'nota' => 'Procesamiento real - Emails obtenidos de Gmail API'
            ];

        } catch (\Exception $e) {
            Log::error('Gmail Real API: Error en procesamiento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error en procesamiento: ' . $e->getMessage(),
                'emails_procesados' => 0,
                'facturas_encontradas' => 0,
                'errores' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Procesar email individual
     */
    private function procesarEmailIndividual(string $messageId): array
    {
        try {
            $message = $this->gmail->users_messages->get('me', $messageId);
            $facturas = [];

            // Extraer información del email
            $headers = $message->getPayload()->getHeaders();
            $subject = '';
            $from = '';
            $date = '';

            foreach ($headers as $header) {
                switch ($header->getName()) {
                    case 'Subject':
                        $subject = $header->getValue();
                        break;
                    case 'From':
                        $from = $header->getValue();
                        break;
                    case 'Date':
                        $date = $header->getValue();
                        break;
                }
            }

            Log::info('Gmail Real API: Procesando email', [
                'message_id' => $messageId,
                'subject' => $subject,
                'from' => $from
            ]);

            // Procesar archivos adjuntos
            $attachments = $this->extraerArchivosAdjuntos($message);
            
            foreach ($attachments as $attachment) {
                if ($this->esArchivoFactura($attachment['filename'])) {
                    $factura = $this->procesarArchivoFactura($attachment, $messageId, $subject, $from, $date);
                    if ($factura) {
                        $facturas[] = $factura;
                    }
                }
            }

            return $facturas;

        } catch (\Exception $e) {
            Log::error('Gmail Real API: Error procesando email individual', [
                'message_id' => $messageId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Extraer archivos adjuntos
     */
    private function extraerArchivosAdjuntos(Message $message): array
    {
        $attachments = [];
        $parts = $message->getPayload()->getParts();

        if ($parts) {
            foreach ($parts as $part) {
                if ($part->getFilename() && $part->getBody()->getAttachmentId()) {
                    $attachment = $this->gmail->users_messages_attachments->get(
                        'me',
                        $message->getId(),
                        $part->getBody()->getAttachmentId()
                    );

                    $attachments[] = [
                        'filename' => $part->getFilename(),
                        'data' => $attachment->getData(),
                        'size' => $part->getBody()->getSize()
                    ];
                }
            }
        }

        return $attachments;
    }

    /**
     * Verificar si es archivo de factura
     */
    private function esArchivoFactura(string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $facturaKeywords = ['factura', 'invoice', 'fe', 'cufe', 'xml', 'zip'];
        
        if (in_array($extension, ['xml', 'zip', 'rar'])) {
            return true;
        }

        foreach ($facturaKeywords as $keyword) {
            if (stripos($filename, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Procesar archivo de factura
     */
    private function procesarArchivoFactura(array $attachment, string $messageId, string $subject, string $from, string $date): ?array
    {
        try {
            // Decodificar datos del archivo
            $data = base64_decode(str_replace(['-', '_'], ['+', '/'], $attachment['data']));
            
            // Guardar archivo temporalmente
            $tempPath = storage_path('app/temp/' . $attachment['filename']);
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }
            file_put_contents($tempPath, $data);

            // Procesar según tipo de archivo
            $extension = strtolower(pathinfo($attachment['filename'], PATHINFO_EXTENSION));
            
            if ($extension === 'xml') {
                return $this->procesarXmlFactura($tempPath, $messageId, $subject, $from, $date);
            } elseif (in_array($extension, ['zip', 'rar'])) {
                return $this->procesarArchivoComprimido($tempPath, $messageId, $subject, $from, $date);
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Gmail Real API: Error procesando archivo de factura', [
                'filename' => $attachment['filename'],
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Procesar XML de factura
     */
    private function procesarXmlFactura(string $xmlPath, string $messageId, string $subject, string $from, string $date): ?array
    {
        try {
            $xmlService = new XmlFacturaService();
            $datosFactura = $xmlService->procesarXML($xmlPath);

            if ($datosFactura) {
                // Crear factura en base de datos
                $usuario = auth()->user();
                $empresa = $usuario->empresa;

                $factura = \App\Models\FacturaDianProcesada::create([
                    'empresa_id' => $empresa->id,
                    'mensaje_id' => $messageId,
                    'remitente_email' => $this->extraerEmail($from),
                    'remitente_nombre' => $this->extraerNombre($from),
                    'asunto_email' => $subject,
                    'fecha_email' => $this->parsearFecha($date),
                    'cufe' => $datosFactura['cufe'] ?? 'CUFE_' . uniqid(),
                    'numero_factura' => $datosFactura['numero_factura'] ?? 'AUTO_' . time(),
                    'nit_emisor' => $datosFactura['nit_emisor'] ?? 'N/A',
                    'nombre_emisor' => $datosFactura['nombre_emisor'] ?? $this->extraerNombre($from),
                    'valor_total' => $datosFactura['valor_total'] ?? 0,
                    'fecha_factura' => $datosFactura['fecha_factura'] ?? now(),
                    'archivos_adjuntos' => json_encode([basename($xmlPath)]),
                    'archivos_extraidos' => json_encode([basename($xmlPath)]),
                    'ruta_xml' => $xmlPath,
                    'ruta_pdf' => null,
                    'estado' => 'procesada',
                    'detalles_procesamiento' => json_encode(['metodo' => 'gmail_api_real', 'fecha' => now()]),
                    'errores' => json_encode([]),
                    'acuse_enviado' => false,
                    'fecha_acuse' => null,
                    'id_acuse' => null,
                    'contenido_acuse' => null,
                    'intentos_procesamiento' => 1,
                    'ultimo_intento' => now(),
                    'metadatos_adicionales' => json_encode(['tipo' => 'gmail_api_real', 'message_id' => $messageId]),
                    'observaciones' => 'Factura procesada automáticamente desde Gmail API'
                ]);

                Log::info('Gmail Real API: Factura creada desde email real', [
                    'factura_id' => $factura->id,
                    'numero_factura' => $factura->numero_factura,
                    'cufe' => $factura->cufe,
                    'message_id' => $messageId
                ]);

                return $factura->toArray();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Gmail Real API: Error procesando XML de factura', [
                'xml_path' => $xmlPath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Procesar archivo comprimido
     */
    private function procesarArchivoComprimido(string $filePath, string $messageId, string $subject, string $from, string $date): ?array
    {
        // Por ahora, implementación básica
        // En el futuro se puede expandir para extraer ZIPs y RARs
        Log::info('Gmail Real API: Archivo comprimido detectado', [
            'file_path' => $filePath,
            'message_id' => $messageId
        ]);
        
        return null;
    }

    /**
     * Extraer email de la cadena From
     */
    private function extraerEmail(string $from): string
    {
        if (preg_match('/<(.+?)>/', $from, $matches)) {
            return $matches[1];
        }
        return $from;
    }

    /**
     * Extraer nombre de la cadena From
     */
    private function extraerNombre(string $from): string
    {
        if (preg_match('/^(.+?)\s*</', $from, $matches)) {
            return trim($matches[1], '"');
        }
        return $this->extraerEmail($from);
    }

    /**
     * Parsear fecha del email
     */
    private function parsearFecha(string $date): \Carbon\Carbon
    {
        try {
            return \Carbon\Carbon::parse($date);
        } catch (\Exception $e) {
            return now();
        }
    }
}
