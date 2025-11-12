<?php

namespace App\Http\Controllers\Dian;

use App\Http\Controllers\Controller;
use App\Models\EmailBuzon;
use App\Services\DynamicEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AcuseController extends Controller
{
    protected $dynamicEmailService;

    public function __construct(DynamicEmailService $dynamicEmailService)
    {
        $this->middleware('auth');
        $this->dynamicEmailService = $dynamicEmailService;
    }

    /**
     * Mostrar lista de acuses
     */
    public function index(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        
        // Filtros
        $filtros = [
            'estado' => $request->get('estado'),
            'proveedor' => $request->get('proveedor'),
            'fecha_desde' => $request->get('fecha_desde'),
            'fecha_hasta' => $request->get('fecha_hasta'),
            'buscar' => $request->get('buscar')
        ];

        // Query base
        $query = EmailBuzon::where('empresa_id', $empresaId)
                          ->where('tiene_facturas', true)
                          ->whereNotNull('metadatos');

        // Aplicar filtros
        if ($filtros['estado']) {
            $query->where('estado', $filtros['estado']);
        }

        if ($filtros['proveedor']) {
            $query->where('remitente_nombre', 'LIKE', "%{$filtros['proveedor']}%");
        }

        if ($filtros['fecha_desde']) {
            $query->whereDate('fecha_email', '>=', $filtros['fecha_desde']);
        }

        if ($filtros['fecha_hasta']) {
            $query->whereDate('fecha_email', '<=', $filtros['fecha_hasta']);
        }

        if ($filtros['buscar']) {
            $query->where(function($q) use ($filtros) {
                $q->where('asunto', 'LIKE', "%{$filtros['buscar']}%")
                  ->orWhere('remitente_nombre', 'LIKE', "%{$filtros['buscar']}%")
                  ->orWhere('remitente_email', 'LIKE', "%{$filtros['buscar']}%");
            });
        }

        // Obtener emails con información de acuses
        $emails = $query->orderBy('fecha_email', 'desc')
                       ->paginate(20)
                       ->appends($request->query());

        // Procesar metadatos para cada email
        foreach ($emails as $email) {
            $metadatos = is_string($email->metadatos) ? 
                        json_decode($email->metadatos, true) : 
                        ($email->metadatos ?? []);
            
            $email->acuse_enviado = $metadatos['acuse_enviado'] ?? false;
            $email->fecha_acuse = $metadatos['fecha_acuse'] ?? null;
            $email->email_acuse = $metadatos['email_acuse_enviado_a'] ?? $email->remitente_email;
            $email->email_real = $metadatos['email_real_proveedor'] ?? null;
            $email->datos_proveedor = $metadatos['datos_proveedor_xml'] ?? [];
        }

        // Estadísticas
        $estadisticas = $this->obtenerEstadisticasAcuses($empresaId);

        // Proveedores para filtro
        $proveedores = EmailBuzon::where('empresa_id', $empresaId)
                                ->where('tiene_facturas', true)
                                ->distinct()
                                ->pluck('remitente_nombre')
                                ->filter()
                                ->sort()
                                ->values();

        return view('dian.acuses.index', compact('emails', 'filtros', 'estadisticas', 'proveedores'));
    }

    /**
     * Mostrar detalles de un acuse
     */
    public function show(EmailBuzon $email)
    {
        // Verificar que pertenece a la empresa del usuario
        if ($email->empresa_id !== Auth::user()->empresa_id) {
            abort(403, 'No tienes acceso a este email.');
        }

        // Procesar metadatos
        $metadatos = is_string($email->metadatos) ? 
                    json_decode($email->metadatos, true) : 
                    ($email->metadatos ?? []);

        $email->acuse_enviado = $metadatos['acuse_enviado'] ?? false;
        $email->fecha_acuse = $metadatos['fecha_acuse'] ?? null;
        $email->email_acuse = $metadatos['email_acuse_enviado_a'] ?? $email->remitente_email;
        $email->email_real = $metadatos['email_real_proveedor'] ?? null;
        $email->datos_proveedor = $metadatos['datos_proveedor_xml'] ?? [];
        $email->diferencia_emails = $metadatos['diferencia_emails'] ?? [];

        return view('dian.acuses.show', compact('email', 'metadatos'));
    }

    /**
     * Enviar acuse manualmente
     */
    public function enviar(Request $request, EmailBuzon $email)
    {
        try {
            // Verificar que pertenece a la empresa del usuario
            if ($email->empresa_id !== Auth::user()->empresa_id) {
                return response()->json(['success' => false, 'message' => 'No tienes acceso a este email.'], 403);
            }

            // Obtener metadatos
            $metadatos = is_string($email->metadatos) ? 
                        json_decode($email->metadatos, true) : 
                        ($email->metadatos ?? []);

            // Determinar email de destino
            $emailDestino = $request->get('email_destino') ?? 
                           $metadatos['email_real_proveedor'] ?? 
                           $email->remitente_email;

            // Preparar datos de la factura
            $datosProveedor = $metadatos['datos_proveedor_xml'] ?? [];
            $datosFactura = [
                'cufe' => $datosProveedor['cufe'] ?? 'CUFE-AUTO-' . $email->id,
                'numero_factura' => 'FE-2024-' . str_pad($email->id, 6, '0', STR_PAD_LEFT),
                'fecha_factura' => $email->fecha_email->format('Y-m-d'),
                'proveedor' => [
                    'nombre' => $datosProveedor['nombre'] ?? $email->remitente_nombre,
                    'nit' => $datosProveedor['nit'] ?? 'N/A',
                    'email' => $emailDestino
                ]
            ];

            // Enviar acuse
            $resultado = $this->dynamicEmailService->enviarEmail(
                Auth::user()->empresa_id,
                'acuses',
                $emailDestino,
                'Acuse de Recibo - Factura ' . $datosFactura['numero_factura'],
                'emails.acuse-recibo',
                [
                    'email' => $email,
                    'datosFactura' => $datosFactura,
                    'empresa' => Auth::user()->empresa,
                    'fechaAcuse' => now()->format('d/m/Y H:i:s')
                ]
            );

            if ($resultado['success']) {
                // Actualizar metadatos
                $metadatos['acuse_enviado'] = true;
                $metadatos['fecha_acuse'] = now()->toISOString();
                $metadatos['email_acuse_enviado_a'] = $emailDestino;
                $metadatos['envio_manual'] = true;
                $metadatos['usuario_envio'] = Auth::user()->name;

                $email->update(['metadatos' => json_encode($metadatos)]);

                Log::info('Acuse enviado manualmente', [
                    'email_id' => $email->id,
                    'destinatario' => $emailDestino,
                    'usuario' => Auth::user()->name
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Acuse enviado exitosamente',
                    'destinatario' => $emailDestino,
                    'configuracion' => $resultado['configuracion_usada']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error enviando acuse: ' . $resultado['message']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error enviando acuse manual', [
                'email_id' => $email->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Reenviar acuse
     */
    public function reenviar(Request $request, EmailBuzon $email)
    {
        return $this->enviar($request, $email);
    }

    /**
     * Obtener estadísticas de acuses
     */
    private function obtenerEstadisticasAcuses($empresaId)
    {
        $totalEmails = EmailBuzon::where('empresa_id', $empresaId)
                                ->where('tiene_facturas', true)
                                ->count();

        $emailsConAcuse = EmailBuzon::where('empresa_id', $empresaId)
                                  ->where('tiene_facturas', true)
                                  ->whereNotNull('metadatos')
                                  ->get()
                                  ->filter(function($email) {
                                      $metadatos = is_string($email->metadatos) ? 
                                                  json_decode($email->metadatos, true) : 
                                                  ($email->metadatos ?? []);
                                      return $metadatos['acuse_enviado'] ?? false;
                                  })
                                  ->count();

        $acusesHoy = EmailBuzon::where('empresa_id', $empresaId)
                              ->where('tiene_facturas', true)
                              ->whereNotNull('metadatos')
                              ->whereDate('updated_at', today())
                              ->get()
                              ->filter(function($email) {
                                  $metadatos = is_string($email->metadatos) ? 
                                              json_decode($email->metadatos, true) : 
                                              ($email->metadatos ?? []);
                                  return $metadatos['acuse_enviado'] ?? false;
                              })
                              ->count();

        $emailsSinAcuse = $totalEmails - $emailsConAcuse;

        return [
            'total_emails' => $totalEmails,
            'acuses_enviados' => $emailsConAcuse,
            'acuses_pendientes' => $emailsSinAcuse,
            'acuses_hoy' => $acusesHoy,
            'porcentaje_enviados' => $totalEmails > 0 ? round(($emailsConAcuse / $totalEmails) * 100, 1) : 0
        ];
    }
}
