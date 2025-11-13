<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$empresa = DB::table('empresas')->first();

echo "Estado de Credenciales de Alegra:\n";
echo "==================================\n\n";

echo "Email configurado: " . ($empresa->alegra_email ?? 'NO CONFIGURADO') . "\n";
echo "Token configurado: " . (empty($empresa->alegra_token) ? 'NO' : 'SI') . "\n";

if (!empty($empresa->alegra_token)) {
    echo "Longitud del token: " . strlen($empresa->alegra_token) . " caracteres\n";
    echo "Primeros 10 caracteres: " . substr($empresa->alegra_token, 0, 10) . "...\n";
    echo "Últimos 5 caracteres: ..." . substr($empresa->alegra_token, -5) . "\n";
} else {
    echo "⚠ TOKEN NO CONFIGURADO\n";
}

echo "\n";
echo "Otros datos:\n";
echo "============\n";
echo "ID Resolución Alegra: " . ($empresa->id_resolucion_alegra ?? 'NO') . "\n";
echo "ID Cliente Genérico: " . ($empresa->id_cliente_generico_alegra ?? 'NO') . "\n";
echo "Facturación Electrónica: " . ($empresa->factura_electronica_habilitada ? 'SI' : 'NO') . "\n";

echo "\n";
echo "Diagnóstico:\n";
echo "============\n";

if (empty($empresa->alegra_email) || empty($empresa->alegra_token)) {
    echo "❌ PROBLEMA: Credenciales incompletas\n";
    echo "   Solución: Ir a Configuración → Empresa → Configurar credenciales de Alegra\n";
} else {
    echo "⚠ Las credenciales están configuradas pero Alegra responde 401\n";
    echo "  Posibles causas:\n";
    echo "  1. Token expirado o inválido\n";
    echo "  2. Email y token no coinciden\n";
    echo "  3. Token sin permisos necesarios\n";
    echo "\n";
    echo "  Solución: Generar un NUEVO token en Alegra:\n";
    echo "  1. Ir a https://app.alegra.com/\n";
    echo "  2. Usuario → Integraciones → API\n";
    echo "  3. Generar nuevo token\n";
    echo "  4. Copiar y pegar en la configuración de la empresa\n";
}
