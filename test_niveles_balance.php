<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\BalanceGeneralController;
use Illuminate\Http\Request;
use Carbon\Carbon;

echo "=== PRUEBA DE NIVELES DE DETALLE EN BALANCE GENERAL ===\n\n";

$balanceController = new BalanceGeneralController();
$fechaCorte = Carbon::now()->format('Y-m-d');

$niveles = [
    1 => 'Clases',
    2 => 'Grupos', 
    3 => 'Cuentas',
    4 => 'Subcuentas (Máximo Detalle)'
];

foreach ($niveles as $nivel => $descripcion) {
    echo "🔍 NIVEL {$nivel} - {$descripcion}:\n";
    echo str_repeat("-", 50) . "\n";
    
    try {
        $request = new Request([
            'fecha_corte' => $fechaCorte,
            'nivel_detalle' => $nivel,
            'mostrar_ceros' => false
        ]);
        
        $response = $balanceController->generar($request);
        $data = json_decode($response->getContent(), true);
        
        if ($data['success']) {
            $balance = $data['balance'];
            
            echo "📊 ACTIVOS:\n";
            foreach ($balance['activos'] as $cuenta) {
                $indentacion = str_repeat("  ", $cuenta['nivel'] - 1);
                echo "  {$indentacion}{$cuenta['codigo']} - {$cuenta['nombre']} (Nivel {$cuenta['nivel']}): \${$cuenta['saldo_formateado']}\n";
            }
            
            echo "\n🏛️ PATRIMONIO:\n";
            foreach ($balance['patrimonio'] as $cuenta) {
                $indentacion = str_repeat("  ", $cuenta['nivel'] - 1);
                echo "  {$indentacion}{$cuenta['codigo']} - {$cuenta['nombre']} (Nivel {$cuenta['nivel']}): \${$cuenta['saldo_formateado']}\n";
            }
            
            echo "\n💰 TOTALES:\n";
            echo "  Total Activos: \${$balance['totales']['total_activos_formateado']}\n";
            echo "  Total Patrimonio: \${$balance['totales']['total_patrimonio_formateado']}\n";
            echo "  Cuentas mostradas: " . (count($balance['activos']) + count($balance['pasivos']) + count($balance['patrimonio'])) . "\n";
            
        } else {
            echo "❌ Error: " . ($data['message'] ?? 'Error desconocido') . "\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ Excepción: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("=", 80) . "\n\n";
}

echo "🎯 RESUMEN:\n";
echo "- Nivel 1: Debe mostrar solo clases (1, 2, 3)\n";
echo "- Nivel 2: Debe mostrar clases + grupos (11, 12, 21, 31, etc.)\n";
echo "- Nivel 3: Debe mostrar hasta cuentas (1105, 1110, etc.)\n";
echo "- Nivel 4: Debe mostrar hasta subcuentas (110501, 111001, etc.)\n\n";

echo "✅ Si cada nivel muestra diferente cantidad de cuentas, ¡funciona correctamente!\n";
