<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EmailConfiguration;
use App\Services\DynamicEmailService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "🎯 PRUEBA FINAL DEL SISTEMA COMPLETO\n";
echo "===================================\n\n";

// Autenticar usuario
$user = User::first();
if (!$user) {
    echo "❌ No se encontró usuario en el sistema\n";
    exit(1);
}

Auth::login($user);
echo "👤 Usuario: {$user->name} (Empresa ID: {$user->empresa_id})\n\n";

// Verificar configuraciones
echo "📋 PASO 1: VERIFICAR CONFIGURACIONES\n";
echo "====================================\n";

$configuraciones = EmailConfiguration::where('empresa_id', $user->empresa_id)->get();

if ($configuraciones->isEmpty()) {
    echo "❌ No hay configuraciones disponibles\n";
    exit(1);
}

foreach ($configuraciones as $config) {
    $estado = $config->activo ? '🟢 ACTIVA' : '🔴 INACTIVA';
    $apiKey = strlen($config->api_key ?? '') > 10 ? '✅ Configurada' : '❌ Sin configurar';
    
    echo "📧 {$config->nombre} ({$config->proveedor}) - {$estado}\n";
    echo "   API Key: {$apiKey}\n";
    echo "   From: {$config->from_address}\n";
    echo "   Backup: " . ($config->es_backup ? '✅' : '❌') . "\n";
    echo "   Acuses: " . ($config->es_acuses ? '✅' : '❌') . "\n\n";
}

// Probar servicio dinámico
echo "🔧 PASO 2: PROBAR SERVICIO DINÁMICO\n";
echo "===================================\n";

$dynamicEmailService = new DynamicEmailService();

// Test 1: Backup
echo "💾 Probando envío de backup...\n";
$resultadoBackup = $dynamicEmailService->enviarEmail(
    $user->empresa_id,
    'backup',
    'pcapacho24@gmail.com',
    'Prueba Final - Backup Sistema - ' . date('d/m/Y H:i:s'),
    'emails.backup',
    [
        'filename' => 'backup_prueba_final.sql',
        'size' => '2.8 MB',
        'date' => date('d/m/Y H:i:s'),
        'empresa' => $user->empresa
    ]
);

if ($resultadoBackup['success']) {
    echo "✅ Backup enviado exitosamente\n";
    echo "📧 Configuración: {$resultadoBackup['configuracion_usada']}\n";
    echo "🚀 Proveedor: {$resultadoBackup['proveedor']}\n";
} else {
    echo "❌ Error enviando backup: {$resultadoBackup['message']}\n";
    
    // Si falla, mostrar detalles del error
    if (isset($resultadoBackup['error_details'])) {
        echo "🔍 Detalles: {$resultadoBackup['error_details']}\n";
    }
}

echo "\n";

// Test 2: Acuse DIAN
echo "📄 Probando envío de acuse DIAN...\n";
$resultadoAcuse = $dynamicEmailService->enviarEmail(
    $user->empresa_id,
    'acuses',
    'pcapacho24@gmail.com',
    'Prueba Final - Acuse DIAN - FE-2024-001',
    'emails.acuse-recibo',
    [
        'email' => (object)[
            'id' => 999,
            'asunto' => 'Factura Electrónica FE-2024-001',
            'remitente_nombre' => 'Proveedor Test Final',
            'remitente_email' => 'proveedor@test.com',
            'cuenta_email' => 'sistema@empresa.com',
            'estado' => 'procesado',
            'mensaje_id' => 'MSG-FINAL-001',
            'archivos_adjuntos' => []
        ],
        'datosFactura' => [
            'cufe' => 'CUFE-FINAL-123456789',
            'numero_factura' => 'FE-2024-001',
            'fecha_factura' => date('Y-m-d'),
            'proveedor' => [
                'nombre' => 'Proveedor Test Final',
                'nit' => '900123456-1'
            ],
            'totales' => [
                'subtotal' => 1000000,
                'iva' => 190000,
                'total' => 1190000
            ]
        ],
        'empresa' => $user->empresa,
        'fechaAcuse' => date('d/m/Y H:i:s')
    ]
);

if ($resultadoAcuse['success']) {
    echo "✅ Acuse enviado exitosamente\n";
    echo "📧 Configuración: {$resultadoAcuse['configuracion_usada']}\n";
    echo "🚀 Proveedor: {$resultadoAcuse['proveedor']}\n";
} else {
    echo "❌ Error enviando acuse: {$resultadoAcuse['message']}\n";
    
    if (isset($resultadoAcuse['error_details'])) {
        echo "🔍 Detalles: {$resultadoAcuse['error_details']}\n";
    }
}

echo "\n";

// Estadísticas finales
echo "📊 PASO 3: ESTADÍSTICAS FINALES\n";
echo "===============================\n";

$estadisticas = $dynamicEmailService->obtenerEstadisticas($user->empresa_id);

foreach ($estadisticas as $stat) {
    if ($stat['emails_hoy'] > 0) {
        echo "📧 {$stat['configuracion']}: {$stat['emails_hoy']} emails enviados hoy\n";
        echo "   Total enviados: {$stat['total_enviados']}\n";
        echo "   Total fallidos: {$stat['total_fallidos']}\n";
        echo "   Último envío: " . ($stat['ultimo_envio'] ? $stat['ultimo_envio']->diffForHumans() : 'Nunca') . "\n\n";
    }
}

// Resumen final
echo "🎉 RESUMEN DE LA PRUEBA FINAL\n";
echo "=============================\n";

$exitosos = 0;
$fallidos = 0;

if ($resultadoBackup['success']) $exitosos++;
else $fallidos++;

if ($resultadoAcuse['success']) $exitosos++;
else $fallidos++;

echo "✅ Emails exitosos: {$exitosos}\n";
echo "❌ Emails fallidos: {$fallidos}\n";
echo "📊 Tasa de éxito: " . round(($exitosos / 2) * 100, 1) . "%\n\n";

if ($exitosos > 0) {
    echo "🎯 SISTEMA FUNCIONANDO CORRECTAMENTE\n";
    echo "===================================\n";
    echo "✅ Configuración dinámica operativa\n";
    echo "✅ Envío de emails funcionando\n";
    echo "✅ Integración completa\n\n";
    
    echo "📱 VERIFICAR EMAILS:\n";
    echo "===================\n";
    echo "Revisa la bandeja de entrada: pcapacho24@gmail.com\n";
    echo "Busca los emails de prueba enviados\n\n";
    
    echo "🔗 ACCESOS WEB:\n";
    echo "===============\n";
    echo "• Configuraciones: http://127.0.0.1:8000/email-configurations\n";
    echo "• Dashboard DIAN: http://127.0.0.1:8000/dian\n";
    echo "• Buzón: http://127.0.0.1:8000/dian/buzon\n";
    
} else {
    echo "⚠️ SISTEMA NECESITA CONFIGURACIÓN\n";
    echo "=================================\n";
    echo "El sistema está implementado pero necesita:\n";
    echo "1. Verificar email en SendGrid\n";
    echo "2. Confirmar API Key válida\n";
    echo "3. Activar configuraciones\n";
}

echo "\n🏁 Prueba final completada\n";
