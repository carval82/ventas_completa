<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProveedorElectronico;
use App\Models\ConfiguracionDian;

echo "🏢 CONFIGURANDO PROVEEDORES ELECTRÓNICOS\n";
echo "========================================\n\n";

$config = ConfiguracionDian::where('activo', true)->first();
$empresa = $config->empresa;

echo "🏢 Empresa: " . $empresa->nombre . "\n";
echo "📧 Email: " . $config->email_dian . "\n\n";

// Limpiar proveedores existentes
ProveedorElectronico::where('empresa_id', $empresa->id)->delete();

// Crear proveedores específicos
$proveedores = [
    [
        'nombre_proveedor' => 'Don Jorge SAS',
        'email_proveedor' => 'facturacion@donjorgesas.com',
        'nit_proveedor' => '900123456-1',
        'dominios_email' => ['donjorgesas.com'],
        'palabras_clave' => ['donjorgesas', 'don jorge', 'factura electronica'],
        'observaciones' => 'Proveedor principal de productos agrícolas'
    ],
    [
        'nombre_proveedor' => 'Automatafe',
        'email_proveedor' => 'facturacion@automatafe.com',
        'nit_proveedor' => '800654321-9',
        'dominios_email' => ['automatafe.com'],
        'palabras_clave' => ['automatafe', 'factura electronica', 'automatización'],
        'observaciones' => 'Proveedor de equipos de automatización'
    ],
    [
        'nombre_proveedor' => 'Equiredes Soluciones',
        'email_proveedor' => 'facturacion@equiredes.com',
        'nit_proveedor' => '700987654-3',
        'dominios_email' => ['equiredes.com', 'equiredessol.com'],
        'palabras_clave' => ['equiredes', 'soluciones', 'factura electronica'],
        'observaciones' => 'Proveedor de soluciones de red y telecomunicaciones'
    ],
    [
        'nombre_proveedor' => 'Colcomercio',
        'email_proveedor' => 'Notificacionesfacturacionelectronica@colcomercio.com.co',
        'nit_proveedor' => '860007539-1',
        'dominios_email' => ['colcomercio.com.co'],
        'palabras_clave' => ['colcomercio', 'factura electronica', 'notificaciones'],
        'observaciones' => 'Confederación Colombiana de Cámaras de Comercio'
    ],
    [
        'nombre_proveedor' => 'World Office',
        'email_proveedor' => 'no-responder@worldoffice.com.co',
        'nit_proveedor' => '900456789-2',
        'dominios_email' => ['worldoffice.com.co'],
        'palabras_clave' => ['worldoffice', 'world office', 'factura electronica'],
        'observaciones' => 'Proveedor de servicios de oficina y suministros'
    ]
];

echo "📝 Creando proveedores autorizados...\n\n";

foreach ($proveedores as $proveedor_data) {
    $proveedor = ProveedorElectronico::create([
        'empresa_id' => $empresa->id,
        'nombre_proveedor' => $proveedor_data['nombre_proveedor'],
        'email_proveedor' => $proveedor_data['email_proveedor'],
        'nit_proveedor' => $proveedor_data['nit_proveedor'],
        'dominios_email' => $proveedor_data['dominios_email'],
        'palabras_clave' => $proveedor_data['palabras_clave'],
        'activo' => true,
        'observaciones' => $proveedor_data['observaciones']
    ]);
    
    echo "✅ " . $proveedor->nombre_proveedor . "\n";
    echo "   📧 " . $proveedor->email_proveedor . "\n";
    echo "   🏷️  Dominios: " . implode(', ', $proveedor->dominios_email) . "\n";
    echo "   🔍 Palabras clave: " . implode(', ', $proveedor->palabras_clave) . "\n\n";
}

echo "🎉 PROVEEDORES CONFIGURADOS EXITOSAMENTE\n";
echo "========================================\n\n";

echo "💡 AHORA EL SISTEMA SOLO PROCESARÁ EMAILS DE:\n";
foreach ($proveedores as $proveedor) {
    echo "- " . $proveedor['nombre_proveedor'] . " (" . $proveedor['email_proveedor'] . ")\n";
}

echo "\n🔧 PRÓXIMO PASO: Actualizar el servicio BuzonEmailService para usar estos proveedores\n";
