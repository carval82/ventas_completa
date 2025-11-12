    /**
     * Abre una factura directamente usando la API de Alegra con cURL
     * @param string $idFactura ID de la factura en Alegra
     * @return array
     */
    public function abrirFacturaDirecto($idFactura)
    {
        try {
            Log::info('Iniciando apertura directa de factura con cURL', [
                'id_factura' => $idFactura
            ]);
            
            // Obtener credenciales
            $credenciales = $this->obtenerCredencialesAlegra();
            if (!$credenciales['success']) {
                return $credenciales;
            }
            
            $email = $credenciales['email'];
            $token = $credenciales['token'];
            
            // Configurar cURL
            $ch = curl_init();
            $url = "https://api.alegra.com/api/v1/invoices/{$idFactura}/open";
            
            // Datos con el formato correcto para abrir la factura
            $datos = json_encode([
                'paymentForm' => 'CASH',
                'paymentMethod' => 'CASH'
            ]);
            
            // Configurar opciones de cURL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $datos);
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
                'http_code' => $httpCode,
                'response' => $response,
                'error' => $error
            ]);
            
            // Procesar la respuesta
            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                return [
                    'success' => true,
                    'message' => 'Factura abierta correctamente',
                    'data' => $data
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Error al abrir la factura',
                'error' => $response
            ];
        } catch (\Exception $e) {
            Log::error('Excepción al abrir factura directamente: ' . $e->getMessage(), [
                'id_factura' => $idFactura
            ]);
            
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
