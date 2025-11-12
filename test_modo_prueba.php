<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\EmailBuzon;

echo "🧪 MODO PRUEBA - GUARDANDO TODOS LOS EMAILS\n";
echo "==========================================\n\n";

$config = ConfiguracionDian::where('activo', true)->first();
$empresa = $config->empresa;

echo "🏢 Empresa: " . $empresa->nombre . "\n";
echo "📧 Email: " . $config->email_dian . "\n\n";

// Limpiar emails anteriores
echo "🧹 Limpiando emails anteriores...\n";
EmailBuzon::where('empresa_id', $empresa->id)->delete();

echo "📥 Descargando TODOS los emails (sin filtros)...\n\n";

try {
    $servidor = '{imap.gmail.com:993/imap/ssl}INBOX';
    $email = $config->email_dian;
    $password = $config->password_email;
    
    $conexion = @imap_open($servidor, $email, $password);
    
    if (!$conexion) {
        echo "❌ Error de conexión IMAP\n";
        exit;
    }
    
    echo "✅ Conexión IMAP exitosa\n";
    
    // Buscar emails de los últimos 7 días
    $fecha_desde = date('d-M-Y', strtotime('-7 days'));
    $busqueda = "SINCE \"$fecha_desde\"";
    echo "🔍 Buscando emails desde: $fecha_desde\n";
    
    $emails_ids = imap_search($conexion, $busqueda);
    
    if (!$emails_ids) {
        echo "📭 No se encontraron emails\n";
        imap_close($conexion);
        exit;
    }
    
    echo "📧 Emails encontrados: " . count($emails_ids) . "\n\n";
    
    $guardados = 0;
    
    // Procesar todos los emails sin filtros
    foreach ($emails_ids as $email_id) {
        try {
            $header = imap_headerinfo($conexion, $email_id);
            
            $from = isset($header->from[0]) ? $header->from[0] : null;
            $remitente_email = $from ? $from->mailbox . '@' . $from->host : 'unknown@unknown.com';
            $remitente_nombre = $from ? (isset($from->personal) ? $from->personal : $from->mailbox) : 'Desconocido';
            $asunto = isset($header->subject) ? $header->subject : 'Sin asunto';
            $fecha = isset($header->date) ? date('Y-m-d H:i:s', strtotime($header->date)) : now();
            
            // Crear email en la base de datos SIN FILTROS
            $emailBuzon = EmailBuzon::create([
                'empresa_id' => $empresa->id,
                'mensaje_id' => 'PRUEBA_' . $email_id . '_' . time(),
                'cuenta_email' => $config->email_dian,
                'remitente_email' => $remitente_email,
                'remitente_nombre' => $remitente_nombre,
                'asunto' => $asunto,
                'contenido_texto' => substr(imap_body($conexion, $email_id), 0, 1000), // Solo primeros 1000 chars
                'fecha_email' => $fecha,
                'fecha_descarga' => now(),
                'archivos_adjuntos' => [],
                'tiene_facturas' => false, // Por defecto false en modo prueba
                'procesado' => false,
                'estado' => 'nuevo',
                'metadatos' => [
                    'tipo' => 'modo_prueba',
                    'email_id' => $email_id,
                    'servidor' => 'imap_prueba'
                ]
            ]);
            
            echo "✅ Guardado: " . $remitente_email . " - " . substr($asunto, 0, 50) . "...\n";
            $guardados++;
            
        } catch (\Exception $e) {
            echo "❌ Error procesando email $email_id: " . $e->getMessage() . "\n";
        }
    }
    
    imap_close($conexion);
    
    echo "\n📊 RESUMEN:\n";
    echo "===========\n";
    echo "Emails encontrados: " . count($emails_ids) . "\n";
    echo "Emails guardados: $guardados\n\n";
    
    echo "🎯 AHORA PUEDES VER LOS EMAILS EN:\n";
    echo "http://127.0.0.1:8000/dian/buzon\n\n";
    
    echo "💡 EMAILS GUARDADOS:\n";
    echo "====================\n";
    
    $emails_guardados = EmailBuzon::where('empresa_id', $empresa->id)
        ->orderBy('fecha_email', 'desc')
        ->get();
    
    foreach ($emails_guardados as $email) {
        echo "📧 " . $email->remitente_email . "\n";
        echo "   👤 " . $email->remitente_nombre . "\n";
        echo "   📋 " . substr($email->asunto, 0, 60) . "...\n";
        echo "   📅 " . $email->fecha_email . "\n\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "🏁 Modo prueba completado\n";
echo "\n💡 NOTA: Estos emails son solo para prueba.\n";
echo "Para volver al modo normal, ejecuta el procesamiento regular.\n";
