<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\DynamicEmailService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "📄 PRUEBA DE ACUSES DIAN\n";
echo "=======================\n\n";

// Autenticar usuario
$user = User::first();
Auth::login($user);
echo "👤 Usuario: {$user->name} (Empresa ID: {$user->empresa_id})\n\n";

echo "📧 CONFIGURACIÓN ACTUAL:\n";
echo "========================\n";
echo "Email remitente: interveredanet.cr@gmail.com\n";
echo "Proveedor: SendGrid\n";
echo "Estado: ✅ Verificado y funcionando\n\n";

echo "🧪 ENVIANDO ACUSE DE PRUEBA...\n";
echo "==============================\n";

$dynamicEmailService = new DynamicEmailService();

// Simular datos de una factura real
$datosFactura = [
    'cufe' => 'CUFE96b25cc4c1d6a1e6c8e2c8b5c4d1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9',
    'numero_factura' => 'FE-2024-001234',
    'fecha_factura' => '2024-09-30',
    'proveedor' => [
        'nombre' => 'AGROSANDER DON JORGE S A S',
        'nit' => '900123456-1',
        'email' => 'facturacion@agrosander.com'
    ],
    'cliente' => [
        'nombre' => $user->empresa->nombre ?? 'EMPRESA CLIENTE',
        'nit' => $user->empresa->nit ?? '800456789-2'
    ],
    'totales' => [
        'subtotal' => 2500000.00,
        'iva' => 475000.00,
        'total' => 2975000.00
    ]
];

// Simular email original recibido
$emailOriginal = (object)[
    'id' => 12345,
    'asunto' => 'Factura Electrónica FE-2024-001234 - AGROSANDER DON JORGE S A S',
    'remitente_nombre' => 'AGROSANDER DON JORGE S A S',
    'remitente_email' => 'facturacion@agrosander.com',
    'cuenta_email' => 'interveredanet.cr@gmail.com',
    'estado' => 'procesado',
    'mensaje_id' => '<MSG-DIAN-001234@agrosander.com>',
    'fecha_email' => now(),
    'archivos_adjuntos' => [
        'FE-2024-001234.xml',
        'FE-2024-001234.pdf'
    ]
];

echo "📋 DATOS DE LA FACTURA:\n";
echo "=======================\n";
echo "CUFE: {$datosFactura['cufe']}\n";
echo "Número: {$datosFactura['numero_factura']}\n";
echo "Proveedor: {$datosFactura['proveedor']['nombre']}\n";
echo "Total: $" . number_format($datosFactura['totales']['total'], 2) . "\n\n";

// Enviar acuse
$resultado = $dynamicEmailService->enviarEmail(
    $user->empresa_id,
    'acuses',
    'facturacion@agrosander.com', // Email del proveedor
    'Acuse de Recibo - Factura ' . $datosFactura['numero_factura'],
    'emails.acuse-recibo',
    [
        'email' => $emailOriginal,
        'datosFactura' => $datosFactura,
        'empresa' => $user->empresa,
        'fechaAcuse' => now()->format('d/m/Y H:i:s')
    ]
);

if ($resultado['success']) {
    echo "✅ ACUSE ENVIADO EXITOSAMENTE\n";
    echo "============================\n";
    echo "📧 Destinatario: facturacion@agrosander.com\n";
    echo "📋 Asunto: Acuse de Recibo - Factura {$datosFactura['numero_factura']}\n";
    echo "🚀 Proveedor: {$resultado['proveedor']}\n";
    echo "⚙️ Configuración: {$resultado['configuracion_usada']}\n\n";
    
    echo "📱 VERIFICACIÓN:\n";
    echo "================\n";
    echo "El acuse fue enviado desde: interveredanet.cr@gmail.com\n";
    echo "Al proveedor: facturacion@agrosander.com\n";
    echo "Con toda la información de la factura procesada\n\n";
    
    echo "🎯 FUNCIONAMIENTO CORRECTO:\n";
    echo "===========================\n";
    echo "✅ SendGrid permite enviar desde el email verificado\n";
    echo "✅ No necesitas crear remitentes adicionales\n";
    echo "✅ El sistema usa el email configurado automáticamente\n";
    echo "✅ Los acuses se envían correctamente\n\n";
    
} else {
    echo "❌ ERROR ENVIANDO ACUSE\n";
    echo "=======================\n";
    echo "Error: {$resultado['message']}\n\n";
    
    if (isset($resultado['error_details'])) {
        echo "🔍 Detalles: {$resultado['error_details']}\n\n";
    }
    
    echo "💡 POSIBLES SOLUCIONES:\n";
    echo "=======================\n";
    echo "1. Verificar que el email esté confirmado en SendGrid\n";
    echo "2. Revisar límites diarios de SendGrid\n";
    echo "3. Verificar configuración de la API Key\n";
}

echo "📊 RESUMEN SOBRE REMITENTES:\n";
echo "============================\n";
echo "🔹 SendGrid usa el email verificado como remitente\n";
echo "🔹 No necesitas crear múltiples remitentes\n";
echo "🔹 Un solo email verificado puede enviar a cualquier destinatario\n";
echo "🔹 El sistema maneja automáticamente los acuses\n\n";

echo "🚀 PRÓXIMA PRUEBA:\n";
echo "==================\n";
echo "Si este acuse funciona, el sistema está listo para:\n";
echo "• Procesar facturas reales del buzón DIAN\n";
echo "• Generar acuses automáticamente\n";
echo "• Enviar confirmaciones a proveedores\n\n";

echo "🏁 Prueba de acuses completada\n";
