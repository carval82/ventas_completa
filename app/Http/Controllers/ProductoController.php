<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ProductoEquivalencia;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Actualiza todos los productos en Alegra con su porcentaje de IVA correcto
     * 
     * @return \Illuminate\Http\Response
     */
    public function actualizarProductosAlegra()
    {
        try {
            $alegraService = app(\App\Http\Services\AlegraService::class);
            $productos = Producto::whereNotNull('id_alegra')->get();
            $actualizados = 0;
            $errores = 0;
            
            foreach ($productos as $producto) {
                $result = $alegraService->actualizarProductoAlegra($producto);
                
                if ($result['success']) {
                    $actualizados++;
                } else {
                    $errores++;
                }
            }
            
            return back()->with('success', "Productos actualizados en Alegra: {$actualizados}. Errores: {$errores}");
        } catch (\Exception $e) {
            \Log::error('Error al actualizar productos en Alegra', [
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Error al actualizar productos en Alegra: ' . $e->getMessage());
        }
    }

    public function index(Request $request)
    {
        $query = Producto::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'LIKE', "%{$search}%")
                  ->orWhere('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('descripcion', 'LIKE', "%{$search}%");
            });
        }

        $productos = $query->latest()->paginate(10)->appends($request->query());
        return view('productos.index', compact('productos'));
    }

    public function create(Request $request)
    {
        try {
            // Guardar en sesión el origen de la petición
            if ($request->has('return_to')) {
                session(['return_to' => $request->return_to]);
                Log::info('Origen de la petición guardado:', ['return_to' => $request->return_to]);
            }
            
            return view('productos.create', [
                'fromCompras' => session('return_to') === 'compras',
                'fromVentas' => session('return_to') === 'ventas'
            ]);
        } catch (\Exception $e) {
            Log::error('Error en create:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    public function store(Request $request)
    {
        try {
            Log::info('Iniciando creación de producto:', [
                'request_data' => $request->all()
            ]);
    
            // Determinar regla de stock según origen
            $stockRule = session('return_to') === 'ventas' ? 'required|numeric|min:1' : 'required|numeric|min:0';
            
            // Validar datos
            $validated = $request->validate([
                'codigo' => 'required|unique:productos',
                'nombre' => 'required',
                'descripcion' => 'nullable',
                'precio_compra' => 'required|numeric|min:0',
                'precio_final' => 'required|numeric|min:0', // Cambiamos a precio_final en lugar de precio_venta
                'iva' => 'required|numeric|min:0|max:100',
                'stock_minimo' => 'required|numeric|min:0',
                'stock' => $stockRule
            ]);
    
            Log::info('Datos validados correctamente:', $validated);
    
            // Asegurar estado activo
            $validated['estado'] = true;
    
            // Calcular todos los campos de precios a partir del precio final
            $precioFinal = $validated['precio_final'];
            $ivaPorcentaje = $validated['iva'];
            $precioCompra = $validated['precio_compra'];
            
            // Calcular precio sin IVA: PrecioFinal / (1 + IVA%/100)
            $precioSinIVA = $precioFinal / (1 + ($ivaPorcentaje / 100));
            
            // Calcular el valor del IVA
            $valorIVA = $precioFinal - $precioSinIVA;
            
            // Calcular el porcentaje de ganancia
            $porcentajeGanancia = 0;
            if ($precioCompra > 0) {
                $porcentajeGanancia = (($precioFinal - $precioCompra) / $precioCompra) * 100;
            }
            
            // Actualizar los valores calculados
            $validated['precio_venta'] = $precioSinIVA;
            $validated['precio_final'] = $precioFinal;
            $validated['valor_iva'] = $valorIVA;
            $validated['porcentaje_ganancia'] = $porcentajeGanancia;
            
            Log::info('Precios calculados:', [
                'precio_compra' => $precioCompra,
                'precio_final' => $precioFinal,
                'precio_sin_iva' => $precioSinIVA,
                'iva_porcentaje' => $ivaPorcentaje,
                'valor_iva' => $valorIVA,
                'porcentaje_ganancia' => $porcentajeGanancia
            ]);
            
            // Crear el producto
            $producto = Producto::create($validated);
            
            Log::info('Producto creado exitosamente:', [
                'producto_id' => $producto->id,
                'producto_data' => $producto->toArray()
            ]);
            
            // Procesar códigos relacionados si existen
            if ($request->has('codigos_relacionados')) {
                foreach ($request->codigos_relacionados as $codigo) {
                    if (!empty($codigo['codigo'])) {
                        $producto->codigosRelacionados()->create([
                            'codigo' => $codigo['codigo'],
                            'descripcion' => $codigo['descripcion'] ?? null
                        ]);
                        
                        Log::info('Código relacionado creado:', [
                            'producto_id' => $producto->id,
                            'codigo' => $codigo['codigo']
                        ]);
                    }
                }
            }
            
            // Procesar equivalencias de unidades si existen
            if ($request->has('equivalencias') && ($request->has('unidad_base_equivalencia') || $request->has('unidad_medida'))) {
                $unidadBase = $request->unidad_base_equivalencia ?? $request->unidad_medida ?? 'unidad';
                
                Log::info('Procesando equivalencias:', [
                    'producto_id' => $producto->id,
                    'unidad_base' => $unidadBase,
                    'equivalencias' => $request->equivalencias
                ]);
                
                foreach ($request->equivalencias as $equivalencia) {
                    if (!empty($equivalencia['cantidad']) && !empty($equivalencia['unidad'])) {
                        // Crear equivalencia directa (unidad_base -> unidad_destino)
                        ProductoEquivalencia::create([
                            'producto_id' => $producto->id,
                            'unidad_origen' => $unidadBase,
                            'unidad_destino' => $equivalencia['unidad'],
                            'factor_conversion' => floatval($equivalencia['cantidad']),
                            'descripcion' => $equivalencia['descripcion'] ?? "1 {$unidadBase} = {$equivalencia['cantidad']} {$equivalencia['unidad']}",
                            'activo' => true
                        ]);
                        
                        // Crear equivalencia inversa (unidad_destino -> unidad_base)
                        $factorInverso = 1 / floatval($equivalencia['cantidad']);
                        ProductoEquivalencia::create([
                            'producto_id' => $producto->id,
                            'unidad_origen' => $equivalencia['unidad'],
                            'unidad_destino' => $unidadBase,
                            'factor_conversion' => $factorInverso,
                            'descripcion' => "1 {$equivalencia['unidad']} = " . number_format($factorInverso, 4) . " {$unidadBase}",
                            'activo' => true
                        ]);
                        
                        Log::info('Equivalencia creada:', [
                            'producto_id' => $producto->id,
                            'directa' => "{$unidadBase} -> {$equivalencia['unidad']} (factor: {$equivalencia['cantidad']})",
                            'inversa' => "{$equivalencia['unidad']} -> {$unidadBase} (factor: {$factorInverso})"
                        ]);
                    }
                }
                
                // Crear equivalencias cruzadas entre unidades (ej: kg <-> lb)
                $this->crearEquivalenciasCruzadas($producto->id, $request->equivalencias);
            }
    
            // Verificar origen usando la sesión
            $returnTo = session('return_to');
            Log::info('Return to:', ['return_to' => $returnTo]);
    
            if ($returnTo) {
                session()->forget('return_to'); // Limpiar la sesión
                Log::info('Redirigiendo a:', ['return_to' => $returnTo]);
                
                if ($returnTo === 'compras') {
                    return redirect()->route('compras.create')
                        ->with('producto_creado', $producto->toJson());
                } elseif ($returnTo === 'ventas') {
                    return redirect()->route('ventas.create')
                        ->with('producto_creado', $producto->toJson());
                }
            }
    
            Log::info('Redirigiendo al índice de productos');
            return redirect()->route('productos.index')
                ->with('success', 'Producto creado exitosamente');
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación:', [
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ]);
            return back()->withErrors($e->errors())->withInput();
    
        } catch (\Exception $e) {
            Log::error('Error creando producto:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return back()->with('error', 'Error al crear el producto: ' . $e->getMessage())
                ->withInput();
        }
    }


    public function show(Producto $producto)
    {
        $producto->load('detalleVentas.venta');
        return view('productos.show', compact('producto'));
    }

    public function edit(Producto $producto)
    {
        // Obtener proveedores activos con sus datos completos
        $proveedores = Proveedor::where('estado', true)
                               ->select('id', 'razon_social', 'nit')
                               ->get();

        // Obtener los proveedores ya asignados al producto
        $proveedoresAsignados = $producto->proveedores;
        
        // Cargar los códigos relacionados
        $codigosRelacionados = $producto->codigosRelacionados;

        return view('productos.edit', compact('producto', 'proveedores', 'proveedoresAsignados', 'codigosRelacionados'));
    }

    public function update(Request $request, Producto $producto)
    {
        $request->validate([
            'codigo' => 'required|unique:productos,codigo,' . $producto->id,
            'nombre' => 'required',
            'descripcion' => 'nullable',
            'precio_compra' => 'required|numeric|min:0',
            'precio_final' => 'required|numeric|min:0', // Ahora requerimos precio_final en lugar de precio_venta
            'iva' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'estado' => 'required|boolean'
        ]);

        try {
            // Calcular el precio de venta sin IVA a partir del precio final
            $precio_final = $request->precio_final;
            $iva_porcentaje = $request->iva;
            $precio_compra = $request->precio_compra;
            
            // Calculamos el precio sin IVA: PrecioFinal / (1 + IVA%/100)
            $precio_venta = $precio_final / (1 + ($iva_porcentaje / 100));
            
            // Calculamos el valor del IVA
            $valor_iva = $precio_final - $precio_venta;
            
            // Calculamos el porcentaje de ganancia
            $porcentaje_ganancia = 0;
            if ($precio_compra > 0) {
                $porcentaje_ganancia = (($precio_final - $precio_compra) / $precio_compra) * 100;
            }
            
            // Agregamos los valores calculados al request
            $request->merge([
                'precio_venta' => $precio_venta,
                'valor_iva' => $valor_iva,
                'porcentaje_ganancia' => $porcentaje_ganancia
            ]);
            
            $producto->update($request->all());
            
            // Procesar códigos relacionados si existen
            if ($request->has('codigos_relacionados')) {
                // Eliminar códigos relacionados existentes que no estén en la nueva lista
                $idsExistentes = [];
                
                foreach ($request->codigos_relacionados as $codigo) {
                    if (!empty($codigo['codigo'])) {
                        // Si tiene ID, actualizar el existente
                        if (!empty($codigo['id'])) {
                            $codigoRelacionado = $producto->codigosRelacionados()->find($codigo['id']);
                            if ($codigoRelacionado) {
                                $codigoRelacionado->update([
                                    'codigo' => $codigo['codigo'],
                                    'descripcion' => $codigo['descripcion'] ?? null
                                ]);
                                $idsExistentes[] = $codigoRelacionado->id;
                            }
                        } else {
                            // Si no tiene ID, crear uno nuevo
                            $nuevoCodigoRelacionado = $producto->codigosRelacionados()->create([
                                'codigo' => $codigo['codigo'],
                                'descripcion' => $codigo['descripcion'] ?? null
                            ]);
                            $idsExistentes[] = $nuevoCodigoRelacionado->id;
                        }
                    }
                }
                
                // Eliminar códigos que ya no están en la lista
                $producto->codigosRelacionados()->whereNotIn('id', $idsExistentes)->delete();
            } else {
                // Si no hay códigos relacionados en la solicitud, eliminar todos los existentes
                $producto->codigosRelacionados()->delete();
            }
            
            return redirect()->route('productos.index')
                           ->with('success', 'Producto actualizado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error actualizando producto: ' . $e->getMessage());
            return back()->with('error', 'Error al actualizar el producto')
                        ->withInput();
        }
    }

    public function destroy(Producto $producto)
    {
        try {
            $producto->delete();
            return redirect()->route('productos.index')
                           ->with('success', 'Producto eliminado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error eliminando producto: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar el producto');
        }
    }

    /**
     * Almacena un nuevo producto vía API
     */
    public function apiStore(Request $request)
    {
        try {
            Log::info('Datos recibidos:', $request->all());
    
            $validated = $request->validate([
                'codigo' => 'required|unique:productos',
                'nombre' => 'required',
                'descripcion' => 'nullable',
                'precio_compra' => 'required|numeric|min:0',
                'precio_venta' => 'required|numeric|min:0',
                'stock_minimo' => 'required|numeric|min:0'
            ]);
    
            $producto = Producto::create($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Producto creado exitosamente',
                'data' => $producto
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error creando producto API:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el producto: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Busca un producto por código o código relacionado
     */
    public function buscarPorCodigo(Request $request)
    {
        try {
            $codigo = $request->codigo;
            
            if (!$codigo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe proporcionar un código'
                ], 400);
            }
            
            // Primero buscar en la tabla de productos
            $producto = Producto::where('codigo', $codigo)
                                ->where('estado', true)
                                ->first();
            
            $isRelatedCode = false;
            
            // Si no se encuentra, buscar en códigos relacionados
            if (!$producto) {
                $codigoRelacionado = \App\Models\CodigoRelacionado::where('codigo', $codigo)
                                                                 ->first();
                
                if ($codigoRelacionado) {
                    $producto = $codigoRelacionado->producto;
                    $isRelatedCode = true;
                    
                    // Verificar que el producto esté activo
                    if (!$producto->estado) {
                        $producto = null;
                        $isRelatedCode = false;
                    }
                }
            }
            
            if ($producto) {
                return response()->json([
                    'success' => true,
                    'data' => $producto,
                    'is_related_code' => $isRelatedCode,
                    'message' => 'Producto encontrado'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No se encontró ningún producto con ese código'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Error buscando producto por código:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar el producto: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Busca productos que coincidan con un código relacionado
     */
    public function buscarProductosPorCodigoRelacionado(Request $request)
    {
        try {
            $codigo = $request->codigo;
            
            if (!$codigo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe proporcionar un código'
                ], 400);
            }
            
            // Buscar en códigos relacionados
            $codigosRelacionados = \App\Models\CodigoRelacionado::where('codigo', 'LIKE', "%{$codigo}%")
                                                               ->with('producto')
                                                               ->get();
            
            $productosIds = [];
            
            foreach ($codigosRelacionados as $codigoRelacionado) {
                if ($codigoRelacionado->producto && $codigoRelacionado->producto->estado) {
                    $productosIds[] = $codigoRelacionado->producto_id;
                }
            }
            
            return response()->json([
                'success' => true,
                'productos_ids' => $productosIds,
                'message' => 'Búsqueda completada'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error buscando productos por código relacionado:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar productos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function imprimirCodigoBarras($id)
    {
        $producto = Producto::findOrFail($id);
        return view('productos.barcode', compact('producto'));
    }

    public function asignarProveedor(Request $request, Producto $producto)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'precio_compra' => 'required|numeric|min:0',
            'codigo_proveedor' => 'nullable|string|max:255'
        ]);

        try {
            // Intentar crear la asignación directamente
            $producto->proveedores()->attach($request->proveedor_id, [
                'precio_compra' => $request->precio_compra,
                'codigo_proveedor' => $request->codigo_proveedor
            ]);

            return redirect()->back()->with('success', 'Proveedor asignado correctamente');

        } catch (\Exception $e) {
            Log::error('Error al asignar proveedor:', [
                'producto_id' => $producto->id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Error al asignar el proveedor');
        }
    }

    public function removeProveedor(Request $request, Producto $producto)
    {
        try {
            $producto->proveedores()->detach($request->proveedor_id);
            return redirect()->back()->with('success', 'Proveedor eliminado correctamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar proveedor:', [
                'producto_id' => $producto->id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Error al eliminar el proveedor');
        }
    }

    public function search(Request $request)
    {
        $term = $request->input('q');
        
        $productos = Producto::where('estado', true)
            ->where(function($query) use ($term) {
                $query->where('codigo', 'LIKE', "%{$term}%")
                      ->orWhere('nombre', 'LIKE', "%{$term}%");
            })
            ->with(['stocks.ubicacion'])
            ->limit(10)
            ->get();

        return response()->json([
            'items' => $productos->map(function($producto) {
                return [
                    'id' => $producto->id,
                    'codigo' => $producto->codigo,
                    'nombre' => $producto->nombre,
                    'precio_compra' => $producto->precio_compra,
                    'stocks' => $producto->stocks->map(function($stock) {
                        return [
                            'ubicacion' => [
                                'id' => $stock->ubicacion->id,
                                'nombre' => $stock->ubicacion->nombre
                            ],
                            'stock' => $stock->stock
                        ];
                    })
                ];
            })
        ]);
    }

    public function searchApi(Request $request)
    {
        $query = $request->get('q');
        $productos = Producto::where('codigo', 'LIKE', "%{$query}%")
                            ->orWhere('nombre', 'LIKE', "%{$query}%")
                            ->get();
                            
        return response()->json(['items' => $productos]);
    }

    /**
     * Mostrar formulario para gestionar unidades de medida
     *
     * @return \Illuminate\Http\Response
     */
    public function unidadesMedida()
    {
        $productos = Producto::orderBy('nombre')->get();
        return view('productos.unidades_medida', compact('productos'));
    }

    /**
     * Actualizar unidades de medida de productos
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function actualizarUnidades(Request $request)
    {
        $unidades = $request->input('unidades', []);
        
        foreach ($unidades as $id => $unidad) {
            $producto = Producto::find($id);
            if ($producto) {
                $producto->unidad_medida = $unidad;
                $producto->save();
                
                // Si el producto ya está sincronizado con Alegra, actualizarlo
                if ($producto->id_alegra) {
                    try {
                        $alegraService = app(\App\Services\AlegraService::class);
                        $alegraService->actualizarProductoAlegra($producto);
                    } catch (\Exception $e) {
                        \Log::error('Error al actualizar producto en Alegra', [
                            'producto_id' => $producto->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }
        
        return redirect()->route('productos.unidades_medida')
            ->with('status', 'Unidades de medida actualizadas correctamente');
    }
    
    /**
     * Crear equivalencias cruzadas entre unidades del mismo producto
     * Por ejemplo, si tengo kg y lb, crear conversión directa kg <-> lb
     */
    private function crearEquivalenciasCruzadas($productoId, $equivalencias)
    {
        if (!is_array($equivalencias) || count($equivalencias) < 2) {
            return; // Necesitamos al menos 2 unidades para crear cruzadas
        }
        
        $unidades = [];
        foreach ($equivalencias as $equiv) {
            if (!empty($equiv['cantidad']) && !empty($equiv['unidad'])) {
                $unidades[] = [
                    'unidad' => $equiv['unidad'],
                    'factor_desde_base' => floatval($equiv['cantidad'])
                ];
            }
        }
        
        // Crear conversiones cruzadas entre todas las unidades
        for ($i = 0; $i < count($unidades); $i++) {
            for ($j = $i + 1; $j < count($unidades); $j++) {
                $unidad1 = $unidades[$i];
                $unidad2 = $unidades[$j];
                
                // Calcular factor de conversión directo
                // Si 1 base = 25 lb y 1 base = 12.5 kg, entonces 1 kg = 25/12.5 = 2 lb
                $factor1a2 = $unidad1['factor_desde_base'] / $unidad2['factor_desde_base'];
                $factor2a1 = $unidad2['factor_desde_base'] / $unidad1['factor_desde_base'];
                
                // Verificar si ya existe la conversión
                $existeConversion = ProductoEquivalencia::where('producto_id', $productoId)
                    ->where('unidad_origen', $unidad1['unidad'])
                    ->where('unidad_destino', $unidad2['unidad'])
                    ->exists();
                
                if (!$existeConversion) {
                    // Crear conversión unidad1 -> unidad2
                    ProductoEquivalencia::create([
                        'producto_id' => $productoId,
                        'unidad_origen' => $unidad1['unidad'],
                        'unidad_destino' => $unidad2['unidad'],
                        'factor_conversion' => $factor1a2,
                        'descripcion' => "1 {$unidad1['unidad']} = " . number_format($factor1a2, 4) . " {$unidad2['unidad']}",
                        'activo' => true
                    ]);
                    
                    // Crear conversión unidad2 -> unidad1
                    ProductoEquivalencia::create([
                        'producto_id' => $productoId,
                        'unidad_origen' => $unidad2['unidad'],
                        'unidad_destino' => $unidad1['unidad'],
                        'factor_conversion' => $factor2a1,
                        'descripcion' => "1 {$unidad2['unidad']} = " . number_format($factor2a1, 4) . " {$unidad1['unidad']}",
                        'activo' => true
                    ]);
                    
                    Log::info('Equivalencia cruzada creada:', [
                        'producto_id' => $productoId,
                        'conversion1' => "{$unidad1['unidad']} -> {$unidad2['unidad']} (factor: {$factor1a2})",
                        'conversion2' => "{$unidad2['unidad']} -> {$unidad1['unidad']} (factor: {$factor2a1})"
                    ]);
                }
            }
        }
    }
}