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
     * Crear una factura
     */
    public function crearFactura($data)
    {
        try {
            $response = $this->http->post('/api/v1/invoices', $data);
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::error('Error al crear factura en Alegra', [
                'error' => $response->json(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Error al crear factura'
            ];

        } catch (\Exception $e) {
            Log::error('Exception al crear factura en Alegra', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
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
}