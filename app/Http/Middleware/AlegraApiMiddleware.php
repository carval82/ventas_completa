<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AlegraApiMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('alegra.email') || !config('alegra.token')) {
            return response()->json(['error' => 'Credenciales de Alegra no configuradas'], 401);
        }

        return $next($request);
    }
} 