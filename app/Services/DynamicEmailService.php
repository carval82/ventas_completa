<?php

namespace App\Services;

use App\Models\EmailConfiguration;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class DynamicEmailService
{
    /**
     * Enviar email usando configuración dinámica por empresa
     */
    public function enviarEmail($empresaId, $tipo, $destinatario, $asunto, $vista, $datos = [], $adjuntos = [])
    {
        try {
            // Obtener configuración según el tipo
            $configuracion = $this->obtenerConfiguracion($empresaId, $tipo);
            
            if (!$configuracion) {
                throw new \Exception("No se encontró configuración de email para tipo: $tipo");
            }

            if (!$configuracion->puedeEnviarEmail()) {
                throw new \Exception("Se ha alcanzado el límite diario de emails para esta configuración");
            }

            // Configurar Laravel Mail dinámicamente
            $this->configurarMailer($configuracion);

            // Enviar el email
            Mail::send($vista, $datos, function ($message) use ($destinatario, $asunto, $configuracion, $adjuntos) {
                $message->to($destinatario)
                        ->subject($asunto)
                        ->from($configuracion->from_address, $configuracion->from_name);
                
                // Agregar adjuntos si los hay
                foreach ($adjuntos as $adjunto) {
                    if (is_array($adjunto)) {
                        $message->attach($adjunto['path'], $adjunto['options'] ?? []);
                    } else {
                        $message->attach($adjunto);
                    }
                }
            });

            // Incrementar contador y registrar estadísticas
            $configuracion->incrementarContador();
            $this->registrarEnvio($configuracion, $destinatario, $asunto, true);

            Log::info('Email enviado exitosamente', [
                'empresa_id' => $empresaId,
                'tipo' => $tipo,
                'destinatario' => $destinatario,
                'proveedor' => $configuracion->proveedor,
                'configuracion' => $configuracion->nombre
            ]);

            return [
                'success' => true,
                'message' => 'Email enviado exitosamente',
                'configuracion_usada' => $configuracion->nombre,
                'proveedor' => $configuracion->proveedor
            ];

        } catch (\Exception $e) {
            if (isset($configuracion)) {
                $this->registrarEnvio($configuracion, $destinatario ?? 'N/A', $asunto ?? 'N/A', false, $e->getMessage());
            }

            Log::error('Error enviando email dinámico', [
                'empresa_id' => $empresaId,
                'tipo' => $tipo,
                'error' => $e->getMessage(),
                'destinatario' => $destinatario ?? 'N/A'
            ]);

            return [
                'success' => false,
                'message' => 'Error enviando email: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener configuración según el tipo de email
     */
    private function obtenerConfiguracion($empresaId, $tipo)
    {
        switch ($tipo) {
            case 'backup':
                return EmailConfiguration::paraBackup($empresaId);
            
            case 'acuses':
            case 'acuse':
                return EmailConfiguration::paraAcuses($empresaId);
            
            case 'notificaciones':
            case 'notificacion':
                return EmailConfiguration::paraNotificaciones($empresaId);
            
            default:
                // Buscar configuración activa por defecto
                return EmailConfiguration::where('empresa_id', $empresaId)
                                        ->where('activo', true)
                                        ->first();
        }
    }

    /**
     * Configurar Laravel Mail dinámicamente
     */
    private function configurarMailer(EmailConfiguration $configuracion)
    {
        $mailConfig = $configuracion->toMailConfig();
        
        // Configurar el mailer dinámicamente
        Config::set('mail.default', 'dynamic');
        Config::set('mail.mailers.dynamic', $mailConfig);
        Config::set('mail.from', [
            'address' => $configuracion->from_address,
            'name' => $configuracion->from_name
        ]);

        Log::info('Configuración de mail aplicada', [
            'proveedor' => $configuracion->proveedor,
            'host' => $configuracion->host,
            'from' => $configuracion->from_address
        ]);
    }

    /**
     * Registrar estadísticas de envío
     */
    private function registrarEnvio(EmailConfiguration $configuracion, $destinatario, $asunto, $exitoso, $error = null)
    {
        $estadisticas = $configuracion->estadisticas ?? [];
        
        $hoy = now()->toDateString();
        if (!isset($estadisticas[$hoy])) {
            $estadisticas[$hoy] = [
                'enviados' => 0,
                'fallidos' => 0,
                'errores' => []
            ];
        }

        if ($exitoso) {
            $estadisticas[$hoy]['enviados']++;
        } else {
            $estadisticas[$hoy]['fallidos']++;
            $estadisticas[$hoy]['errores'][] = [
                'hora' => now()->toTimeString(),
                'destinatario' => $destinatario,
                'asunto' => $asunto,
                'error' => $error
            ];
        }

        // Mantener solo los últimos 30 días de estadísticas
        $fechasAMantener = collect(array_keys($estadisticas))
                          ->sort()
                          ->reverse()
                          ->take(30)
                          ->toArray();
        
        $estadisticas = array_intersect_key($estadisticas, array_flip($fechasAMantener));

        $configuracion->update(['estadisticas' => $estadisticas]);
    }

    /**
     * Obtener estadísticas de una configuración
     */
    public function obtenerEstadisticas($empresaId, $configuracionId = null)
    {
        $query = EmailConfiguration::where('empresa_id', $empresaId);
        
        if ($configuracionId) {
            $query->where('id', $configuracionId);
        }

        $configuraciones = $query->get();
        $estadisticas = [];

        foreach ($configuraciones as $config) {
            $stats = $config->estadisticas ?? [];
            $totalEnviados = 0;
            $totalFallidos = 0;

            foreach ($stats as $fecha => $datos) {
                $totalEnviados += $datos['enviados'] ?? 0;
                $totalFallidos += $datos['fallidos'] ?? 0;
            }

            $estadisticas[] = [
                'configuracion' => $config->nombre,
                'proveedor' => $config->proveedor,
                'activo' => $config->activo,
                'total_enviados' => $totalEnviados,
                'total_fallidos' => $totalFallidos,
                'emails_hoy' => $config->emails_enviados_hoy,
                'limite_diario' => $config->limite_diario,
                'ultimo_envio' => $config->ultimo_envio,
                'puede_enviar' => $config->puedeEnviarEmail()
            ];
        }

        return $estadisticas;
    }

    /**
     * Probar configuración de email
     */
    public function probarConfiguracion($configuracionId, $emailPrueba)
    {
        try {
            $configuracion = EmailConfiguration::findOrFail($configuracionId);
            
            if (!$configuracion->puedeEnviarEmail()) {
                throw new \Exception("Se ha alcanzado el límite diario de emails");
            }

            $this->configurarMailer($configuracion);

            Mail::raw('Este es un email de prueba del sistema de configuración dinámica de emails.', function ($message) use ($emailPrueba, $configuracion) {
                $message->to($emailPrueba)
                        ->subject('🧪 Prueba de Configuración - ' . $configuracion->nombre)
                        ->from($configuracion->from_address, $configuracion->from_name);
            });

            $configuracion->incrementarContador();
            $this->registrarEnvio($configuracion, $emailPrueba, 'Prueba de configuración', true);

            return [
                'success' => true,
                'message' => 'Email de prueba enviado exitosamente'
            ];

        } catch (\Exception $e) {
            if (isset($configuracion)) {
                $this->registrarEnvio($configuracion, $emailPrueba, 'Prueba de configuración', false, $e->getMessage());
            }

            return [
                'success' => false,
                'message' => 'Error enviando email de prueba: ' . $e->getMessage()
            ];
        }
    }
}
