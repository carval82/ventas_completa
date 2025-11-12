<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class DianService
{
    private $baseUrl;
    private $username;
    private $password;
    private $testMode;

    public function __construct()
    {
        $this->testMode = config('dian.test_mode', true);
        $this->baseUrl = $this->testMode 
            ? config('dian.test_url', 'https://vpfe-hab.dian.gov.co/WcfDianCustomerServices.svc')
            : config('dian.production_url', 'https://vpfe.dian.gov.co/WcfDianCustomerServices.svc');
        
        $this->username = config('dian.username');
        $this->password = config('dian.password');
    }

    /**
     * Enviar factura electrónica directamente a DIAN
     */
    public function enviarFacturaElectronica($facturaData)
    {
        try {
            Log::info('Iniciando envío directo a DIAN', ['factura_id' => $facturaData['id']]);

            // Generar XML UBL 2.1
            $xmlContent = $this->generarXmlUbl($facturaData);
            
            // Firmar digitalmente el XML
            $xmlFirmado = $this->firmarXml($xmlContent);
            
            // Enviar a DIAN
            $response = $this->enviarADian($xmlFirmado, $facturaData);
            
            return [
                'success' => true,
                'cufe' => $response['cufe'] ?? null,
                'numero_factura' => $response['numero'] ?? null,
                'mensaje' => 'Factura enviada exitosamente a DIAN'
            ];

        } catch (\Exception $e) {
            Log::error('Error enviando factura a DIAN', [
                'error' => $e->getMessage(),
                'factura_id' => $facturaData['id'] ?? null
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generar XML en formato UBL 2.1
     */
    private function generarXmlUbl($facturaData)
    {
        // Implementación básica del XML UBL 2.1
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2">' . "\n";
        
        // Información básica
        $xml .= '<ID>' . $facturaData['numero'] . '</ID>' . "\n";
        $xml .= '<IssueDate>' . date('Y-m-d') . '</IssueDate>' . "\n";
        $xml .= '<InvoiceTypeCode>01</InvoiceTypeCode>' . "\n";
        
        // Información del emisor
        $xml .= '<AccountingSupplierParty>' . "\n";
        $xml .= '<Party>' . "\n";
        $xml .= '<PartyIdentification>' . "\n";
        $xml .= '<ID>' . config('dian.nit_empresa') . '</ID>' . "\n";
        $xml .= '</PartyIdentification>' . "\n";
        $xml .= '</Party>' . "\n";
        $xml .= '</AccountingSupplierParty>' . "\n";
        
        $xml .= '</Invoice>';
        
        return $xml;
    }

    /**
     * Firmar digitalmente el XML
     */
    private function firmarXml($xmlContent)
    {
        // Por ahora retornamos el XML sin firmar
        // En producción se debe implementar firma digital
        Log::warning('XML no firmado digitalmente - implementar en producción');
        return $xmlContent;
    }

    /**
     * Enviar XML a DIAN
     */
    private function enviarADian($xmlContent, $facturaData)
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'Content-Type' => 'application/soap+xml',
                'SOAPAction' => 'http://wcf.dian.colombia'
            ])
            ->post($this->baseUrl, [
                'xml' => base64_encode($xmlContent),
                'nit' => config('dian.nit_empresa')
            ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Error en respuesta de DIAN: ' . $response->body());
    }
}
