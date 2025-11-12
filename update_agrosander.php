<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProveedorElectronico;
use App\Models\ConfiguracionDian;

echo "🔧 ACTUALIZANDO CONFIGURACIÓN DE AGROSANDER DON JORGE S A S\n";
echo "==========================================================\n\n";

$config = ConfiguracionDian::where('activo', true)->first();
$empresa = $config->empresa;

// Buscar el proveedor Don Jorge SAS
$proveedor = ProveedorElectronico::where('empresa_id', $empresa->id)
    ->where('nombre_proveedor', 'Don Jorge SAS')
    ->first();

if ($proveedor) {
    echo "📝 Actualizando proveedor existente...\n";
    
    // Actualizar con información completa de Agrosander
    $proveedor->update([
        'nombre_proveedor' => 'Agrosander Don Jorge S A S',
        'email_proveedor' => 'facturacion@agrosander.com',
        'nit_proveedor' => 'JRME130551', // Basado en el log que viste
        'dominios_email' => [
            'agrosander.com',
            'donjorgesas.com',
            'agrosanderdonjorge.com'
        ],
        'palabras_clave' => [
            'agrosander',
            'don jorge',
            'agrosander don jorge',
            'agrosander don jorge s a s',
            'factura electronica',
            'JRME130551',
            'donjorgesas'
        ],
        'observaciones' => 'Agrosander Don Jorge S A S - Proveedor principal de productos agrícolas. NIT: JRME130551'
    ]);
    
    echo "✅ Proveedor actualizado exitosamente\n\n";
} else {
    echo "📝 Creando nuevo proveedor Agrosander...\n";
    
    // Crear nuevo proveedor
    $proveedor = ProveedorElectronico::create([
        'empresa_id' => $empresa->id,
        'nombre_proveedor' => 'Agrosander Don Jorge S A S',
        'email_proveedor' => 'facturacion@agrosander.com',
        'nit_proveedor' => 'JRME130551',
        'dominios_email' => [
            'agrosander.com',
            'donjorgesas.com',
            'agrosanderdonjorge.com'
        ],
        'palabras_clave' => [
            'agrosander',
            'don jorge',
            'agrosander don jorge',
            'agrosander don jorge s a s',
            'factura electronica',
            'JRME130551',
            'donjorgesas'
        ],
        'activo' => true,
        'observaciones' => 'Agrosander Don Jorge S A S - Proveedor principal de productos agrícolas. NIT: JRME130551'
    ]);
    
    echo "✅ Proveedor creado exitosamente\n\n";
}

echo "📋 CONFIGURACIÓN ACTUALIZADA:\n";
echo "=============================\n";
echo "🏢 Nombre: " . $proveedor->nombre_proveedor . "\n";
echo "📧 Email: " . $proveedor->email_proveedor . "\n";
echo "🆔 NIT: " . $proveedor->nit_proveedor . "\n";
echo "🏷️  Dominios: " . implode(', ', $proveedor->dominios_email) . "\n";
echo "🔍 Palabras clave: " . implode(', ', $proveedor->palabras_clave) . "\n";
echo "📝 Observaciones: " . $proveedor->observaciones . "\n\n";

echo "💡 AHORA EL SISTEMA DETECTARÁ EMAILS DE:\n";
echo "========================================\n";
echo "✅ Cualquier email de dominios: agrosander.com, donjorgesas.com, agrosanderdonjorge.com\n";
echo "✅ Emails con palabras clave: agrosander, don jorge, JRME130551, etc.\n";
echo "✅ Emails con el NIT: JRME130551\n";
echo "✅ Variaciones del nombre de la empresa\n\n";

echo "🚀 ¡Configuración completada! Ahora prueba el procesamiento de emails.\n";
