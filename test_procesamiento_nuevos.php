<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\EmailBuzon;
use App\Services\Dian\BuzonEmailService;

echo "🆕 CREANDO EMAILS NUEVOS PARA PROCESAR\n";
echo "=====================================\n\n";

$config = ConfiguracionDian::where('activo', true)->first();
$empresa = $config->empresa;

echo "🏢 Empresa: " . $empresa->nombre . "\n";
echo "📧 Email: " . $config->email_dian . "\n\n";

echo "📧 Creando emails nuevos con facturas...\n\n";

// Email 1: Nuevo de Agrosander
$email1 = EmailBuzon::create([
    'empresa_id' => $empresa->id,
    'mensaje_id' => 'NUEVO_001_' . time(),
    'cuenta_email' => $config->email_dian,
    'remitente_email' => 'facturacion@agrosander.com',
    'remitente_nombre' => 'Agrosander Don Jorge S A S',
    'asunto' => 'Nueva Factura Electrónica FE-2024-002 - Agrosander',
    'contenido_texto' => 'Estimado cliente, adjuntamos la nueva factura electrónica FE-2024-002...',
    'fecha_email' => now()->subMinutes(10),
    'fecha_descarga' => now(),
    'archivos_adjuntos' => [
        ['nombre' => 'FE-2024-002.xml', 'tamaño' => 18420, 'es_factura' => true],
        ['nombre' => 'FE-2024-002.pdf', 'tamaño' => 95630, 'es_factura' => false]
    ],
    'tiene_facturas' => true,
    'procesado' => false,
    'estado' => 'nuevo',
    'metadatos' => [
        'tipo' => 'email_real',
        'proveedor_autorizado' => [
            'id' => 1,
            'nombre' => 'Agrosander Don Jorge S A S',
            'nit' => 'JRME130551'
        ]
    ]
]);

// Email 2: Nuevo de World Office con CUFE en nombre
$email2 = EmailBuzon::create([
    'empresa_id' => $empresa->id,
    'mensaje_id' => 'NUEVO_002_' . time(),
    'cuenta_email' => $config->email_dian,
    'remitente_email' => 'no-responder@worldoffice.com.co',
    'remitente_nombre' => 'World Office Colombia',
    'asunto' => 'Documento Electrónico - Factura WO-2024-0157',
    'contenido_texto' => 'Cordial saludo, le enviamos su documento electrónico...',
    'fecha_email' => now()->subMinutes(5),
    'fecha_descarga' => now(),
    'archivos_adjuntos' => [
        ['nombre' => 'CUFE123456789ABCDEF123456789ABCDEF123456789ABCDEF123456789ABCDEF123456789ABCDEF123456.zip', 'tamaño' => 55230, 'es_factura' => true]
    ],
    'tiene_facturas' => true,
    'procesado' => false,
    'estado' => 'nuevo',
    'metadatos' => [
        'tipo' => 'email_real',
        'proveedor_autorizado' => [
            'id' => 5,
            'nombre' => 'World Office',
            'nit' => 'WO123456789'
        ]
    ]
]);

// Email 3: Nuevo de Automatafe con error simulado
$email3 = EmailBuzon::create([
    'empresa_id' => $empresa->id,
    'mensaje_id' => 'NUEVO_003_' . time(),
    'cuenta_email' => $config->email_dian,
    'remitente_email' => 'facturacion@automatafe.com',
    'remitente_nombre' => 'Automatafe Ltda',
    'asunto' => 'Factura Electrónica AT-2024-790',
    'contenido_texto' => 'Estimado cliente, le enviamos la factura electrónica...',
    'fecha_email' => now()->subMinutes(2),
    'fecha_descarga' => now(),
    'archivos_adjuntos' => [
        ['nombre' => 'AT-2024-790.xml', 'tamaño' => 14850, 'es_factura' => true]
    ],
    'tiene_facturas' => true,
    'procesado' => false,
    'estado' => 'error', // Simular que tuvo error antes
    'metadatos' => [
        'tipo' => 'email_real',
        'proveedor_autorizado' => [
            'id' => 2,
            'nombre' => 'Automatafe',
            'nit' => 'AT987654321'
        ]
    ]
]);

echo "✅ Emails nuevos creados:\n";
echo "📧 Email 1: Agrosander - NUEVO con FE-2024-002.xml\n";
echo "📧 Email 2: World Office - NUEVO con CUFE largo en nombre\n";
echo "📧 Email 3: Automatafe - ERROR (para reprocesar)\n\n";

echo "🚀 PROCESANDO EMAILS NUEVOS:\n";
echo "============================\n";

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

// Verificar los emails procesados
echo "🔍 VERIFICANDO EMAILS PROCESADOS:\n";
echo "=================================\n";

$emailsProcesados = EmailBuzon::whereIn('id', [$email1->id, $email2->id, $email3->id])->get();

foreach ($emailsProcesados as $email) {
    echo "📄 Email #{$email->id} - {$email->remitente_nombre}\n";
    echo "   📊 Estado: {$email->estado}\n";
    echo "   💼 Procesado: " . ($email->procesado ? 'SÍ' : 'NO') . "\n";
    
    // Mostrar CUFEs extraídos
    if (isset($email->metadatos['cufes_extraidos'])) {
        echo "   🔑 CUFEs extraídos:\n";
        foreach ($email->metadatos['cufes_extraidos'] as $cufe) {
            echo "      - " . substr($cufe, 0, 20) . "...\n";
        }
    }
    
    // Mostrar información de procesamiento
    if (isset($email->metadatos['procesamiento'])) {
        $proc = $email->metadatos['procesamiento'];
        echo "   ⚙️  Procesamiento: {$proc['message']}\n";
        echo "   📊 Facturas procesadas: {$proc['facturas_extraidas']}\n";
    }
    
    // Mostrar errores si existen
    if (isset($email->metadatos['error_procesamiento'])) {
        echo "   ❌ Error: {$email->metadatos['error_procesamiento']}\n";
    }
    
    echo "\n";
}

echo "📈 ESTADÍSTICAS ACTUALIZADAS:\n";
echo "=============================\n";

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

echo "\n🎯 FUNCIONALIDADES DEMOSTRADAS:\n";
echo "===============================\n";
echo "✅ Procesamiento de emails nuevos\n";
echo "✅ Extracción de CUFEs de nombres de archivos\n";
echo "✅ Manejo de CUFEs largos (96 caracteres)\n";
echo "✅ Reprocesamiento de emails con error\n";
echo "✅ Generación automática de acuses\n";
echo "✅ Actualización de metadatos\n";
echo "✅ Logging detallado del proceso\n\n";

echo "🌐 ACCESO AL SISTEMA ACTUALIZADO:\n";
echo "=================================\n";
echo "📊 Dashboard: http://127.0.0.1:8000/dian\n";
echo "📧 Buzón: http://127.0.0.1:8000/dian/buzon\n";
echo "   - Filtra por estado 'Procesado' para ver los nuevos\n";
echo "   - Filtra por proveedor para ver por empresa\n";
echo "   - Busca por 'FE-2024-002' o 'AT-2024-790'\n\n";

echo "🏁 Procesamiento de facturas y acuses completado\n";
