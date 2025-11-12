<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantMiddleware
{
    /**
     * Identificar y configurar el tenant basado en el subdominio o parámetro
     */
    public function handle(Request $request, Closure $next)
    {
        $tenant = $this->identificarTenant($request);

        if (!$tenant) {
            return response()->json([
                'error' => 'Empresa no encontrada'
            ], 404);
        }

        if (!$tenant->puedeOperar()) {
            return response()->json([
                'error' => 'Empresa inactiva o expirada'
            ], 403);
        }

        // Configurar conexión del tenant
        $tenant->configurarConexion();

        // Guardar tenant en el request para uso posterior
        $request->attributes->set('tenant', $tenant);

        // Configurar conexión por defecto para este request
        Config::set('database.default', 'tenant');

        return $next($request);
    }

    /**
     * Identificar tenant por subdominio o parámetro
     */
    private function identificarTenant(Request $request): ?Tenant
    {
        // Opción 1: Por subdominio (empresa-abc.tudominio.com)
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];
        
        if ($subdomain && $subdomain !== 'www') {
            $tenant = Tenant::where('slug', $subdomain)->first();
            if ($tenant) {
                return $tenant;
            }
        }

        // Opción 2: Por parámetro en la URL (/empresa/empresa-abc/dashboard)
        $slug = $request->route('empresa');
        if ($slug) {
            return Tenant::where('slug', $slug)->first();
        }

        // Opción 3: Por header personalizado
        $tenantHeader = $request->header('X-Tenant');
        if ($tenantHeader) {
            return Tenant::where('slug', $tenantHeader)->first();
        }

        // Opción 4: Por sesión (para desarrollo)
        $sessionTenant = session('tenant_slug');
        if ($sessionTenant) {
            return Tenant::where('slug', $sessionTenant)->first();
        }

        return null;
    }
}
