<?php

namespace App\Services\Alegra;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AlegraService
{
    protected $baseUrl;
    protected $apiKey;
    protected $apiToken;
    protected $headers;

    public function __construct()
    {
        $this->baseUrl = config('services.alegra.base_url', 'https://api.alegra.com/api/v1');
        
        // Obtener credenciales desde el modelo de Empresa
        try {
            $empresa = \App\Models\Empresa::first();
            if ($empresa) {
                $this->apiKey = $empresa->alegra_email;
                $this->apiToken = $empresa->alegra_token;
                
                // Registrar para depuración
                Log::info('Credenciales de Alegra obtenidas desde la empresa: ' . $empresa->nombre_comercial);
            } else {
                // Si no hay empresa, usar valores de configuración como respaldo
                $this->apiKey = config('services.alegra.api_key');
                $this->apiToken = config('services.alegra.api_token');
                Log::warning('No se encontró información de empresa, usando configuración por defecto para Alegra');
            }
        } catch (\Exception $e) {
            // En caso de error, usar valores de configuración
            $this->apiKey = config('services.alegra.api_key');
            $this->apiToken = config('services.alegra.api_token');
            Log::error('Error al obtener credenciales de Alegra desde la empresa: ' . $e->getMessage());
        }
        
        // Autenticación básica con API key y token
        $this->headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->apiToken)
        ];
    }

    /**
     * Obtiene todas las facturas de Alegra
     * 
     * @param array $params Parámetros de filtrado
     * @return array
     */
    public function getInvoices(array $params = [])
    {
        try {
            // Limpiar caché durante desarrollo para forzar datos frescos
            Cache::forget('alegra_invoices_' . md5(json_encode($params)));
            
            // Log de parámetros recibidos para depuración
            Log::info('Parámetros recibidos en getInvoices:', $params);
            
            // Establecer límite predeterminado si no está definido
            if (!isset($params['limit']) || $params['limit'] > 30) {
                $params['limit'] = 30; // Máximo permitido por Alegra
            }
            
            // Establecer límite total predeterminado si no está definido
            if (!isset($params['totalLimit'])) {
                $params['totalLimit'] = 90; // Obtener hasta 90 facturas en total (3 páginas)
            }
            
            // Determinar el número total de facturas a obtener
            $totalFacturas = $params['totalLimit'];
            
            // Establecer límite por página (máximo 30 según API de Alegra)
            $limitePorPagina = $params['limit'];
            
            // Preparar parámetros base para la consulta
            $queryParams = [
                'limit' => $limitePorPagina,
                'order_direction' => $params['orderDirection'] ?? 'DESC',
                'order_field' => $params['orderField'] ?? 'date',
            ];
            
            // IMPORTANTE: Corregir los nombres de parámetros para filtros de fecha
            // La API de Alegra usa 'date_start' y 'date_end' en lugar de 'start' y 'end'
            if (isset($params['start']) && !empty($params['start'])) {
                // Asegurar formato YYYY-MM-DD
                $fecha = Carbon::parse($params['start'])->format('Y-m-d');
                $queryParams['date_start'] = $fecha;
                Log::info('Filtro fecha inicio aplicado: ' . $fecha);
            }
            
            if (isset($params['end']) && !empty($params['end'])) {
                // Asegurar formato YYYY-MM-DD
                $fecha = Carbon::parse($params['end'])->format('Y-m-d');
                $queryParams['date_end'] = $fecha;
                Log::info('Filtro fecha fin aplicado: ' . $fecha);
            }
            
            // Agregar filtro de estado si está definido
            if (isset($params['status']) && !empty($params['status'])) {
                $queryParams['status'] = $params['status'];
                Log::info('Filtro estado aplicado: ' . $params['status']);
            }
            
            // Inicializar array para almacenar todas las facturas
            $todasLasFacturas = [];
            $pagina = 1;
            $facturasTotales = 0;
            $facturasEnPagina = 0;
            
            Log::info('Iniciando consulta paginada a Alegra con parámetros:', $queryParams);
            
            // Realizar múltiples solicitudes paginadas hasta alcanzar el límite total
            do {
                // Establecer página actual
                $queryParams['page'] = $pagina;
                
                Log::info('Solicitando página ' . $pagina . ' a API de Alegra');
                
                $response = Http::withHeaders($this->headers)
                    ->get($this->baseUrl . '/invoices', $queryParams);
                
                // Registrar la respuesta
                Log::info('Código de respuesta de Alegra (página ' . $pagina . '): ' . $response->status());
                
                if ($response->successful()) {
                    $data = $response->json();
                    $facturasEnPagina = count($data);
                    
                    if ($facturasEnPagina > 0) {
                        // Agregar facturas de esta página al resultado total
                        $todasLasFacturas = array_merge($todasLasFacturas, $data);
                        $facturasTotales += $facturasEnPagina;
                        
                        // Registrar información de paginación
                        Log::info('Obtenidas ' . $facturasEnPagina . ' facturas en página ' . $pagina . '. Total acumulado: ' . $facturasTotales);
                        
                        // Si es la primera página, registrar la estructura de la primera factura
                        if ($pagina == 1 && !empty($data)) {
                            Log::info('Estructura de la primera factura: ' . json_encode($data[0]));
                            Log::info('Fecha de la primera factura: ' . ($data[0]['date'] ?? 'No disponible'));
                        }
                        
                        // Incrementar página para la siguiente iteración
                        $pagina++;
                    } else {
                        // No hay más facturas, salir del bucle
                        Log::info('No hay más facturas disponibles en la página ' . $pagina);
                        break;
                    }
                } else {
                    // Error en la solicitud, registrar y salir del bucle
                    Log::error('Error al obtener facturas de Alegra (página ' . $pagina . '): ' . $response->body());
                    break;
                }
                
                // Continuar hasta alcanzar el límite total o hasta que no haya más facturas
            } while ($facturasTotales < $totalFacturas && $facturasEnPagina > 0);
            
            // Registrar resumen final
            Log::info('Finalizada paginación. Total de facturas obtenidas: ' . count($todasLasFacturas));
            
            if (!empty($todasLasFacturas)) {
                return $todasLasFacturas;
            }
            
            // Si llegamos aquí sin facturas, registrar error general
            Log::error('No se pudieron obtener facturas de Alegra');
            return [];
            
        } catch (\Exception $e) {
            Log::error('Error en la conexión con Alegra: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene una factura específica de Alegra
     * 
     * @param int $invoiceId ID de la factura en Alegra
     * @return array
     */
    public function getInvoice($invoiceId)
    {
        try {
            $cacheKey = 'alegra_invoice_' . $invoiceId;
            
            return Cache::remember($cacheKey, 900, function () use ($invoiceId) {
                $response = Http::withHeaders($this->headers)
                    ->get($this->baseUrl . '/invoices/' . $invoiceId);
                
                if ($response->successful()) {
                    return $response->json();
                }
                
                Log::error('Error al obtener factura de Alegra: ' . $response->body());
                return null;
            });
        } catch (\Exception $e) {
            Log::error('Error en la conexión con Alegra: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Crea una nueva factura en Alegra
     * 
     * @param array $invoiceData Datos de la factura
     * @return array|null
     */
    public function createInvoice($invoiceData)
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->post($this->baseUrl . '/invoices', $invoiceData);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('Error al crear factura en Alegra: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Error en la conexión con Alegra: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualiza una factura existente en Alegra
     * 
     * @param int $invoiceId ID de la factura en Alegra
     * @param array $invoiceData Datos de la factura
     * @return array|null
     */
    public function updateInvoice($invoiceId, $invoiceData)
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->put($this->baseUrl . '/invoices/' . $invoiceId, $invoiceData);
            
            if ($response->successful()) {
                // Actualizar caché
                Cache::forget('alegra_invoice_' . $invoiceId);
                return $response->json();
            }
            
            Log::error('Error al actualizar factura en Alegra: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Error en la conexión con Alegra: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Envía una factura por correo electrónico
     * 
     * @param int $invoiceId ID de la factura en Alegra
     * @param array $emailData Datos del correo
     * @return bool
     */
    public function sendInvoiceByEmail($invoiceId, $emailData)
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->post($this->baseUrl . '/invoices/' . $invoiceId . '/email', $emailData);
            
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Error al enviar factura por correo: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el estado de la factura electrónica
     * 
     * @param int $invoiceId ID de la factura en Alegra
     * @return array|null
     */
    public function getElectronicInvoiceStatus($invoiceId)
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->get($this->baseUrl . '/invoices/' . $invoiceId . '/electronic-document');
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('Error al obtener estado de factura electrónica: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Error en la conexión con Alegra: ' . $e->getMessage());
            return null;
        }
    }
}
