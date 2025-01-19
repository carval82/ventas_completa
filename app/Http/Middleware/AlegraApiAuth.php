<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class AlegraApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar que las credenciales de Alegra estén configuradas
        if (!config('alegra.user') || !config('alegra.token')) {
            Log::error('Alegra API: Credenciales no configuradas');
            return response()->json([
                'error' => 'Alegra API: Credenciales no configuradas'
            ], 401);
        }

        // Agregar los headers de autenticación de Alegra
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'application/json');
        
        // Alegra usa autenticación básica con email:token
        $auth = base64_encode(config('alegra.user') . ':' . config('alegra.token'));
        $request->headers->set('Authorization', 'Basic ' . $auth);

        try {
            $response = $next($request);

            // Log de respuestas con error para debugging
            if ($response->getStatusCode() >= 400) {
                Log::error('Alegra API Error', [
                    'status' => $response->getStatusCode(),
                    'response' => $response->getContent(),
                    'request_path' => $request->path(),
                    'request_method' => $request->method()
                ]);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Alegra API Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error en la comunicación con Alegra API',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}