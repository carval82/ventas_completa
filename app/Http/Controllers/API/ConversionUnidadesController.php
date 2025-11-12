<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\ProductoEquivalencia;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ConversionUnidadesController extends Controller
{
    /**
     * Obtener unidades disponibles para un producto
     */
    public function obtenerUnidadesDisponibles(Request $request): JsonResponse
    {
        try {
            $productoId = $request->get('producto_id');
            $producto = Producto::find($productoId);
            
            if (!$producto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }
            
            // NUEVO: Obtener unidades desde productos equivalentes
            $unidadesDisponibles = $this->obtenerUnidadesDeProductosEquivalentes($producto);
            
            // Si no hay productos equivalentes, buscar en equivalencias tradicionales
            if ($unidadesDisponibles->isEmpty()) {
                $unidadesDisponibles = ProductoEquivalencia::getUnidadesDisponibles($productoId);
            }
            
            // Si aún no hay equivalencias, usar unidades por defecto
            if ($unidadesDisponibles->isEmpty()) {
                $unidadesDisponibles = collect(['unidad', 'kg', 'lb', 'g']);
            }
            
            // Formatear unidades para el frontend
            $unidades = $unidadesDisponibles->map(function($unidad) {
                return [
                    'codigo' => $unidad,
                    'nombre' => $this->getNombreUnidad($unidad)
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'unidades' => $unidades,
                    'unidad_base' => $producto->unidad_medida ?? 'unidad',
                    'permite_conversiones' => true // Siempre permitir si hay equivalencias
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener unidades: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Realizar conversión de unidades
     */
    public function convertirUnidad(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'producto_id' => 'required|exists:productos,id',
                'unidad_origen' => 'required|string',
                'unidad_destino' => 'required|string',
                'cantidad' => 'required|numeric|min:0.001',
                'precio' => 'required|numeric|min:0'
            ]);
            
            $producto = Producto::find($request->producto_id);
            
            if (!$producto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }
            
            // Realizar conversión
            $resultado = $this->realizarConversion(
                $producto,
                $request->unidad_origen,
                $request->unidad_destino,
                $request->cantidad,
                $request->precio
            );
            
            if (!$resultado['success']) {
                return response()->json($resultado, 400);
            }
            
            return response()->json([
                'success' => true,
                'data' => $resultado['data']
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en conversión: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Validar stock con conversión
     */
    public function validarStockConversion(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'producto_id' => 'required|exists:productos,id',
                'cantidad' => 'required|numeric|min:0.001',
                'unidad' => 'required|string'
            ]);
            
            $producto = Producto::find($request->producto_id);
            $cantidadEnUnidadBase = $this->convertirAUnidadBase(
                $producto,
                $request->cantidad,
                $request->unidad
            );
            
            $stockDisponible = $producto->stock;
            $stockSuficiente = $cantidadEnUnidadBase <= $stockDisponible;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'stock_suficiente' => $stockSuficiente,
                    'stock_disponible' => $stockDisponible,
                    'cantidad_solicitada_base' => $cantidadEnUnidadBase,
                    'unidad_base' => $producto->unidad_medida ?? 'unidad'
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al validar stock: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener unidades compatibles para un producto
     */
    private function getUnidadesCompatibles(Producto $producto): array
    {
        $unidadBase = $producto->unidad_medida ?? 'unidad';
        $tipoUnidad = $this->getTipoUnidad($unidadBase);
        
        $unidades = [
            'peso' => [
                ['codigo' => 'unidad', 'nombre' => 'Unidad', 'factor' => 1],
                ['codigo' => 'kg', 'nombre' => 'Kilogramo', 'factor' => 1],
                ['codigo' => 'g', 'nombre' => 'Gramo', 'factor' => 0.001],
                ['codigo' => 'lb', 'nombre' => 'Libra', 'factor' => 0.453592]
            ],
            'volumen' => [
                ['codigo' => 'l', 'nombre' => 'Litro', 'factor' => 1],
                ['codigo' => 'ml', 'nombre' => 'Mililitro', 'factor' => 0.001],
                ['codigo' => 'cc', 'nombre' => 'Centímetro cúbico', 'factor' => 0.001]
            ],
            'especial' => [
                ['codigo' => 'unidad', 'nombre' => 'Unidad', 'factor' => 1],
                ['codigo' => 'bulto', 'nombre' => 'Bulto', 'factor' => $producto->unidades_por_bulto ?? 1],
                ['codigo' => 'caja', 'nombre' => 'Caja', 'factor' => $producto->unidades_por_bulto ?? 1],
                ['codigo' => 'docena', 'nombre' => 'Docena', 'factor' => 12]
            ]
        ];
        
        return $unidades[$tipoUnidad] ?? $unidades['peso'];
    }
    
    /**
     * Determinar el tipo de unidad
     */
    private function getTipoUnidad(string $unidad): string
    {
        $tipos = [
            'peso' => ['unidad', 'kg', 'g', 'lb'],
            'volumen' => ['l', 'ml', 'cc'],
            'especial' => ['bulto', 'caja', 'docena']
        ];
        
        foreach ($tipos as $tipo => $unidadesTipo) {
            if (in_array($unidad, $unidadesTipo)) {
                return $tipo;
            }
        }
        
        return 'peso'; // Por defecto
    }
    
    /**
     * Realizar conversión entre unidades usando equivalencias del producto
     */
    private function realizarConversion(Producto $producto, string $unidadOrigen, string $unidadDestino, float $cantidad, float $precio): array
    {
        try {
            // NUEVO: Primero intentar conversión con productos equivalentes
            $resultadoProductosEquivalentes = $this->convertirConProductosEquivalentes(
                $producto, $unidadOrigen, $unidadDestino, $cantidad, $precio
            );
            
            if ($resultadoProductosEquivalentes['success']) {
                return $resultadoProductosEquivalentes;
            }
            
            // Si no funciona con productos equivalentes, usar sistema tradicional
            $resultadoConversion = ProductoEquivalencia::convertir(
                $producto->id, 
                $unidadOrigen, 
                $unidadDestino, 
                $cantidad
            );
            
            if (!$resultadoConversion['success']) {
                return [
                    'success' => false,
                    'message' => $resultadoConversion['error'] ?? 'Conversión no disponible entre estas unidades'
                ];
            }
            
            $cantidadConvertida = $resultadoConversion['cantidad_convertida'];
            $factorConversion = $resultadoConversion['factor_usado'];
            
            // El precio se ajusta inversamente al factor de conversión
            // Si 1 paca = 25 lb, entonces el precio por libra = precio_paca / 25
            $precioConvertido = $precio / $factorConversion;
            
            return [
                'success' => true,
                'data' => [
                    'cantidad_convertida' => round($cantidadConvertida, 3),
                    'precio_convertido' => round($precioConvertido, 2),
                    'factor_conversion' => $factorConversion,
                    'unidad_destino' => $unidadDestino,
                    'ruta_conversion' => $resultadoConversion['ruta'] ?? [],
                    'descripcion' => $resultadoConversion['descripcion'] ?? ''
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error en cálculo de conversión: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener factor de conversión para una unidad
     */
    private function getFactorConversion(Producto $producto, string $unidad): ?float
    {
        $factores = [
            // Peso
            'unidad' => 1,
            'kg' => 1,
            'g' => 0.001,
            'lb' => 0.453592,
            
            // Volumen
            'l' => 1,
            'ml' => 0.001,
            'cc' => 0.001,
            
            // Especiales
            'bulto' => $producto->unidades_por_bulto ?? 1,
            'caja' => $producto->unidades_por_bulto ?? 1,
            'docena' => 12
        ];
        
        return $factores[$unidad] ?? null;
    }
    
    /**
     * Convertir cantidad a unidad base del producto
     */
    private function convertirAUnidadBase(Producto $producto, float $cantidad, string $unidad): float
    {
        $unidadBase = $producto->unidad_medida ?? 'unidad';
        
        if ($unidad === $unidadBase) {
            return $cantidad;
        }
        
        $factorOrigen = $this->getFactorConversion($producto, $unidad);
        $factorBase = $this->getFactorConversion($producto, $unidadBase);
        
        if ($factorOrigen === null || $factorBase === null) {
            return $cantidad; // Si no se puede convertir, devolver cantidad original
        }
        
        return $cantidad * ($factorOrigen / $factorBase);
    }
    
    /**
     * Obtener nombre descriptivo de una unidad
     */
    private function getNombreUnidad(string $codigo): string
    {
        $nombres = [
            'unidad' => 'Unidad',
            'paca' => 'Paca',
            'bulto' => 'Bulto',
            'caja' => 'Caja',
            'kg' => 'Kilogramo',
            'g' => 'Gramo',
            'lb' => 'Libra',
            'l' => 'Litro',
            'ml' => 'Mililitro',
            'cc' => 'Centímetro cúbico',
            'galon' => 'Galón',
            'docena' => 'Docena'
        ];
        
        return $nombres[$codigo] ?? ucfirst($codigo);
    }
    
    /**
     * Obtener unidades disponibles desde productos equivalentes
     */
    private function obtenerUnidadesDeProductosEquivalentes(Producto $producto): \Illuminate\Support\Collection
    {
        $unidades = collect();
        
        // Agregar la unidad del producto actual
        $unidades->push($producto->unidad_medida ?? 'unidad');
        
        // Si es producto base, obtener unidades de productos equivalentes
        if ($producto->es_producto_base) {
            $productosEquivalentes = $producto->productosEquivalentes;
            foreach ($productosEquivalentes as $equivalente) {
                $unidades->push($equivalente->unidad_medida);
            }
        }
        
        // Si es producto equivalente, obtener unidades del producto base y hermanos
        if (!$producto->es_producto_base && $producto->producto_base_id) {
            $productoBase = $producto->productoBase;
            if ($productoBase) {
                $unidades->push($productoBase->unidad_medida);
                
                // Agregar unidades de productos hermanos
                $hermanos = $productoBase->productosEquivalentes->where('id', '!=', $producto->id);
                foreach ($hermanos as $hermano) {
                    $unidades->push($hermano->unidad_medida);
                }
            }
        }
        
        return $unidades->unique()->filter();
    }
    
    /**
     * Convertir usando productos equivalentes (nuevo sistema)
     */
    private function convertirConProductosEquivalentes(Producto $producto, string $unidadOrigen, string $unidadDestino, float $cantidad, float $precio): array
    {
        try {
            // Si las unidades son iguales, no hay conversión
            if ($unidadOrigen === $unidadDestino) {
                return [
                    'success' => true,
                    'data' => [
                        'cantidad_convertida' => $cantidad,
                        'precio_convertido' => $precio,
                        'factor_conversion' => 1.0,
                        'unidad_destino' => $unidadDestino,
                        'descripcion' => 'Sin conversión necesaria'
                    ]
                ];
            }
            
            // Obtener factor de conversión entre las unidades
            $factorConversion = $this->obtenerFactorEntreUnidades($producto, $unidadOrigen, $unidadDestino);
            
            if ($factorConversion === null) {
                return ['success' => false, 'message' => 'No se encontró conversión entre estas unidades'];
            }
            
            // Calcular cantidad convertida
            $cantidadConvertida = $cantidad * $factorConversion;
            
            // Calcular precio convertido (inversamente proporcional)
            $precioConvertido = $precio / $factorConversion;
            
            return [
                'success' => true,
                'data' => [
                    'cantidad_convertida' => round($cantidadConvertida, 3),
                    'precio_convertido' => round($precioConvertido, 2),
                    'factor_conversion' => $factorConversion,
                    'unidad_destino' => $unidadDestino,
                    'descripcion' => "Conversión: {$cantidad} {$unidadOrigen} = {$cantidadConvertida} {$unidadDestino}"
                ]
            ];
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error en conversión: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener factor de conversión entre dos unidades usando productos equivalentes
     */
    private function obtenerFactorEntreUnidades(Producto $producto, string $unidadOrigen, string $unidadDestino): ?float
    {
        // Obtener todos los productos de la familia (base + equivalentes)
        $productosRelacionados = collect([$producto]);
        
        if ($producto->es_producto_base) {
            $productosRelacionados = $productosRelacionados->merge($producto->productosEquivalentes);
        } elseif ($producto->producto_base_id) {
            $productoBase = $producto->productoBase;
            if ($productoBase) {
                $productosRelacionados = collect([$productoBase])
                    ->merge($productoBase->productosEquivalentes);
            }
        }
        
        // Buscar productos con las unidades origen y destino
        $productoOrigen = $productosRelacionados->firstWhere('unidad_medida', $unidadOrigen);
        $productoDestino = $productosRelacionados->firstWhere('unidad_medida', $unidadDestino);
        
        if (!$productoOrigen || !$productoDestino) {
            return null;
        }
        
        // Calcular factor de conversión usando los factor_stock
        // Factor = factor_destino / factor_origen
        $factorConversion = $productoDestino->factor_stock / $productoOrigen->factor_stock;
        
        return $factorConversion;
    }
}
