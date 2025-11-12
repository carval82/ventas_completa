<?php

// Importar las clases necesarias
require_once __DIR__ . '/../vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Support\Facades\DB;

echo "=== ACTUALIZACIÓN MANUAL DE ID_ALEGRA EN CLIENTE ===\n\n";

// Obtener el cliente
$cliente = Cliente::first();

if (!$cliente) {
    echo "❌ No se encontraron clientes en la base de datos local.\n";
    exit(1);
}

echo "Cliente encontrado: " . $cliente->nombres . " " . $cliente->apellidos . " (ID: " . $cliente->id . ")\n";
echo "Cédula: " . $cliente->cedula . "\n\n";

// Verificar si ya tiene un ID de Alegra
if (!empty($cliente->id_alegra)) {
    echo "⚠️ El cliente ya tiene un ID de Alegra: " . $cliente->id_alegra . ". El script continuará para verificar y sincronizar si es necesario.\n";
    fflush(STDOUT);
}

// Obtener credenciales de Alegra
$empresa = Empresa::first();

if (!$empresa || empty($empresa->alegra_email) || empty($empresa->alegra_token)) {
    echo "❌ No se encontraron credenciales de Alegra en la empresa.\n";
    exit(1);
}

$email = $empresa->alegra_email;
$token = $empresa->alegra_token;

echo "Credenciales de Alegra obtenidas correctamente.\n";
echo "Email: " . $email . "\n";
echo "Token: " . substr($token, 0, 3) . '...' . substr($token, -3) . "\n\n";

// Verificar si el cliente existe en Alegra buscando por su identificación
echo "Verificando si el cliente existe en Alegra...\n";

// Configurar cURL
$ch = curl_init();
$url = 'https://api.alegra.com/api/v1/contacts?identification=' . urlencode($cliente->cedula);

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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

echo "Código de respuesta HTTP: " . $httpCode . "\n";

if ($error) {
    echo "Error cURL (búsqueda): " . $error . "\n";
    fflush(STDOUT);
    exit(1);
}

if ($httpCode >= 200 && $httpCode < 300) {
    $clientesAlegra = json_decode($response, true);
    $clienteEncontradoEnAlegra = false; // Inicializada antes de verificar si $clientesAlegra está vacío

    if (!empty($clientesAlegra)) {
        
        foreach ($clientesAlegra as $clienteAlegraItem) { // Renombrar para evitar confusión con $cliente (local)
            if (isset($clienteAlegraItem['identification']) && $clienteAlegraItem['identification'] == $cliente->cedula) { // Corregido: usar $clienteAlegraItem
                $clienteEncontradoEnAlegra = true;
                $idAlegra = $clienteAlegraItem['id'];
                
                echo "✅ Cliente encontrado en Alegra con ID: " . $idAlegra . "\n";
fflush(STDOUT);
                echo "Nombre en Alegra: " . $clienteAlegraItem['name'] . "\n";
fflush(STDOUT);
                
                // Actualizar el ID de Alegra en el cliente local usando SQL directo
                try {
                    // Primero intentar con Eloquent
                    $cliente->id_alegra = $idAlegra;
                    $resultado = $cliente->save();
                    
                    echo "Resultado de actualización con Eloquent: " . ($resultado ? "Éxito" : "Fallo") . "\n";
fflush(STDOUT);
                    
                    // Verificar si se actualizó correctamente
                    $clienteActualizado = Cliente::find($cliente->id);
                    
                    if ($clienteActualizado->id_alegra == $idAlegra) {
                        echo "✅ Cliente actualizado correctamente con Eloquent.\n";
fflush(STDOUT);
                    } else {
                        echo "❌ La actualización con Eloquent no se reflejó en la base de datos.\n";
                        echo "Intentando con SQL directo...\n";
                        
                        // Intentar con SQL directo
                        $afectados = DB::update('update clientes set id_alegra = ? where id = ?', [$idAlegra, $cliente->id]);
                        
                        echo "Filas afectadas con SQL directo: " . $afectados . "\n";
fflush(STDOUT);
                        
                        // Verificar nuevamente
                        $clienteVerificacion = Cliente::find($cliente->id);
                        
                        if ($clienteVerificacion->id_alegra == $idAlegra) {
                            echo "✅ Cliente actualizado correctamente con SQL directo.\n";
fflush(STDOUT);
                        } else {
                            echo "❌ La actualización con SQL directo tampoco funcionó.\n";
                            echo "Último intento con PDO directo...\n";
                            fflush(STDOUT);
                            
                            // Intentar con PDO directo
                            $pdo = DB::connection()->getPdo();
                            $stmt = $pdo->prepare('UPDATE clientes SET id_alegra = :id_alegra WHERE id = :id');
                            $resultado = $stmt->execute([
                                ':id_alegra' => $idAlegra,
                                ':id' => $cliente->id
                            ]);
                            
                            echo "Resultado de PDO: " . ($resultado ? "Éxito" : "Fallo") . "\n";
fflush(STDOUT);
                        }
                    }
                    
                    // Verificar que el formato para enviar a Alegra en una factura es correcto
                    echo "\nFormato correcto para incluir este cliente en una factura:\n";
                    echo "{ \"client\": { \"id\": " . $idAlegra . " } }\n";
                    
                    // Verificar estado actual de sincronización
                    $totalClientes = Cliente::count();
                    $clientesSincronizados = Cliente::whereNotNull('id_alegra')->where('id_alegra', '!=', '')->count();
                    $porcentaje = round(($clientesSincronizados / $totalClientes) * 100, 2);
                    
                    echo "\nEstado actual: {$clientesSincronizados}/{$totalClientes} clientes sincronizados ({$porcentaje}%)\n";
                    
                } catch (\Exception $e) {
                    echo "❌ Error al actualizar el cliente: " . $e->getMessage() . "\n";
                }
                
                break;
            }
        }
        // Esta llave cierra el foreach ($clientesAlegra as $clienteAlegraItem)
    } // Esta llave cierra el if (!empty($clientesAlegra))

    // Ahora, fuera del bloque if (!empty($clientesAlegra)), verificamos si se encontró y, si no, creamos.
    if (!$clienteEncontradoEnAlegra) {
            echo "\nℹ️ Cliente con cédula " . $cliente->cedula . " no encontrado en Alegra. Procediendo a crear...\n";
            fflush(STDOUT);
            
            // Preparar datos del cliente para Alegra
            $datos = [
                'name' => $cliente->nombres . ' ' . $cliente->apellidos,
                'nameObject' => [
                    'firstName' => $cliente->nombres,
                    'lastName' => $cliente->apellidos,
                ],
                'identification' => $cliente->cedula,
                'email' => $cliente->email ?: 'sin@email.com',
                'phonePrimary' => $cliente->telefono ?: '0000000000',
                'address' => [
                    'address' => $cliente->direccion ?? 'Sin dirección'
                ],
                'type' => 'client',
                'kindOfPerson' => 'PERSON_ENTITY',
                'regime' => 'SIMPLIFIED_REGIME'
            ];
            
            echo "Datos a enviar a Alegra para creación:\n";
            echo json_encode($datos, JSON_PRETTY_PRINT) . "\n\n";
            fflush(STDOUT);
            
            // Configurar cURL
            $ch = curl_init();
            $url = 'https://api.alegra.com/api/v1/contacts';
            
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
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
            
            echo "Código de respuesta HTTP (creación): " . $httpCode . "\n";
            fflush(STDOUT);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
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
                
                echo "Código de respuesta HTTP (creación): " . $httpCode . "\n";
                fflush(STDOUT);

                if ($error) {
                    echo "Error cURL (creación): " . $error . "\n";
                    fflush(STDOUT);
                }
                
                if ($httpCode >= 200 && $httpCode < 300) {
                    $data = json_decode($response, true);
                    
                    if (isset($data['id'])) {
                        $idAlegra = $data['id'];
                        echo "✅ Cliente creado exitosamente en Alegra con ID: " . $idAlegra . "\n";
                        fflush(STDOUT);
                        
                        // Actualizar el ID de Alegra en el cliente local
                        try {
                            $cliente->id_alegra = $idAlegra;
                            $cliente->save();
                            
                            echo "✅ Cliente actualizado localmente con ID de Alegra: " . $cliente->id_alegra . "\n";
                            fflush(STDOUT);
                            
                            // Verificar que el formato para enviar a Alegra en una factura es correcto
                            echo "\nFormato correcto para incluir este cliente en una factura:\n";
                            echo "{ \"client\": { \"id\": " . $idAlegra . " } }\n";
                            
                            // Verificar estado actual de sincronización
                            $totalClientes = Cliente::count();
                            $clientesSincronizados = Cliente::whereNotNull('id_alegra')->where('id_alegra', '!=', '')->count();
                            $porcentaje = round(($clientesSincronizados / $totalClientes) * 100, 2);
                            
                            echo "\nEstado actual: {$clientesSincronizados}/{$totalClientes} clientes sincronizados ({$porcentaje}%)\n";
                        } catch (\Exception $e) {
                            echo "❌ Error al actualizar el cliente: " . $e->getMessage() . "\n";
                        }
                    } else {
                        echo "❌ Error (creación): La respuesta no contiene un ID de Alegra.\n";
                        echo "Respuesta completa (creación): " . $response . "\n";
                        fflush(STDOUT);
                    }
                } else {
                    echo "❌ Error al crear cliente en Alegra. Código HTTP: {$httpCode}\n";
                    echo "Respuesta Alegra (creación): " . $response . "\n";
                    fflush(STDOUT);
                }
            } // Cierre del if (!$clienteEncontradoEnAlegra)
                        // Fin de la lógica de creación automática si no se encontró
        // Esta llave cierra el if (!$clienteEncontradoEnAlegra) donde ocurre la creación
} // Esta es el cierre del if ($httpCode >= 200 && $httpCode < 300) de la búsqueda inicial
else {
        echo "❌ Error al buscar clientes en Alegra (HTTP Code: " . $httpCode . ")\n";
        echo "Respuesta (búsqueda): " . $response . "\n";
        fflush(STDOUT);
    } // Esta es el cierre del if (!$clienteEncontradoEnAlegra) que ahora contiene la creación si es false


echo "\nProceso de actualización/creación de cliente completado.\n";
fflush(STDOUT);
