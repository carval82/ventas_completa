<?php
// Script para diagnosticar problemas de conexión con Alegra
require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Log;

// Configuración de credenciales
$email = "anitasape1982@gmail.com";
$token = "b8ca29dbb62a079643f5";
$baseUrl = "https://api.alegra.com/api/v1";

echo "=== DIAGNÓSTICO DE CONEXIÓN CON ALEGRA ===\n\n";
echo "Usando credenciales:\n";
echo "Email: $email\n";
echo "Token: " . substr($token, 0, 3) . '...' . substr($token, -3) . "\n\n";

// Función para realizar una solicitud a la API
function hacerSolicitud($url, $email, $token, $metodo = 'GET', $datos = null) {
    $ch = curl_init();
    
    // Configurar opciones de cURL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $metodo);
    
    // Configurar encabezados
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($email . ':' . $token)
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Si hay datos para enviar (POST/PUT)
    if ($datos !== null && ($metodo === 'POST' || $metodo === 'PUT')) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
    }
    
    // Ejecutar la solicitud
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

// 1. Probar conexión básica con la API
echo "1. Probando conexión básica con la API...\n";
$resultado = hacerSolicitud("$baseUrl/company", $email, $token);

echo "Código HTTP: " . $resultado['http_code'] . "\n";
if ($resultado['error']) {
    echo "Error cURL: " . $resultado['error'] . "\n";
}

if ($resultado['http_code'] >= 200 && $resultado['http_code'] < 300) {
    echo "¡Conexión exitosa!\n";
    $data = json_decode($resultado['response'], true);
    echo "Empresa: " . $data['name'] . "\n";
    echo "Identificación: " . $data['identification'] . "\n\n";
} else {
    echo "Error en la conexión: " . $resultado['response'] . "\n\n";
    exit;
}

// 2. Probar obtener plantillas de numeración
echo "2. Probando obtener plantillas de numeración...\n";
$resultado = hacerSolicitud("$baseUrl/number-templates", $email, $token);

echo "Código HTTP: " . $resultado['http_code'] . "\n";
if ($resultado['error']) {
    echo "Error cURL: " . $resultado['error'] . "\n";
}

if ($resultado['http_code'] >= 200 && $resultado['http_code'] < 300) {
    echo "¡Solicitud exitosa!\n";
    $templates = json_decode($resultado['response'], true);
    
    if (is_array($templates)) {
        echo "Se encontraron " . count($templates) . " plantillas de numeración.\n\n";
        
        // Buscar plantillas electrónicas activas
        $electronicTemplates = array_filter($templates, function($template) {
            return isset($template['isElectronic']) && $template['isElectronic'] === true && 
                   isset($template['status']) && $template['status'] === 'active';
        });
        
        if (count($electronicTemplates) > 0) {
            echo "Plantillas electrónicas activas encontradas: " . count($electronicTemplates) . "\n";
            foreach ($electronicTemplates as $template) {
                echo "- ID: " . $template['id'] . "\n";
                echo "  Nombre: " . $template['name'] . "\n";
                echo "  Prefijo: " . ($template['prefix'] ?? 'Sin prefijo') . "\n";
                echo "  Estado: " . $template['status'] . "\n";
                echo "  Electrónica: " . ($template['isElectronic'] ? 'Sí' : 'No') . "\n\n";
            }
        } else {
            echo "No se encontraron plantillas electrónicas activas.\n";
            echo "Se usará la resolución configurada manualmente.\n\n";
        }
    } else {
        echo "Error al decodificar la respuesta JSON.\n\n";
    }
} else {
    echo "Error al obtener plantillas: " . $resultado['response'] . "\n\n";
}

// 3. Verificar la ruta de la API para probar conexión
echo "3. Verificando la ruta de la API en el controlador...\n";
echo "Ruta esperada: " . $baseUrl . "/company\n\n";

// 4. Verificar si hay problemas de CORS o red
echo "4. Verificando posibles problemas de red...\n";
$resultado = hacerSolicitud("https://www.google.com", "", "", "GET");
echo "Prueba de conexión a Google - Código HTTP: " . $resultado['http_code'] . "\n";
if ($resultado['http_code'] >= 200 && $resultado['http_code'] < 300) {
    echo "La conexión a Internet funciona correctamente.\n\n";
} else {
    echo "Posible problema de conexión a Internet.\n\n";
}

// 5. Verificar la configuración de la resolución manual
echo "5. Verificando la configuración de resolución manual...\n";
$resolucion = [
    'texto' => 'Resolución DIAN N° 18764083087981 - Prefijo FEV',
    'prefijo' => 'FEV',
    'id' => '1',
    'numero_resolucion' => '18764083087981',
    'fecha_inicio' => '08/11/2024',
    'fecha_fin' => '08/11/2026'
];

echo "Resolución manual configurada:\n";
echo "Texto: " . $resolucion['texto'] . "\n";
echo "Prefijo: " . $resolucion['prefijo'] . "\n";
echo "ID: " . $resolucion['id'] . "\n";
echo "Número: " . $resolucion['numero_resolucion'] . "\n";
echo "Fecha inicio: " . $resolucion['fecha_inicio'] . "\n";
echo "Fecha fin: " . $resolucion['fecha_fin'] . "\n\n";

echo "=== DIAGNÓSTICO COMPLETADO ===\n";
echo "Si la conexión básica con la API fue exitosa pero sigue habiendo problemas en la interfaz web, es posible que haya un problema con la ruta de la API o con el manejo de la respuesta en JavaScript.\n";
?>
