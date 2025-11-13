<?php
/**
 * Crea la empresa con configuración de Alegra
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Creando empresa con configuración de Alegra...\n";
echo "===============================================\n\n";

// Leer backup
$backupFile = 'backup_config_2025-11-13_00-23-31.json';
$backup = json_decode(file_get_contents($backupFile), true);

// Crear empresa completa
$empresaData = [
    'nombre_comercial' => $backup['empresa']['nombre_comercial'],
    'razon_social' => $backup['empresa']['razon_social'],
    'nit' => $backup['empresa']['nit'],
    'direccion' => $backup['empresa']['direccion'],
    'telefono' => $backup['empresa']['telefono'],
    'email' => $backup['empresa']['email'],
    'sitio_web' => null,
    'logo' => null,
    'formato_impresion' => 'carta',
    'generar_qr_local' => 0,
    'regimen_tributario' => $backup['empresa']['regimen_tributario'],
    'resolucion_facturacion' => null,
    'prefijo_factura' => 'FE',
    'id_resolucion_alegra' => $backup['empresa']['id_resolucion_alegra'],
    'id_cliente_generico_alegra' => null,
    'fecha_resolucion' => null,
    'fecha_vencimiento_resolucion' => null,
    'factura_electronica_habilitada' => $backup['empresa']['factura_electronica_habilitada'],
    'alegra_email' => $backup['empresa']['alegra_email'],
    'alegra_token' => $backup['empresa']['alegra_token'],
    'alegra_multiples_impuestos' => 0,
    'created_at' => now(),
    'updated_at' => now()
];

$empresaId = DB::table('empresas')->insertGetId($empresaData);

echo "✅ Empresa creada (ID: {$empresaId})\n\n";

echo "Configuración:\n";
echo "  Nombre: {$empresaData['nombre_comercial']}\n";
echo "  NIT: {$empresaData['nit']}\n";
echo "  Email: {$empresaData['email']}\n";
echo "  Régimen: {$empresaData['regimen_tributario']}\n";
echo "  Email Alegra: {$empresaData['alegra_email']}\n";
echo "  Token Alegra: " . substr($empresaData['alegra_token'], 0, 10) . "...\n";
echo "  Facturación electrónica: SI\n";
