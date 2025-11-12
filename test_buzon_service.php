<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ConfiguracionDian;
use App\Services\Dian\BuzonEmailService;

echo "🧪 PROBANDO SERVICIO BUZÓN EMAIL\n";
echo "=================================\n\n";

$config = ConfiguracionDian::where('activo', true)->first();
$empresa = $config->empresa;

echo "🏢 Empresa: " . $empresa->nombre . "\n";
echo "📧 Email: " . $config->email_dian . "\n\n";

// Crear servicio
$buzonService = new BuzonEmailService($config);

echo "🔄 Iniciando sincronización de emails...\n\n";

// Sincronizar emails
$resultado = $buzonService->sincronizarEmails();

echo "📊 RESULTADOS:\n";
echo "Success: " . ($resultado['success'] ? 'SÍ' : 'NO') . "\n";
echo "Mensaje: " . $resultado['message'] . "\n";
echo "Emails descargados: " . $resultado['emails_descargados'] . "\n";
echo "Emails con facturas: " . $resultado['emails_con_facturas'] . "\n\n";

if ($resultado['success'] && $resultado['emails_descargados'] > 0) {
    echo "✅ ¡EMAILS REALES PROCESADOS EXITOSAMENTE!\n\n";
    
    // Mostrar emails en la base de datos
    $emails = \App\Models\EmailBuzon::where('empresa_id', $empresa->id)
        ->orderBy('fecha_email', 'desc')
        ->limit(5)
        ->get();
    
    echo "📧 EMAILS EN EL BUZÓN:\n";
    foreach ($emails as $email) {
        echo "- De: " . $email->remitente_email . "\n";
        echo "  Asunto: " . $email->asunto . "\n";
        echo "  Fecha: " . $email->fecha_email . "\n";
        echo "  Facturas: " . ($email->tiene_facturas ? 'SÍ' : 'NO') . "\n";
        echo "  Estado: " . $email->estado . "\n\n";
    }
} else {
    echo "❌ No se procesaron emails o hubo un error\n";
}

echo "🏁 Prueba del servicio completada\n";
