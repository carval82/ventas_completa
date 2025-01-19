<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;

class TimezoneMiddleware
{
    public function handle($request, Closure $next)
    {
        // Configurar zona horaria
        date_default_timezone_set('America/Colombia');
        Carbon::setLocale('es');
        
        return $next($request);
    }
} 