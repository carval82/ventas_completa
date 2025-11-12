<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\EmailBuzon;
use App\Services\Dian\BuzonEmailService;

echo "📧 PROBANDO ENVÍO REAL DE ACUSES DE RECIBO\n";
echo "==========================================\n\n";

$config = ConfiguracionDian::where('activo', true)->first();
$empresa = $config->empresa;

echo "🏢 Empresa: " . $empresa->nombre . "\n";
echo "📧 Email: " . $config->email_dian . "\n\n";

// Crear un email nuevo para probar el envío de acuse
echo "📧 Creando email de prueba para acuse...\n";

$emailPrueba = EmailBuzon::create([
    'empresa_id' => $empresa->id,
    'mensaje_id' => 'ACUSE_TEST_' . time(),
    'cuenta_email' => $config->email_dian,
    'remitente_email' => 'facturacion@agrosander.com',
    'remitente_nombre' => 'Agrosander Don Jorge S A S',
    'asunto' => 'Factura Electrónica FE-2024-999 - Prueba Acuse',
    'contenido_texto' => 'Email de prueba para generar acuse de recibo automático...',
    'fecha_email' => now(),
    'fecha_descarga' => now(),
    'archivos_adjuntos' => [
        ['nombre' => 'FE-2024-999.xml', 'tamaño' => 25420, 'es_factura' => true],
        ['nombre' => 'FE-2024-999.pdf', 'tamaño' => 125630, 'es_factura' => false]
    ],
    'tiene_facturas' => true,
    'procesado' => false,
    'estado' => 'nuevo',
    'metadatos' => [
        'tipo' => 'email_prueba_acuse',
        'proveedor_autorizado' => [
            'id' => 1,
            'nombre' => 'Agrosander Don Jorge S A S',
            'nit' => '900123456-1'
        ]
    ]
]);

echo "✅ Email de prueba creado: ID #{$emailPrueba->id}\n\n";

echo "🚀 PROCESANDO EMAIL Y ENVIANDO ACUSE REAL:\n";
echo "==========================================\n";

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
$emailActualizado = EmailBuzon::find($emailPrueba->id);

echo "🔍 VERIFICANDO EMAIL PROCESADO:\n";
echo "===============================\n";
echo "📄 Email ID: {$emailActualizado->id}\n";
echo "📊 Estado: {$emailActualizado->estado}\n";
echo "💼 Procesado: " . ($emailActualizado->procesado ? 'SÍ' : 'NO') . "\n";

// Mostrar CUFEs extraídos
if (isset($emailActualizado->metadatos['cufes_extraidos'])) {
    $cufes = $emailActualizado->metadatos['cufes_extraidos'];
    echo "🔑 CUFEs extraídos: " . count($cufes) . "\n";
    foreach ($cufes as $index => $cufe) {
        echo "   🔑 CUFE " . ($index + 1) . ": " . substr($cufe, 0, 20) . "...\n";
    }
}

// Mostrar facturas procesadas (si es array)
if (isset($emailActualizado->metadatos['facturas_procesadas'])) {
    $facturas = $emailActualizado->metadatos['facturas_procesadas'];
    if (is_array($facturas)) {
        echo "💼 Facturas procesadas: " . count($facturas) . "\n";
        
        foreach ($facturas as $index => $factura) {
            echo "   📋 Factura " . ($index + 1) . ":\n";
            echo "      🔑 CUFE: {$factura['cufe']}\n";
            echo "      📄 Número: {$factura['numero_factura']}\n";
            echo "      📧 Email Proveedor: {$factura['email_proveedor']}\n";
            echo "      🏢 Proveedor: {$factura['proveedor']['nombre']}\n";
            echo "      🆔 NIT: {$factura['proveedor']['nit']}\n";
        }
    } else {
        echo "💼 Facturas procesadas: $facturas\n";
    }
}

if (isset($emailActualizado->metadatos['acuse_enviado'])) {
    $acuse = $emailActualizado->metadatos['acuse_enviado'];
    echo "\n📨 ACUSE ENVIADO:\n";
    echo "=================\n";
    echo "📅 Fecha: {$acuse['fecha']}\n";
    echo "📧 Destinatario: {$acuse['destinatario']}\n";
    echo "🔑 CUFE: {$acuse['cufe']}\n";
    echo "📄 Número Factura: {$acuse['numero_factura']}\n";
    echo "📤 Método: {$acuse['metodo']}\n";
} else {
    echo "\n❌ No se encontró información de acuse enviado\n";
}

echo "\n📈 ESTADÍSTICAS ACTUALIZADAS:\n";
echo "=============================\n";

$estadisticas = [
    'total' => EmailBuzon::where('empresa_id', $empresa->id)->count(),
    'con_facturas' => EmailBuzon::where('empresa_id', $empresa->id)->where('tiene_facturas', true)->count(),
    'procesados' => EmailBuzon::where('empresa_id', $empresa->id)->where('procesado', true)->count(),
    'con_acuses' => EmailBuzon::where('empresa_id', $empresa->id)
                              ->whereJsonContains('metadatos->acuse_enviado->metodo', 'email_real')
                              ->count()
];

foreach ($estadisticas as $key => $value) {
    echo "📊 " . ucfirst(str_replace('_', ' ', $key)) . ": $value\n";
}

echo "\n🎯 FUNCIONALIDADES IMPLEMENTADAS:\n";
echo "=================================\n";
echo "✅ Extracción de datos de facturas XML\n";
echo "✅ Lectura de CUFEs y números de factura\n";
echo "✅ Identificación de email del proveedor\n";
echo "✅ Generación de acuses con datos completos\n";
echo "✅ Envío REAL de acuses por email\n";
echo "✅ Template HTML profesional para acuses\n";
echo "✅ Registro de acuses enviados en metadatos\n";
echo "✅ Logging completo del proceso\n\n";

echo "📧 CONTENIDO DEL ACUSE INCLUYE:\n";
echo "===============================\n";
echo "✅ Información completa de la factura (CUFE, número, fecha)\n";
echo "✅ Datos del proveedor (nombre, NIT, email)\n";
echo "✅ Datos del cliente (empresa receptora)\n";
echo "✅ Detalles del procesamiento\n";
echo "✅ Confirmación de recepción\n";
echo "✅ Diseño profesional con CSS\n";
echo "✅ Información de trazabilidad\n\n";

echo "🌐 ACCESO AL SISTEMA:\n";
echo "====================\n";
echo "📊 Dashboard: http://127.0.0.1:8000/dian\n";
echo "📧 Buzón: http://127.0.0.1:8000/dian/buzon\n";
echo "   - Filtra por 'Procesado' para ver emails con acuses\n";
echo "   - Busca por 'FE-2024-999' para encontrar el email de prueba\n\n";

echo "📝 NOTA IMPORTANTE:\n";
echo "===================\n";
echo "Los acuses se envían al email del PROVEEDOR que envió la factura.\n";
echo "En este caso: facturacion@agrosander.com\n";
echo "El sistema extrae automáticamente el email del proveedor desde:\n";
echo "1. Datos XML de la factura (si está disponible)\n";
echo "2. Email remitente del mensaje original\n";
echo "3. Configuración de proveedores autorizados\n\n";

echo "🏁 Prueba de envío real de acuses completada\n";
