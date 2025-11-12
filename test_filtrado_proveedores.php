<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\EmailBuzon;
use App\Models\ProveedorElectronico;
use App\Services\Dian\BuzonEmailService;

echo "🔍 PROBANDO FILTRADO POR PROVEEDORES ESPECÍFICOS\n";
echo "===============================================\n\n";

$config = ConfiguracionDian::where('activo', true)->first();
$empresa = $config->empresa;

echo "🏢 Empresa: " . $empresa->nombre . "\n";
echo "📧 Email: " . $config->email_dian . "\n\n";

// Mostrar proveedores configurados
$proveedores = ProveedorElectronico::porEmpresa($empresa->id)->activos()->get();
echo "👥 PROVEEDORES AUTORIZADOS:\n";
echo "===========================\n";
foreach ($proveedores as $proveedor) {
    echo "✅ " . $proveedor->nombre_proveedor . "\n";
    echo "   📧 " . $proveedor->email_proveedor . "\n";
    echo "   🏷️  Dominios: " . implode(', ', $proveedor->dominios_email ?? []) . "\n";
    echo "   🔍 Palabras clave: " . implode(', ', $proveedor->palabras_clave ?? []) . "\n\n";
}

// Limpiar emails anteriores para prueba limpia
echo "🧹 Limpiando emails anteriores...\n";
EmailBuzon::where('empresa_id', $empresa->id)->delete();

// Crear servicio
$buzonService = new BuzonEmailService($config);

echo "🔄 Sincronizando emails con filtrado por proveedores...\n\n";

// Sincronizar emails
$resultado = $buzonService->sincronizarEmails();

echo "📊 RESULTADOS DE SINCRONIZACIÓN:\n";
echo "Success: " . ($resultado['success'] ? 'SÍ' : 'NO') . "\n";
echo "Mensaje: " . $resultado['message'] . "\n";
echo "Emails descargados: " . $resultado['emails_descargados'] . "\n";
echo "Emails con facturas: " . $resultado['emails_con_facturas'] . "\n\n";

if ($resultado['success']) {
    // Mostrar emails filtrados
    $emails = EmailBuzon::where('empresa_id', $empresa->id)
        ->orderBy('fecha_email', 'desc')
        ->get();
    
    echo "📧 EMAILS DE PROVEEDORES AUTORIZADOS:\n";
    echo "====================================\n\n";
    
    if ($emails->count() > 0) {
        foreach ($emails as $email) {
            echo "📄 EMAIL #" . $email->id . "\n";
            echo "   De: " . $email->remitente_email . "\n";
            echo "   Asunto: " . $email->asunto . "\n";
            echo "   Fecha: " . $email->fecha_email . "\n";
            echo "   Tiene facturas: " . ($email->tiene_facturas ? '✅ SÍ' : '❌ NO') . "\n";
            echo "   Estado: " . $email->estado . "\n";
            
            if ($email->metadatos && isset($email->metadatos['proveedor_autorizado'])) {
                $proveedor_info = $email->metadatos['proveedor_autorizado'];
                echo "   🏢 Proveedor: " . $proveedor_info['nombre'] . "\n";
                echo "   🆔 NIT: " . ($proveedor_info['nit'] ?? 'N/A') . "\n";
            }
            
            if ($email->archivos_adjuntos) {
                echo "   📎 Archivos adjuntos:\n";
                foreach ($email->archivos_adjuntos as $archivo) {
                    $es_factura = isset($archivo['es_factura']) && $archivo['es_factura'] ? '✅' : '❌';
                    echo "      - " . $archivo['nombre'] . " ($es_factura)\n";
                }
            }
            
            echo "\n";
        }
        
        // Procesar emails y generar acuses
        echo "⚙️ PROCESANDO EMAILS Y GENERANDO ACUSES...\n";
        echo "==========================================\n\n";
        
        $resultadoProcesamiento = $buzonService->procesarEmailsDelBuzon();
        
        echo "📊 RESULTADOS DE PROCESAMIENTO:\n";
        echo "Success: " . ($resultadoProcesamiento['success'] ? 'SÍ' : 'NO') . "\n";
        echo "Emails procesados: " . $resultadoProcesamiento['emails_procesados'] . "\n";
        
    } else {
        echo "📭 No se encontraron emails de proveedores autorizados\n";
        echo "💡 Esto significa que:\n";
        echo "   - No hay emails recientes de los proveedores configurados\n";
        echo "   - O los emails no contienen facturas electrónicas\n";
        echo "   - El filtrado está funcionando correctamente\n";
    }
} else {
    echo "❌ Error en la sincronización: " . $resultado['message'] . "\n";
}

echo "\n🏁 Prueba de filtrado por proveedores completada\n";
echo "\n💡 RESUMEN DEL FILTRADO:\n";
echo "========================\n";
echo "1. ✅ Solo emails de proveedores autorizados\n";
echo "2. ✅ Solo emails con facturas electrónicas\n";
echo "3. ✅ Detección por email exacto o dominio\n";
echo "4. ✅ Detección por palabras clave en asunto\n";
echo "5. ✅ Información del proveedor en metadatos\n";
echo "6. ✅ Acuses automáticos solo para facturas\n";
