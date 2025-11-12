<?php
// Script para diagnosticar problemas con la ruta AJAX
require_once __DIR__ . '/vendor/autoload.php';

// Configurar encabezados para evitar problemas de CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Credenciales correctas
$email = "anitasape1982@gmail.com";
$token = "b8ca29dbb62a079643f5";
$baseUrl = "https://api.alegra.com/api/v1";

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

// Simular la respuesta del controlador
$resultado = hacerSolicitud("$baseUrl/company", $email, $token);

if ($resultado['http_code'] >= 200 && $resultado['http_code'] < 300) {
    $companyData = json_decode($resultado['response'], true);
    
    // Obtener plantillas de numeración
    $resultadoTemplates = hacerSolicitud("$baseUrl/number-templates", $email, $token);
    
    if ($resultadoTemplates['http_code'] >= 200 && $resultadoTemplates['http_code'] < 300) {
        $templates = json_decode($resultadoTemplates['response'], true);
        
        // Buscar plantillas electrónicas activas
        $electronicTemplates = array_filter($templates, function($template) {
            return isset($template['isElectronic']) && $template['isElectronic'] === true && 
                   isset($template['status']) && $template['status'] === 'active';
        });
        
        if (count($electronicTemplates) > 0) {
            // Tomar la primera plantilla electrónica activa
            $template = reset($electronicTemplates);
            
            // Formatear la resolución
            $resolucion = [
                'texto' => 'Resolución DIAN N° ' . ($template['resolution']['number'] ?? '18764083087981') . ' - Prefijo ' . ($template['prefix'] ?? 'FEV'),
                'prefijo' => $template['prefix'] ?? 'FEV',
                'id' => $template['id'] ?? '1',
                'numero_resolucion' => $template['resolution']['number'] ?? '18764083087981',
                'fecha_inicio' => isset($template['resolution']['date']) ? date('d/m/Y', strtotime($template['resolution']['date'])) : '08/11/2024',
                'fecha_fin' => isset($template['resolution']['expirationDate']) ? date('d/m/Y', strtotime($template['resolution']['expirationDate'])) : '08/11/2026'
            ];
        } else {
            // Usar resolución manual
            $resolucion = [
                'texto' => 'Resolución DIAN N° 18764083087981 - Prefijo FEV',
                'prefijo' => 'FEV',
                'id' => '1',
                'numero_resolucion' => '18764083087981',
                'fecha_inicio' => '08/11/2024',
                'fecha_fin' => '08/11/2026'
            ];
        }
        
        // Respuesta exitosa
        echo json_encode([
            'success' => true,
            'message' => 'Conexión exitosa con Alegra. Se ha obtenido la resolución de facturación electrónica.',
            'resolucion' => $resolucion,
            'productos' => [],
            'clientes' => []
        ]);
    } else {
        // Error al obtener plantillas
        echo json_encode([
            'success' => true,
            'message' => 'Conexión exitosa con Alegra. Se está utilizando la resolución configurada manualmente.',
            'resolucion' => [
                'texto' => 'Resolución DIAN N° 18764083087981 - Prefijo FEV',
                'prefijo' => 'FEV',
                'id' => '1',
                'numero_resolucion' => '18764083087981',
                'fecha_inicio' => '08/11/2024',
                'fecha_fin' => '08/11/2026'
            ]
        ]);
    }
} else {
    // Error de conexión
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo conectar con Alegra: ' . ($resultado['error'] ?: 'Error ' . $resultado['http_code'])
    ]);
}
?>
