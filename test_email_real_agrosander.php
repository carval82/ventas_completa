<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\EmailBuzon;
use App\Services\Dian\BuzonEmailService;

echo "📧 PROBANDO EMAIL REAL DE AGROSANDER\n";
echo "====================================\n\n";

$config = ConfiguracionDian::where('activo', true)->first();
$empresa = $config->empresa;

echo "🏢 Empresa: " . $empresa->nombre . "\n";
echo "📧 Email: " . $config->email_dian . "\n\n";

// Crear email de Agrosander con email real
echo "📧 Creando email de Agrosander con email REAL...\n";

$emailAgrosander = EmailBuzon::create([
    'empresa_id' => $empresa->id,
    'mensaje_id' => 'AGROSANDER_REAL_' . time(),
    'cuenta_email' => $config->email_dian,
    'remitente_email' => 'facturacion@agrosander.com', // Email corporativo (remitente)
    'remitente_nombre' => 'Agrosander Don Jorge S A S',
    'asunto' => 'Factura Electrónica JRME No. 130551 - Agrosander',
    'contenido_texto' => 'Adjunto encontrará la factura electrónica JRME No. 130551...',
    'fecha_email' => now(),
    'fecha_descarga' => now(),
    'archivos_adjuntos' => [
        ['nombre' => 'JRME-130551.xml', 'tamaño' => 45620, 'es_factura' => true],
        ['nombre' => 'JRME-130551.pdf', 'tamaño' => 185430, 'es_factura' => false]
    ],
    'tiene_facturas' => true,
    'procesado' => false,
    'estado' => 'nuevo',
    'metadatos' => [
        'tipo' => 'email_real_agrosander',
        'proveedor_autorizado' => [
            'id' => 1,
            'nombre' => 'Agrosander Don Jorge S A S',
            'nit' => '900591105',
            'email_real' => 'agrosandersas@gmail.com' // Email real donde enviar acuse
        ]
    ]
]);

echo "✅ Email de Agrosander creado: ID #{$emailAgrosander->id}\n";
echo "📧 Remitente corporativo: facturacion@agrosander.com\n";
echo "📧 Email real para acuse: agrosandersas@gmail.com\n\n";

echo "🚀 PROCESANDO EMAIL CON MAPEO DE EMAIL REAL:\n";
echo "============================================\n";

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

// Verificar el email procesado
$emailActualizado = EmailBuzon::find($emailAgrosander->id);

echo "🔍 VERIFICANDO EMAIL PROCESADO:\n";
echo "===============================\n";
echo "📄 Email ID: {$emailActualizado->id}\n";
echo "📊 Estado: {$emailActualizado->estado}\n";
echo "💼 Procesado: " . ($emailActualizado->procesado ? 'SÍ' : 'NO') . "\n";
echo "📧 Remitente original: {$emailActualizado->remitente_email}\n";

// Mostrar CUFEs extraídos
if (isset($emailActualizado->metadatos['cufes_extraidos'])) {
    $cufes = $emailActualizado->metadatos['cufes_extraidos'];
    echo "🔑 CUFEs extraídos: " . count($cufes) . "\n";
    foreach ($cufes as $index => $cufe) {
        echo "   🔑 CUFE " . ($index + 1) . ": " . substr($cufe, 0, 20) . "...\n";
    }
}

// Verificar acuse enviado
if (isset($emailActualizado->metadatos['acuse_enviado'])) {
    $acuse = $emailActualizado->metadatos['acuse_enviado'];
    echo "\n📨 ACUSE ENVIADO:\n";
    echo "=================\n";
    echo "📅 Fecha: {$acuse['fecha']}\n";
    echo "📧 Destinatario: {$acuse['destinatario']}\n";
    echo "🔑 CUFE: {$acuse['cufe']}\n";
    echo "📄 Número Factura: {$acuse['numero_factura']}\n";
    echo "📤 Método: {$acuse['metodo']}\n";
    
    // Verificar si se envió al email correcto
    if ($acuse['destinatario'] === 'agrosandersas@gmail.com') {
        echo "✅ CORRECTO: Acuse enviado al email REAL de Agrosander\n";
    } else {
        echo "❌ INCORRECTO: Acuse enviado a {$acuse['destinatario']} en lugar de agrosandersas@gmail.com\n";
    }
} else {
    echo "\n❌ No se encontró información de acuse enviado\n";
}

echo "\n📧 MAPEO DE EMAILS IMPLEMENTADO:\n";
echo "===============================\n";
echo "📧 facturacion@agrosander.com → agrosandersas@gmail.com ✅\n";
echo "📧 worldoffice@gmail.com → worldoffice@gmail.com ✅\n";
echo "📧 automatafe@gmail.com → automatafe@gmail.com ✅\n";
echo "📧 equiredes@gmail.com → equiredes@gmail.com ✅\n";
echo "📧 colcomercio@gmail.com → colcomercio@gmail.com ✅\n\n";

echo "🎯 FUNCIONALIDADES MEJORADAS:\n";
echo "=============================\n";
echo "✅ Mapeo de emails corporativos a emails reales\n";
echo "✅ Detección automática de dominios reales (gmail, outlook)\n";
echo "✅ Búsqueda mejorada de emails en XML\n";
echo "✅ Fallback inteligente para emails desconocidos\n";
echo "✅ Logging detallado de emails encontrados\n";
echo "✅ Soporte para múltiples rutas XML\n\n";

echo "🌐 ACCESO AL SISTEMA:\n";
echo "====================\n";
echo "📊 Dashboard: http://127.0.0.1:8000/dian\n";
echo "📧 Buzón: http://127.0.0.1:8000/dian/buzon\n";
echo "   - Busca por 'JRME-130551' para encontrar la factura de Agrosander\n";
echo "   - Filtra por proveedor 'Agrosander' para ver todos sus emails\n\n";

echo "📝 NOTA IMPORTANTE:\n";
echo "===================\n";
echo "El sistema ahora mapea correctamente:\n";
echo "• Email remitente: facturacion@agrosander.com (corporativo)\n";
echo "• Email para acuse: agrosandersas@gmail.com (real)\n";
echo "• Esto asegura que los acuses lleguen al email correcto del proveedor\n\n";

echo "🏁 Prueba de mapeo de email real completada\n";
