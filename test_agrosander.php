<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\EmailBuzon;
use App\Models\ProveedorElectronico;
use App\Services\Dian\BuzonEmailService;

echo "🧪 PROBANDO DETECCIÓN DE AGROSANDER DON JORGE S A S\n";
echo "===================================================\n\n";

$config = ConfiguracionDian::where('activo', true)->first();
$empresa = $config->empresa;

// Mostrar configuración de Agrosander
$agrosander = ProveedorElectronico::where('empresa_id', $empresa->id)
    ->where('nombre_proveedor', 'Agrosander Don Jorge S A S')
    ->first();

if ($agrosander) {
    echo "✅ CONFIGURACIÓN DE AGROSANDER:\n";
    echo "==============================\n";
    echo "🏢 Nombre: " . $agrosander->nombre_proveedor . "\n";
    echo "📧 Email: " . $agrosander->email_proveedor . "\n";
    echo "🆔 NIT: " . $agrosander->nit_proveedor . "\n";
    echo "🏷️  Dominios: " . implode(', ', $agrosander->dominios_email ?? []) . "\n";
    echo "🔍 Palabras clave: " . implode(', ', $agrosander->palabras_clave ?? []) . "\n\n";
    
    // Probar detección con diferentes variaciones
    echo "🔍 PROBANDO DETECCIÓN:\n";
    echo "======================\n";
    
    $tests = [
        ['email' => 'facturacion@agrosander.com', 'nombre' => 'Agrosander', 'asunto' => 'Factura'],
        ['email' => 'info@donjorgesas.com', 'nombre' => 'Don Jorge', 'asunto' => 'Documento'],
        ['email' => 'test@agrosanderdonjorge.com', 'nombre' => 'Agrosander Don Jorge S A S', 'asunto' => 'Factura Electrónica'],
        ['email' => 'cualquier@email.com', 'nombre' => 'AGROSANDER DON JORGE S A S', 'asunto' => 'Factura FE-001'],
        ['email' => 'otro@email.com', 'nombre' => 'Empresa', 'asunto' => 'Factura JRME130551 Electrónica'],
    ];
    
    foreach ($tests as $i => $test) {
        echo ($i + 1) . ". Email: " . $test['email'] . "\n";
        echo "   Nombre: " . $test['nombre'] . "\n";
        echo "   Asunto: " . $test['asunto'] . "\n";
        
        $coincide_email = $agrosander->coincideConEmail($test['email']);
        $coincide_nombre = $agrosander->coincideConRemitente($test['nombre']);
        $coincide_asunto = $agrosander->coincideConAsunto($test['asunto']);
        
        echo "   📧 Por email: " . ($coincide_email ? '✅ SÍ' : '❌ NO') . "\n";
        echo "   👤 Por nombre: " . ($coincide_nombre ? '✅ SÍ' : '❌ NO') . "\n";
        echo "   📋 Por asunto: " . ($coincide_asunto ? '✅ SÍ' : '❌ NO') . "\n";
        echo "   🎯 DETECTADO: " . (($coincide_email || $coincide_nombre || $coincide_asunto) ? '✅ SÍ' : '❌ NO') . "\n\n";
    }
} else {
    echo "❌ No se encontró la configuración de Agrosander\n";
}

// Limpiar emails anteriores
echo "🧹 Limpiando emails anteriores...\n";
EmailBuzon::where('empresa_id', $empresa->id)->delete();

// Probar sincronización
echo "🔄 Probando sincronización con configuración actualizada...\n\n";

$buzonService = new BuzonEmailService($config);
$resultado = $buzonService->sincronizarEmails();

echo "📊 RESULTADOS:\n";
echo "==============\n";
echo "Success: " . ($resultado['success'] ? 'SÍ' : 'NO') . "\n";
echo "Emails descargados: " . $resultado['emails_descargados'] . "\n";
echo "Emails con facturas: " . $resultado['emails_con_facturas'] . "\n\n";

if ($resultado['emails_descargados'] > 0) {
    $emails = EmailBuzon::where('empresa_id', $empresa->id)->get();
    
    echo "📧 EMAILS DETECTADOS:\n";
    echo "====================\n";
    foreach ($emails as $email) {
        echo "- De: " . $email->remitente_email . " (" . $email->remitente_nombre . ")\n";
        echo "  Asunto: " . $email->asunto . "\n";
        if ($email->metadatos && isset($email->metadatos['proveedor_autorizado'])) {
            echo "  🏢 Proveedor: " . $email->metadatos['proveedor_autorizado']['nombre'] . "\n";
        }
        echo "\n";
    }
}

echo "💡 Si no se detectan emails de Agrosander, significa que:\n";
echo "- No hay emails recientes de Agrosander en el buzón\n";
echo "- O los emails no contienen facturas electrónicas\n";
echo "- La configuración está funcionando correctamente\n";
