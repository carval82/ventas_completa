<?php

namespace App\Http\Controllers;

use App\Services\FacturacionElectronicaFactory;
use App\Models\Venta;
use App\Models\ConfiguracionFacturacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class FacturacionElectronicaController extends Controller
{
    /**
     * Mostrar configuración de proveedores
     */
    public function index()
    {
        $proveedores = FacturacionElectronicaFactory::getProveedoresDisponibles();
        $proveedorActivo = ConfiguracionFacturacion::getProveedorActivo();
        $proveedorActivoNombre = $proveedorActivo ? $proveedorActivo->proveedor : 'ninguno';
        
        // Obtener configuraciones guardadas
        $configuracionesGuardadas = ConfiguracionFacturacion::all()->keyBy('proveedor');
        
        // Obtener estado de configuración de cada proveedor
        foreach ($proveedores as $key => &$proveedor) {
            $configGuardada = $configuracionesGuardadas->get($key);
            
            if ($configGuardada) {
                $proveedor['configurado'] = $configGuardada->configurado;
                $proveedor['config_actual'] = $configGuardada->configuracion;
                $proveedor['ultima_prueba'] = $configGuardada->ultima_prueba;
                $proveedor['resultado_prueba'] = $configGuardada->resultado_prueba;
            } else {
                $proveedor['configurado'] = false;
                $proveedor['config_actual'] = [];
            }
        }

        return view('facturacion.index', compact('proveedores', 'proveedorActivoNombre', 'proveedorActivo'));
    }

    /**
     * Cambiar proveedor activo
     */
    public function cambiarProveedor(Request $request)
    {
        $request->validate([
            'proveedor' => 'required|string|in:alegra,dian,siigo,worldoffice'
        ]);

        $proveedor = $request->proveedor;

        // Verificar que el proveedor esté disponible
        if (!FacturacionElectronicaFactory::isProveedorDisponible($proveedor)) {
            return redirect()->back()->with('error', 'El proveedor seleccionado no está disponible');
        }

        // Verificar que esté configurado
        $config = ConfiguracionFacturacion::where('proveedor', $proveedor)->first();
        if (!$config || !$config->configurado) {
            return redirect()->back()->with('error', 'El proveedor seleccionado no está configurado correctamente');
        }

        try {
            // Activar proveedor
            ConfiguracionFacturacion::activarProveedor($proveedor);
            
            $proveedorNombre = FacturacionElectronicaFactory::getProveedoresDisponibles()[$proveedor]['nombre'];
            return redirect()->back()->with('success', "Proveedor cambiado exitosamente a: {$proveedorNombre}");

        } catch (\Exception $e) {
            Log::error('Error al cambiar proveedor', [
                'proveedor' => $proveedor,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Error al cambiar proveedor: ' . $e->getMessage());
        }
    }

    /**
     * Enviar factura usando proveedor activo
     */
    public function enviarFactura(Request $request, $ventaId)
    {
        try {
            $venta = Venta::with(['detalles.producto', 'cliente'])->findOrFail($ventaId);
            
            $proveedor = $request->input('proveedor') ?? config('facturacion.proveedor_activo');
            $module = FacturacionElectronicaFactory::create($proveedor);

            // Preparar datos
            $facturaData = [
                'id' => $venta->id,
                'numero' => $venta->numero_factura,
                'fecha' => $venta->fecha_venta,
                'cliente' => $venta->cliente,
                'detalles' => $venta->detalles,
                'total' => $venta->total,
                'subtotal' => $venta->subtotal,
                'iva' => $venta->iva,
                'descuento' => $venta->descuento ?? 0
            ];

            $resultado = $module->enviarFactura($facturaData);

            if ($resultado['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $resultado['mensaje'] ?? 'Factura enviada exitosamente',
                    'proveedor' => $module->getNombreProveedor(),
                    'data' => $resultado
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $resultado['error'] ?? 'Error al enviar factura',
                    'proveedor' => $module->getNombreProveedor()
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error en envío de factura', [
                'venta_id' => $ventaId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincronizar productos desde el proveedor hacia nuestra aplicación
     */
    public function sincronizarProductos(Request $request)
    {
        $request->validate([
            'proveedor' => 'required|string|in:alegra,dian,siigo,worldoffice'
        ]);

        $proveedor = $request->proveedor;

        try {
            $resultado = $this->sincronizarProductosDesdeProveedor($proveedor);
            
            return response()->json([
                'success' => true,
                'message' => 'Productos sincronizados exitosamente',
                'data' => $resultado
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Probar conexión con proveedor - usando método probado del EmpresaController
     */
    public function probarConexion(Request $request)
    {
        $request->validate([
            'proveedor' => 'required|string|in:alegra,dian,siigo,worldoffice'
        ]);

        $proveedor = $request->proveedor;

        try {
            if ($proveedor === 'alegra') {
                // Para Alegra, usar el método probado del EmpresaController
                return $this->probarConexionAlegraDirecto($request);
            }
            
            // Para otros proveedores, usar el método original
            $config = ConfiguracionFacturacion::where('proveedor', $proveedor)->first();
            if (!$config || !$config->configurado) {
                return response()->json([
                    'success' => false,
                    'message' => 'El proveedor no está configurado. Configure primero las credenciales.'
                ], 400);
            }

            $resultado = $this->probarConexionEspecifica(null, $proveedor, $config->configuracion);
            
            $config->update([
                'ultima_prueba' => now(),
                'resultado_prueba' => $resultado
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Conexión exitosa con ' . ucfirst($proveedor),
                'data' => $resultado
            ]);

        } catch (\Exception $e) {
            Log::error('Error probando conexión', [
                'proveedor' => $proveedor,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Probar conexión con Alegra usando el método del EmpresaController
     */
    private function probarConexionAlegraDirecto($request)
    {
        // Obtener configuración de Alegra
        $config = ConfiguracionFacturacion::where('proveedor', 'alegra')->first();
        
        if (!$config || !$config->configurado) {
            return response()->json([
                'success' => false,
                'message' => 'Alegra no está configurado. Configure primero las credenciales.'
            ], 400);
        }

        $configuracion = $config->configuracion;
        
        // Crear request simulado para el método del EmpresaController
        $empresaRequest = new Request();
        $empresaRequest->merge([
            'alegra_email' => $configuracion['usuario'],
            'alegra_token' => $configuracion['token'],
            'solo_verificar' => 'true'
        ]);

        // Usar el método probado del EmpresaController
        $empresaController = new \App\Http\Controllers\EmpresaController();
        $resultado = $empresaController->probarConexion($empresaRequest);
        
        // Actualizar resultado en nuestra tabla
        $config->update([
            'ultima_prueba' => now(),
            'resultado_prueba' => $resultado->getData(true)
        ]);

        return $resultado;
    }

    /**
     * Probar conexión directa (GET) - muestra resultado en página
     */
    public function probarConexionDirecto($proveedor)
    {
        try {
            // Simular request POST para reutilizar lógica
            $request = new Request();
            $request->merge(['proveedor' => $proveedor]);
            
            $resultado = $this->probarConexion($request);
            $data = $resultado->getData(true);
            
            return view('facturacion.resultado-conexion', [
                'proveedor' => $proveedor,
                'resultado' => $data,
                'success' => $data['success'] ?? false
            ]);
            
        } catch (\Exception $e) {
            return view('facturacion.resultado-conexion', [
                'proveedor' => $proveedor,
                'resultado' => ['message' => 'Error: ' . $e->getMessage()],
                'success' => false
            ]);
        }
    }

    /**
     * Probar conexión específica según el proveedor
     */
    private function probarConexionEspecifica($modulo, $proveedor, $configuracion)
    {
        switch ($proveedor) {
            case 'alegra':
                return $this->probarConexionAlegra($configuracion);
                
            case 'dian':
                return $this->probarConexionDian($configuracion);
                
            case 'siigo':
                return $this->probarConexionSiigo($configuracion);
                
            case 'worldoffice':
                return $this->probarConexionWorldOffice($configuracion);
                
            default:
                throw new \Exception('Proveedor no soportado');
        }
    }

    /**
     * Probar conexión con Alegra usando el servicio existente
     */
    private function probarConexionAlegra($config)
    {
        // Usar el AlegraService existente que ya funciona
        $alegraService = new \App\Services\AlegraService($config['usuario'], $config['token']);
        
        // Usar el método probarConexion que ya está probado y funciona
        $resultado = $alegraService->probarConexion();
        
        if ($resultado['success']) {
            $data = $resultado['data'];
            return [
                'empresa' => $data['name'] ?? 'N/A',
                'nit' => $data['identification'] ?? 'N/A',
                'email' => $data['email'] ?? 'N/A',
                'status' => 'Conectado exitosamente'
            ];
        }

        throw new \Exception($resultado['error'] ?? 'Error desconocido al conectar con Alegra');
    }


    /**
     * Sincronizar clientes desde el proveedor hacia nuestra aplicación
     */
    public function sincronizarClientes(Request $request)
    {
        $request->validate([
            'proveedor' => 'required|string|in:alegra,dian,siigo,worldoffice'
        ]);

        $proveedor = $request->proveedor;

        try {
            $resultado = $this->sincronizarClientesDesdeProveedor($proveedor);
            
            return response()->json([
                'success' => true,
                'message' => 'Clientes sincronizados exitosamente',
                'data' => $resultado
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al sincronizar clientes', [
                'proveedor' => $proveedor,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar clientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincronizar productos desde un proveedor específico
     */
    private function sincronizarProductosDesdeProveedor($proveedor)
    {
        switch ($proveedor) {
            case 'alegra':
                return $this->sincronizarProductosAlegra();
            case 'dian':
                return $this->sincronizarProductosDian();
            case 'siigo':
                return $this->sincronizarProductosSiigo();
            case 'worldoffice':
                return $this->sincronizarProductosWorldOffice();
            default:
                throw new \Exception('Proveedor no soportado para sincronización de productos');
        }
    }

    /**
     * Sincronizar clientes desde un proveedor específico
     */
    private function sincronizarClientesDesdeProveedor($proveedor)
    {
        switch ($proveedor) {
            case 'alegra':
                return $this->sincronizarClientesAlegra();
            case 'dian':
                return $this->sincronizarClientesDian();
            case 'siigo':
                return $this->sincronizarClientesSiigo();
            case 'worldoffice':
                return $this->sincronizarClientesWorldOffice();
            default:
                throw new \Exception('Proveedor no soportado para sincronización de clientes');
        }
    }

    /**
     * Sincronizar productos desde Alegra
     */
    private function sincronizarProductosAlegra()
    {
        // Obtener configuración de Alegra
        $configuracion = \App\Models\ConfiguracionFacturacion::where('proveedor', 'alegra')->first();
        
        if (!$configuracion) {
            throw new \Exception('No se encontró configuración para Alegra');
        }

        $config = is_string($configuracion->configuracion) 
            ? json_decode($configuracion->configuracion, true) 
            : $configuracion->configuracion;
        $alegraService = new \App\Services\AlegraService($config['usuario'], $config['token']);
        
        // Obtener TODOS los productos de Alegra usando paginación
        $productosAlegra = $alegraService->obtenerTodosLosProductosPaginados();
        
        if (!$productosAlegra['success']) {
            throw new \Exception('Error al obtener productos de Alegra: ' . ($productosAlegra['message'] ?? 'Error desconocido'));
        }

        $productosCreados = 0;
        $productosActualizados = 0;
        $errores = [];

        foreach ($productosAlegra['data'] as $productoAlegra) {
            try {
                // Buscar si el producto ya existe por id_alegra
                $producto = \App\Models\Producto::where('id_alegra', $productoAlegra['id'])->first();
                
                if (!$producto) {
                    // Buscar por código/referencia
                    $producto = \App\Models\Producto::where('codigo', $productoAlegra['reference'] ?? '')->first();
                }

                // Extraer precio correctamente
                $precio = 0;
                if (isset($productoAlegra['price']) && is_array($productoAlegra['price']) && count($productoAlegra['price']) > 0) {
                    $precio = $productoAlegra['price'][0]['price'] ?? 0;
                }

                // Extraer IVA según régimen tributario de la empresa
                $empresa = \App\Models\Empresa::first();
                $iva = 0; // Por defecto para no responsables de IVA
                
                if ($empresa && $empresa->regimen_tributario === 'responsable_iva') {
                    $iva = 19; // 19% para responsables de IVA
                    // Si Alegra tiene un porcentaje específico, usarlo
                    if (isset($productoAlegra['tax']) && is_array($productoAlegra['tax']) && count($productoAlegra['tax']) > 0) {
                        $iva = floatval($productoAlegra['tax'][0]['percentage'] ?? 19);
                    }
                } elseif ($empresa && $empresa->regimen_tributario === 'regimen_simple') {
                    $iva = 19; // 19% para régimen simple
                    if (isset($productoAlegra['tax']) && is_array($productoAlegra['tax']) && count($productoAlegra['tax']) > 0) {
                        $iva = floatval($productoAlegra['tax'][0]['percentage'] ?? 19);
                    }
                }
                // Para 'no_responsable_iva' se mantiene iva = 0

                // Extraer unidad de medida
                $unidadMedida = 'unidad';
                if (isset($productoAlegra['inventory']['unit'])) {
                    $unidadMedida = $productoAlegra['inventory']['unit'];
                }

                $datosProducto = [
                    'codigo' => $productoAlegra['reference'] ?? 'ALG-' . $productoAlegra['id'],
                    'nombre' => $productoAlegra['name'] ?? 'Producto sin nombre',
                    'descripcion' => $productoAlegra['description'] ?? '',
                    'precio_venta' => $precio,
                    'precio_compra' => $precio * 0.7, // Estimación del 70% como precio de compra
                    'precio_final' => $precio,
                    'iva' => $iva,
                    'valor_iva' => $precio * ($iva / 100),
                    'stock' => 5, // Stock mínimo por defecto cuando no hay stock en Alegra
                    'stock_minimo' => 1,
                    'estado' => ($productoAlegra['status'] ?? 'active') === 'active',
                    'id_alegra' => $productoAlegra['id'],
                    'unidad_medida' => $unidadMedida
                ];

                if ($producto) {
                    // Actualizar producto existente
                    $producto->update($datosProducto);
                    $productosActualizados++;
                } else {
                    // Crear nuevo producto
                    \App\Models\Producto::create($datosProducto);
                    $productosCreados++;
                }
            } catch (\Exception $e) {
                $errores[] = "Error con producto {$productoAlegra['name']}: " . $e->getMessage();
            }
        }

        return [
            'productos_creados' => $productosCreados,
            'productos_actualizados' => $productosActualizados,
            'total_procesados' => count($productosAlegra['data']),
            'errores' => $errores
        ];
    }

    /**
     * Sincronizar clientes desde Alegra
     */
    private function sincronizarClientesAlegra()
    {
        // Obtener configuración de Alegra
        $configuracion = \App\Models\ConfiguracionFacturacion::where('proveedor', 'alegra')->first();
        
        if (!$configuracion) {
            throw new \Exception('No se encontró configuración para Alegra');
        }

        $config = is_string($configuracion->configuracion) 
            ? json_decode($configuracion->configuracion, true) 
            : $configuracion->configuracion;
        $alegraService = new \App\Services\AlegraService($config['usuario'], $config['token']);
        
        // Obtener TODOS los clientes de Alegra usando paginación
        $clientesAlegra = $alegraService->obtenerTodosLosClientesPaginados();
        
        if (!$clientesAlegra['success']) {
            throw new \Exception('Error al obtener clientes de Alegra: ' . ($clientesAlegra['message'] ?? 'Error desconocido'));
        }

        $clientesCreados = 0;
        $clientesActualizados = 0;
        $errores = [];

        foreach ($clientesAlegra['data'] as $clienteAlegra) {
            try {
                // Saltar el cliente genérico
                if (($clienteAlegra['identification'] ?? '') === '222222222222') {
                    continue;
                }

                // Buscar si el cliente ya existe por id_alegra
                $cliente = \App\Models\Cliente::where('id_alegra', $clienteAlegra['id'])->first();
                
                if (!$cliente) {
                    // Buscar por cédula
                    $cliente = \App\Models\Cliente::where('cedula', $clienteAlegra['identification'] ?? '')->first();
                }

                $nombreCompleto = $clienteAlegra['name'] ?? 'Cliente sin nombre';
                $partesNombre = explode(' ', $nombreCompleto, 2);

                // Determinar tipo de documento y persona basado en Alegra
                $tipoDocumento = 'CC'; // Por defecto
                $tipoPersona = 'PERSON_ENTITY'; // Por defecto (usar valores del ENUM)
                $regimen = 'SIMPLIFIED_REGIME'; // Por defecto (usar valores del ENUM)
                
                if (isset($clienteAlegra['identificationObject']['type'])) {
                    $tipoDocumento = $clienteAlegra['identificationObject']['type'] === 'NIT' ? 'NIT' : 'CC';
                }
                
                if (isset($clienteAlegra['kindOfPerson'])) {
                    $tipoPersona = $clienteAlegra['kindOfPerson']; // Usar directamente el valor de Alegra
                }
                
                if (isset($clienteAlegra['regime'])) {
                    $regimen = $clienteAlegra['regime']; // Usar directamente el valor de Alegra
                }

                $datosCliente = [
                    'nombres' => $partesNombre[0] ?? '',
                    'apellidos' => $partesNombre[1] ?? '',
                    'cedula' => $clienteAlegra['identification'] ?? '',
                    'telefono' => $clienteAlegra['phonePrimary'] ?? $clienteAlegra['mobile'] ?? '',
                    'email' => $clienteAlegra['email'] ?? '',
                    'direccion' => isset($clienteAlegra['address']['address']) ? $clienteAlegra['address']['address'] : '',
                    'ciudad' => isset($clienteAlegra['address']['city']) ? $clienteAlegra['address']['city'] : '',
                    'departamento' => isset($clienteAlegra['address']['department']) ? $clienteAlegra['address']['department'] : '',
                    'tipo_documento' => $tipoDocumento,
                    'tipo_persona' => $tipoPersona,
                    'regimen' => $regimen,
                    'estado' => ($clienteAlegra['status'] ?? 'active') === 'active',
                    'id_alegra' => $clienteAlegra['id']
                ];

                if ($cliente) {
                    // Actualizar cliente existente
                    $cliente->update($datosCliente);
                    $clientesActualizados++;
                } else {
                    // Crear nuevo cliente
                    \App\Models\Cliente::create($datosCliente);
                    $clientesCreados++;
                }
            } catch (\Exception $e) {
                $errores[] = "Error con cliente {$clienteAlegra['name']}: " . $e->getMessage();
            }
        }

        return [
            'clientes_creados' => $clientesCreados,
            'clientes_actualizados' => $clientesActualizados,
            'total_procesados' => count($clientesAlegra['data']),
            'errores' => $errores
        ];
    }

    // Métodos placeholder para otros proveedores
    private function sincronizarProductosDian()
    {
        throw new \Exception('Sincronización de productos con DIAN no implementada aún');
    }

    private function sincronizarProductosSiigo()
    {
        throw new \Exception('Sincronización de productos con Siigo no implementada aún');
    }

    private function sincronizarProductosWorldOffice()
    {
        throw new \Exception('Sincronización de productos con WorldOffice no implementada aún');
    }

    private function sincronizarClientesDian()
    {
        throw new \Exception('Sincronización de clientes con DIAN no implementada aún');
    }

    private function sincronizarClientesSiigo()
    {
        throw new \Exception('Sincronización de clientes con Siigo no implementada aún');
    }

    private function sincronizarClientesWorldOffice()
    {
        throw new \Exception('Sincronización de clientes con WorldOffice no implementada aún');
    }

    /**
     * Mostrar vista previa de productos a sincronizar
     */
    public function sincronizarProductosVista($proveedor)
    {
        try {
            $productosPreview = $this->obtenerProductosPreview($proveedor);
            
            return view('facturacion.sincronizar-productos', [
                'proveedor' => $proveedor,
                'productos' => $productosPreview,
                'success' => true
            ]);
        } catch (\Exception $e) {
            return view('facturacion.sincronizar-productos', [
                'proveedor' => $proveedor,
                'productos' => [],
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Mostrar vista previa de clientes a sincronizar
     */
    public function sincronizarClientesVista($proveedor)
    {
        try {
            $clientesPreview = $this->obtenerClientesPreview($proveedor);
            
            return view('facturacion.sincronizar-clientes', [
                'proveedor' => $proveedor,
                'clientes' => $clientesPreview,
                'success' => true
            ]);
        } catch (\Exception $e) {
            return view('facturacion.sincronizar-clientes', [
                'proveedor' => $proveedor,
                'clientes' => [],
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener vista previa de productos desde el proveedor
     */
    private function obtenerProductosPreview($proveedor)
    {
        switch ($proveedor) {
            case 'alegra':
                return $this->obtenerProductosPreviewAlegra();
            case 'dian':
                throw new \Exception('Vista previa de productos DIAN no implementada aún');
            case 'siigo':
                throw new \Exception('Vista previa de productos Siigo no implementada aún');
            case 'worldoffice':
                throw new \Exception('Vista previa de productos WorldOffice no implementada aún');
            default:
                throw new \Exception('Proveedor no soportado para vista previa de productos');
        }
    }

    /**
     * Obtener vista previa de clientes desde el proveedor
     */
    private function obtenerClientesPreview($proveedor)
    {
        switch ($proveedor) {
            case 'alegra':
                return $this->obtenerClientesPreviewAlegra();
            case 'dian':
                throw new \Exception('Vista previa de clientes DIAN no implementada aún');
            case 'siigo':
                throw new \Exception('Vista previa de clientes Siigo no implementada aún');
            case 'worldoffice':
                throw new \Exception('Vista previa de clientes WorldOffice no implementada aún');
            default:
                throw new \Exception('Proveedor no soportado para vista previa de clientes');
        }
    }

    /**
     * Obtener vista previa de productos desde Alegra
     */
    private function obtenerProductosPreviewAlegra()
    {
        // Obtener configuración de Alegra
        $configuracion = \App\Models\ConfiguracionFacturacion::where('proveedor', 'alegra')->first();
        
        if (!$configuracion) {
            throw new \Exception('No se encontró configuración para Alegra');
        }

        $config = is_string($configuracion->configuracion) 
            ? json_decode($configuracion->configuracion, true) 
            : $configuracion->configuracion;
        $alegraService = new \App\Services\AlegraService($config['usuario'], $config['token']);
        
        // Obtener TODOS los productos de Alegra usando paginación
        $productosAlegra = $alegraService->obtenerTodosLosProductosPaginados();
        
        if (!$productosAlegra['success']) {
            throw new \Exception('Error al obtener productos de Alegra: ' . ($productosAlegra['message'] ?? 'Error desconocido'));
        }

        // Obtener régimen tributario de la empresa para mostrar IVA correcto
        $empresa = \App\Models\Empresa::first();
        $ivaDefault = 0; // Por defecto para no responsables de IVA
        
        if ($empresa && $empresa->regimen_tributario === 'responsable_iva') {
            $ivaDefault = 19; // 19% para responsables de IVA
        } elseif ($empresa && $empresa->regimen_tributario === 'regimen_simple') {
            $ivaDefault = 19; // 19% para régimen simple
        }

        $productosPreview = [];
        foreach ($productosAlegra['data'] as $productoAlegra) {
            // Buscar si el producto ya existe
            $productoExistente = \App\Models\Producto::where('id_alegra', $productoAlegra['id'])->first();
            if (!$productoExistente) {
                $productoExistente = \App\Models\Producto::where('codigo', $productoAlegra['reference'] ?? '')->first();
            }

            // Calcular IVA según régimen tributario
            $ivaProducto = $ivaDefault;
            if (isset($productoAlegra['tax'][0]['percentage']) && $ivaDefault > 0) {
                $ivaProducto = $productoAlegra['tax'][0]['percentage'];
            }

            $productosPreview[] = [
                'id_alegra' => $productoAlegra['id'],
                'codigo' => $productoAlegra['reference'] ?? 'ALG-' . $productoAlegra['id'],
                'nombre' => $productoAlegra['name'] ?? 'Producto sin nombre',
                'precio' => isset($productoAlegra['price'][0]['price']) ? $productoAlegra['price'][0]['price'] : 0,
                'stock' => 5, // Stock mínimo asignado cuando no hay stock en Alegra
                'iva' => $ivaProducto, // IVA según régimen tributario
                'estado' => ($productoAlegra['status'] ?? 'active') === 'active' ? 'Activo' : 'Inactivo',
                'accion' => $productoExistente ? 'Actualizar' : 'Crear',
                'existe_local' => $productoExistente ? true : false
            ];
        }

        return $productosPreview;
    }

    /**
     * Obtener vista previa de clientes desde Alegra
     */
    private function obtenerClientesPreviewAlegra()
    {
        // Obtener configuración de Alegra
        $configuracion = \App\Models\ConfiguracionFacturacion::where('proveedor', 'alegra')->first();
        
        if (!$configuracion) {
            throw new \Exception('No se encontró configuración para Alegra');
        }

        $config = is_string($configuracion->configuracion) 
            ? json_decode($configuracion->configuracion, true) 
            : $configuracion->configuracion;
        $alegraService = new \App\Services\AlegraService($config['usuario'], $config['token']);
        
        // Obtener TODOS los clientes de Alegra usando paginación
        $clientesAlegra = $alegraService->obtenerTodosLosClientesPaginados();
        
        if (!$clientesAlegra['success']) {
            throw new \Exception('Error al obtener clientes de Alegra: ' . ($clientesAlegra['message'] ?? 'Error desconocido'));
        }

        $clientesPreview = [];
        foreach ($clientesAlegra['data'] as $clienteAlegra) {
            // Saltar el cliente genérico
            if (($clienteAlegra['identification'] ?? '') === '222222222222') {
                continue;
            }

            // Buscar si el cliente ya existe
            $clienteExistente = \App\Models\Cliente::where('id_alegra', $clienteAlegra['id'])->first();
            if (!$clienteExistente) {
                $clienteExistente = \App\Models\Cliente::where('cedula', $clienteAlegra['identification'] ?? '')->first();
            }

            $clientesPreview[] = [
                'id_alegra' => $clienteAlegra['id'],
                'nombre' => $clienteAlegra['name'] ?? 'Cliente sin nombre',
                'cedula' => $clienteAlegra['identification'] ?? '',
                'telefono' => $clienteAlegra['phonePrimary'] ?? $clienteAlegra['mobile'] ?? '',
                'email' => $clienteAlegra['email'] ?? '',
                'ciudad' => $clienteAlegra['address']['city'] ?? '',
                'estado' => ($clienteAlegra['status'] ?? 'active') === 'active' ? 'Activo' : 'Inactivo',
                'accion' => $clienteExistente ? 'Actualizar' : 'Crear',
                'existe_local' => $clienteExistente ? true : false
            ];
        }

        return $clientesPreview;
    }

    /**
     * Probar conexión con DIAN
     */
    private function probarConexionDian($config)
    {
        // Para DIAN, verificamos que los datos estén completos
        $required = ['nit_empresa', 'username', 'password'];
        foreach ($required as $field) {
            if (empty($config[$field])) {
                throw new \Exception("Campo requerido faltante: $field");
            }
        }

        // Simulamos una conexión exitosa (en producción aquí iría la lógica real de DIAN)
        return [
            'nit' => $config['nit_empresa'],
            'usuario' => $config['username'],
            'modo' => $config['test_mode'] ? 'Pruebas' : 'Producción',
            'status' => 'Configuración válida'
        ];
    }

    /**
     * Probar conexión con Siigo
     */
    private function probarConexionSiigo($config)
    {
        $client = new \GuzzleHttp\Client();
        
        // Primero obtener token de acceso
        $authResponse = $client->post($config['base_url'] . '/auth', [
            'json' => [
                'username' => $config['username'],
                'access_key' => $config['access_key']
            ],
            'timeout' => 10
        ]);

        if ($authResponse->getStatusCode() === 200) {
            $authData = json_decode($authResponse->getBody(), true);
            $token = $authData['access_token'] ?? null;

            if ($token) {
                // Probar obtener información de la empresa
                $companyResponse = $client->get($config['base_url'] . '/users/user-info', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type' => 'application/json'
                    ],
                    'timeout' => 10
                ]);

                if ($companyResponse->getStatusCode() === 200) {
                    $companyData = json_decode($companyResponse->getBody(), true);
                    return [
                        'usuario' => $config['username'],
                        'empresa' => $companyData['company_name'] ?? 'N/A',
                        'status' => 'Conectado exitosamente'
                    ];
                }
            }
        }

        throw new \Exception('Error de autenticación con Siigo');
    }

    /**
     * Probar conexión con World Office
     */
    private function probarConexionWorldOffice($config)
    {
        $client = new \GuzzleHttp\Client();
        
        $response = $client->get($config['base_url'] . '/companies/' . $config['company_id'], [
            'headers' => [
                'Authorization' => 'Bearer ' . $config['api_key'],
                'Content-Type' => 'application/json'
            ],
            'timeout' => 10
        ]);

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody(), true);
            return [
                'company_id' => $config['company_id'],
                'empresa' => $data['name'] ?? 'N/A',
                'status' => 'Conectado exitosamente'
            ];
        }

        throw new \Exception('Respuesta inesperada de World Office');
    }

    /**
     * Mostrar formulario de configuración para un proveedor
     */
    public function configurar($proveedor)
    {
        if (!in_array($proveedor, ['alegra', 'dian', 'siigo', 'worldoffice'])) {
            return redirect()->back()->with('error', 'Proveedor no válido');
        }

        $config = ConfiguracionFacturacion::where('proveedor', $proveedor)->first();
        $configuracionActual = $config ? $config->configuracion : [];

        return view('facturacion.configurar', compact('proveedor', 'configuracionActual'));
    }

    /**
     * Guardar configuración de proveedor
     */
    public function guardarConfiguracion(Request $request, $proveedor)
    {
        if (!in_array($proveedor, ['alegra', 'dian', 'siigo', 'worldoffice'])) {
            return redirect()->back()->with('error', 'Proveedor no válido');
        }

        // Validaciones específicas por proveedor
        $rules = $this->getValidationRules($proveedor);
        $request->validate($rules);

        try {
            // Guardar configuración
            ConfiguracionFacturacion::guardarConfiguracion($proveedor, $request->all());

            return redirect()->route('facturacion.index')
                ->with('success', 'Configuración guardada exitosamente');

        } catch (\Exception $e) {
            Log::error('Error guardando configuración', [
                'proveedor' => $proveedor,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Error al guardar configuración: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Obtener reglas de validación por proveedor
     */
    private function getValidationRules($proveedor)
    {
        return match($proveedor) {
            'alegra' => [
                'usuario' => 'required|string',
                'token' => 'required|string',
                'url_base' => 'required|url'
            ],
            'dian' => [
                'nit_empresa' => 'required|string',
                'username' => 'required|string',
                'password' => 'required|string',
                'test_mode' => 'boolean'
            ],
            'siigo' => [
                'username' => 'required|string',
                'access_key' => 'required|string',
                'base_url' => 'required|url'
            ],
            'worldoffice' => [
                'api_key' => 'required|string',
                'company_id' => 'required|string',
                'base_url' => 'required|url'
            ],
            default => []
        };
    }
}
