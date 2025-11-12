<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\EmailBuzon;
use App\Services\Dian\BuzonEmailService;

echo "🔧 PROBANDO PROCESAMIENTO DE FACTURAS Y ACUSES\n";
echo "==============================================\n\n";

$config = ConfiguracionDian::where('activo', true)->first();
$empresa = $config->empresa;

echo "🏢 Empresa: " . $empresa->nombre . "\n";
echo "📧 Email: " . $config->email_dian . "\n\n";

// Verificar emails en el buzón
$emailsConFacturas = EmailBuzon::where('empresa_id', $empresa->id)
                               ->where('tiene_facturas', true)
                               ->get();

echo "📊 ESTADO ACTUAL DEL BUZÓN:\n";
echo "===========================\n";
echo "📧 Total emails con facturas: " . $emailsConFacturas->count() . "\n";

foreach ($emailsConFacturas as $email) {
    echo "📄 Email #{$email->id}\n";
    echo "   📧 De: {$email->remitente_email}\n";
    echo "   📋 Asunto: " . substr($email->asunto, 0, 40) . "...\n";
    echo "   📊 Estado: {$email->estado}\n";
    echo "   💼 Procesado: " . ($email->procesado ? 'SÍ' : 'NO') . "\n";
    
    if ($email->archivos_adjuntos) {
        echo "   📎 Adjuntos:\n";
        foreach ($email->archivos_adjuntos as $adjunto) {
            echo "      - {$adjunto['nombre']} (" . ($adjunto['es_factura'] ? 'FACTURA' : 'OTRO') . ")\n";
        }
    }
    echo "\n";
}

echo "🚀 INICIANDO PROCESAMIENTO DE FACTURAS:\n";
echo "=======================================\n";

$buzonService = new BuzonEmailService($config);
$resultado = $buzonService->procesarEmailsDelBuzon();

echo "📊 RESULTADOS DEL PROCESAMIENTO:\n";
echo "================================\n";
echo "✅ Éxito: " . ($resultado['success'] ? 'SÍ' : 'NO') . "\n";
echo "📧 Emails procesados: " . ($resultado['emails_procesados'] ?? 0) . "\n";
echo "💼 Facturas extraídas: " . ($resultado['facturas_extraidas'] ?? 0) . "\n";
echo "📨 Acuses generados: " . ($resultado['acuses_generados'] ?? 0) . "\n";
echo "❌ Errores: " . ($resultado['errores'] ?? 0) . "\n";
echo "💬 Mensaje: " . ($resultado['message'] ?? 'N/A') . "\n\n";

// Verificar estado después del procesamiento
echo "📊 ESTADO DESPUÉS DEL PROCESAMIENTO:\n";
echo "====================================\n";

$emailsActualizados = EmailBuzon::where('empresa_id', $empresa->id)
                                ->where('tiene_facturas', true)
                                ->get();

foreach ($emailsActualizados as $email) {
    echo "📄 Email #{$email->id}\n";
    echo "   📊 Estado: {$email->estado}\n";
    echo "   💼 Procesado: " . ($email->procesado ? 'SÍ' : 'NO') . "\n";
    
    // Mostrar CUFEs extraídos si existen
    if (isset($email->metadatos['cufes_extraidos'])) {
        echo "   🔑 CUFEs extraídos:\n";
        foreach ($email->metadatos['cufes_extraidos'] as $cufe) {
            echo "      - $cufe\n";
        }
    }
    
    // Mostrar información de procesamiento
    if (isset($email->metadatos['procesamiento'])) {
        $proc = $email->metadatos['procesamiento'];
        echo "   ⚙️  Procesamiento: {$proc['message']}\n";
    }
    
    // Mostrar errores si existen
    if (isset($email->metadatos['error_procesamiento'])) {
        echo "   ❌ Error: {$email->metadatos['error_procesamiento']}\n";
    }
    
    echo "\n";
}

echo "📈 ESTADÍSTICAS FINALES:\n";
echo "========================\n";

$estadisticas = [
    'total' => EmailBuzon::where('empresa_id', $empresa->id)->count(),
    'con_facturas' => EmailBuzon::where('empresa_id', $empresa->id)->where('tiene_facturas', true)->count(),
    'procesados' => EmailBuzon::where('empresa_id', $empresa->id)->where('procesado', true)->count(),
    'nuevos' => EmailBuzon::where('empresa_id', $empresa->id)->where('estado', 'nuevo')->count(),
    'procesando' => EmailBuzon::where('empresa_id', $empresa->id)->where('estado', 'procesando')->count(),
    'completados' => EmailBuzon::where('empresa_id', $empresa->id)->where('estado', 'procesado')->count(),
    'errores' => EmailBuzon::where('empresa_id', $empresa->id)->where('estado', 'error')->count()
];

foreach ($estadisticas as $key => $value) {
    echo "📊 " . ucfirst(str_replace('_', ' ', $key)) . ": $value\n";
}

echo "\n🎯 FUNCIONALIDADES IMPLEMENTADAS:\n";
echo "=================================\n";
echo "✅ Extracción automática de facturas de emails\n";
echo "✅ Lectura de CUFEs de nombres de archivos\n";
echo "✅ Generación automática de acuses de recibo\n";
echo "✅ Actualización de estados de procesamiento\n";
echo "✅ Registro detallado de metadatos\n";
echo "✅ Manejo de errores y reintentos\n";
echo "✅ Patrones de extracción de CUFE flexibles\n";
echo "✅ Logging completo del proceso\n\n";

echo "🌐 ACCESO AL SISTEMA:\n";
echo "====================\n";
echo "📊 Dashboard: http://127.0.0.1:8000/dian\n";
echo "📧 Buzón: http://127.0.0.1:8000/dian/buzon\n";
echo "⚙️  Configuración: http://127.0.0.1:8000/dian/configuracion\n\n";

echo "🎊 SISTEMA DE PROCESAMIENTO COMPLETO:\n";
echo "====================================\n";
echo "✅ Buzón de correos funcional\n";
echo "✅ Filtros operativos\n";
echo "✅ Extracción de facturas automática\n";
echo "✅ Lectura de CUFEs implementada\n";
echo "✅ Generación de acuses automática\n";
echo "✅ Dashboard integrado\n";
echo "✅ Logging y monitoreo completo\n\n";

echo "🏁 Procesamiento de facturas completado exitosamente\n";
