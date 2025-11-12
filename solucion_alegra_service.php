<?php
/**
 * Solución para el método abrirFacturaDirecto en AlegraService.php
 * 
 * Este archivo contiene la implementación recomendada para el método abrirFacturaDirecto
 * basada en las pruebas realizadas con la API de Alegra.
 */

/**
 * Abre una factura en Alegra (cambia estado de draft a open) usando cURL directamente
 * Intenta con múltiples formatos de payload y verifica el estado después de cada intento
 * 
 * @param string $facturaId ID de la factura en Alegra
 * @param bool $verificarEstado Si es true, verifica el estado de la factura después de cada intento
 * @param int $maxIntentosPorFormato Número máximo de intentos por cada formato de payload
 * @return array Resultado de la operación
 */
public function abrirFacturaDirecto($facturaId, $verificarEstado = true, $maxIntentosPorFormato = 2)
{
    try {
        Log::info('Iniciando apertura directa de factura con cURL', [
            'factura_id' => $facturaId,
            'verificar_estado' => $verificarEstado
        ]);
        
        // Obtener credenciales
        $empresa = Empresa::first();
        $email = $empresa->alegra_email;
        $token = $empresa->alegra_token;
        
        // Verificar el estado actual de la factura
        if ($verificarEstado) {
            $estadoInicial = $this->consultarEstadoFactura($facturaId);
            
            if (!$estadoInicial['success']) {
                Log::error('Error al consultar el estado inicial de la factura', [
                    'factura_id' => $facturaId,
                    'error' => $estadoInicial['message'] ?? 'Error desconocido'
                ]);
                
                return $estadoInicial;
            }
            
            // Si la factura ya está en estado 'open', no es necesario cambiarla
            if ($estadoInicial['data']['status'] === 'open') {
                Log::info('La factura ya está en estado open, no es necesario cambiarla', [
                    'factura_id' => $facturaId
                ]);
                
                return [
                    'success' => true,
                    'message' => 'La factura ya está en estado open',
                    'data' => $estadoInicial['data']
                ];
            }
            
            // Si la factura no está en estado 'draft', puede haber problemas al cambiarla
            if ($estadoInicial['data']['status'] !== 'draft') {
                Log::warning('La factura no está en estado draft, puede haber problemas al cambiarla', [
                    'factura_id' => $facturaId,
                    'estado_actual' => $estadoInicial['data']['status']
                ]);
            }
        }
        
        // Formatos de payload a intentar, en orden de preferencia
        $formatos = [
            'completo' => [
                'paymentForm' => 'CASH',
                'paymentMethod' => 'CASH'
            ],
            'minimo' => [
                'paymentForm' => 'CASH'
            ],
            'vacio' => []
        ];
        
        // Intentar cada formato con múltiples intentos
        foreach ($formatos as $formato => $datosFormato) {
            for ($intento = 1; $intento <= $maxIntentosPorFormato; $intento++) {
                Log::info('Intentando abrir factura con formato', [
                    'factura_id' => $facturaId,
                    'formato' => $formato,
                    'intento' => $intento,
                    'datos' => $datosFormato
                ]);
                
                // Preparar los datos
                $datos = json_encode($datosFormato);
                
                // Configurar cURL
                $ch = curl_init();
                $url = "https://api.alegra.com/api/v1/invoices/{$facturaId}/open";
                
                // Configurar opciones de cURL
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $datos);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15); // 15 segundos máximo
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 5 segundos para conectar
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization: Basic ' . base64_encode($email . ':' . $token)
                ]);
                
                // Ejecutar la solicitud
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                
                curl_close($ch);
                
                // Registrar la respuesta
                Log::info('Respuesta de apertura de factura con cURL', [
                    'factura_id' => $facturaId,
                    'formato' => $formato,
                    'http_code' => $httpCode,
                    'error' => $error,
                    'response' => $response
                ]);
                
                // Si la solicitud fue exitosa, verificar el estado actual
                if ($httpCode >= 200 && $httpCode < 300) {
                    // Esperar un momento para que el cambio se aplique
                    sleep(1);
                    
                    // Verificar el estado actual de la factura
                    if ($verificarEstado) {
                        $nuevoEstado = $this->consultarEstadoFactura($facturaId);
                        
                        if ($nuevoEstado['success'] && $nuevoEstado['data']['status'] === 'open') {
                            Log::info('Factura abierta correctamente', [
                                'factura_id' => $facturaId,
                                'formato_exitoso' => $formato
                            ]);
                            
                            return [
                                'success' => true,
                                'message' => 'Factura abierta correctamente',
                                'data' => $nuevoEstado['data'],
                                'formato_exitoso' => $formato
                            ];
                        }
                        
                        Log::warning('La solicitud fue exitosa pero la factura no cambió a estado open', [
                            'factura_id' => $facturaId,
                            'formato' => $formato,
                            'estado_actual' => $nuevoEstado['data']['status'] ?? 'desconocido'
                        ]);
                    } else {
                        // Si no verificamos el estado, asumimos que fue exitoso
                        Log::info('Solicitud exitosa, asumiendo que la factura se abrió correctamente', [
                            'factura_id' => $facturaId,
                            'formato' => $formato
                        ]);
                        
                        return [
                            'success' => true,
                            'message' => 'Solicitud exitosa, asumiendo que la factura se abrió correctamente',
                            'http_code' => $httpCode,
                            'response' => $response
                        ];
                    }
                } else {
                    Log::warning('Error al abrir factura', [
                        'factura_id' => $facturaId,
                        'formato' => $formato,
                        'http_code' => $httpCode,
                        'error' => $error,
                        'response' => $response
                    ]);
                }
            }
        }
        
        // Si llegamos aquí, intentar un método alternativo: actualizar la factura directamente
        Log::info('Intentando método alternativo: actualizar la factura directamente', [
            'factura_id' => $facturaId
        ]);
        
        // Configurar cURL para actualizar la factura directamente
        $ch = curl_init();
        $url = "https://api.alegra.com/api/v1/invoices/{$facturaId}";
        
        // Payload para actualizar directamente
        $datosDirectos = json_encode([
            'status' => 'open',
            'paymentForm' => 'CASH',
            'paymentMethod' => 'CASH'
        ]);
        
        // Configurar opciones de cURL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $datosDirectos);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($email . ':' . $token)
        ]);
        
        // Ejecutar la solicitud
        $responseDirecto = curl_exec($ch);
        $httpCodeDirecto = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorDirecto = curl_error($ch);
        
        curl_close($ch);
        
        // Registrar la respuesta
        Log::info('Respuesta de actualización directa de factura', [
            'factura_id' => $facturaId,
            'http_code' => $httpCodeDirecto,
            'error' => $errorDirecto,
            'response' => $responseDirecto
        ]);
        
        // Si la solicitud fue exitosa, verificar el estado actual
        if ($httpCodeDirecto >= 200 && $httpCodeDirecto < 300) {
            // Esperar un momento para que el cambio se aplique
            sleep(1);
            
            // Verificar el estado actual de la factura
            if ($verificarEstado) {
                $estadoFinal = $this->consultarEstadoFactura($facturaId);
                
                if ($estadoFinal['success'] && $estadoFinal['data']['status'] === 'open') {
                    Log::info('Factura abierta correctamente mediante actualización directa', [
                        'factura_id' => $facturaId
                    ]);
                    
                    return [
                        'success' => true,
                        'message' => 'Factura abierta correctamente mediante actualización directa',
                        'data' => $estadoFinal['data']
                    ];
                }
                
                Log::warning('La actualización directa fue exitosa pero la factura no cambió a estado open', [
                    'factura_id' => $facturaId,
                    'estado_actual' => $estadoFinal['data']['status'] ?? 'desconocido'
                ]);
            } else {
                // Si no verificamos el estado, asumimos que fue exitoso
                Log::info('Actualización directa exitosa, asumiendo que la factura se abrió correctamente', [
                    'factura_id' => $facturaId
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Actualización directa exitosa, asumiendo que la factura se abrió correctamente',
                    'http_code' => $httpCodeDirecto,
                    'response' => $responseDirecto
                ];
            }
        }
        
        // Si llegamos aquí, ninguno de los métodos funcionó
        Log::error('No se pudo abrir la factura con ninguno de los métodos intentados', [
            'factura_id' => $facturaId,
            'formatos_intentados' => array_keys($formatos),
            'metodo_alternativo' => 'actualización directa'
        ]);
        
        return [
            'success' => false,
            'message' => 'No se pudo abrir la factura con ninguno de los métodos intentados',
            'formatos_intentados' => array_keys($formatos),
            'metodo_alternativo' => 'actualización directa',
            'recomendacion' => 'Verificar permisos en Alegra o contactar soporte'
        ];
    } catch (\Exception $e) {
        Log::error('Excepción al abrir factura directamente', [
            'factura_id' => $facturaId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
