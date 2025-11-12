<?php
// Script para actualizar las credenciales de Alegra en la base de datos
require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Configurar conexión a la base de datos
$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'ventas_completa',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

// Hacer que Eloquent esté disponible globalmente
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Credenciales correctas
$email = "anitasape1982@gmail.com";
$token = "b8ca29dbb62a079643f5";

// Actualizar las credenciales en la base de datos
try {
    Capsule::table('empresas')
        ->where('id', 1)
        ->update([
            'alegra_email' => $email,
            'alegra_token' => $token,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    
    echo "Credenciales de Alegra actualizadas correctamente en la base de datos.\n";
    echo "Email: $email\n";
    echo "Token: " . substr($token, 0, 3) . '...' . substr($token, -3) . "\n";
} catch (Exception $e) {
    echo "Error al actualizar las credenciales: " . $e->getMessage() . "\n";
}

// Obtener plantillas de numeración y configurar la resolución
$baseUrl = "https://api.alegra.com/api/v1";
$url = "$baseUrl/number-templates";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($email . ':' . $token)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

if ($httpCode == 200) {
    echo "Plantillas de numeración obtenidas correctamente.\n";
    
    $templates = json_decode($response, true);
    
    // Buscar plantilla de facturación electrónica activa
    $electronicTemplate = null;
    
    foreach ($templates as $template) {
        if (isset($template['isElectronic']) && $template['isElectronic'] === true && 
            isset($template['status']) && $template['status'] === 'active') {
            $electronicTemplate = $template;
            break;
        }
    }
    
    if ($electronicTemplate) {
        echo "Se encontró una plantilla de facturación electrónica activa:\n";
        echo "ID: " . $electronicTemplate['id'] . "\n";
        echo "Nombre: " . $electronicTemplate['name'] . "\n";
        echo "Prefijo: " . $electronicTemplate['prefix'] . "\n";
        
        // Formatear los datos de la resolución
        $resolucion = [
            'texto' => 'Resolución DIAN N° ' . ($electronicTemplate['resolutionNumber'] ?? '18764083087981') . ' - Prefijo: ' . $electronicTemplate['prefix'],
            'prefijo' => $electronicTemplate['prefix'],
            'id' => $electronicTemplate['id'],
            'numero_resolucion' => $electronicTemplate['resolutionNumber'] ?? '18764083087981',
            'fecha_inicio' => $electronicTemplate['startDate'] ?? '2024-11-08',
            'fecha_fin' => $electronicTemplate['endDate'] ?? '2026-11-08'
        ];
        
        // Actualizar la empresa con los datos de la resolución
        try {
            Capsule::table('empresas')
                ->where('id', 1)
                ->update([
                    'resolucion_facturacion' => json_encode($resolucion),
                    'prefijo_factura' => $electronicTemplate['prefix'],
                    'id_resolucion_alegra' => $electronicTemplate['id'],
                    'fecha_resolucion' => $electronicTemplate['startDate'] ?? '2024-11-08',
                    'fecha_vencimiento_resolucion' => $electronicTemplate['endDate'] ?? '2026-11-08',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            
            echo "Resolución de facturación electrónica configurada correctamente.\n";
        } catch (Exception $e) {
            echo "Error al configurar la resolución: " . $e->getMessage() . "\n";
        }
    } else {
        echo "No se encontró una plantilla de facturación electrónica activa.\n";
        echo "Configurando valores predeterminados para la resolución...\n";
        
        // Configurar resolución predeterminada
        $resolucion = [
            'texto' => 'Resolución DIAN N° 18764083087981 - Prefijo: FEV',
            'prefijo' => 'FEV',
            'id' => '1',
            'numero_resolucion' => '18764083087981',
            'fecha_inicio' => '2024-11-08',
            'fecha_fin' => '2026-11-08'
        ];
        
        try {
            Capsule::table('empresas')
                ->where('id', 1)
                ->update([
                    'resolucion_facturacion' => json_encode($resolucion),
                    'prefijo_factura' => 'FEV',
                    'id_resolucion_alegra' => '1',
                    'fecha_resolucion' => '2024-11-08',
                    'fecha_vencimiento_resolucion' => '2026-11-08',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            
            echo "Resolución de facturación electrónica predeterminada configurada correctamente.\n";
        } catch (Exception $e) {
            echo "Error al configurar la resolución predeterminada: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "Error al obtener plantillas de numeración. Código HTTP: $httpCode\n";
    echo "Error: $error\n";
    echo "Respuesta: " . substr($response, 0, 100) . "\n";
}

echo "\nProceso completado.\n";
?>
