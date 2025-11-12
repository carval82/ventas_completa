<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\ConversionUnidad;

class ConversionController extends Controller
{
    /**
     * Obtener unidades disponibles para un producto
     */
    public function obtenerUnidadesDisponibles($productoId)
    {
        try {
            $producto = Producto::findOrFail($productoId);
            
            if (!$producto->permite_conversiones) {
                return response()->json([
                    'success' => true,
                    'unidades' => [
                        [
                            'codigo' => $producto->unidad_medida,
                            'nombre' => $this->getNombreUnidad($producto->unidad_medida),
                            'es_base' => true
                        ]
                    ]
                ]);
            }

            $unidades = [];
            $unidadesDisponibles = $producto->getUnidadesDisponibles();
            
            foreach ($unidadesDisponibles as $unidad) {
                $unidades[] = [
                    'codigo' => $unidad,
                    'nombre' => $this->getNombreUnidad($unidad),
                    'es_base' => $unidad === $producto->unidad_medida
                ];
            }

            return response()->json([
                'success' => true,
                'unidades' => $unidades
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener unidades: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convertir cantidad y calcular precio
     */
    public function convertir(Request $request)
    {
        try {
            $request->validate([
                'producto_id' => 'required|exists:productos,id',
                'cantidad' => 'required|numeric|min:0.01',
                'unidad_origen' => 'required|string',
                'unidad_destino' => 'required|string'
            ]);

            $producto = Producto::findOrFail($request->producto_id);
            
            // Convertir cantidad
            $cantidadConvertida = $producto->convertirCantidad(
                $request->cantidad, 
                $request->unidad_destino
            );

            if ($cantidadConvertida === null) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se puede convertir entre estas unidades'
                ], 400);
            }

            // Calcular precio por unidad convertida
            $precioUnitario = $producto->calcularPrecioPorUnidad($request->unidad_destino);
            $precioTotal = $precioUnitario * $request->cantidad;

            // Validar stock disponible
            $stockSuficiente = $producto->validarStockConversion(
                $request->cantidad, 
                $request->unidad_destino
            );

            // Calcular cuánto stock queda en la unidad solicitada
            $stockDisponibleConvertido = null;
            if ($producto->permite_conversiones) {
                $stockDisponibleConvertido = $producto->convertirCantidad(
                    $producto->stock, 
                    $request->unidad_destino
                );
            }

            return response()->json([
                'success' => true,
                'conversion' => [
                    'cantidad_original' => $request->cantidad,
                    'unidad_original' => $request->unidad_origen,
                    'cantidad_convertida' => round($cantidadConvertida, 4),
                    'unidad_convertida' => $request->unidad_destino,
                    'precio_unitario' => round($precioUnitario, 2),
                    'precio_total' => round($precioTotal, 2),
                    'stock_suficiente' => $stockSuficiente,
                    'stock_disponible' => $stockDisponibleConvertido ? round($stockDisponibleConvertido, 4) : $producto->stock,
                    'unidad_stock' => $request->unidad_destino
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error en conversión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información completa de conversión para un producto
     */
    public function informacionProducto($productoId)
    {
        try {
            $producto = Producto::with(['stockUbicaciones'])->findOrFail($productoId);
            
            return response()->json([
                'success' => true,
                'producto' => [
                    'id' => $producto->id,
                    'codigo' => $producto->codigo,
                    'nombre' => $producto->nombre,
                    'unidad_base' => $producto->unidad_medida,
                    'precio_base' => $producto->precio_final,
                    'stock_base' => $producto->stock,
                    'permite_conversiones' => $producto->permite_conversiones,
                    'peso_por_unidad' => $producto->peso_por_unidad,
                    'volumen_por_unidad' => $producto->volumen_por_unidad,
                    'unidades_por_bulto' => $producto->unidades_por_bulto,
                    'peso_por_bulto' => $producto->peso_por_bulto,
                    'unidades_disponibles' => $producto->getUnidadesDisponibles()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener información: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener nombre legible de la unidad
     */
    private function getNombreUnidad($codigo)
    {
        $nombres = [
            'unit' => 'Unidad',
            'dozen' => 'Docena',
            'box' => 'Caja',
            'pack' => 'Paquete', 
            'bulto' => 'Bulto',
            'kg' => 'Kilogramo',
            'g' => 'Gramo',
            'lb' => 'Libra',
            'oz' => 'Onza',
            'l' => 'Litro',
            'ml' => 'Mililitro',
            'cc' => 'Centímetro Cúbico',
            'gal' => 'Galón',
            'm' => 'Metro',
            'cm' => 'Centímetro',
            'mm' => 'Milímetro',
            'service' => 'Servicio'
        ];

        return $nombres[$codigo] ?? ucfirst($codigo);
    }
}
