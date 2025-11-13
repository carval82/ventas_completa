<?php
/**
 * Script para activar formato electrónico en el servidor del cliente
 * Ejecutar después de git pull y migrate
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

echo "╔════════════════════════════════════════════╗\n";
echo "║  CONFIGURACIÓN POST-DEPLOY                 ║\n";
echo "╚════════════════════════════════════════════╝\n\n";

// 1. Verificar migración
echo "1️⃣  Verificando migración...\n";
try {
    $columnaExiste = DB::select("SHOW COLUMNS FROM empresas LIKE 'usar_formato_electronico'");
    if (empty($columnaExiste)) {
        echo "   ❌ Campo 'usar_formato_electronico' NO existe\n";
        echo "   ⚠️  Ejecuta: php artisan migrate\n\n";
        exit(1);
    }
    echo "   ✅ Migración OK\n\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// 2. Activar formato electrónico
echo "2️⃣  Activando formato electrónico...\n";
try {
    $updated = DB::table('empresas')->update(['usar_formato_electronico' => true]);
    echo "   ✅ Formato electrónico ACTIVADO ({$updated} registro(s))\n\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// 3. Limpiar cachés
echo "3️⃣  Limpiando cachés...\n";
try {
    Artisan::call('optimize:clear');
    echo "   ✅ Cachés limpiados\n\n";
} catch (Exception $e) {
    echo "   ⚠️  Limpia manualmente: php artisan optimize:clear\n\n";
}

// 4. Verificar vista
echo "4️⃣  Verificando vista...\n";
$vistaPath = resource_path('views/ventas/print_factura_electronica.blade.php');
if (file_exists($vistaPath)) {
    echo "   ✅ Vista 'print_factura_electronica.blade.php' existe\n";
    echo "   📄 Tamaño: " . number_format(filesize($vistaPath)) . " bytes\n\n";
} else {
    echo "   ❌ Vista NO encontrada\n";
    echo "   ⚠️  Verifica que se haya subido a Git correctamente\n\n";
}

// 5. Mostrar configuración actual
echo "5️⃣  Configuración actual:\n";
$empresa = DB::table('empresas')->first();
if ($empresa) {
    echo "   • Empresa: {$empresa->nombre_comercial}\n";
    echo "   • Formato impresión: " . ($empresa->formato_impresion ?? 'No definido') . "\n";
    echo "   • Usar formato electrónico: " . ($empresa->usar_formato_electronico ? '✅ SÍ' : '❌ NO') . "\n";
    echo "   • Alegra habilitada: " . ($empresa->factura_electronica_habilitada ? '✅ SÍ' : '❌ NO') . "\n\n";
}

echo "╔════════════════════════════════════════════╗\n";
echo "║  ✅ CONFIGURACIÓN COMPLETADA               ║\n";
echo "╚════════════════════════════════════════════╝\n\n";

echo "📝 PRÓXIMOS PASOS:\n";
echo "1. Abre el navegador en modo incógnito (Ctrl + Shift + N)\n";
echo "2. Ve a: /ventas\n";
echo "3. Click en 'Ver' → 'Imprimir' en cualquier venta\n";
echo "4. Verás el nuevo diseño profesional\n\n";
