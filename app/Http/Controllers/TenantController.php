<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class TenantController extends Controller
{
    /**
     * Crear nueva empresa/tenant
     */
    public function crear(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'nit' => 'required|string|unique:tenants,nit',
            'email' => 'required|email|unique:tenants,email',
            'telefono' => 'nullable|string',
            'direccion' => 'nullable|string',
            'plan' => 'required|in:basico,premium,enterprise'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Generar slug único
            $slug = $this->generarSlugUnico($request->nombre);
            
            // Crear tenant
            $tenant = Tenant::create([
                'slug' => $slug,
                'nombre' => $request->nombre,
                'nit' => $request->nit,
                'email' => $request->email,
                'telefono' => $request->telefono,
                'direccion' => $request->direccion,
                'database_name' => 'ventas_' . $slug,
                'database_host' => env('DB_HOST', 'localhost'),
                'database_port' => env('DB_PORT', '3306'),
                'database_username' => env('DB_USERNAME'),
                'database_password' => env('DB_PASSWORD'),
                'plan' => $request->plan,
                'fecha_creacion' => now(),
                'limite_usuarios' => $this->getLimitesPorPlan($request->plan)['usuarios'],
                'limite_productos' => $this->getLimitesPorPlan($request->plan)['productos'],
                'limite_ventas_mes' => $this->getLimitesPorPlan($request->plan)['ventas_mes'],
            ]);

            // Crear base de datos
            if ($tenant->crearBaseDatos()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Empresa creada exitosamente',
                    'data' => [
                        'tenant' => $tenant,
                        'url_acceso' => $this->generarUrlAcceso($tenant),
                        'credenciales_iniciales' => [
                            'email' => $tenant->email,
                            'password' => 'admin123' // Cambiar en producción
                        ]
                    ]
                ]);
            } else {
                $tenant->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Error creando base de datos'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creando empresa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar tenants
     */
    public function listar()
    {
        $tenants = Tenant::with([])
            ->select(['id', 'slug', 'nombre', 'nit', 'email', 'plan', 'activo', 'created_at'])
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $tenants
        ]);
    }

    /**
     * Obtener estadísticas de un tenant
     */
    public function estadisticas($slug)
    {
        $tenant = Tenant::where('slug', $slug)->first();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa no encontrada'
            ], 404);
        }

        $estadisticas = $tenant->getEstadisticas();

        return response()->json([
            'success' => true,
            'data' => [
                'tenant' => $tenant,
                'estadisticas' => $estadisticas,
                'limites' => [
                    'usuarios' => $tenant->limite_usuarios,
                    'productos' => $tenant->limite_productos,
                    'ventas_mes' => $tenant->limite_ventas_mes
                ]
            ]
        ]);
    }

    /**
     * Activar/Desactivar tenant
     */
    public function toggleEstado($slug)
    {
        $tenant = Tenant::where('slug', $slug)->first();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa no encontrada'
            ], 404);
        }

        $tenant->activo = !$tenant->activo;
        $tenant->save();

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado',
            'data' => ['activo' => $tenant->activo]
        ]);
    }

    /**
     * Generar slug único
     */
    private function generarSlugUnico($nombre): string
    {
        $slug = Str::slug($nombre);
        $contador = 1;
        $slugOriginal = $slug;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $slugOriginal . '-' . $contador;
            $contador++;
        }

        return $slug;
    }

    /**
     * Obtener límites por plan
     */
    private function getLimitesPorPlan($plan): array
    {
        $limites = [
            'basico' => [
                'usuarios' => 3,
                'productos' => 500,
                'ventas_mes' => 200
            ],
            'premium' => [
                'usuarios' => 10,
                'productos' => 2000,
                'ventas_mes' => 1000
            ],
            'enterprise' => [
                'usuarios' => 50,
                'productos' => 10000,
                'ventas_mes' => 5000
            ]
        ];

        return $limites[$plan] ?? $limites['basico'];
    }

    /**
     * Generar URL de acceso
     */
    private function generarUrlAcceso($tenant): string
    {
        // Opción 1: Subdominio
        // return "https://{$tenant->slug}." . config('app.domain');
        
        // Opción 2: Path
        return config('app.url') . "/empresa/{$tenant->slug}";
    }
}
