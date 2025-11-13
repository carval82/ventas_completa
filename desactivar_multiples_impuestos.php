<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════\n";
echo "  DESACTIVAR MÚLTIPLES IMPUESTOS ALEGRA\n";
echo "═══════════════════════════════════════════\n\n";

$empresa = DB::table('empresas')->first();

echo "Configuración actual:\n";
echo "  alegra_multiples_impuestos: " . ($empresa->alegra_multiples_impuestos ? 'SÍ (1)' : 'NO (0)') . "\n\n";

if ($empresa->alegra_multiples_impuestos) {
    echo "⚠️  Los múltiples impuestos están ACTIVOS pero tu cuenta de Alegra no los soporta\n\n";
    echo "Desactivando...\n";
    
    DB::table('empresas')
        ->where('id', $empresa->id)
        ->update([
            'alegra_multiples_impuestos' => 0,
            'updated_at' => now()
        ]);
    
    echo "✅ Múltiples impuestos DESACTIVADOS\n\n";
    echo "Ahora el sistema enviará:\n";
    echo "  - IVA simple (19% o 0%)\n";
    echo "  - Sin formato de múltiples impuestos\n";
} else {
    echo "✅ Ya están desactivados (correcto)\n";
}
