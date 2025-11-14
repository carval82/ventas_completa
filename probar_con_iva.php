<?php
/**
 * Script para cambiar temporalmente a responsable de IVA y probar
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Empresa;
use Illuminate\Support\Facades\DB;

echo "╔═══════════════════════════════════════════╗\n";
echo "║  CAMBIAR RÉGIMEN A RESPONSABLE DE IVA     ║\n";
echo "╚═══════════════════════════════════════════╝\n\n";

$empresa = Empresa::first();

if (!$empresa) {
    echo "❌ No hay empresa configurada\n";
    exit(1);
}

echo "🏢 Empresa actual:\n";
echo "   Nombre: {$empresa->nombre_comercial}\n";
echo "   Régimen actual: {$empresa->regimen_tributario}\n\n";

if ($empresa->regimen_tributario === 'responsable_iva') {
    echo "✅ Ya es responsable de IVA\n";
} else {
    echo "🔄 Cambiando a 'responsable_iva'...\n";
    
    DB::table('empresas')
        ->where('id', $empresa->id)
        ->update(['regimen_tributario' => 'responsable_iva']);
    
    echo "✅ Régimen actualizado a 'responsable_iva'\n\n";
    echo "📝 Ahora las facturas mostrarán:\n";
    echo "   • Tabla de impuestos con IVA 19%\n";
    echo "   • Base gravable\n";
    echo "   • Impuesto desglosado\n\n";
}

echo "💡 Para revertir el cambio:\n";
echo "   Opción 1: Desde la UI → Configuración → Empresa\n";
echo "   Opción 2: Ejecutar query:\n";
echo "   UPDATE empresas SET regimen_tributario = 'no_responsable_iva' WHERE id = {$empresa->id};\n\n";

// Limpiar caché
echo "🧹 Limpiando cachés...\n";
\Artisan::call('optimize:clear');
echo "✅ Cachés limpiados\n\n";

echo "🎯 AHORA PUEDES:\n";
echo "1. Abrir el navegador en modo incógnito (Ctrl + Shift + N)\n";
echo "2. Ir a /ventas\n";
echo "3. Ver → Imprimir cualquier venta\n";
echo "4. Verás el IVA desglosado correctamente\n\n";
