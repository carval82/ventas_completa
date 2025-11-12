<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Empresa;

echo "🏢 CREACIÓN DE EMPRESA CON CREDENCIALES ALEGRA\n";
echo "==============================================\n\n";

try {
    // Verificar si ya existe una empresa
    $empresaExistente = Empresa::first();
    if ($empresaExistente) {
        echo "⚠️ Ya existe una empresa: {$empresaExistente->nombre_comercial}\n";
        echo "¿Desea actualizar sus credenciales de Alegra? (Actualizando...)\n\n";
        
        $empresaExistente->update([
            'alegra_email' => 'pcapacho24@hotmail.com',
            'alegra_token' => '4398994d2a44f8153123'
        ]);
        
        echo "✅ Credenciales de Alegra actualizadas en la empresa existente\n";
        echo "   - Email: {$empresaExistente->alegra_email}\n";
        echo "   - Token: " . substr($empresaExistente->alegra_token, 0, 8) . "...\n";
        
    } else {
        // Crear nueva empresa con credenciales
        $datosEmpresa = [
            'nombre_comercial' => 'Mi Empresa',
            'razon_social' => 'Mi Empresa S.A.S.',
            'nit' => '900123456-7',
            'direccion' => 'Calle Principal #123',
            'telefono' => '3001234567',
            'email' => 'contacto@miempresa.com',
            'regimen_tributario' => 'no_responsable_iva',
            'resolucion_facturacion' => 'No aplica',
            'factura_electronica_habilitada' => true,
            'alegra_multiples_impuestos' => false,
            'alegra_email' => 'pcapacho24@hotmail.com',
            'alegra_token' => '4398994d2a44f8153123'
        ];

        echo "📋 Creando nueva empresa con credenciales de Alegra:\n";
        foreach ($datosEmpresa as $key => $value) {
            if ($key === 'alegra_token') {
                echo "   - {$key}: " . substr($value, 0, 8) . "...\n";
            } else {
                echo "   - {$key}: {$value}\n";
            }
        }
        echo "\n";

        $empresa = Empresa::create($datosEmpresa);
        
        echo "✅ EMPRESA CREADA EXITOSAMENTE!\n";
        echo "   ID: {$empresa->id}\n";
        echo "   Nombre: {$empresa->nombre_comercial}\n";
        echo "   Alegra Email: {$empresa->alegra_email}\n";
        echo "   Alegra Token: " . substr($empresa->alegra_token, 0, 8) . "...\n";
    }

    echo "\n🎯 VERIFICACIÓN FINAL:\n";
    $empresa = Empresa::first();
    echo "   - Empresa tiene alegra_email: " . ($empresa->alegra_email ? 'SÍ' : 'NO') . "\n";
    echo "   - Empresa tiene alegra_token: " . ($empresa->alegra_token ? 'SÍ' : 'NO') . "\n";
    echo "   - Email configurado: {$empresa->alegra_email}\n";
    echo "   - Token configurado: " . substr($empresa->alegra_token, 0, 8) . "...\n";

    echo "\n✅ AHORA EL MÉTODO DE SINCRONIZACIÓN USARÁ LAS CREDENCIALES CORRECTAS\n";
    echo "🔧 El método sincronizarAlegra() ahora utilizará:\n";
    echo "   - Email: {$empresa->alegra_email}\n";
    echo "   - Token: " . substr($empresa->alegra_token, 0, 8) . "...\n";
    echo "\n🎉 ¡Listo para sincronizar con Alegra!\n";

} catch (Exception $e) {
    echo "❌ ERROR:\n";
    echo "   Mensaje: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n🎯 Proceso completado\n";
