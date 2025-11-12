<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\EmailBuzon;
use App\Models\ProveedorElectronico;

echo "📅 PROBANDO BÚSQUEDA MENSUAL DE FACTURAS\n";
echo "========================================\n\n";

$config = ConfiguracionDian::where('activo', true)->first();
$empresa = $config->empresa;

echo "🏢 Empresa: " . $empresa->nombre . "\n";
echo "📧 Email: " . $config->email_dian . "\n\n";

// Mostrar períodos de búsqueda
echo "📅 PERÍODOS DE BÚSQUEDA DISPONIBLES:\n";
echo "====================================\n";

$periodos = [
    'mes_actual' => 'Desde el primer día del mes actual hasta hoy',
    'mes_anterior' => 'Todo el mes anterior completo',
    'ultimos_30_dias' => 'Últimos 30 días',
    'ultimos_7_dias' => 'Últimos 7 días',
    'hoy' => 'Solo emails de hoy'
];

foreach ($periodos as $periodo => $descripcion) {
    echo "🔹 $periodo: $descripcion\n";
}

echo "\n📊 FECHAS CALCULADAS:\n";
echo "====================\n";

// Mostrar fechas para cada período
$fechas = [
    'Primer día del mes actual' => date('d-M-Y', strtotime('first day of this month')),
    'Último día del mes anterior' => date('d-M-Y', strtotime('last day of last month')),
    'Hace 30 días' => date('d-M-Y', strtotime('-30 days')),
    'Hace 7 días' => date('d-M-Y', strtotime('-7 days')),
    'Hoy' => date('d-M-Y')
];

foreach ($fechas as $descripcion => $fecha) {
    echo "📅 $descripcion: $fecha\n";
}

echo "\n🔍 PROBANDO BÚSQUEDA DIRECTA EN IMAP:\n";
echo "====================================\n";

try {
    $servidor = '{imap.gmail.com:993/imap/ssl}INBOX';
    $email = $config->email_dian;
    $password = $config->password_email;
    
    $conexion = @imap_open($servidor, $email, $password);
    
    if (!$conexion) {
        echo "❌ Error de conexión IMAP\n";
        exit;
    }
    
    echo "✅ Conexión IMAP exitosa\n\n";
    
    // Probar diferentes períodos
    foreach ($periodos as $periodo => $descripcion) {
        echo "🔍 Probando período: $periodo\n";
        echo "   Descripción: $descripcion\n";
        
        // Calcular criterio de búsqueda
        switch ($periodo) {
            case 'mes_actual':
                $desde = date('d-M-Y', strtotime('first day of this month'));
                $criterio = 'SINCE "' . $desde . '"';
                break;
                
            case 'mes_anterior':
                $desde = date('d-M-Y', strtotime('first day of last month'));
                $hasta = date('d-M-Y', strtotime('last day of last month'));
                $criterio = 'SINCE "' . $desde . '" BEFORE "' . date('d-M-Y', strtotime($hasta . ' +1 day')) . '"';
                break;
                
            case 'ultimos_30_dias':
                $desde = date('d-M-Y', strtotime('-30 days'));
                $criterio = 'SINCE "' . $desde . '"';
                break;
                
            case 'ultimos_7_dias':
                $desde = date('d-M-Y', strtotime('-7 days'));
                $criterio = 'SINCE "' . $desde . '"';
                break;
                
            case 'hoy':
                $desde = date('d-M-Y');
                $criterio = 'SINCE "' . $desde . '"';
                break;
        }
        
        echo "   Criterio IMAP: $criterio\n";
        
        $emails_ids = imap_search($conexion, $criterio);
        
        if ($emails_ids) {
            echo "   📧 Emails encontrados: " . count($emails_ids) . "\n";
            
            // Mostrar primeros 3 emails de este período
            $limite = min(3, count($emails_ids));
            for ($i = 0; $i < $limite; $i++) {
                $email_id = $emails_ids[$i];
                $header = imap_headerinfo($conexion, $email_id);
                
                $from = isset($header->from[0]) ? $header->from[0] : null;
                $remitente_email = $from ? $from->mailbox . '@' . $from->host : 'unknown';
                $remitente_nombre = $from ? (isset($from->personal) ? $from->personal : $from->mailbox) : 'Desconocido';
                $asunto = isset($header->subject) ? substr($header->subject, 0, 50) : 'Sin asunto';
                $fecha = isset($header->date) ? $header->date : 'Sin fecha';
                
                echo "      " . ($i + 1) . ". $remitente_email - $asunto...\n";
                echo "         Fecha: $fecha\n";
            }
        } else {
            echo "   📭 No se encontraron emails\n";
        }
        
        echo "\n";
    }
    
    imap_close($conexion);
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "🎯 RECOMENDACIÓN PARA FACTURAS:\n";
echo "===============================\n";
echo "✅ Usar 'mes_actual' para capturar todas las facturas del mes\n";
echo "✅ Esto incluye desde el 1 de septiembre hasta hoy (28 de septiembre)\n";
echo "✅ Ideal para procesamiento mensual de facturas de proveedores\n\n";

echo "🔧 PRÓXIMO PASO:\n";
echo "================\n";
echo "Ejecutar sincronización con período 'mes_actual' para buscar\n";
echo "facturas de Agrosander, Automatafe, Equiredes, etc. del mes completo\n\n";

echo "🏁 Análisis de períodos completado\n";
