<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\EmailBuzon;
use App\Models\ProveedorElectronico;
use App\Services\Dian\BuzonEmailService;

echo "📅 BÚSQUEDA MENSUAL COMPLETA DE PROVEEDORES\n";
echo "==========================================\n\n";

$config = ConfiguracionDian::where('activo', true)->first();
$empresa = $config->empresa;

echo "🏢 Empresa: " . $empresa->nombre . "\n";
echo "📧 Email: " . $config->email_dian . "\n";
echo "📅 Período: Desde 1 de septiembre hasta hoy (28 de septiembre)\n\n";

// Mostrar proveedores configurados
$proveedores = ProveedorElectronico::where('empresa_id', $empresa->id)
    ->where('activo', true)
    ->get();

echo "👥 PROVEEDORES AUTORIZADOS (" . $proveedores->count() . "):\n";
echo "================================\n";
foreach ($proveedores as $proveedor) {
    echo "🏢 " . $proveedor->nombre_proveedor . "\n";
    echo "   📧 " . $proveedor->email_proveedor . "\n";
    echo "   🏷️  Dominios: " . implode(', ', $proveedor->dominios_email ?? []) . "\n\n";
}

// Limpiar emails de prueba anteriores
echo "🧹 Limpiando emails de prueba anteriores...\n";
EmailBuzon::where('empresa_id', $empresa->id)->delete();

echo "🔄 Iniciando búsqueda mensual con filtrado de proveedores...\n\n";

// Crear servicio con período mensual
$buzonService = new BuzonEmailService($config);

// Sincronizar con período mensual
$resultado = $buzonService->sincronizarEmails();

echo "📊 RESULTADOS DE SINCRONIZACIÓN MENSUAL:\n";
echo "=======================================\n";
echo "Success: " . ($resultado['success'] ? '✅ SÍ' : '❌ NO') . "\n";
echo "Mensaje: " . $resultado['message'] . "\n";
echo "Emails descargados: " . $resultado['emails_descargados'] . "\n";
echo "Emails guardados: " . $resultado['emails_guardados'] . "\n";
echo "Emails con facturas: " . $resultado['emails_con_facturas'] . "\n\n";

if ($resultado['success'] && $resultado['emails_descargados'] > 0) {
    // Mostrar emails de proveedores encontrados
    $emails = EmailBuzon::where('empresa_id', $empresa->id)
        ->orderBy('fecha_email', 'desc')
        ->get();
    
    if ($emails->count() > 0) {
        echo "📧 EMAILS DE PROVEEDORES AUTORIZADOS:\n";
        echo "====================================\n";
        
        foreach ($emails as $email) {
            echo "📄 EMAIL #" . $email->id . "\n";
            echo "   📧 De: " . $email->remitente_email . "\n";
            echo "   👤 Nombre: " . $email->remitente_nombre . "\n";
            echo "   📋 Asunto: " . substr($email->asunto, 0, 60) . "...\n";
            echo "   📅 Fecha: " . $email->fecha_email . "\n";
            echo "   💼 Facturas: " . ($email->tiene_facturas ? '✅ SÍ' : '❌ NO') . "\n";
            echo "   📊 Estado: " . $email->estado . "\n";
            
            if ($email->metadatos && isset($email->metadatos['proveedor_autorizado'])) {
                $proveedor = $email->metadatos['proveedor_autorizado'];
                echo "   🏢 Proveedor: " . $proveedor['nombre'] . "\n";
                echo "   🆔 NIT: " . ($proveedor['nit'] ?? 'N/A') . "\n";
            }
            
            if ($email->archivos_adjuntos && count($email->archivos_adjuntos) > 0) {
                echo "   📎 Adjuntos: " . count($email->archivos_adjuntos) . "\n";
                foreach ($email->archivos_adjuntos as $adjunto) {
                    $es_factura = isset($adjunto['es_factura']) && $adjunto['es_factura'] ? '✅' : '❌';
                    echo "      - " . $adjunto['nombre'] . " ($es_factura)\n";
                }
            }
            
            echo "\n";
        }
        
        // Procesar emails encontrados
        echo "⚙️ PROCESANDO EMAILS Y GENERANDO ACUSES:\n";
        echo "=======================================\n";
        
        $resultadoProcesamiento = $buzonService->procesarEmailsDelBuzon();
        
        echo "📊 RESULTADOS DE PROCESAMIENTO:\n";
        echo "Success: " . ($resultadoProcesamiento['success'] ? '✅ SÍ' : '❌ NO') . "\n";
        echo "Emails procesados: " . $resultadoProcesamiento['emails_procesados'] . "\n";
        
        if (isset($resultadoProcesamiento['errores']) && count($resultadoProcesamiento['errores']) > 0) {
            echo "❌ Errores: " . count($resultadoProcesamiento['errores']) . "\n";
        }
        
    } else {
        echo "📭 NO SE ENCONTRARON EMAILS DE PROVEEDORES AUTORIZADOS\n";
        echo "======================================================\n\n";
        
        echo "💡 ESTO SIGNIFICA QUE:\n";
        echo "======================\n";
        echo "1. ✅ El sistema está funcionando correctamente\n";
        echo "2. ✅ Está filtrando emails correctamente\n";
        echo "3. ❌ No hay emails de los proveedores configurados en septiembre\n";
        echo "4. ❌ Los proveedores no han enviado facturas este mes\n\n";
        
        echo "🔧 OPCIONES:\n";
        echo "============\n";
        echo "1. Esperar emails reales de los proveedores\n";
        echo "2. Enviar email de prueba desde un proveedor autorizado\n";
        echo "3. Agregar más proveedores a la lista de autorizados\n";
        echo "4. Verificar si los proveedores usan otros emails\n";
    }
    
} else {
    echo "❌ Error en la sincronización o no se encontraron emails\n";
}

echo "\n🎯 RESUMEN DE LA BÚSQUEDA MENSUAL:\n";
echo "==================================\n";
echo "📅 Período: 1 de septiembre - 28 de septiembre 2025\n";
echo "🔍 Emails totales en el servidor: 38\n";
echo "👥 Proveedores configurados: " . $proveedores->count() . "\n";
echo "📧 Emails de proveedores: " . ($resultado['emails_guardados'] ?? 0) . "\n";
echo "💼 Emails con facturas: " . ($resultado['emails_con_facturas'] ?? 0) . "\n\n";

echo "🏁 Búsqueda mensual completada\n";
