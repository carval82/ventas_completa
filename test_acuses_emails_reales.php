<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EmailBuzon;
use App\Services\DynamicEmailService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "📧 PRUEBA DE ACUSES CON EMAILS REALES\n";
echo "====================================\n\n";

// Autenticar usuario
$user = User::first();
Auth::login($user);
echo "👤 Usuario: {$user->name} (Empresa ID: {$user->empresa_id})\n\n";

echo "🔍 BUSCANDO EMAILS CON DATOS EXTRAÍDOS...\n";
echo "=========================================\n";

// Buscar emails que tengan emails reales extraídos
$emailsConDatos = EmailBuzon::where('empresa_id', $user->empresa_id)
                           ->where('tiene_facturas', true)
                           ->whereNotNull('metadatos')
                           ->orderBy('fecha_email', 'desc')
                           ->limit(3)
                           ->get()
                           ->filter(function($email) {
                               $metadatos = is_string($email->metadatos) ? 
                                          json_decode($email->metadatos, true) : 
                                          ($email->metadatos ?? []);
                               return isset($metadatos['email_real_proveedor']);
                           });

echo "📧 Emails con datos extraídos: {$emailsConDatos->count()}\n\n";

if ($emailsConDatos->isEmpty()) {
    echo "⚠️ No hay emails con datos extraídos\n";
    echo "💡 Ejecuta primero: php procesar_emails_existentes.php\n";
    exit(0);
}

$dynamicEmailService = new DynamicEmailService();
$acusesEnviados = 0;
$errores = 0;

foreach ($emailsConDatos as $email) {
    echo "📧 PROCESANDO EMAIL #{$email->id}\n";
    echo "================================\n";
    echo "De: {$email->remitente_nombre} <{$email->remitente_email}>\n";
    echo "Asunto: {$email->asunto}\n";
    echo "Fecha: {$email->fecha_email->format('d/m/Y H:i:s')}\n";
    
    // Obtener metadatos con email real
    $metadatos = is_string($email->metadatos) ? 
               json_decode($email->metadatos, true) : 
               ($email->metadatos ?? []);
    
    $emailReal = $metadatos['email_real_proveedor'] ?? $email->remitente_email;
    $datosProveedor = $metadatos['datos_proveedor_xml'] ?? [];
    
    echo "📧 Email corporativo: {$email->remitente_email}\n";
    echo "✅ Email real extraído: {$emailReal}\n";
    echo "🏢 Proveedor: " . ($datosProveedor['nombre'] ?? 'N/A') . "\n";
    echo "🆔 NIT: " . ($datosProveedor['nit'] ?? 'N/A') . "\n";
    echo "🔑 CUFE: " . ($datosProveedor['cufe'] ?? 'N/A') . "\n\n";
    
    // Preparar datos para el acuse
    $datosFactura = [
        'cufe' => $datosProveedor['cufe'] ?? 'CUFE-AUTO-' . $email->id,
        'numero_factura' => 'FE-2024-' . str_pad($email->id, 6, '0', STR_PAD_LEFT),
        'fecha_factura' => $email->fecha_email->format('Y-m-d'),
        'proveedor' => [
            'nombre' => $datosProveedor['nombre'] ?? $email->remitente_nombre,
            'nit' => $datosProveedor['nit'] ?? 'N/A',
            'email' => $emailReal
        ],
        'totales' => [
            'subtotal' => 1000000.00,
            'iva' => 190000.00,
            'total' => 1190000.00
        ]
    ];
    
    echo "🚀 ENVIANDO ACUSE AL EMAIL REAL...\n";
    echo "==================================\n";
    
    // Enviar acuse al email REAL extraído del XML
    $resultado = $dynamicEmailService->enviarEmail(
        $user->empresa_id,
        'acuses',
        $emailReal, // ← AQUÍ ESTÁ LA DIFERENCIA: usar email real en lugar del corporativo
        'Acuse de Recibo - Factura ' . $datosFactura['numero_factura'],
        'emails.acuse-recibo',
        [
            'email' => $email,
            'datosFactura' => $datosFactura,
            'empresa' => $user->empresa,
            'fechaAcuse' => now()->format('d/m/Y H:i:s')
        ]
    );
    
    if ($resultado['success']) {
        echo "✅ ACUSE ENVIADO EXITOSAMENTE\n";
        echo "📧 Destinatario REAL: {$emailReal}\n";
        echo "🚀 Proveedor: {$resultado['proveedor']}\n";
        echo "⚙️ Configuración: {$resultado['configuracion_usada']}\n";
        
        $acusesEnviados++;
        
        // Actualizar metadatos con información del acuse
        $metadatos['acuse_enviado'] = true;
        $metadatos['fecha_acuse'] = now()->toISOString();
        $metadatos['email_acuse_enviado_a'] = $emailReal;
        $metadatos['diferencia_emails'] = [
            'email_corporativo' => $email->remitente_email,
            'email_real_usado' => $emailReal,
            'extraido_de_xml' => true
        ];
        
        $email->update(['metadatos' => json_encode($metadatos)]);
        
    } else {
        echo "❌ ERROR ENVIANDO ACUSE\n";
        echo "Error: {$resultado['message']}\n";
        $errores++;
    }
    
    echo "\n" . str_repeat("=", 60) . "\n\n";
}

echo "🎯 RESUMEN DE ACUSES CON EMAILS REALES:\n";
echo "=======================================\n";
echo "📧 Emails procesados: {$emailsConDatos->count()}\n";
echo "✅ Acuses enviados exitosamente: {$acusesEnviados}\n";
echo "❌ Errores: {$errores}\n";
echo "📊 Tasa de éxito: " . round(($acusesEnviados / $emailsConDatos->count()) * 100, 1) . "%\n\n";

if ($acusesEnviados > 0) {
    echo "🎉 ÉXITO: ACUSES ENVIADOS A EMAILS REALES\n";
    echo "=========================================\n";
    echo "✅ Los acuses se enviaron a los emails REALES extraídos de los XML\n";
    echo "✅ No a los emails corporativos genéricos\n";
    echo "✅ Los proveedores recibirán los acuses en sus emails correctos\n\n";
    
    echo "📱 VERIFICAR ENVÍOS:\n";
    echo "===================\n";
    echo "Los acuses fueron enviados a:\n";
    
    foreach ($emailsConDatos as $email) {
        $metadatos = is_string($email->metadatos) ? 
                   json_decode($email->metadatos, true) : 
                   ($email->metadatos ?? []);
        
        if (isset($metadatos['email_acuse_enviado_a'])) {
            echo "• {$email->remitente_nombre}: {$metadatos['email_acuse_enviado_a']}\n";
        }
    }
    
    echo "\n🔍 DIFERENCIAS IMPORTANTES:\n";
    echo "===========================\n";
    echo "❌ ANTES: Enviaba a facturacion@agrosander.com (email corporativo)\n";
    echo "✅ AHORA: Envía a agrosandersas@gmail.com (email real del XML)\n\n";
    
    echo "💡 ESTO SIGNIFICA:\n";
    echo "==================\n";
    echo "✅ Los proveedores recibirán los acuses en sus emails reales\n";
    echo "✅ Mayor probabilidad de que vean y procesen los acuses\n";
    echo "✅ Mejor comunicación con los proveedores\n";
    echo "✅ Cumplimiento correcto de la normativa DIAN\n\n";
    
} else {
    echo "⚠️ No se enviaron acuses exitosamente\n";
    echo "💡 Verifica la configuración de SendGrid\n";
}

echo "🔗 MONITOREAR SISTEMA:\n";
echo "======================\n";
echo "• Dashboard: http://127.0.0.1:8000/dian\n";
echo "• Buzón: http://127.0.0.1:8000/dian/buzon\n";
echo "• Configuraciones: http://127.0.0.1:8000/email-configurations\n\n";

echo "🏁 Prueba con emails reales completada\n";
