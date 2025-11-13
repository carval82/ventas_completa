<?php
/**
 * Diagnostica datos residuales de empresas
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "DIAGNÓSTICO DE DATOS DE EMPRESA\n";
echo "================================\n\n";

// 1. Ver todas las empresas
echo "1. EMPRESAS EN LA BASE DE DATOS:\n";
echo "--------------------------------\n";
$empresas = DB::table('empresas')->get();
echo "Total empresas: " . count($empresas) . "\n\n";

foreach ($empresas as $empresa) {
    echo "Empresa ID: {$empresa->id}\n";
    echo "  Nombre: {$empresa->nombre_comercial}\n";
    echo "  Razón Social: {$empresa->razon_social}\n";
    echo "  NIT: {$empresa->nit}\n";
    echo "  Email Alegra: " . ($empresa->alegra_email ?? 'N/A') . "\n";
    echo "  Token Alegra: " . (isset($empresa->alegra_token) ? substr($empresa->alegra_token, 0, 10) . '...' : 'N/A') . "\n\n";
}

// 2. Buscar referencias a "Plásticos Sánchez"
echo "2. BÚSQUEDA DE 'PLASTICOS SANCHEZ':\n";
echo "-----------------------------------\n";

// Buscar en ventas
$ventasPlasticos = DB::table('ventas')
    ->where('numero_factura', 'LIKE', '%PLASTICOS%')
    ->orWhere('numero_factura_alegra', 'LIKE', '%PLASTICOS%')
    ->count();
echo "Ventas con 'PLASTICOS': {$ventasPlasticos}\n";

// Buscar en clientes
$clientesPlasticos = DB::table('clientes')
    ->where('nombre', 'LIKE', '%PLASTICOS%')
    ->orWhere('razon_social', 'LIKE', '%PLASTICOS%')
    ->count();
echo "Clientes con 'PLASTICOS': {$clientesPlasticos}\n";

// 3. Ver últimas facturas electrónicas
echo "\n3. ÚLTIMAS 10 FACTURAS ELECTRÓNICAS:\n";
echo "------------------------------------\n";
$ventas = DB::table('ventas')
    ->whereNotNull('alegra_id')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get(['id', 'numero_factura', 'numero_factura_alegra', 'alegra_id', 'total', 'created_at']);

foreach ($ventas as $v) {
    echo "Venta #{$v->id}\n";
    echo "  Número local: {$v->numero_factura}\n";
    echo "  Número Alegra: " . ($v->numero_factura_alegra ?? 'N/A') . "\n";
    echo "  ID Alegra: {$v->alegra_id}\n";
    echo "  Total: \${$v->total}\n";
    echo "  Fecha: {$v->created_at}\n\n";
}

// 4. Verificar usuarios
echo "4. USUARIOS EN EL SISTEMA:\n";
echo "--------------------------\n";
$usuarios = DB::table('users')->get(['id', 'name', 'email', 'empresa_id']);
foreach ($usuarios as $u) {
    echo "Usuario ID: {$u->id}\n";
    echo "  Nombre: {$u->name}\n";
    echo "  Email: {$u->email}\n";
    echo "  Empresa ID: " . ($u->empresa_id ?? 'N/A') . "\n\n";
}
