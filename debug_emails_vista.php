<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\EmailBuzon;
use App\Models\ProveedorElectronico;

echo "🔍 DIAGNÓSTICO DE EMAILS EN LA VISTA\n";
echo "===================================\n\n";

$config = ConfiguracionDian::where('activo', true)->first();
$empresa = $config->empresa;

echo "🏢 Empresa ID: " . $empresa->id . "\n";
echo "📧 Email configurado: " . $config->email_dian . "\n\n";

// Verificar emails en la base de datos
echo "1. EMAILS EN LA BASE DE DATOS:\n";
echo "==============================\n";

$emails_total = EmailBuzon::where('empresa_id', $empresa->id)->count();
echo "📊 Total emails en BD: $emails_total\n";

if ($emails_total > 0) {
    $emails = EmailBuzon::where('empresa_id', $empresa->id)
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    
    foreach ($emails as $email) {
        echo "📧 Email ID: " . $email->id . "\n";
        echo "   De: " . $email->remitente_email . " (" . $email->remitente_nombre . ")\n";
        echo "   Asunto: " . $email->asunto . "\n";
        echo "   Fecha: " . $email->fecha_email . "\n";
        echo "   Facturas: " . ($email->tiene_facturas ? 'SÍ' : 'NO') . "\n";
        echo "   Estado: " . $email->estado . "\n";
        echo "   Creado: " . $email->created_at . "\n\n";
    }
} else {
    echo "📭 No hay emails en la base de datos\n\n";
}

// Verificar proveedores
echo "2. PROVEEDORES CONFIGURADOS:\n";
echo "============================\n";

$proveedores = ProveedorElectronico::where('empresa_id', $empresa->id)
    ->where('activo', true)
    ->get();

echo "👥 Total proveedores: " . $proveedores->count() . "\n\n";

foreach ($proveedores as $proveedor) {
    echo "🏢 " . $proveedor->nombre_proveedor . "\n";
    echo "   📧 " . $proveedor->email_proveedor . "\n";
    echo "   🏷️  Dominios: " . implode(', ', $proveedor->dominios_email ?? []) . "\n";
    echo "   🔍 Palabras: " . implode(', ', array_slice($proveedor->palabras_clave ?? [], 0, 3)) . "...\n\n";
}

// Probar conexión IMAP directa y ver qué emails hay
echo "3. EMAILS REALES EN EL SERVIDOR:\n";
echo "================================\n";

try {
    $servidor = '{imap.gmail.com:993/imap/ssl}INBOX';
    $email = $config->email_dian;
    $password = $config->password_email;
    
    echo "🔗 Conectando a: $servidor\n";
    
    $conexion = @imap_open($servidor, $email, $password);
    
    if ($conexion) {
        echo "✅ Conexión exitosa\n";
        
        // Buscar emails recientes
        $fecha_desde = date('d-M-Y', strtotime('-7 days'));
        $busqueda = "SINCE \"$fecha_desde\"";
        echo "🔍 Buscando emails desde: $fecha_desde\n";
        
        $emails_ids = imap_search($conexion, $busqueda);
        
        if ($emails_ids) {
            echo "📧 Emails encontrados: " . count($emails_ids) . "\n\n";
            
            // Mostrar primeros 5 emails con detalles
            $limite = min(5, count($emails_ids));
            for ($i = 0; $i < $limite; $i++) {
                $email_id = $emails_ids[$i];
                $header = imap_headerinfo($conexion, $email_id);
                
                $from = isset($header->from[0]) ? $header->from[0] : null;
                $remitente_email = $from ? $from->mailbox . '@' . $from->host : 'unknown';
                $remitente_nombre = $from ? (isset($from->personal) ? $from->personal : $from->mailbox) : 'Desconocido';
                $asunto = isset($header->subject) ? $header->subject : 'Sin asunto';
                $fecha = isset($header->date) ? $header->date : 'Sin fecha';
                
                echo ($i + 1) . ". Email del servidor:\n";
                echo "   📧 De: $remitente_email\n";
                echo "   👤 Nombre: $remitente_nombre\n";
                echo "   📋 Asunto: $asunto\n";
                echo "   📅 Fecha: $fecha\n";
                
                // Verificar si coincide con algún proveedor
                $coincide_proveedor = false;
                foreach ($proveedores as $proveedor) {
                    if ($proveedor->coincideConEmail($remitente_email) || 
                        $proveedor->coincideConAsunto($asunto) || 
                        $proveedor->coincideConRemitente($remitente_nombre)) {
                        echo "   ✅ COINCIDE CON: " . $proveedor->nombre_proveedor . "\n";
                        $coincide_proveedor = true;
                        break;
                    }
                }
                
                if (!$coincide_proveedor) {
                    echo "   ❌ NO COINCIDE con ningún proveedor autorizado\n";
                }
                
                echo "\n";
            }
        } else {
            echo "📭 No se encontraron emails recientes\n";
        }
        
        imap_close($conexion);
    } else {
        echo "❌ Error de conexión IMAP\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n4. RECOMENDACIONES:\n";
echo "===================\n";

if ($emails_total == 0) {
    echo "💡 No hay emails en la base de datos porque:\n";
    echo "   1. Los emails no son de proveedores autorizados\n";
    echo "   2. Los emails no contienen facturas electrónicas\n";
    echo "   3. El filtrado está funcionando correctamente\n\n";
    
    echo "🔧 PARA SOLUCIONARLO:\n";
    echo "   1. Agregar más proveedores a la lista de autorizados\n";
    echo "   2. Enviar un email de prueba desde un proveedor autorizado\n";
    echo "   3. Temporalmente deshabilitar el filtro para ver todos los emails\n";
}

echo "\n🏁 Diagnóstico completado\n";
