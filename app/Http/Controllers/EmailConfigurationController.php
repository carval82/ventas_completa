<?php

namespace App\Http\Controllers;

use App\Models\EmailConfiguration;
use App\Services\DynamicEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EmailConfigurationController extends Controller
{
    protected $dynamicEmailService;

    public function __construct(DynamicEmailService $dynamicEmailService)
    {
        $this->dynamicEmailService = $dynamicEmailService;
    }

    /**
     * Mostrar configuraciones de email
     */
    public function index()
    {
        $empresaId = Auth::user()->empresa_id;
        
        $configuraciones = EmailConfiguration::where('empresa_id', $empresaId)
                                           ->orderBy('created_at', 'desc')
                                           ->get();

        $estadisticas = $this->dynamicEmailService->obtenerEstadisticas($empresaId);

        return view('email-configurations.index', compact('configuraciones', 'estadisticas'));
    }

    /**
     * Mostrar formulario de nueva configuración
     */
    public function create()
    {
        return view('email-configurations.create');
    }

    /**
     * Guardar nueva configuración
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'proveedor' => 'required|in:smtp,sendgrid,mailgun,ses,postmark',
            'from_address' => 'required|email',
            'from_name' => 'required|string|max:255',
            'host' => 'required_if:proveedor,smtp|nullable|string',
            'port' => 'required_if:proveedor,smtp|nullable|integer',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'api_key' => 'required_if:proveedor,sendgrid,mailgun,ses,postmark|nullable|string',
            'encryption' => 'required_if:proveedor,smtp|in:tls,ssl,none',
            'limite_diario' => 'nullable|integer|min:1',
            'es_backup' => 'boolean',
            'es_acuses' => 'boolean',
            'es_notificaciones' => 'boolean'
        ]);

        try {
            $empresaId = Auth::user()->empresa_id;

            // Verificar que el nombre sea único para la empresa
            $existe = EmailConfiguration::where('empresa_id', $empresaId)
                                      ->where('nombre', $request->nombre)
                                      ->exists();

            if ($existe) {
                return back()->withErrors(['nombre' => 'Ya existe una configuración con este nombre']);
            }

            // Si es la primera configuración, marcarla como activa por defecto
            $esLaPrimera = EmailConfiguration::where('empresa_id', $empresaId)->count() === 0;

            $configuracion = EmailConfiguration::create([
                'empresa_id' => $empresaId,
                'nombre' => $request->nombre,
                'proveedor' => $request->proveedor,
                'host' => $request->host,
                'port' => $request->port,
                'username' => $request->username,
                'password' => $request->password,
                'api_key' => $request->api_key,
                'encryption' => $request->encryption ?? 'tls',
                'from_address' => $request->from_address,
                'from_name' => $request->from_name,
                'limite_diario' => $request->limite_diario,
                'es_backup' => $request->boolean('es_backup'),
                'es_acuses' => $request->boolean('es_acuses'),
                'es_notificaciones' => $request->boolean('es_notificaciones'),
                'activo' => $esLaPrimera ? true : $request->boolean('activo', false),
                'fecha_reset_contador' => now()->toDateString()
            ]);

            Log::info('Configuración de email creada', [
                'configuracion_id' => $configuracion->id,
                'empresa_id' => $empresaId,
                'proveedor' => $configuracion->proveedor
            ]);

            return redirect()->route('email-configurations.index')
                           ->with('success', 'Configuración de email creada exitosamente');

        } catch (\Exception $e) {
            Log::error('Error creando configuración de email', [
                'error' => $e->getMessage(),
                'empresa_id' => Auth::user()->empresa_id
            ]);

            return back()->withErrors(['error' => 'Error creando configuración: ' . $e->getMessage()]);
        }
    }

    /**
     * Mostrar configuración específica
     */
    public function show(EmailConfiguration $emailConfiguration)
    {
        $this->authorize('view', $emailConfiguration);
        
        $estadisticas = $this->dynamicEmailService->obtenerEstadisticas(
            $emailConfiguration->empresa_id, 
            $emailConfiguration->id
        );

        return view('email-configurations.show', compact('emailConfiguration', 'estadisticas'));
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(EmailConfiguration $emailConfiguration)
    {
        $this->authorize('update', $emailConfiguration);
        
        return view('email-configurations.edit', compact('emailConfiguration'));
    }

    /**
     * Actualizar configuración
     */
    public function update(Request $request, EmailConfiguration $emailConfiguration)
    {
        $this->authorize('update', $emailConfiguration);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'proveedor' => 'required|in:smtp,sendgrid,mailgun,ses,postmark',
            'from_address' => 'required|email',
            'from_name' => 'required|string|max:255',
            'host' => 'required_if:proveedor,smtp|nullable|string',
            'port' => 'required_if:proveedor,smtp|nullable|integer',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'api_key' => 'required_if:proveedor,sendgrid,mailgun,ses,postmark|nullable|string',
            'encryption' => 'required_if:proveedor,smtp|in:tls,ssl,none',
            'limite_diario' => 'nullable|integer|min:1'
        ]);

        try {
            // Verificar nombre único (excluyendo la configuración actual)
            $existe = EmailConfiguration::where('empresa_id', $emailConfiguration->empresa_id)
                                      ->where('nombre', $request->nombre)
                                      ->where('id', '!=', $emailConfiguration->id)
                                      ->exists();

            if ($existe) {
                return back()->withErrors(['nombre' => 'Ya existe una configuración con este nombre']);
            }

            $datosActualizacion = [
                'nombre' => $request->nombre,
                'proveedor' => $request->proveedor,
                'host' => $request->host,
                'port' => $request->port,
                'username' => $request->username,
                'encryption' => $request->encryption ?? 'tls',
                'from_address' => $request->from_address,
                'from_name' => $request->from_name,
                'limite_diario' => $request->limite_diario,
                'es_backup' => $request->boolean('es_backup'),
                'es_acuses' => $request->boolean('es_acuses'),
                'es_notificaciones' => $request->boolean('es_notificaciones'),
                'activo' => $request->boolean('activo')
            ];

            // Solo actualizar password/api_key si se proporcionan
            if ($request->filled('password')) {
                $datosActualizacion['password'] = $request->password;
            }

            if ($request->filled('api_key')) {
                $datosActualizacion['api_key'] = $request->api_key;
            }

            $emailConfiguration->update($datosActualizacion);

            return redirect()->route('email-configurations.index')
                           ->with('success', 'Configuración actualizada exitosamente');

        } catch (\Exception $e) {
            Log::error('Error actualizando configuración de email', [
                'configuracion_id' => $emailConfiguration->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Error actualizando configuración: ' . $e->getMessage()]);
        }
    }

    /**
     * Eliminar configuración
     */
    public function destroy(EmailConfiguration $emailConfiguration)
    {
        $this->authorize('delete', $emailConfiguration);

        try {
            $emailConfiguration->delete();

            return redirect()->route('email-configurations.index')
                           ->with('success', 'Configuración eliminada exitosamente');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error eliminando configuración: ' . $e->getMessage()]);
        }
    }

    /**
     * Probar configuración
     */
    public function probar(Request $request, EmailConfiguration $emailConfiguration)
    {
        $this->authorize('update', $emailConfiguration);

        $request->validate([
            'email_prueba' => 'required|email'
        ]);

        $resultado = $this->dynamicEmailService->probarConfiguracion(
            $emailConfiguration->id,
            $request->email_prueba
        );

        if ($resultado['success']) {
            return back()->with('success', $resultado['message']);
        } else {
            return back()->withErrors(['error' => $resultado['message']]);
        }
    }

    /**
     * Activar/desactivar configuración
     */
    public function toggleActivo(EmailConfiguration $emailConfiguration)
    {
        $this->authorize('update', $emailConfiguration);

        try {
            $emailConfiguration->update(['activo' => !$emailConfiguration->activo]);

            $estado = $emailConfiguration->activo ? 'activada' : 'desactivada';
            
            return back()->with('success', "Configuración {$estado} exitosamente");

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error cambiando estado: ' . $e->getMessage()]);
        }
    }
}
