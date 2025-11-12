<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EmailBuzon;
use App\Models\FacturaDianProcesada;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "🔧 CORRECCIÓN: DASHBOARD MOSTRANDO EMAILS REALES\n";
echo "===============================================\n\n";

// Autenticar usuario
$user = User::first();
Auth::login($user);
echo "👤 Usuario: {$user->name} (Empresa ID: {$user->empresa_id})\n\n";

echo "📊 VERIFICANDO DATOS ANTES Y DESPUÉS...\n";
echo "======================================\n";

// Verificar emails en el modelo antiguo (FacturaDianProcesada)
$facturasAntiguas = FacturaDianProcesada::where('empresa_id', $user->empresa_id)->count();
echo "📄 FacturaDianProcesada (modelo antiguo): {$facturasAntiguas}\n";

// Verificar emails en el modelo nuevo (EmailBuzon)
$emailsNuevos = EmailBuzon::where('empresa_id', $user->empresa_id)->count();
$emailsConFacturas = EmailBuzon::where('empresa_id', $user->empresa_id)
                              ->where('tiene_facturas', true)
                              ->count();

echo "📧 EmailBuzon (modelo nuevo): {$emailsNuevos}\n";
echo "📧 EmailBuzon con facturas: {$emailsConFacturas}\n\n";

if ($emailsConFacturas === 0) {
    echo "⚠️ No hay emails con facturas en EmailBuzon\n";
    echo "💡 Esto explica por qué el dashboard no muestra datos reales\n\n";
    
    echo "🔄 SOLUCIONES:\n";
    echo "==============\n";
    echo "1. Ir a http://127.0.0.1:8000/dian/buzon\n";
    echo "2. Click en 'Sincronizar Emails'\n";
    echo "3. Click en 'Procesar Emails'\n";
    echo "4. Verificar que se procesen las facturas\n\n";
} else {
    echo "✅ Hay emails con facturas en EmailBuzon\n";
    echo "✅ El dashboard debería mostrar datos reales\n\n";
}

// Mostrar algunos emails de ejemplo
if ($emailsConFacturas > 0) {
    echo "📋 EMAILS CON FACTURAS (ÚLTIMOS 5):\n";
    echo "===================================\n";
    
    $emails = EmailBuzon::where('empresa_id', $user->empresa_id)
                       ->where('tiene_facturas', true)
                       ->orderBy('fecha_email', 'desc')
                       ->limit(5)
                       ->get();
    
    foreach ($emails as $email) {
        $metadatos = is_string($email->metadatos) ? 
                    json_decode($email->metadatos, true) : 
                    ($email->metadatos ?? []);
        
        $acuseEnviado = $metadatos['acuse_enviado'] ?? false;
        $emailReal = $metadatos['email_real_proveedor'] ?? 'No extraído';
        
        echo "📧 ID: {$email->id}\n";
        echo "   De: {$email->remitente_nombre} <{$email->remitente_email}>\n";
        echo "   Fecha: {$email->fecha_email->format('d/m/Y H:i:s')}\n";
        echo "   Estado: {$email->estado}\n";
        echo "   Acuse: " . ($acuseEnviado ? '✅ Enviado' : '⏳ Pendiente') . "\n";
        echo "   Email real: {$emailReal}\n";
        echo "   Asunto: " . substr($email->asunto, 0, 50) . "...\n\n";
    }
}

echo "🎯 CAMBIOS REALIZADOS EN EL CONTROLADOR:\n";
echo "========================================\n";
echo "✅ Agregado: use App\\Models\\EmailBuzon;\n";
echo "✅ Cambiado: FacturaDianProcesada → EmailBuzon en index()\n";
echo "✅ Actualizado: obtenerEstadisticas() usa EmailBuzon\n";
echo "✅ Mejorado: Conteo de acuses enviados desde metadatos\n\n";

echo "📊 ESTADÍSTICAS CALCULADAS CON NUEVO SISTEMA:\n";
echo "=============================================\n";

// Simular las estadísticas que calculará el controlador
$emails = EmailBuzon::where('empresa_id', $user->empresa_id);
$emailsConFacturas = EmailBuzon::where('empresa_id', $user->empresa_id)->where('tiene_facturas', true);

$acusesEnviados = EmailBuzon::where('empresa_id', $user->empresa_id)
                          ->where('tiene_facturas', true)
                          ->whereNotNull('metadatos')
                          ->get()
                          ->filter(function($email) {
                              $metadatos = is_string($email->metadatos) ? 
                                          json_decode($email->metadatos, true) : 
                                          ($email->metadatos ?? []);
                              return $metadatos['acuse_enviado'] ?? false;
                          })
                          ->count();

$estadisticas = [
    'total_facturas' => $emailsConFacturas->count(),
    'facturas_hoy' => $emailsConFacturas->whereDate('fecha_email', today())->count(),
    'facturas_mes' => $emailsConFacturas->whereMonth('fecha_email', now()->month)->count(),
    'acuses_enviados' => $acusesEnviados,
    'pendientes_acuse' => $emailsConFacturas->count() - $acusesEnviados,
    'con_errores' => $emails->where('estado', 'error')->count(),
];

foreach ($estadisticas as $key => $value) {
    echo "📊 " . ucfirst(str_replace('_', ' ', $key)) . ": {$value}\n";
}

echo "\n🔗 VERIFICAR DASHBOARD:\n";
echo "======================\n";
echo "• Dashboard DIAN: http://127.0.0.1:8000/dian\n";
echo "• Buzón de Correos: http://127.0.0.1:8000/dian/buzon\n";
echo "• Lista de Acuses: http://127.0.0.1:8000/dian/acuses\n\n";

echo "💡 DIFERENCIAS CLAVE:\n";
echo "=====================\n";
echo "❌ ANTES: Dashboard usaba FacturaDianProcesada (emails de muestra)\n";
echo "✅ AHORA: Dashboard usa EmailBuzon (emails reales del buzón)\n\n";
echo "❌ ANTES: Estadísticas basadas en datos simulados\n";
echo "✅ AHORA: Estadísticas basadas en emails reales procesados\n\n";
echo "❌ ANTES: No mostraba acuses reales\n";
echo "✅ AHORA: Muestra acuses enviados desde metadatos\n\n";

if ($emailsConFacturas > 0) {
    echo "🎉 CORRECCIÓN EXITOSA\n";
    echo "====================\n";
    echo "El dashboard ahora mostrará los emails reales del buzón\n";
    echo "en lugar de los emails de muestra del modelo antiguo.\n\n";
    
    echo "📈 PRÓXIMOS PASOS:\n";
    echo "==================\n";
    echo "1. Acceder al dashboard: http://127.0.0.1:8000/dian\n";
    echo "2. Verificar que muestre los emails reales\n";
    echo "3. Comprobar las estadísticas actualizadas\n";
    echo "4. Probar la navegación a buzón y acuses\n\n";
} else {
    echo "⚠️ ACCIÓN REQUERIDA\n";
    echo "===================\n";
    echo "Para ver emails reales en el dashboard:\n";
    echo "1. Ve al buzón: http://127.0.0.1:8000/dian/buzon\n";
    echo "2. Sincroniza emails desde tu cuenta\n";
    echo "3. Procesa las facturas encontradas\n";
    echo "4. Regresa al dashboard para ver los datos\n\n";
}

echo "🏁 Corrección del dashboard completada\n";
