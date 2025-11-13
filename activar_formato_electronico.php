<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════\n";
echo "  ACTIVAR FORMATO ELECTRÓNICO\n";
echo "═══════════════════════════════════════════\n\n";

$empresa = DB::table('empresas')->first();

if (!$empresa) {
    echo "❌ No se encontró empresa en la BD\n";
    exit(1);
}

echo "Empresa: {$empresa->nombre_comercial}\n";
echo "Formato actual: {$empresa->usar_formato_electronico}\n\n";

$updated = DB::table('empresas')
    ->where('id', $empresa->id)
    ->update(['usar_formato_electronico' => true]);

if ($updated) {
    echo "✅ Formato electrónico ACTIVADO\n";
    echo "\nAhora TODAS las facturas se imprimirán con el diseño profesional.\n";
} else {
    echo "❌ No se pudo actualizar\n";
}
