<?php

namespace App\Services\Dian;

use App\Models\EmailBuzon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AttachmentProcessorService
{
    /**
     * Procesar archivos adjuntos de un email IMAP
     */
    public function procesarArchivosAdjuntos($conexion, $email_id, EmailBuzon $emailBuzon): array
    {
        $archivosGuardados = [];
        
        try {
            $estructura = imap_fetchstructure($conexion, $email_id);
            
            if (isset($estructura->parts) && count($estructura->parts)) {
                foreach ($estructura->parts as $partNum => $part) {
                    $attachment = $this->procesarParte($conexion, $email_id, $part, $partNum + 1);
                    
                    if ($attachment) {
                        $archivoGuardado = $this->guardarArchivo($emailBuzon, $attachment);
                        if ($archivoGuardado) {
                            $archivosGuardados[] = $archivoGuardado;
                        }
                    }
                }
            }
            
            Log::info('Archivos adjuntos procesados', [
                'email_id' => $emailBuzon->id,
                'archivos_guardados' => count($archivosGuardados)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error procesando archivos adjuntos', [
                'email_id' => $emailBuzon->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return $archivosGuardados;
    }
    
    /**
     * Procesar una parte individual del email
     */
    private function procesarParte($conexion, $email_id, $part, $partNum): ?array
    {
        $attachment = null;
        
        // Verificar si es un archivo adjunto
        if (isset($part->disposition) && strtolower($part->disposition) === 'attachment') {
            $filename = null;
            
            // Obtener nombre del archivo
            if (isset($part->dparameters)) {
                foreach ($part->dparameters as $param) {
                    if (strtolower($param->attribute) === 'filename') {
                        $filename = $param->value;
                        break;
                    }
                }
            }
            
            if (!$filename && isset($part->parameters)) {
                foreach ($part->parameters as $param) {
                    if (strtolower($param->attribute) === 'name') {
                        $filename = $param->value;
                        break;
                    }
                }
            }
            
            if ($filename) {
                // Obtener contenido del archivo
                $data = imap_fetchbody($conexion, $email_id, $partNum);
                
                // Decodificar según la codificación
                if (isset($part->encoding)) {
                    switch ($part->encoding) {
                        case 3: // BASE64
                            $data = base64_decode($data);
                            break;
                        case 4: // QUOTED-PRINTABLE
                            $data = quoted_printable_decode($data);
                            break;
                    }
                }
                
                $attachment = [
                    'filename' => $filename,
                    'data' => $data,
                    'size' => strlen($data),
                    'type' => $part->subtype ?? 'unknown'
                ];
            }
        }
        
        return $attachment;
    }
    
    /**
     * Guardar archivo en storage
     */
    private function guardarArchivo(EmailBuzon $emailBuzon, array $attachment): ?array
    {
        try {
            $emailDir = "attachments/email_{$emailBuzon->id}";
            $filename = $this->sanitizeFilename($attachment['filename']);
            $filepath = "{$emailDir}/{$filename}";
            
            // Crear directorio si no existe
            if (!Storage::exists($emailDir)) {
                Storage::makeDirectory($emailDir);
            }
            
            // Guardar archivo
            Storage::put($filepath, $attachment['data']);
            
            $archivoInfo = [
                'nombre' => $filename,
                'ruta' => $filepath,
                'tamano' => $attachment['size'],
                'tipo' => $attachment['type'],
                'es_factura' => $this->esArchivoFactura($filename),
                'fecha_guardado' => now()->toISOString()
            ];
            
            Log::info('Archivo guardado', [
                'email_id' => $emailBuzon->id,
                'archivo' => $filename,
                'tamano' => $attachment['size'],
                'es_factura' => $archivoInfo['es_factura']
            ]);
            
            return $archivoInfo;
            
        } catch (\Exception $e) {
            Log::error('Error guardando archivo', [
                'email_id' => $emailBuzon->id,
                'filename' => $attachment['filename'],
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Extraer emails del contenido XML
     */
    public function extraerEmailsDeXML(string $rutaArchivo): array
    {
        try {
            if (!Storage::exists($rutaArchivo)) {
                return [];
            }
            
            $contenidoXml = Storage::get($rutaArchivo);
            $emailsEncontrados = [];
            
            // Patrones específicos para facturas electrónicas colombianas
            $patrones = [
                // Patrón para ElectronicMail en DIAN
                '/<cbc:ElectronicMail[^>]*>([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})<\/cbc:ElectronicMail>/i',
                // Patrón para Contact/ElectronicMail
                '/<cac:Contact>.*?<cbc:ElectronicMail[^>]*>([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})<\/cbc:ElectronicMail>.*?<\/cac:Contact>/s',
                // Patrón para AccountingSupplierParty
                '/<cac:AccountingSupplierParty>.*?<cbc:ElectronicMail[^>]*>([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})<\/cbc:ElectronicMail>.*?<\/cac:AccountingSupplierParty>/s',
                // Patrón general para emails
                '/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i'
            ];
            
            foreach ($patrones as $patron) {
                if (preg_match_all($patron, $contenidoXml, $matches)) {
                    foreach ($matches[1] as $email) {
                        $email = trim($email);
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $emailsEncontrados[] = $email;
                        }
                    }
                }
            }
            
            return array_unique($emailsEncontrados);
            
        } catch (\Exception $e) {
            Log::error('Error extrayendo emails de XML', [
                'archivo' => $rutaArchivo,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Extraer datos del proveedor del XML
     */
    public function extraerDatosProveedorXML(string $rutaArchivo): array
    {
        try {
            if (!Storage::exists($rutaArchivo)) {
                return [];
            }
            
            $contenidoXml = Storage::get($rutaArchivo);
            $datos = [
                'nombre' => null,
                'nit' => null,
                'email' => null,
                'cufe' => null
            ];
            
            // Extraer nombre del proveedor
            $patronesNombre = [
                '/<cac:AccountingSupplierParty>.*?<cac:Party>.*?<cac:PartyName>.*?<cbc:Name[^>]*>([^<]+)<\/cbc:Name>.*?<\/cac:PartyName>.*?<\/cac:Party>.*?<\/cac:AccountingSupplierParty>/s',
                '/<cbc:RegistrationName[^>]*>([^<]+)<\/cbc:RegistrationName>/i',
                '/<cac:AccountingSupplierParty>.*?<cbc:Name[^>]*>([^<]+)<\/cbc:Name>.*?<\/cac:AccountingSupplierParty>/s'
            ];
            
            foreach ($patronesNombre as $patron) {
                if (preg_match($patron, $contenidoXml, $matches)) {
                    $datos['nombre'] = trim($matches[1]);
                    break;
                }
            }
            
            // Extraer NIT
            $patronesNit = [
                '/<cac:AccountingSupplierParty>.*?<cbc:CompanyID[^>]*>([^<]+)<\/cbc:CompanyID>.*?<\/cac:AccountingSupplierParty>/s',
                '/<cbc:ID[^>]*schemeID="31"[^>]*>([^<]+)<\/cbc:ID>/i'
            ];
            
            foreach ($patronesNit as $patron) {
                if (preg_match($patron, $contenidoXml, $matches)) {
                    $datos['nit'] = trim($matches[1]);
                    break;
                }
            }
            
            // Extraer CUFE
            $patronesCufe = [
                '/<cbc:UUID[^>]*>([a-zA-Z0-9-]{36,96})<\/cbc:UUID>/i',
                '/<ext:UBLExtension>.*?<sts:DianExtensions>.*?<sts:InvoiceControl>.*?<sts:InvoiceAuthorization>([a-zA-Z0-9-]{36,96})<\/sts:InvoiceAuthorization>.*?<\/sts:InvoiceControl>.*?<\/sts:DianExtensions>.*?<\/ext:UBLExtension>/s'
            ];
            
            foreach ($patronesCufe as $patron) {
                if (preg_match($patron, $contenidoXml, $matches)) {
                    $datos['cufe'] = trim($matches[1]);
                    break;
                }
            }
            
            // Extraer email
            $emails = $this->extraerEmailsDeXML($rutaArchivo);
            if (!empty($emails)) {
                $datos['email'] = $emails[0]; // Tomar el primer email encontrado
            }
            
            return $datos;
            
        } catch (\Exception $e) {
            Log::error('Error extrayendo datos del proveedor de XML', [
                'archivo' => $rutaArchivo,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Verificar si es archivo de factura electrónica
     */
    private function esArchivoFactura(string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $filename_lower = strtolower($filename);
        
        // Extensiones de facturas electrónicas
        if (in_array($extension, ['xml', 'zip', 'rar', '7z'])) {
            return true;
        }
        
        // Palabras clave específicas de facturas electrónicas DIAN
        $facturaKeywords = [
            'factura', 'invoice', 'fe', 'cufe', 'cude', 'xml',
            'facturae', 'nota_credito', 'nota_debito', 'nc', 'nd',
            'documento_electronico', 'dian', 'electronica'
        ];
        
        foreach ($facturaKeywords as $keyword) {
            if (stripos($filename_lower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * Limpiar nombre de archivo
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remover caracteres peligrosos
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Limitar longitud
        if (strlen($filename) > 100) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 100 - strlen($extension) - 1) . '.' . $extension;
        }
        
        return $filename;
    }
}
