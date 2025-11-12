<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmailBuzon;
use App\Models\ProveedorElectronico;
use App\Models\ConfiguracionDian;
use App\Services\Dian\BuzonEmailService;
use Illuminate\Support\Facades\Log;

class BuzonEmailController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar buzón de correos estilo Outlook
     */
    public function index(Request $request)
    {
        $empresa = auth()->user()->empresa;
        
        if (!$empresa) {
            return redirect()->route('home')->with('error', 'Debe tener una empresa asociada');
        }

        Log::info('Buzón Email: Acceso al buzón', [
            'usuario_id' => auth()->id(),
            'empresa_id' => $empresa->id
        ]);

        // Obtener configuración
        $configuracion = ConfiguracionDian::getConfiguracionActiva($empresa->id);
        
        // Query base
        $query = EmailBuzon::where('empresa_id', $empresa->id);
        
        // Aplicar filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
            Log::info('Buzón Email: Filtro por estado aplicado', ['estado' => $request->estado]);
        }
        
        if ($request->filled('tiene_facturas')) {
            $tieneFacturas = $request->tiene_facturas === '1';
            $query->where('tiene_facturas', $tieneFacturas);
            Log::info('Buzón Email: Filtro por facturas aplicado', ['tiene_facturas' => $tieneFacturas]);
        }
        
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_email', '>=', $request->fecha_desde);
            Log::info('Buzón Email: Filtro fecha desde aplicado', ['fecha_desde' => $request->fecha_desde]);
        }
        
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_email', '<=', $request->fecha_hasta);
            Log::info('Buzón Email: Filtro fecha hasta aplicado', ['fecha_hasta' => $request->fecha_hasta]);
        }
        
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('remitente_email', 'like', "%{$buscar}%")
                  ->orWhere('remitente_nombre', 'like', "%{$buscar}%")
                  ->orWhere('asunto', 'like', "%{$buscar}%");
            });
            Log::info('Buzón Email: Filtro de búsqueda aplicado', ['buscar' => $buscar]);
        }
        
        if ($request->filled('proveedor')) {
            // Filtrar por proveedor específico usando metadatos
            $query->whereJsonContains('metadatos->proveedor_autorizado->id', (int)$request->proveedor);
            Log::info('Buzón Email: Filtro por proveedor aplicado', ['proveedor_id' => $request->proveedor]);
        }

        // Obtener emails con paginación
        $emails = $query->orderBy('fecha_email', 'desc')->paginate(20);
        
        // Obtener estadísticas
        $estadisticas = $this->obtenerEstadisticasBuzon($empresa->id);
        
        // Obtener proveedores para filtros
        $proveedores = ProveedorElectronico::where('empresa_id', $empresa->id)
                                          ->where('activo', true)
                                          ->orderBy('nombre_proveedor')
                                          ->get();

        Log::info('Buzón Email: Vista cargada', [
            'total_emails' => $emails->total(),
            'emails_pagina' => $emails->count(),
            'filtros_aplicados' => $request->only(['estado', 'tiene_facturas', 'fecha_desde', 'fecha_hasta', 'buscar', 'proveedor'])
        ]);

        return view('dian.buzon', compact('emails', 'estadisticas', 'proveedores', 'configuracion'));
    }

    /**
     * Ver detalle de email
     */
    public function verEmail(EmailBuzon $email)
    {
        // Verificar que pertenece a la empresa del usuario
        if ($email->empresa_id !== auth()->user()->empresa->id) {
            abort(403);
        }

        Log::info('Buzón Email: Acceso a detalle de email', [
            'email_id' => $email->id,
            'usuario_id' => auth()->id()
        ]);

        return view('dian.email-detalle', compact('email'));
    }

    /**
     * Sincronizar emails manualmente
     */
    public function sincronizar()
    {
        try {
            $empresa = auth()->user()->empresa;
            $configuracion = ConfiguracionDian::getConfiguracionActiva($empresa->id);
            
            if (!$configuracion) {
                return back()->with('error', 'No hay configuración DIAN activa');
            }

            Log::info('Buzón Email: Sincronización manual iniciada', [
                'empresa_id' => $empresa->id,
                'usuario_id' => auth()->id()
            ]);

            $buzonService = new BuzonEmailService($configuracion);
            $resultado = $buzonService->sincronizarEmails();

            Log::info('Buzón Email: Sincronización manual completada', $resultado);

            if ($resultado['success']) {
                $mensaje = "Sincronización exitosa: {$resultado['emails_descargados']} emails descargados, {$resultado['emails_con_facturas']} con facturas.";
                return back()->with('success', $mensaje);
            } else {
                return back()->with('error', $resultado['message']);
            }

        } catch (\Exception $e) {
            Log::error('Buzón Email: Error en sincronización manual', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Error sincronizando emails: ' . $e->getMessage());
        }
    }

    /**
     * Procesar emails del buzón
     */
    public function procesar()
    {
        try {
            $empresa = auth()->user()->empresa;
            $configuracion = ConfiguracionDian::getConfiguracionActiva($empresa->id);
            
            if (!$configuracion) {
                return back()->with('error', 'No hay configuración DIAN activa');
            }

            Log::info('Buzón Email: Procesamiento manual iniciado', [
                'empresa_id' => $empresa->id,
                'usuario_id' => auth()->id()
            ]);

            $buzonService = new BuzonEmailService($configuracion);
            $resultado = $buzonService->procesarEmailsDelBuzon();

            Log::info('Buzón Email: Procesamiento manual completado', $resultado);

            if ($resultado['success']) {
                $mensaje = "Procesamiento exitoso: {$resultado['emails_procesados']} emails procesados.";
                return back()->with('success', $mensaje);
            } else {
                return back()->with('error', $resultado['message']);
            }

        } catch (\Exception $e) {
            Log::error('Buzón Email: Error en procesamiento manual', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Error procesando emails: ' . $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas del buzón
     */
    private function obtenerEstadisticasBuzon($empresaId): array
    {
        // Obtener último email para fecha de sincronización
        $ultimoEmail = EmailBuzon::where('empresa_id', $empresaId)
                                 ->orderBy('created_at', 'desc')
                                 ->first();
        
        return [
            'total' => EmailBuzon::where('empresa_id', $empresaId)->count(),
            'nuevos' => EmailBuzon::where('empresa_id', $empresaId)->where('estado', 'nuevo')->count(),
            'procesando' => EmailBuzon::where('empresa_id', $empresaId)->where('estado', 'procesando')->count(),
            'procesados' => EmailBuzon::where('empresa_id', $empresaId)->where('estado', 'procesado')->count(),
            'error' => EmailBuzon::where('empresa_id', $empresaId)->where('estado', 'error')->count(),
            'con_facturas' => EmailBuzon::where('empresa_id', $empresaId)->where('tiene_facturas', true)->count(),
            'hoy' => EmailBuzon::where('empresa_id', $empresaId)->whereDate('fecha_email', today())->count(),
            'mes_actual' => EmailBuzon::where('empresa_id', $empresaId)
                                     ->whereMonth('fecha_email', now()->month)
                                     ->whereYear('fecha_email', now()->year)
                                     ->count(),
            'ultima_sincronizacion' => $ultimoEmail ? $ultimoEmail->created_at : null
        ];
    }
}
