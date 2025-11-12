<?php

namespace App\Services\Dian;

use App\Models\ConfiguracionDian;
use App\Models\FacturaDianProcesada;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class AcuseGeneratorService
{
    private $configuracion;

    public function __construct(ConfiguracionDian $configuracion)
    {
        $this->configuracion = $configuracion;
    }

    /**
     * Enviar acuses para facturas procesadas sin acuse
     */
    public function enviarAcusesPendientes(): array
    {
        $resultados = [
            'acuses_enviados' => 0,
            'errores' => [],
            'facturas_procesadas' => []
        ];

        try {
            // Obtener facturas procesadas sin acuse
            $facturasSinAcuse = FacturaDianProcesada::where('empresa_id', $this->configuracion->empresa_id)
                                                  ->sinAcuse()
                                                  ->where('estado', 'procesada')
                                                  ->whereNotNull('cufe')
                                                  ->get();

            foreach ($facturasSinAcuse as $factura) {
                try {
                    $resultadoEnvio = $this->enviarAcuseFactura($factura);
                    
                    if ($resultadoEnvio['success']) {
                        $resultados['acuses_enviados']++;
                        $resultados['facturas_procesadas'][] = $factura->cufe;
                        
                        // Actualizar configuración
                        $this->configuracion->incrementarAcusesEnviados();
                    } else {
                        $resultados['errores'][] = "Error enviando acuse para CUFE {$factura->cufe}: {$resultadoEnvio['error']}";
                    }
                    
                } catch (\Exception $e) {
                    $error = "Error procesando acuse para CUFE {$factura->cufe}: " . $e->getMessage();
                    $resultados['errores'][] = $error;
                    Log::error($error);
                }
            }

        } catch (\Exception $e) {
            $resultados['errores'][] = "Error general enviando acuses: " . $e->getMessage();
            Log::error("Error en envío de acuses: " . $e->getMessage());
        }

        return $resultados;
    }

    /**
     * Enviar acuse para una factura específica
     */
    public function enviarAcuseFactura(FacturaDianProcesada $factura): array
    {
        try {
            // Generar contenido del acuse
            $contenidoAcuse = $this->generarContenidoAcuse($factura);
            
            // Configurar datos del email
            $datosEmail = [
                'destinatario' => $factura->remitente_email,
                'asunto' => $this->generarAsuntoAcuse($factura),
                'contenido' => $contenidoAcuse,
                'remitente' => $this->configuracion->email_remitente ?: $this->configuracion->email_dian,
                'nombre_remitente' => $this->configuracion->nombre_remitente ?: $this->configuracion->empresa->nombre
            ];

            // Enviar email
            $idEmail = $this->enviarEmail($datosEmail);
            
            if ($idEmail) {
                // Marcar acuse como enviado
                $factura->marcarAcuseEnviado($idEmail, $contenidoAcuse);
                
                Log::info("Acuse enviado exitosamente para CUFE: {$factura->cufe}");
                
                return [
                    'success' => true,
                    'id_email' => $idEmail,
                    'destinatario' => $datosEmail['destinatario']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'No se pudo enviar el email'
                ];
            }

        } catch (\Exception $e) {
            Log::error("Error enviando acuse para CUFE {$factura->cufe}: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generar contenido del acuse de recibido
     */
    private function generarContenidoAcuse(FacturaDianProcesada $factura): string
    {
        $plantilla = $this->configuracion->plantilla_acuse;
        $empresa = $this->configuracion->empresa;

        // Variables para reemplazar en la plantilla
        $variables = [
            '{cufe}' => $factura->cufe,
            '{numero_factura}' => $factura->numero_factura ?: 'No especificado',
            '{nit_emisor}' => $factura->nit_emisor ?: 'No especificado',
            '{nombre_emisor}' => $factura->nombre_emisor ?: 'No especificado',
            '{valor_total}' => number_format($factura->valor_total ?: 0, 2, ',', '.'),
            '{fecha_factura}' => $factura->fecha_factura ? $factura->fecha_factura->format('d/m/Y') : 'No especificada',
            '{fecha_recepcion}' => $factura->fecha_email->format('d/m/Y H:i:s'),
            '{nombre_empresa}' => $empresa->nombre,
            '{nit_empresa}' => $empresa->nit,
            '{fecha_acuse}' => now()->format('d/m/Y H:i:s'),
            '{id_mensaje}' => $factura->mensaje_id
        ];

        // Reemplazar variables en la plantilla
        $contenido = str_replace(array_keys($variables), array_values($variables), $plantilla);

        // Agregar información adicional del sistema
        $contenido .= "\n\n" . $this->generarPieAcuse($factura);

        return $contenido;
    }

    /**
     * Generar pie del acuse con información adicional
     */
    private function generarPieAcuse(FacturaDianProcesada $factura): string
    {
        return "
---
INFORMACIÓN TÉCNICA DEL PROCESAMIENTO:
- ID de Procesamiento: {$factura->id}
- Fecha de Procesamiento: " . $factura->updated_at->format('d/m/Y H:i:s') . "
- Estado: " . ucfirst($factura->estado) . "
- Archivos Procesados: " . count($factura->archivos_adjuntos) . "

Este acuse de recibido ha sido generado automáticamente por nuestro sistema de procesamiento de facturas electrónicas.
Para cualquier consulta, por favor contacte a nuestro departamento de contabilidad.

Sistema de Gestión de Facturas Electrónicas v2.0
        ";
    }

    /**
     * Generar asunto del email de acuse
     */
    private function generarAsuntoAcuse(FacturaDianProcesada $factura): string
    {
        $empresa = $this->configuracion->empresa;
        
        return "ACUSE DE RECIBIDO - Factura {$factura->numero_factura} - {$empresa->nombre}";
    }

    /**
     * Enviar email usando Laravel Mail
     */
    private function enviarEmail(array $datosEmail): ?string
    {
        try {
            $idEmail = 'acuse_' . time() . '_' . uniqid();
            
            Mail::raw($datosEmail['contenido'], function (Message $message) use ($datosEmail, $idEmail) {
                $message->to($datosEmail['destinatario'])
                        ->subject($datosEmail['asunto'])
                        ->from($datosEmail['remitente'], $datosEmail['nombre_remitente'])
                        ->getHeaders()
                        ->addTextHeader('Message-ID', "<{$idEmail}@" . config('app.url') . ">");
            });

            return $idEmail;

        } catch (\Exception $e) {
            Log::error("Error enviando email: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generar acuse en formato HTML
     */
    public function generarAcuseHTML(FacturaDianProcesada $factura): string
    {
        $empresa = $this->configuracion->empresa;
        
        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Acuse de Recibido - Factura Electrónica</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; }
        .content { margin: 20px 0; }
        .footer { background-color: #e9ecef; padding: 15px; border-radius: 5px; font-size: 12px; }
        .highlight { background-color: #fff3cd; padding: 10px; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }
        th { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class='header'>
        <h2>ACUSE DE RECIBIDO - FACTURA ELECTRÓNICA</h2>
        <p><strong>{$empresa->nombre}</strong><br>
        NIT: {$empresa->nit}</p>
    </div>

    <div class='content'>
        <p>Estimado proveedor,</p>
        
        <p>Confirmamos la recepción de su factura electrónica con los siguientes datos:</p>
        
        <table>
            <tr><th>CUFE</th><td>{$factura->cufe}</td></tr>
            <tr><th>Número de Factura</th><td>{$factura->numero_factura}</td></tr>
            <tr><th>NIT Emisor</th><td>{$factura->nit_emisor}</td></tr>
            <tr><th>Nombre Emisor</th><td>{$factura->nombre_emisor}</td></tr>
            <tr><th>Valor Total</th><td>$" . number_format($factura->valor_total ?: 0, 2, ',', '.') . "</td></tr>
            <tr><th>Fecha de Factura</th><td>" . ($factura->fecha_factura ? $factura->fecha_factura->format('d/m/Y') : 'No especificada') . "</td></tr>
            <tr><th>Fecha de Recepción</th><td>{$factura->fecha_email->format('d/m/Y H:i:s')}</td></tr>
        </table>
        
        <div class='highlight'>
            <strong>Estado:</strong> La factura ha sido recibida y procesada correctamente en nuestro sistema.
        </div>
    </div>

    <div class='footer'>
        <p><strong>Información del Procesamiento:</strong></p>
        <ul>
            <li>ID de Procesamiento: {$factura->id}</li>
            <li>Fecha de Procesamiento: {$factura->updated_at->format('d/m/Y H:i:s')}</li>
            <li>Archivos Procesados: " . count($factura->archivos_adjuntos) . "</li>
        </ul>
        
        <p>Este es un mensaje automático generado por nuestro sistema de procesamiento de facturas electrónicas.</p>
    </div>
</body>
</html>
        ";
    }

    /**
     * Validar configuración de email
     */
    public function validarConfiguracionEmail(): array
    {
        $errores = [];

        if (empty($this->configuracion->email_remitente) && empty($this->configuracion->email_dian)) {
            $errores[] = 'No se ha configurado email remitente';
        }

        if (empty($this->configuracion->nombre_remitente)) {
            $errores[] = 'No se ha configurado nombre del remitente';
        }

        // Verificar configuración de Mail en Laravel
        try {
            $mailConfig = config('mail');
            if (empty($mailConfig['mailers']['smtp']['host'])) {
                $errores[] = 'Configuración de SMTP no establecida en Laravel';
            }
        } catch (\Exception $e) {
            $errores[] = 'Error en configuración de Mail: ' . $e->getMessage();
        }

        return $errores;
    }

    /**
     * Obtener estadísticas de acuses enviados
     */
    public function obtenerEstadisticasAcuses(): array
    {
        try {
            $facturas = FacturaDianProcesada::where('empresa_id', $this->configuracion->empresa_id);

            return [
                'total_facturas' => $facturas->count(),
                'acuses_enviados' => $facturas->where('acuse_enviado', true)->count(),
                'pendientes_acuse' => $facturas->sinAcuse()->count(),
                'ultimo_acuse' => $facturas->where('acuse_enviado', true)
                                          ->orderBy('fecha_acuse', 'desc')
                                          ->first()?->fecha_acuse,
                'acuses_hoy' => $facturas->where('acuse_enviado', true)
                                        ->whereDate('fecha_acuse', today())
                                        ->count()
            ];

        } catch (\Exception $e) {
            Log::error("Error obteniendo estadísticas de acuses: " . $e->getMessage());
            return [];
        }
    }
}
