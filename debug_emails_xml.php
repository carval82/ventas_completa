<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EmailBuzon;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

echo "🔍 ANÁLISIS DE EMAILS EN ARCHIVOS XML\n";
echo "====================================\n\n";

// Autenticar usuario
$user = User::first();
Auth::login($user);
echo "👤 Usuario: {$user->name} (Empresa ID: {$user->empresa_id})\n\n";

echo "📊 BUSCANDO EMAILS CON FACTURAS...\n";
echo "==================================\n";

// Buscar emails con facturas
$emailsConFacturas = EmailBuzon::where('empresa_id', $user->empresa_id)
                               ->where('tiene_facturas', true)
                               ->orderBy('fecha_email', 'desc')
                               ->limit(5)
                               ->get();

echo "📧 Emails encontrados: {$emailsConFacturas->count()}\n\n";

foreach ($emailsConFacturas as $email) {
    echo "📧 EMAIL #{$email->id}\n";
    echo "====================\n";
    echo "De: {$email->remitente_nombre} <{$email->remitente_email}>\n";
    echo "Asunto: {$email->asunto}\n";
    echo "Fecha: {$email->fecha_email->format('d/m/Y H:i:s')}\n";
    
    // Obtener archivos adjuntos
    $archivos = is_string($email->archivos_adjuntos) ? 
                json_decode($email->archivos_adjuntos, true) : 
                ($email->archivos_adjuntos ?? []);
    
    echo "📎 Archivos adjuntos: " . count($archivos) . "\n";
    
    if (!empty($archivos)) {
        foreach ($archivos as $archivo) {
            echo "   - {$archivo}\n";
        }
    }
    
    // Buscar archivos XML en el directorio de attachments
    $emailDir = "attachments/email_{$email->id}";
    
    if (Storage::exists($emailDir)) {
        echo "\n📁 DIRECTORIO: storage/app/{$emailDir}\n";
        
        $archivosEnDisco = Storage::files($emailDir);
        echo "📂 Archivos en disco: " . count($archivosEnDisco) . "\n";
        
        foreach ($archivosEnDisco as $archivo) {
            $nombreArchivo = basename($archivo);
            $extension = pathinfo($nombreArchivo, PATHINFO_EXTENSION);
            
            echo "   📄 {$nombreArchivo} ({$extension})\n";
            
            // Analizar archivos XML
            if (strtolower($extension) === 'xml') {
                echo "      🔍 ANALIZANDO XML...\n";
                
                try {
                    $contenidoXml = Storage::get($archivo);
                    
                    // Buscar emails en el XML
                    $emailsEncontrados = [];
                    
                    // Patrones para buscar emails
                    $patrones = [
                        '/<cbc:ElectronicMail[^>]*>([^<]+@[^<]+)<\/cbc:ElectronicMail>/i',
                        '/<cbc:ID[^>]*>([^<]+@[^<]+)<\/cbc:ID>/i',
                        '/<Email[^>]*>([^<]+@[^<]+)<\/Email>/i',
                        '/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i'
                    ];
                    
                    foreach ($patrones as $patron) {
                        if (preg_match_all($patron, $contenidoXml, $matches)) {
                            foreach ($matches[1] as $emailEncontrado) {
                                $emailEncontrado = trim($emailEncontrado);
                                if (filter_var($emailEncontrado, FILTER_VALIDATE_EMAIL)) {
                                    $emailsEncontrados[] = $emailEncontrado;
                                }
                            }
                        }
                    }
                    
                    // Eliminar duplicados
                    $emailsEncontrados = array_unique($emailsEncontrados);
                    
                    if (!empty($emailsEncontrados)) {
                        echo "      ✅ EMAILS ENCONTRADOS EN XML:\n";
                        foreach ($emailsEncontrados as $emailXml) {
                            echo "         📧 {$emailXml}\n";
                        }
                    } else {
                        echo "      ❌ No se encontraron emails en el XML\n";
                        
                        // Mostrar una muestra del XML para debug
                        $muestra = substr($contenidoXml, 0, 1000);
                        echo "      📄 MUESTRA DEL XML:\n";
                        echo "      " . str_replace("\n", "\n      ", $muestra) . "...\n";
                    }
                    
                    // Buscar información del proveedor
                    echo "      🏢 BUSCANDO DATOS DEL PROVEEDOR...\n";
                    
                    $patronesProveedor = [
                        '/<cac:AccountingSupplierParty>.*?<cbc:Name[^>]*>([^<]+)<\/cbc:Name>.*?<\/cac:AccountingSupplierParty>/s',
                        '/<cac:Party>.*?<cac:PartyName>.*?<cbc:Name[^>]*>([^<]+)<\/cbc:Name>.*?<\/cac:PartyName>.*?<\/cac:Party>/s',
                        '/<cbc:RegistrationName[^>]*>([^<]+)<\/cbc:RegistrationName>/i'
                    ];
                    
                    foreach ($patronesProveedor as $patron) {
                        if (preg_match($patron, $contenidoXml, $matches)) {
                            echo "         🏢 Nombre: " . trim($matches[1]) . "\n";
                            break;
                        }
                    }
                    
                } catch (\Exception $e) {
                    echo "      ❌ Error leyendo XML: " . $e->getMessage() . "\n";
                }
            }
        }
    } else {
        echo "\n❌ DIRECTORIO NO EXISTE: storage/app/{$emailDir}\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n\n";
}

echo "💡 RECOMENDACIONES:\n";
echo "===================\n";
echo "1. Extraer emails reales de los archivos XML\n";
echo "2. Actualizar el sistema para usar estos emails\n";
echo "3. Validar que los emails extraídos sean correctos\n";
echo "4. Enviar acuses a los emails correctos\n\n";

echo "🔧 PRÓXIMO PASO:\n";
echo "================\n";
echo "Crear función para extraer emails automáticamente de los XML\n";
echo "y actualizar el sistema de acuses para usarlos.\n\n";

echo "🏁 Análisis completado\n";
