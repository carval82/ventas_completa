<?php
// Script de prueba de Alegra para acceso directo desde la URL
header('Content-Type: application/json');

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

// Probar conexión con Alegra
$resultado = hacerSolicitud("$baseUrl/company", $email, $token);

if ($resultado['http_code'] >= 200 && $resultado['http_code'] < 300) {
    // Conexión exitosa
    $resolucion = [
        'texto' => 'Resolución DIAN N° 18764083087981 - Prefijo FEV',
        'prefijo' => 'FEV',
        'id' => '19',
        'numero_resolucion' => '18764083087981',
        'fecha_inicio' => '08/11/2024',
        'fecha_fin' => '08/11/2026'
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Conexión exitosa con Alegra. Se está utilizando la resolución configurada manualmente.',
        'resolucion' => $resolucion
    ]);
} else {
    // Error de conexión
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo conectar con Alegra: ' . ($resultado['error'] ?: 'Error ' . $resultado['http_code'])
    ]);
}
?>
