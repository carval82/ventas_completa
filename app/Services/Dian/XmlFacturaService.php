<?php

namespace App\Services\Dian;

use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;

class XmlFacturaService
{
    private $namespacesComunes = [
        'cac' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
        'cbc' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
        'ext' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2',
        'sts' => 'dian:gov:co:facturaelectronica:Structures-2-1',
        'ds' => 'http://www.w3.org/2000/09/xmldsig#'
    ];

    /**
     * Procesar archivos de facturas XML
     */
    public function procesarFacturas(array $archivos): ?array
    {
        foreach ($archivos as $archivo) {
            $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
            
            if ($extension === 'xml') {
                $datosFactura = $this->procesarXmlFactura($archivo);
                
                if ($datosFactura) {
                    return $datosFactura;
                }
            }
        }

        return null;
    }

    /**
     * Procesar un archivo XML (método público para subida manual)
     */
    public function procesarXML(string $rutaXml): ?array
    {
        return $this->procesarXmlFactura($rutaXml);
    }

    /**
     * Procesar un archivo XML de factura específico
     */
    public function procesarXmlFactura(string $rutaXml): ?array
    {
        try {
            if (!file_exists($rutaXml)) {
                Log::error("Archivo XML no encontrado: {$rutaXml}");
                return null;
            }

            $contenidoXml = file_get_contents($rutaXml);
            
            if (empty($contenidoXml)) {
                Log::error("Archivo XML vacío: {$rutaXml}");
                return null;
            }

            // Crear documento DOM
            $dom = new DOMDocument();
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            
            // Cargar XML suprimiendo errores de formato
            libxml_use_internal_errors(true);
            $cargado = $dom->loadXML($contenidoXml);
            
            if (!$cargado) {
                $errores = libxml_get_errors();
                Log::error("Error cargando XML: " . json_encode($errores));
                return null;
            }

            // Crear XPath
            $xpath = new DOMXPath($dom);
            
            // Registrar namespaces
            foreach ($this->namespacesComunes as $prefix => $uri) {
                $xpath->registerNamespace($prefix, $uri);
            }

            // Extraer datos de la factura
            $datosFactura = $this->extraerDatosFactura($xpath, $rutaXml);
            
            if ($datosFactura) {
                Log::info("Factura XML procesada exitosamente: CUFE {$datosFactura['cufe']}");
                return $datosFactura;
            }

        } catch (\Exception $e) {
            Log::error("Error procesando XML {$rutaXml}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Extraer datos principales de la factura
     */
    private function extraerDatosFactura(DOMXPath $xpath, string $rutaXml): ?array
    {
        try {
            $datos = [
                'ruta_xml' => str_replace(storage_path('app/'), '', $rutaXml),
                'cufe' => null,
                'numero_factura' => null,
                'nit_emisor' => null,
                'nombre_emisor' => null,
                'valor_total' => null,
                'fecha_factura' => null,
                'detalles_procesamiento' => []
            ];

            // Extraer CUFE (Código Único de Facturación Electrónica)
            $datos['cufe'] = $this->extraerCUFE($xpath);
            
            // Extraer número de factura
            $datos['numero_factura'] = $this->extraerNumeroFactura($xpath);
            
            // Extraer datos del emisor
            $emisor = $this->extraerDatosEmisor($xpath);
            $datos['nit_emisor'] = $emisor['nit'];
            $datos['nombre_emisor'] = $emisor['nombre'];
            
            // Extraer valor total
            $datos['valor_total'] = $this->extraerValorTotal($xpath);
            
            // Extraer fecha de factura
            $datos['fecha_factura'] = $this->extraerFechaFactura($xpath);
            
            // Validar que se extrajeron los datos mínimos
            if (empty($datos['cufe'])) {
                Log::warning("No se pudo extraer CUFE del XML: {$rutaXml}");
                return null;
            }

            $datos['detalles_procesamiento'] = [
                'archivo_procesado' => basename($rutaXml),
                'fecha_procesamiento' => now()->toISOString(),
                'campos_extraidos' => array_keys(array_filter($datos, function($value) {
                    return !is_null($value) && $value !== '';
                }))
            ];

            return $datos;

        } catch (\Exception $e) {
            Log::error("Error extrayendo datos de factura: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extraer CUFE del XML
     */
    private function extraerCUFE(DOMXPath $xpath): ?string
    {
        // Posibles ubicaciones del CUFE
        $consultasCUFE = [
            "//cbc:UUID",
            "//ext:ExtensionContent//sts:DianExtensions//sts:InvoiceControl//sts:InvoiceAuthorization",
            "//ds:DigestValue",
            "//*[local-name()='UUID']",
            "//*[contains(local-name(), 'CUFE')]",
            "//*[contains(text(), 'CUFE')]/../text()"
        ];

        foreach ($consultasCUFE as $consulta) {
            try {
                $nodos = $xpath->query($consulta);
                
                if ($nodos && $nodos->length > 0) {
                    $valor = trim($nodos->item(0)->nodeValue);
                    
                    // Validar que parece un CUFE (generalmente 96 caracteres alfanuméricos)
                    if (strlen($valor) >= 40 && preg_match('/^[a-f0-9\-]+$/i', $valor)) {
                        return $valor;
                    }
                }
            } catch (\Exception $e) {
                // Continuar con la siguiente consulta
                continue;
            }
        }

        return null;
    }

    /**
     * Extraer número de factura
     */
    private function extraerNumeroFactura(DOMXPath $xpath): ?string
    {
        $consultasNumero = [
            "//cbc:ID",
            "//*[local-name()='ID']",
            "//cbc:InvoiceNumber",
            "//*[local-name()='InvoiceNumber']"
        ];

        foreach ($consultasNumero as $consulta) {
            try {
                $nodos = $xpath->query($consulta);
                
                if ($nodos && $nodos->length > 0) {
                    $valor = trim($nodos->item(0)->nodeValue);
                    
                    if (!empty($valor)) {
                        return $valor;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Extraer datos del emisor
     */
    private function extraerDatosEmisor(DOMXPath $xpath): array
    {
        $emisor = ['nit' => null, 'nombre' => null];

        try {
            // Buscar NIT del emisor
            $consultasNIT = [
                "//cac:AccountingSupplierParty//cbc:CompanyID",
                "//cac:Party//cbc:CompanyID",
                "//*[local-name()='CompanyID']"
            ];

            foreach ($consultasNIT as $consulta) {
                $nodos = $xpath->query($consulta);
                if ($nodos && $nodos->length > 0) {
                    $emisor['nit'] = trim($nodos->item(0)->nodeValue);
                    break;
                }
            }

            // Buscar nombre del emisor
            $consultasNombre = [
                "//cac:AccountingSupplierParty//cbc:RegistrationName",
                "//cac:Party//cbc:RegistrationName",
                "//cac:AccountingSupplierParty//cac:Party//cac:PartyName//cbc:Name",
                "//*[local-name()='RegistrationName']"
            ];

            foreach ($consultasNombre as $consulta) {
                $nodos = $xpath->query($consulta);
                if ($nodos && $nodos->length > 0) {
                    $emisor['nombre'] = trim($nodos->item(0)->nodeValue);
                    break;
                }
            }

        } catch (\Exception $e) {
            Log::error("Error extrayendo datos del emisor: " . $e->getMessage());
        }

        return $emisor;
    }

    /**
     * Extraer valor total de la factura
     */
    private function extraerValorTotal(DOMXPath $xpath): ?float
    {
        $consultasTotal = [
            "//cbc:PayableAmount",
            "//cbc:TaxInclusiveAmount",
            "//cbc:LineExtensionAmount",
            "//*[local-name()='PayableAmount']",
            "//*[local-name()='TaxInclusiveAmount']"
        ];

        foreach ($consultasTotal as $consulta) {
            try {
                $nodos = $xpath->query($consulta);
                
                if ($nodos && $nodos->length > 0) {
                    $valor = trim($nodos->item(0)->nodeValue);
                    $valorNumerico = floatval(str_replace(',', '', $valor));
                    
                    if ($valorNumerico > 0) {
                        return $valorNumerico;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Extraer fecha de la factura
     */
    private function extraerFechaFactura(DOMXPath $xpath): ?string
    {
        $consultasFecha = [
            "//cbc:IssueDate",
            "//*[local-name()='IssueDate']",
            "//cbc:InvoiceDate",
            "//*[local-name()='InvoiceDate']"
        ];

        foreach ($consultasFecha as $consulta) {
            try {
                $nodos = $xpath->query($consulta);
                
                if ($nodos && $nodos->length > 0) {
                    $fecha = trim($nodos->item(0)->nodeValue);
                    
                    // Validar formato de fecha
                    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $fecha)) {
                        return $fecha;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Validar estructura del XML de factura electrónica
     */
    public function validarEstructuraXML(string $rutaXml): bool
    {
        try {
            $contenido = file_get_contents($rutaXml);
            
            // Verificar que contiene elementos típicos de factura electrónica
            $elementosRequeridos = [
                'Invoice',
                'UUID',
                'AccountingSupplierParty',
                'PayableAmount'
            ];

            foreach ($elementosRequeridos as $elemento) {
                if (strpos($contenido, $elemento) === false) {
                    return false;
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::error("Error validando estructura XML: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener resumen del contenido XML
     */
    public function obtenerResumenXML(string $rutaXml): array
    {
        try {
            $dom = new DOMDocument();
            $dom->load($rutaXml);
            
            return [
                'elemento_raiz' => $dom->documentElement->nodeName,
                'namespaces' => $this->extraerNamespaces($dom),
                'tamaño_archivo' => filesize($rutaXml),
                'encoding' => $dom->encoding,
                'version' => $dom->version
            ];

        } catch (\Exception $e) {
            Log::error("Error obteniendo resumen XML: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Extraer namespaces del documento XML
     */
    private function extraerNamespaces(DOMDocument $dom): array
    {
        $namespaces = [];
        $xpath = new DOMXPath($dom);
        
        foreach ($xpath->query('namespace::*', $dom->documentElement) as $node) {
            $namespaces[$node->localName] = $node->nodeValue;
        }

        return $namespaces;
    }
}
