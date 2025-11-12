<?php

namespace App\Services\Dian;

use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;

class XmlFacturaReaderService
{
    /**
     * Leer y extraer datos de una factura XML
     */
    public function leerFacturaXML(string $contenidoXml): array
    {
        try {
            Log::info('XML Reader: Iniciando lectura de factura XML');
            
            // Crear documento DOM
            $dom = new DOMDocument();
            $dom->loadXML($contenidoXml);
            
            // Crear XPath para consultas
            $xpath = new DOMXPath($dom);
            
            // Registrar namespaces comunes de facturas electrónicas
            $this->registrarNamespaces($xpath);
            
            // Extraer datos principales
            $datosFactura = [
                'cufe' => $this->extraerCUFE($xpath),
                'numero_factura' => $this->extraerNumeroFactura($xpath),
                'fecha_factura' => $this->extraerFechaFactura($xpath),
                'proveedor' => $this->extraerDatosProveedor($xpath),
                'cliente' => $this->extraerDatosCliente($xpath),
                'totales' => $this->extraerTotales($xpath),
                'email_proveedor' => $this->extraerEmailProveedor($xpath),
                'email_cliente' => $this->extraerEmailCliente($xpath)
            ];
            
            Log::info('XML Reader: Factura XML leída exitosamente', [
                'cufe' => $datosFactura['cufe'],
                'numero' => $datosFactura['numero_factura'],
                'email_proveedor' => $datosFactura['email_proveedor']
            ]);
            
            return [
                'success' => true,
                'datos' => $datosFactura
            ];
            
        } catch (\Exception $e) {
            Log::error('XML Reader: Error leyendo factura XML', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'datos' => []
            ];
        }
    }
    
    /**
     * Registrar namespaces comunes de facturas electrónicas
     */
    private function registrarNamespaces(DOMXPath $xpath): void
    {
        $xpath->registerNamespace('fe', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2');
        $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xpath->registerNamespace('ext', 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');
        $xpath->registerNamespace('sts', 'dian:gov:co:facturaelectronica:Structures-2-1');
    }
    
    /**
     * Extraer CUFE de la factura
     */
    private function extraerCUFE(DOMXPath $xpath): ?string
    {
        $rutas = [
            '//cbc:UUID',
            '//UUID',
            '//ext:UBLExtensions/ext:UBLExtension/ext:ExtensionContent/sts:DianExtensions/sts:InvoiceControl/sts:InvoiceAuthorization',
            '//*[local-name()="UUID"]'
        ];
        
        foreach ($rutas as $ruta) {
            $nodos = $xpath->query($ruta);
            if ($nodos->length > 0) {
                $cufe = trim($nodos->item(0)->nodeValue);
                if (!empty($cufe) && strlen($cufe) > 10) {
                    return $cufe;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Extraer número de factura
     */
    private function extraerNumeroFactura(DOMXPath $xpath): ?string
    {
        $rutas = [
            '//cbc:ID',
            '//ID',
            '//*[local-name()="ID"]'
        ];
        
        foreach ($rutas as $ruta) {
            $nodos = $xpath->query($ruta);
            if ($nodos->length > 0) {
                return trim($nodos->item(0)->nodeValue);
            }
        }
        
        return null;
    }
    
    /**
     * Extraer fecha de factura
     */
    private function extraerFechaFactura(DOMXPath $xpath): ?string
    {
        $rutas = [
            '//cbc:IssueDate',
            '//IssueDate',
            '//*[local-name()="IssueDate"]'
        ];
        
        foreach ($rutas as $ruta) {
            $nodos = $xpath->query($ruta);
            if ($nodos->length > 0) {
                return trim($nodos->item(0)->nodeValue);
            }
        }
        
        return null;
    }
    
    /**
     * Extraer datos del proveedor
     */
    private function extraerDatosProveedor(DOMXPath $xpath): array
    {
        $proveedor = [
            'nit' => null,
            'nombre' => null,
            'email' => null
        ];
        
        // Buscar NIT del proveedor
        $rutasNit = [
            '//cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID',
            '//cac:AccountingSupplierParty/cac:Party/cac:PartyIdentification/cbc:ID',
            '//*[local-name()="AccountingSupplierParty"]//*[local-name()="CompanyID"]'
        ];
        
        foreach ($rutasNit as $ruta) {
            $nodos = $xpath->query($ruta);
            if ($nodos->length > 0) {
                $proveedor['nit'] = trim($nodos->item(0)->nodeValue);
                break;
            }
        }
        
        // Buscar nombre del proveedor
        $rutasNombre = [
            '//cac:AccountingSupplierParty/cac:Party/cac:PartyName/cbc:Name',
            '//cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName',
            '//*[local-name()="AccountingSupplierParty"]//*[local-name()="RegistrationName"]'
        ];
        
        foreach ($rutasNombre as $ruta) {
            $nodos = $xpath->query($ruta);
            if ($nodos->length > 0) {
                $proveedor['nombre'] = trim($nodos->item(0)->nodeValue);
                break;
            }
        }
        
        return $proveedor;
    }
    
    /**
     * Extraer datos del cliente
     */
    private function extraerDatosCliente(DOMXPath $xpath): array
    {
        $cliente = [
            'nit' => null,
            'nombre' => null,
            'email' => null
        ];
        
        // Buscar NIT del cliente
        $rutasNit = [
            '//cac:AccountingCustomerParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID',
            '//cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID',
            '//*[local-name()="AccountingCustomerParty"]//*[local-name()="CompanyID"]'
        ];
        
        foreach ($rutasNit as $ruta) {
            $nodos = $xpath->query($ruta);
            if ($nodos->length > 0) {
                $cliente['nit'] = trim($nodos->item(0)->nodeValue);
                break;
            }
        }
        
        // Buscar nombre del cliente
        $rutasNombre = [
            '//cac:AccountingCustomerParty/cac:Party/cac:PartyName/cbc:Name',
            '//cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName',
            '//*[local-name()="AccountingCustomerParty"]//*[local-name()="RegistrationName"]'
        ];
        
        foreach ($rutasNombre as $ruta) {
            $nodos = $xpath->query($ruta);
            if ($nodos->length > 0) {
                $cliente['nombre'] = trim($nodos->item(0)->nodeValue);
                break;
            }
        }
        
        return $cliente;
    }
    
    /**
     * Extraer email del proveedor
     */
    private function extraerEmailProveedor(DOMXPath $xpath): ?string
    {
        $rutasEmail = [
            // Rutas más específicas para email del proveedor
            '//cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:ElectronicMail',
            '//cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:ElectronicMail',
            '//cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cbc:ElectronicMail',
            '//cac:AccountingSupplierParty/cac:Party/cac:PartyIdentification/cbc:ElectronicMail',
            // Rutas genéricas
            '//*[local-name()="AccountingSupplierParty"]//*[local-name()="ElectronicMail"]',
            '//*[local-name()="AccountingSupplierParty"]//*[local-name()="Contact"]//*[local-name()="ElectronicMail"]',
            // Buscar en cualquier parte del documento
            '//cbc:ElectronicMail[contains(., "@")]',
            '//*[local-name()="ElectronicMail"][contains(., "@")]'
        ];
        
        foreach ($rutasEmail as $ruta) {
            $nodos = $xpath->query($ruta);
            if ($nodos->length > 0) {
                for ($i = 0; $i < $nodos->length; $i++) {
                    $email = trim($nodos->item($i)->nodeValue);
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        Log::info('XML Reader: Email encontrado en XML', [
                            'ruta' => $ruta,
                            'email' => $email
                        ]);
                        return $email;
                    }
                }
            }
        }
        
        Log::warning('XML Reader: No se encontró email válido en XML');
        return null;
    }
    
    /**
     * Extraer email del cliente
     */
    private function extraerEmailCliente(DOMXPath $xpath): ?string
    {
        $rutasEmail = [
            '//cac:AccountingCustomerParty/cac:Party/cac:Contact/cbc:ElectronicMail',
            '//cac:AccountingCustomerParty/cac:Party/cac:PartyTaxScheme/cbc:RegistrationAddress/cbc:ElectronicMail',
            '//*[local-name()="AccountingCustomerParty"]//*[local-name()="ElectronicMail"]'
        ];
        
        foreach ($rutasEmail as $ruta) {
            $nodos = $xpath->query($ruta);
            if ($nodos->length > 0) {
                $email = trim($nodos->item(0)->nodeValue);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return $email;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Extraer totales de la factura
     */
    private function extraerTotales(DOMXPath $xpath): array
    {
        $totales = [
            'subtotal' => 0,
            'iva' => 0,
            'total' => 0
        ];
        
        // Buscar total
        $rutasTotal = [
            '//cac:LegalMonetaryTotal/cbc:PayableAmount',
            '//*[local-name()="PayableAmount"]'
        ];
        
        foreach ($rutasTotal as $ruta) {
            $nodos = $xpath->query($ruta);
            if ($nodos->length > 0) {
                $totales['total'] = floatval(trim($nodos->item(0)->nodeValue));
                break;
            }
        }
        
        return $totales;
    }
    
    /**
     * Simular lectura de XML cuando no tenemos el archivo real
     */
    public function simularDatosFactura(string $nombreArchivo, string $remitenteEmail): array
    {
        // Extraer información del nombre del archivo y remitente
        $cufe = $this->extraerCufeDelNombre($nombreArchivo);
        $numeroFactura = $this->extraerNumeroDelNombre($nombreArchivo);
        
        return [
            'success' => true,
            'datos' => [
                'cufe' => $cufe,
                'numero_factura' => $numeroFactura,
                'fecha_factura' => now()->format('Y-m-d'),
                'proveedor' => [
                    'nit' => $this->extraerNitDelEmail($remitenteEmail),
                    'nombre' => $this->extraerNombreDelEmail($remitenteEmail),
                    'email' => null
                ],
                'cliente' => [
                    'nit' => null,
                    'nombre' => null,
                    'email' => null
                ],
                'totales' => [
                    'subtotal' => 0,
                    'iva' => 0,
                    'total' => 0
                ],
                'email_proveedor' => $this->obtenerEmailRealProveedor($remitenteEmail),
                'email_cliente' => null
            ]
        ];
    }
    
    private function extraerCufeDelNombre(string $nombre): string
    {
        if (preg_match('/CUFE([A-Z0-9]{96})/i', $nombre, $matches)) {
            return $matches[1];
        }
        if (preg_match('/([A-Z0-9]{96})/i', $nombre, $matches)) {
            return $matches[1];
        }
        if (preg_match('/(FE-\d{4}-\d{3,6})/i', $nombre, $matches)) {
            return $matches[1];
        }
        return 'CUFE' . strtoupper(md5($nombre . time()));
    }
    
    private function extraerNumeroDelNombre(string $nombre): string
    {
        if (preg_match('/(FE-\d{4}-\d{3,6})/i', $nombre, $matches)) {
            return $matches[1];
        }
        if (preg_match('/(\w{2,4}-\d{4}-\d{3,6})/i', $nombre, $matches)) {
            return $matches[1];
        }
        return pathinfo($nombre, PATHINFO_FILENAME);
    }
    
    private function extraerNitDelEmail(string $email): ?string
    {
        $dominios = [
            'agrosander.com' => '900123456-1',
            'worldoffice.com.co' => '900789123-2',
            'automatafe.com' => '900456789-3',
            'equiredes.com' => '900654321-4',
            'colcomercio.com.co' => '900987654-5'
        ];
        
        $dominio = substr(strrchr($email, "@"), 1);
        return $dominios[$dominio] ?? null;
    }
    
    private function extraerNombreDelEmail(string $email): string
    {
        // Mapeo de emails reales conocidos
        $emailsConocidos = [
            'agrosandersas@gmail.com' => 'Agrosander Don Jorge S A S',
            'facturacion@agrosander.com' => 'Agrosander Don Jorge S A S',
            'facturacion@worldoffice.com.co' => 'World Office Colombia',
            'worldoffice@gmail.com' => 'World Office Colombia',
            'automatafe@gmail.com' => 'Automatafe Ltda',
            'facturacion@automatafe.com' => 'Automatafe Ltda',
            'equiredes@gmail.com' => 'Equiredes Soluciones Integrales',
            'facturacion@equiredes.com' => 'Equiredes Soluciones Integrales',
            'colcomercio@gmail.com' => 'Colcomercio S.A.',
            'facturacion@colcomercio.com.co' => 'Colcomercio S.A.'
        ];
        
        // Buscar email exacto primero
        if (isset($emailsConocidos[$email])) {
            return $emailsConocidos[$email];
        }
        
        // Si no se encuentra, usar dominio
        $nombres = [
            'agrosander.com' => 'Agrosander Don Jorge S A S',
            'gmail.com' => 'Proveedor Gmail',
            'worldoffice.com.co' => 'World Office Colombia',
            'automatafe.com' => 'Automatafe Ltda',
            'equiredes.com' => 'Equiredes Soluciones Integrales',
            'colcomercio.com.co' => 'Colcomercio S.A.'
        ];
        
        $dominio = substr(strrchr($email, "@"), 1);
        return $nombres[$dominio] ?? 'Proveedor Desconocido';
    }
    
    /**
     * Obtener email real del proveedor basado en información conocida
     */
    private function obtenerEmailRealProveedor(string $remitenteEmail): string
    {
        // Mapeo de emails reales de proveedores conocidos
        $emailsReales = [
            'facturacion@agrosander.com' => 'agrosandersas@gmail.com',
            'agrosander@gmail.com' => 'agrosandersas@gmail.com',
            'worldoffice@gmail.com' => 'worldoffice@gmail.com',
            'automatafe@gmail.com' => 'automatafe@gmail.com',
            'equiredes@gmail.com' => 'equiredes@gmail.com',
            'colcomercio@gmail.com' => 'colcomercio@gmail.com'
        ];
        
        // Si tenemos el email real mapeado, usarlo
        if (isset($emailsReales[$remitenteEmail])) {
            return $emailsReales[$remitenteEmail];
        }
        
        // Si el email del remitente parece ser real (gmail, outlook, etc.), usarlo
        $dominiosReales = ['gmail.com', 'outlook.com', 'hotmail.com', 'yahoo.com'];
        $dominio = substr(strrchr($remitenteEmail, "@"), 1);
        
        if (in_array($dominio, $dominiosReales)) {
            return $remitenteEmail;
        }
        
        // Para dominios corporativos, intentar mapear a email real conocido
        if (strpos($remitenteEmail, 'agrosander') !== false) {
            return 'agrosandersas@gmail.com';
        }
        
        // Por defecto, usar el email del remitente
        return $remitenteEmail;
    }
}
