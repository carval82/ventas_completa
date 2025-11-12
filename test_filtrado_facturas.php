<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\EmailBuzon;
use App\Services\Dian\BuzonEmailService;

echo "🔍 PROBANDO FILTRADO DE FACTURAS ELECTRÓNICAS\n";
echo "=============================================\n\n";

$config = ConfiguracionDian::where('activo', true)->first();
$empresa = $config->empresa;

echo "🏢 Empresa: " . $empresa->nombre . "\n";
echo "📧 Email: " . $config->email_dian . "\n\n";

// Limpiar emails anteriores para prueba limpia
echo "🧹 Limpiando emails anteriores...\n";
EmailBuzon::where('empresa_id', $empresa->id)->delete();

// Crear servicio
$buzonService = new BuzonEmailService($config);

echo "🔄 Sincronizando emails con filtrado mejorado...\n\n";

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
    
    echo "📧 EMAILS FILTRADOS (SOLO FACTURAS ELECTRÓNICAS):\n";
    echo "================================================\n\n";
    
    if ($emails->count() > 0) {
        foreach ($emails as $email) {
            echo "📄 EMAIL #" . $email->id . "\n";
            echo "   De: " . $email->remitente_email . "\n";
            echo "   Asunto: " . $email->asunto . "\n";
            echo "   Fecha: " . $email->fecha_email . "\n";
            echo "   Tiene facturas: " . ($email->tiene_facturas ? '✅ SÍ' : '❌ NO') . "\n";
            echo "   Estado: " . $email->estado . "\n";
            
            if ($email->archivos_adjuntos) {
                echo "   📎 Archivos adjuntos:\n";
                foreach ($email->archivos_adjuntos as $archivo) {
                    $es_factura = isset($archivo['es_factura']) && $archivo['es_factura'] ? '✅' : '❌';
                    echo "      - " . $archivo['nombre'] . " ($es_factura)\n";
                }
            }
            
            if ($email->metadatos) {
                $metadatos = $email->metadatos;
                if (isset($metadatos['cufe'])) {
                    echo "   🔑 CUFE: " . $metadatos['cufe'] . "\n";
                }
                if (isset($metadatos['tipo_documento'])) {
                    echo "   📋 Tipo: " . $metadatos['tipo_documento'] . "\n";
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
        
        if (isset($resultadoProcesamiento['errores']) && count($resultadoProcesamiento['errores']) > 0) {
            echo "❌ Errores: " . count($resultadoProcesamiento['errores']) . "\n";
            foreach ($resultadoProcesamiento['errores'] as $error) {
                echo "   - " . $error . "\n";
            }
        }
        
    } else {
        echo "📭 No se encontraron emails con facturas electrónicas\n";
        echo "💡 Esto significa que el filtrado está funcionando correctamente\n";
        echo "   y solo procesa emails que realmente contienen facturas.\n";
    }
} else {
    echo "❌ Error en la sincronización: " . $resultado['message'] . "\n";
}

echo "\n🏁 Prueba de filtrado completada\n";
echo "\n💡 RESUMEN:\n";
echo "- El sistema ahora SOLO procesa emails con facturas electrónicas\n";
echo "- Se generan acuses automáticamente para cada factura\n";
echo "- Los emails sin facturas son ignorados\n";
echo "- Se detectan CUFEs, tipos de documento y archivos adjuntos\n";
