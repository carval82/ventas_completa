<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\EmailBuzon;
use App\Services\Dian\BuzonEmailService;

echo "🔧 PROBANDO SERVICIO CORREGIDO\n";
echo "==============================\n\n";

$config = ConfiguracionDian::where('activo', true)->first();
$empresa = $config->empresa;

echo "🏢 Empresa: " . $empresa->nombre . "\n";
echo "📧 Email: " . $config->email_dian . "\n\n";

// Limpiar emails anteriores
echo "🧹 Limpiando emails anteriores...\n";
EmailBuzon::where('empresa_id', $empresa->id)->delete();

// Crear servicio
echo "🔄 Creando servicio BuzonEmailService...\n";
$buzonService = new BuzonEmailService($config);

echo "📥 Iniciando sincronización con logging detallado...\n\n";

// Sincronizar emails
$resultado = $buzonService->sincronizarEmails();

echo "📊 RESULTADOS DE SINCRONIZACIÓN:\n";
echo "================================\n";
echo "Success: " . ($resultado['success'] ? '✅ SÍ' : '❌ NO') . "\n";
echo "Mensaje: " . $resultado['message'] . "\n";
echo "Emails descargados: " . $resultado['emails_descargados'] . "\n";
echo "Emails guardados: " . $resultado['emails_guardados'] . "\n";
echo "Emails con facturas: " . $resultado['emails_con_facturas'] . "\n\n";

if ($resultado['success'] && $resultado['emails_descargados'] > 0) {
    // Mostrar emails descargados
    $emails = EmailBuzon::where('empresa_id', $empresa->id)
        ->orderBy('fecha_email', 'desc')
        ->get();
    
    echo "📧 EMAILS PROCESADOS:\n";
    echo "====================\n";
    
    foreach ($emails as $email) {
        echo "📄 EMAIL #" . $email->id . "\n";
        echo "   De: " . $email->remitente_email . " (" . $email->remitente_nombre . ")\n";
        echo "   Asunto: " . $email->asunto . "\n";
        echo "   Fecha: " . $email->fecha_email . "\n";
        echo "   Tiene facturas: " . ($email->tiene_facturas ? '✅ SÍ' : '❌ NO') . "\n";
        echo "   Estado: " . $email->estado . "\n";
        
        if ($email->metadatos && isset($email->metadatos['proveedor_autorizado'])) {
            $proveedor = $email->metadatos['proveedor_autorizado'];
            echo "   🏢 Proveedor: " . $proveedor['nombre'] . "\n";
        }
        
        if ($email->archivos_adjuntos && count($email->archivos_adjuntos) > 0) {
            echo "   📎 Adjuntos: " . count($email->archivos_adjuntos) . "\n";
            foreach ($email->archivos_adjuntos as $adjunto) {
                $es_factura = isset($adjunto['es_factura']) && $adjunto['es_factura'] ? '✅' : '❌';
                echo "      - " . $adjunto['nombre'] . " ($es_factura)\n";
            }
        }
        
        echo "\n";
    }
    
    // Procesar emails
    echo "⚙️ PROCESANDO EMAILS Y GENERANDO ACUSES...\n";
    echo "==========================================\n";
    
    $resultadoProcesamiento = $buzonService->procesarEmailsDelBuzon();
    
    echo "📊 RESULTADOS DE PROCESAMIENTO:\n";
    echo "Success: " . ($resultadoProcesamiento['success'] ? '✅ SÍ' : '❌ NO') . "\n";
    echo "Emails procesados: " . $resultadoProcesamiento['emails_procesados'] . "\n";
    
    if (isset($resultadoProcesamiento['errores']) && count($resultadoProcesamiento['errores']) > 0) {
        echo "❌ Errores: " . count($resultadoProcesamiento['errores']) . "\n";
        foreach ($resultadoProcesamiento['errores'] as $error) {
            echo "   - " . $error . "\n";
        }
    }
    
} else {
    echo "💡 POSIBLES RAZONES:\n";
    echo "===================\n";
    echo "1. No hay emails de proveedores autorizados en los últimos 30 días\n";
    echo "2. Los emails no contienen facturas electrónicas\n";
    echo "3. Error de conexión IMAP (revisar logs)\n";
    echo "4. Filtrado funcionando correctamente (solo emails relevantes)\n";
}

echo "\n🏁 Prueba completada\n";
echo "\n💡 REVISA LOS LOGS PARA MÁS DETALLES:\n";
echo "tail -f storage/logs/laravel.log | grep 'Buzón Email'\n";
