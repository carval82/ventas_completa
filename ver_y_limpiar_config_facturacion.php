<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════\n";
echo "  CONFIGURACIÓN DE FACTURACIÓN (Alegra)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$config = DB::table('configuracion_facturacion')
    ->where('proveedor', 'alegra')
    ->first();

if ($config) {
    echo "✅ Configuración encontrada (ID: {$config->id})\n\n";
    
    // Decodificar configuración JSON
    $configuracion = json_decode($config->configuracion, true);
    
    echo "Datos almacenados:\n";
    echo "─────────────────────────────────────────────────\n";
    
    if (is_array($configuracion)) {
        foreach ($configuracion as $key => $value) {
            if (stripos($key, 'token') !== false || stripos($key, 'password') !== false) {
                echo "  {$key}: " . substr($value, 0, 20) . "...\n";
            } else {
                echo "  {$key}: {$value}\n";
            }
        }
    } else {
        echo "  configuracion: " . ($config->configuracion ?? 'NULL') . "\n";
    }
    
    echo "\n  activo: " . ($config->activo ? 'SÍ' : 'NO') . "\n";
    echo "  configurado: " . ($config->configurado ? 'SÍ' : 'NO') . "\n";
    echo "  ultima_prueba: " . ($config->ultima_prueba ?? 'NUNCA') . "\n";
    echo "  resultado_prueba: " . ($config->resultado_prueba ?? 'N/A') . "\n";
    
    // Comparar con empresa
    echo "\n═══════════════════════════════════════════════════════════\n";
    echo "  COMPARACIÓN CON TABLA EMPRESAS\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    $empresa = DB::table('empresas')->first();
    
    $emailConfig = $configuracion['ALEGRA_USER'] ?? $configuracion['email'] ?? $configuracion['user'] ?? null;
    $tokenConfig = $configuracion['ALEGRA_TOKEN'] ?? $configuracion['token'] ?? $configuracion['api_token'] ?? null;
    
    echo "Email en config_facturacion: " . ($emailConfig ?? 'NO ENCONTRADO') . "\n";
    echo "Email en empresas: {$empresa->alegra_email}\n\n";
    
    if ($emailConfig && $emailConfig != $empresa->alegra_email) {
        echo "❌ LOS EMAILS SON DIFERENTES!\n";
        echo "   Config tiene: {$emailConfig}\n";
        echo "   Empresa tiene: {$empresa->alegra_email}\n\n";
        echo "   Esto explica por qué ves facturas de otra empresa.\n\n";
    } else {
        echo "✅ Los emails coinciden o no hay email en config\n\n";
    }
    
    // Preguntar si borrar
    echo "═══════════════════════════════════════════════════════════\n";
    echo "  ACCIONES DISPONIBLES\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    echo "1. Borrar esta configuración (RECOMENDADO)\n";
    echo "   → El sistema usará solo la tabla 'empresas'\n\n";
    echo "2. Actualizar con credenciales correctas\n";
    echo "   → Sincronizar con tabla 'empresas'\n\n";
    echo "3. No hacer nada\n\n";
    
    echo "Selecciona una opción (1/2/3): ";
    $opcion = trim(fgets(STDIN));
    
    switch ($opcion) {
        case '1':
            DB::table('configuracion_facturacion')->where('id', $config->id)->delete();
            echo "\n✅ Configuración eliminada\n";
            echo "   El sistema ahora usará SOLO la tabla 'empresas'\n";
            break;
            
        case '2':
            $nuevaConfig = [
                'ALEGRA_USER' => $empresa->alegra_email,
                'ALEGRA_TOKEN' => $empresa->alegra_token
            ];
            
            DB::table('configuracion_facturacion')
                ->where('id', $config->id)
                ->update([
                    'configuracion' => json_encode($nuevaConfig),
                    'updated_at' => now()
                ]);
            
            echo "\n✅ Configuración actualizada con credenciales de 'empresas'\n";
            break;
            
        case '3':
            echo "\n❌ No se realizó ninguna acción\n";
            break;
            
        default:
            echo "\n❌ Opción inválida\n";
    }
    
} else {
    echo "❌ No hay configuración de Alegra en configuracion_facturacion\n";
    echo "   El sistema usará la tabla 'empresas' (correcto)\n";
}
