<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AlegraController extends Controller
{
    protected $baseUrl;
    protected $headers;

    public function __construct()
    {
        $this->baseUrl = config('alegra.base_url');
        $this->headers = [
            'Authorization' => 'Basic ' . base64_encode(config('alegra.user') . ':' . config('alegra.token')),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
    }

    public function test()
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->get($this->baseUrl . '/company');

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error en la respuesta de Alegra',
                'error' => $response->json()
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al conectar con Alegra',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}