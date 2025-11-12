<?php

// Cargar el entorno de Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Empresa;
use Illuminate\Support\Facades\Log;

// Verificar si se proporcionaron credenciales por línea de comandos
$email = $argv[1] ?? null;
$token = $argv[2] ?? null;

if (!$email || !$token) {
    echo "Uso: php cambiar_credenciales_alegra.php <email> <token>\n";
    echo "Ejemplo: php cambiar_credenciales_alegra.php usuario@ejemplo.com mi_token_secreto\n";
    exit(1);
}

try {
    // Buscar la empresa
    $empresa = Empresa::first();
    
    if (!$empresa) {
        echo "No hay empresa configurada. Creando una nueva...\n";
        $empresa = new Empresa();
        $empresa->nombre_comercial = 'Mi Empresa';
        $empresa->razon_social = 'Mi Empresa S.A.S.';
        $empresa->nit = '123456789';
        $empresa->direccion = 'Dirección de la empresa';
        $empresa->telefono = '123456789';
        $empresa->regimen_tributario = 'responsable_iva';
    }
    
    // Configurar credenciales de Alegra
    $empresa->alegra_email = $email;
    $empresa->alegra_token = $token;
    
    // Guardar cambios
    $empresa->save();
    
    echo "Credenciales de Alegra configuradas correctamente.\n";
    echo "Email: " . $empresa->alegra_email . "\n";
    echo "Token: " . substr($empresa->alegra_token, 0, 3) . '...' . substr($empresa->alegra_token, -3) . "\n";
    
    // Probar conexión con Alegra
    $alegraService = new App\Services\AlegraService($email, $token);
    $resultado = $alegraService->probarConexion();
    
    echo "\nPrueba de conexión con Alegra:\n";
    echo "Éxito: " . ($resultado['success'] ? 'Sí' : 'No') . "\n";
    
    if (!$resultado['success']) {
        echo "Error: " . ($resultado['error'] ?? $resultado['message'] ?? 'Error desconocido') . "\n";
    } else {
        echo "Conexión exitosa con Alegra.\n";
        
        // Intentar obtener resolución
        $resolucion = $alegraService->obtenerResolucionPreferida('electronica');
        echo "\nResolución de facturación electrónica:\n";
        echo "Éxito: " . ($resolucion['success'] ? 'Sí' : 'No') . "\n";
        
        if ($resolucion['success']) {
            echo "ID: " . ($resolucion['data']['id'] ?? 'No disponible') . "\n";
            echo "Prefijo: " . ($resolucion['data']['prefix'] ?? 'No disponible') . "\n";
            echo "Estado: " . ($resolucion['data']['status'] ?? 'No disponible') . "\n";
            
            // Guardar información de resolución
            $empresa->id_resolucion_alegra = $resolucion['data']['id'] ?? null;
            $empresa->prefijo_factura = $resolucion['data']['prefix'] ?? null;
            $empresa->save();
            
            echo "\nInformación de resolución guardada en la empresa.\n";
        } else {
            echo "Error al obtener resolución: " . ($resolucion['error'] ?? $resolucion['message'] ?? 'Error desconocido') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    Log::error('Error en script de configuración Alegra: ' . $e->getMessage());
}
