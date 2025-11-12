<?php

namespace App\Services;

use Illuminate\Support\Str;

class QRLocalService
{
    /**
     * Generar un CUFE simulado para facturas locales
     * 
     * @param \App\Models\Venta $venta
     * @param \App\Models\Empresa $empresa
     * @return string
     */
    public function generarCUFELocal($venta, $empresa)
    {
        // Formato similar al CUFE real pero claramente identificable como local
        $componentes = [
            'LOCAL',                                    // Identificador de factura local
            $empresa->nit ?? 'SIN_NIT',                // NIT de la empresa
            $venta->numero_factura,                    // Número de factura
            $venta->fecha_venta->format('Ymd'),        // Fecha YYYYMMDD
            number_format($venta->total, 2, '', ''),   // Total sin decimales
            Str::random(40)                            // Hash aleatorio
        ];
        
        $cufeBase = implode('-', $componentes);
        
        // Generar hash SHA256 para hacerlo más realista
        $cufeHash = hash('sha256', $cufeBase);
        
        return strtoupper($cufeHash);
    }
    
    /**
     * Generar código QR desde un CUFE o texto
     * 
     * @param string $data
     * @return string Base64 encoded PNG
     */
    public function generarQRCode($data)
    {
        try {
            // Usar API externa de qrserver.com (más confiable sin imagick)
            $url = 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query([
                'size' => '300x300',
                'data' => $data,
                'format' => 'png',
                'margin' => 10,
                'ecc' => 'H'  // Error correction High
            ]);
            
            \Log::info('Generando QR via API', [
                'url' => $url,
                'data_length' => strlen($data)
            ]);
            
            $qrImage = @file_get_contents($url);
            
            if ($qrImage !== false && strlen($qrImage) > 0) {
                \Log::info('QR generado exitosamente', [
                    'size' => strlen($qrImage) . ' bytes'
                ]);
                return base64_encode($qrImage);
            }
            
            \Log::warning('No se pudo generar QR', [
                'data' => substr($data, 0, 50),
                'response' => $qrImage
            ]);
            return null;
            
        } catch (\Exception $e) {
            \Log::error('Error generando QR local', [
                'error' => $e->getMessage(),
                'data' => substr($data, 0, 50),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }
    
    /**
     * Generar CUFE y QR para una venta
     * 
     * @param \App\Models\Venta $venta
     * @param \App\Models\Empresa $empresa
     * @return array ['cufe' => string, 'qr' => string]
     */
    public function generarCUFEyQR($venta, $empresa)
    {
        // Generar CUFE
        $cufe = $this->generarCUFELocal($venta, $empresa);
        
        // Generar QR con información completa
        $infoQR = $this->construirInfoParaQR($venta, $empresa, $cufe);
        $qr = $this->generarQRCode($infoQR);
        
        \Log::info('CUFE y QR local generados', [
            'venta_id' => $venta->id,
            'cufe' => substr($cufe, 0, 20) . '...',
            'qr_generado' => $qr ? 'Sí' : 'No'
        ]);
        
        return [
            'cufe' => $cufe,
            'qr' => $qr
        ];
    }
    
    /**
     * Construir información para el código QR
     * 
     * @param \App\Models\Venta $venta
     * @param \App\Models\Empresa $empresa
     * @param string $cufe
     * @return string
     */
    private function construirInfoParaQR($venta, $empresa, $cufe)
    {
        // Formato similar a factura electrónica pero identificado como local
        $info = [
            'Factura Local: ' . $venta->numero_factura,
            'Empresa: ' . $empresa->nombre_comercial,
            'NIT: ' . $empresa->nit,
            'Fecha: ' . $venta->fecha_venta->format('d/m/Y H:i'),
            'Cliente: ' . ($venta->cliente ? $venta->cliente->nombres . ' ' . $venta->cliente->apellidos : 'Cliente General'),
            'Total: $' . number_format($venta->total, 2, ',', '.'),
            'CUFE-LOCAL: ' . $cufe
        ];
        
        return implode("\n", $info);
    }
    
    /**
     * Verificar si endroid/qr-code está instalado
     * 
     * @return bool
     */
    public function isQRLibraryAvailable()
    {
        return class_exists('Endroid\QrCode\QrCode');
    }
}
