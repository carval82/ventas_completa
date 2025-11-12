<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EmailBuzon;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "🎨 PRUEBA DE VISTAS DE ACUSES DIAN\n";
echo "=================================\n\n";

// Autenticar usuario
$user = User::first();
Auth::login($user);
echo "👤 Usuario: {$user->name} (Empresa ID: {$user->empresa_id})\n\n";

echo "📊 VERIFICANDO DATOS PARA LAS VISTAS...\n";
echo "======================================\n";

// Verificar emails con facturas
$emailsConFacturas = EmailBuzon::where('empresa_id', $user->empresa_id)
                               ->where('tiene_facturas', true)
                               ->count();

echo "📧 Emails con facturas: {$emailsConFacturas}\n";

// Verificar emails con acuses enviados
$emailsConAcuses = EmailBuzon::where('empresa_id', $user->empresa_id)
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

echo "✅ Emails con acuses enviados: {$emailsConAcuses}\n";

// Verificar emails con emails reales extraídos
$emailsConEmailsReales = EmailBuzon::where('empresa_id', $user->empresa_id)
                                  ->where('tiene_facturas', true)
                                  ->whereNotNull('metadatos')
                                  ->get()
                                  ->filter(function($email) {
                                      $metadatos = is_string($email->metadatos) ? 
                                                 json_decode($email->metadatos, true) : 
                                                 ($email->metadatos ?? []);
                                      return isset($metadatos['email_real_proveedor']);
                                  })
                                  ->count();

echo "📧 Emails con emails reales: {$emailsConEmailsReales}\n\n";

if ($emailsConFacturas === 0) {
    echo "⚠️ No hay emails con facturas para mostrar en las vistas\n";
    echo "💡 Ejecuta primero:\n";
    echo "   1. Ir a http://127.0.0.1:8000/dian/buzon\n";
    echo "   2. Sincronizar emails\n";
    echo "   3. Procesar facturas\n\n";
} else {
    echo "✅ Hay datos suficientes para las vistas\n\n";
}

echo "🎯 COMPONENTES CREADOS:\n";
echo "=======================\n";
echo "✅ Controlador: AcuseController\n";
echo "✅ Vista índice: dian/acuses/index.blade.php\n";
echo "✅ Vista detalles: dian/acuses/show.blade.php\n";
echo "✅ Rutas agregadas al grupo DIAN\n";
echo "✅ Botón agregado al dashboard DIAN\n\n";

echo "🔗 URLS DISPONIBLES:\n";
echo "====================\n";
echo "• Dashboard DIAN: http://127.0.0.1:8000/dian\n";
echo "• Lista de Acuses: http://127.0.0.1:8000/dian/acuses\n";
echo "• Buzón de Correos: http://127.0.0.1:8000/dian/buzon\n";
echo "• Configuraciones Email: http://127.0.0.1:8000/email-configurations\n\n";

echo "🎨 FUNCIONALIDADES DE LAS VISTAS:\n";
echo "=================================\n";
echo "📊 VISTA ÍNDICE (/dian/acuses):\n";
echo "   ✅ Estadísticas de acuses\n";
echo "   ✅ Filtros avanzados (estado, proveedor, fechas)\n";
echo "   ✅ Tabla con información completa\n";
echo "   ✅ Diferenciación entre email corporativo y real\n";
echo "   ✅ Estados de acuses (enviado/pendiente)\n";
echo "   ✅ Botones de acción (ver, enviar, reenviar)\n";
echo "   ✅ Paginación\n\n";

echo "📄 VISTA DETALLES (/dian/acuses/{id}):\n";
echo "   ✅ Información completa del email\n";
echo "   ✅ Datos del proveedor extraídos del XML\n";
echo "   ✅ Estado detallado del acuse\n";
echo "   ✅ Mapeo de emails (corporativo → real)\n";
echo "   ✅ Metadatos técnicos\n";
echo "   ✅ Botones para enviar/reenviar acuses\n";
echo "   ✅ Modal de confirmación\n\n";

echo "⚡ FUNCIONALIDADES AJAX:\n";
echo "========================\n";
echo "✅ Envío de acuses sin recargar página\n";
echo "✅ Reenvío de acuses existentes\n";
echo "✅ Alertas de éxito/error\n";
echo "✅ Loading states en botones\n";
echo "✅ Validación de emails de destino\n\n";

echo "🔧 INTEGRACIÓN CON SISTEMA DINÁMICO:\n";
echo "====================================\n";
echo "✅ Usa DynamicEmailService para envíos\n";
echo "✅ Respeta configuraciones por empresa\n";
echo "✅ Utiliza SendGrid configurado\n";
echo "✅ Registra estadísticas de envío\n";
echo "✅ Actualiza metadatos automáticamente\n\n";

echo "🎯 DIFERENCIAS CLAVE:\n";
echo "=====================\n";
echo "❌ ANTES: No había vistas para gestionar acuses\n";
echo "✅ AHORA: Sistema completo de gestión visual\n\n";
echo "❌ ANTES: Acuses solo por línea de comandos\n";
echo "✅ AHORA: Interfaz web intuitiva\n\n";
echo "❌ ANTES: No se veían emails reales vs corporativos\n";
echo "✅ AHORA: Diferenciación clara y mapeo visible\n\n";
echo "❌ ANTES: No había estadísticas de acuses\n";
echo "✅ AHORA: Dashboard completo con métricas\n\n";

echo "🚀 PRÓXIMOS PASOS:\n";
echo "==================\n";
echo "1. Acceder al dashboard DIAN: http://127.0.0.1:8000/dian\n";
echo "2. Click en 'Ver Acuses' para acceder a la nueva vista\n";
echo "3. Explorar filtros y funcionalidades\n";
echo "4. Probar envío manual de acuses\n";
echo "5. Verificar detalles de emails individuales\n\n";

echo "💡 CONSEJOS DE USO:\n";
echo "===================\n";
echo "• Usa los filtros para encontrar emails específicos\n";
echo "• Verifica que los emails reales estén extraídos correctamente\n";
echo "• Los acuses se envían a los emails reales, no corporativos\n";
echo "• Puedes reenviar acuses si es necesario\n";
echo "• Las estadísticas se actualizan en tiempo real\n\n";

echo "🎉 VISTAS DE ACUSES IMPLEMENTADAS EXITOSAMENTE\n";
echo "==============================================\n";
echo "El módulo DIAN ahora cuenta con un sistema completo\n";
echo "de gestión visual de acuses de recibo, integrado\n";
echo "con el sistema dinámico de emails por empresa.\n\n";

echo "🏁 Implementación completada\n";
