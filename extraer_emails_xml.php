<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EmailBuzon;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

echo "🔍 EXTRACTOR DE EMAILS REALES DE XML\n";
echo "===================================\n\n";

// Autenticar usuario
$user = User::first();
Auth::login($user);
echo "👤 Usuario: {$user->name} (Empresa ID: {$user->empresa_id})\n\n";

function extraerEmailsDeXML($contenidoXml) {
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
}

function extraerDatosProveedorXML($contenidoXml) {
    $datos = [
        'nombre' => null,
        'nit' => null,
        'email' => null
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
    
    // Extraer email
    $emails = extraerEmailsDeXML($contenidoXml);
    if (!empty($emails)) {
        $datos['email'] = $emails[0]; // Tomar el primer email encontrado
    }
    
    return $datos;
}

echo "📊 PROCESANDO EMAILS CON FACTURAS...\n";
echo "====================================\n";

$emailsConFacturas = EmailBuzon::where('empresa_id', $user->empresa_id)
                               ->where('tiene_facturas', true)
                               ->orderBy('fecha_email', 'desc')
                               ->limit(3)
                               ->get();

$emailsActualizados = 0;
$emailsExtraidos = 0;

foreach ($emailsConFacturas as $email) {
    echo "\n📧 EMAIL #{$email->id}\n";
    echo "====================\n";
    echo "De: {$email->remitente_nombre} <{$email->remitente_email}>\n";
    echo "Asunto: {$email->asunto}\n";
    
    // Buscar archivos XML
    $emailDir = "attachments/email_{$email->id}";
    $emailRealExtraido = null;
    $datosProveedor = null;
    
    if (Storage::exists($emailDir)) {
        $archivos = Storage::files($emailDir);
        
        foreach ($archivos as $archivo) {
            $extension = pathinfo($archivo, PATHINFO_EXTENSION);
            
            if (strtolower($extension) === 'xml') {
                echo "📄 Analizando: " . basename($archivo) . "\n";
                
                try {
                    $contenidoXml = Storage::get($archivo);
                    
                    // Extraer datos del proveedor
                    $datosProveedor = extraerDatosProveedorXML($contenidoXml);
                    
                    if ($datosProveedor['email']) {
                        $emailRealExtraido = $datosProveedor['email'];
                        $emailsExtraidos++;
                        
                        echo "✅ EMAIL EXTRAÍDO: {$emailRealExtraido}\n";
                        echo "🏢 Proveedor: {$datosProveedor['nombre']}\n";
                        echo "🆔 NIT: {$datosProveedor['nit']}\n";
                        
                        // Actualizar metadatos del email
                        $metadatos = is_string($email->metadatos) ? 
                                   json_decode($email->metadatos, true) : 
                                   ($email->metadatos ?? []);
                        
                        $metadatos['email_real_proveedor'] = $emailRealExtraido;
                        $metadatos['datos_proveedor_xml'] = $datosProveedor;
                        $metadatos['email_extraido_automaticamente'] = true;
                        $metadatos['fecha_extraccion'] = now()->toISOString();
                        
                        $email->update(['metadatos' => json_encode($metadatos)]);
                        $emailsActualizados++;
                        
                        break; // Usar el primer XML que tenga email
                    } else {
                        echo "⚠️ No se encontró email en este XML\n";
                    }
                    
                } catch (\Exception $e) {
                    echo "❌ Error procesando XML: " . $e->getMessage() . "\n";
                }
            }
        }
    } else {
        echo "❌ No hay archivos adjuntos\n";
    }
    
    if (!$emailRealExtraido) {
        echo "⚠️ Usando email del remitente: {$email->remitente_email}\n";
    }
}

echo "\n📊 RESUMEN DE EXTRACCIÓN:\n";
echo "=========================\n";
echo "📧 Emails procesados: {$emailsConFacturas->count()}\n";
echo "✅ Emails extraídos de XML: {$emailsExtraidos}\n";
echo "🔄 Emails actualizados: {$emailsActualizados}\n\n";

if ($emailsExtraidos > 0) {
    echo "🎉 ÉXITO: Se extrajeron emails reales de los XML\n";
    echo "📧 Ahora el sistema puede enviar acuses a los emails correctos\n\n";
    
    echo "🧪 PROBAR ACUSES CON EMAILS REALES:\n";
    echo "===================================\n";
    echo "php test_acuses_emails_reales.php\n\n";
} else {
    echo "⚠️ No se pudieron extraer emails de los XML\n";
    echo "💡 Posibles causas:\n";
    echo "   - Los XML no contienen emails\n";
    echo "   - Los archivos no se guardaron correctamente\n";
    echo "   - Los patrones de búsqueda necesitan ajuste\n\n";
}

echo "🏁 Extracción completada\n";
