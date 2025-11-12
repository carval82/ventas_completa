<?php

namespace App\Services;

use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Producto;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para manejar la integración entre el sistema de equivalencias
 * y la API de Alegra para facturación electrónica
 */
class AlegraEquivalenciasService
{
    /**
     * Convierte los detalles de venta con equivalencias a formato compatible con Alegra
     * 
     * @param Venta $venta
     * @return array
     */
    public function prepararItemsParaAlegra(Venta $venta): array
    {
        $items = [];
        
        foreach ($venta->detalles as $detalle) {
            $itemAlegra = $this->convertirDetalleParaAlegra($detalle);
            $items[] = $itemAlegra;
        }
        
        return $items;
    }
    
    /**
     * Convierte un detalle de venta individual para Alegra
     * 
     * @param DetalleVenta $detalle
     * @return array
     */
    public function convertirDetalleParaAlegra(DetalleVenta $detalle): array
    {
        $producto = $detalle->producto;
        
        // Verificar si el producto tiene equivalencias
        if ($this->tieneEquivalencias($producto)) {
            return $this->procesarProductoConEquivalencias($detalle);
        } else {
            return $this->procesarProductoSimple($detalle);
        }
    }
    
    /**
     * Verifica si un producto tiene sistema de equivalencias
     * 
     * @param Producto $producto
     * @return bool
     */
    private function tieneEquivalencias(Producto $producto): bool
    {
        return $producto->es_producto_base === false && 
               !is_null($producto->producto_base_id) && 
               !is_null($producto->factor_stock);
    }
    
    /**
     * Procesa un producto con equivalencias para Alegra
     * 
     * @param DetalleVenta $detalle
     * @return array
     */
    private function procesarProductoConEquivalencias(DetalleVenta $detalle): array
    {
        $producto = $detalle->producto;
        $productoBase = $producto->productoBase;
        
        // OPCIÓN 1: Convertir a unidad base (RECOMENDADO)
        $cantidadBase = $detalle->cantidad * $producto->factor_stock;
        $precioBase = $detalle->precio_unitario / $producto->factor_stock;
        
        // Crear descripción detallada
        $descripcionOriginal = $detalle->cantidad . ' ' . $this->obtenerUnidadMedida($producto);
        $descripcionBase = $cantidadBase . ' ' . $this->obtenerUnidadMedida($productoBase);
        $descripcionCompleta = $producto->nombre . ' (' . $descripcionOriginal . ' = ' . $descripcionBase . ')';
        
        // Log para auditoría
        Log::info('🔄 Conversión Alegra - Producto con equivalencias', [
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'cantidad_original' => $detalle->cantidad,
            'unidad_original' => $this->obtenerUnidadMedida($producto),
            'cantidad_base' => $cantidadBase,
            'unidad_base' => $this->obtenerUnidadMedida($productoBase),
            'precio_original' => $detalle->precio_unitario,
            'precio_base' => $precioBase,
            'factor_conversion' => $producto->factor_stock
        ]);
        
        return [
            'id' => intval($productoBase->id_alegra), // Usar ID del producto base
            'price' => round($precioBase, 2),
            'quantity' => round($cantidadBase, 3),
            'description' => $descripcionCompleta,
            'unit' => $this->mapearUnidadParaAlegra($productoBase), // Unidad estándar
            // Metadatos para trazabilidad
            'metadata' => [
                'producto_equivalente_id' => $producto->id,
                'cantidad_original' => $detalle->cantidad,
                'unidad_original' => $this->obtenerUnidadMedida($producto),
                'factor_conversion' => $producto->factor_stock
            ]
        ];
    }
    
    /**
     * Procesa un producto simple (sin equivalencias) para Alegra
     * 
     * @param DetalleVenta $detalle
     * @return array
     */
    private function procesarProductoSimple(DetalleVenta $detalle): array
    {
        $producto = $detalle->producto;
        
        Log::info('📦 Producto simple para Alegra', [
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'cantidad' => $detalle->cantidad,
            'precio' => $detalle->precio_unitario
        ]);
        
        return [
            'id' => intval($producto->id_alegra),
            'price' => round($detalle->precio_unitario, 2),
            'quantity' => round($detalle->cantidad, 3),
            'description' => $producto->nombre,
            'unit' => $this->mapearUnidadParaAlegra($producto)
        ];
    }
    
    /**
     * Obtiene la unidad de medida de un producto
     * 
     * @param Producto $producto
     * @return string
     */
    private function obtenerUnidadMedida(Producto $producto): string
    {
        // Prioridad: unidad_medida > unidad > "unidad" por defecto
        return $producto->unidad_medida ?? $producto->unidad ?? 'unidad';
    }
    
    /**
     * Mapea unidades del sistema a unidades estándar de Alegra/DIAN
     * 
     * @param Producto $producto
     * @return string
     */
    private function mapearUnidadParaAlegra(Producto $producto): string
    {
        $unidad = strtolower($this->obtenerUnidadMedida($producto));
        
        // Mapeo de unidades personalizadas a unidades DIAN estándar
        $mapeoUnidades = [
            // Peso
            'paca' => 'KGM',      // Kilogramo
            'bulto' => 'KGM',     // Kilogramo
            'saco' => 'KGM',      // Kilogramo
            'kg' => 'KGM',        // Kilogramo
            'kilo' => 'KGM',      // Kilogramo
            'kilogramo' => 'KGM', // Kilogramo
            'lb' => 'LBR',        // Libra
            'libra' => 'LBR',     // Libra
            'g' => 'GRM',         // Gramo
            'gramo' => 'GRM',     // Gramo
            
            // Volumen
            'l' => 'LTR',         // Litro
            'litro' => 'LTR',     // Litro
            'ml' => 'MLT',        // Mililitro
            'mililitro' => 'MLT', // Mililitro
            'cc' => 'CMQ',        // Centímetro cúbico
            'galon' => 'GLI',     // Galón
            'galón' => 'GLI',     // Galón
            
            // Cantidad
            'unidad' => 'NIU',    // Número de unidades
            'und' => 'NIU',       // Número de unidades
            'pza' => 'NIU',       // Número de unidades
            'pieza' => 'NIU',     // Número de unidades
            'caja' => 'BX',       // Caja
            'docena' => 'DZN',    // Docena
            
            // Longitud
            'm' => 'MTR',         // Metro
            'metro' => 'MTR',     // Metro
            'cm' => 'CMT',        // Centímetro
            'centimetro' => 'CMT', // Centímetro
        ];
        
        $unidadDian = $mapeoUnidades[$unidad] ?? 'NIU'; // Por defecto: Número de unidades
        
        Log::info('🏷️ Mapeo de unidad para Alegra', [
            'unidad_original' => $unidad,
            'unidad_dian' => $unidadDian
        ]);
        
        return $unidadDian;
    }
    
    /**
     * Valida que los datos estén listos para enviar a Alegra
     * 
     * @param array $items
     * @return array
     */
    public function validarItemsAlegra(array $items): array
    {
        $errores = [];
        
        foreach ($items as $index => $item) {
            // Validar campos requeridos
            if (!isset($item['id']) || empty($item['id'])) {
                $errores[] = "Item {$index}: ID de producto requerido";
            }
            
            if (!isset($item['price']) || $item['price'] <= 0) {
                $errores[] = "Item {$index}: Precio debe ser mayor a 0";
            }
            
            if (!isset($item['quantity']) || $item['quantity'] <= 0) {
                $errores[] = "Item {$index}: Cantidad debe ser mayor a 0";
            }
            
            // Validar rangos
            if (isset($item['quantity']) && $item['quantity'] > 999999) {
                $errores[] = "Item {$index}: Cantidad excede el límite máximo";
            }
            
            if (isset($item['price']) && $item['price'] > 999999999) {
                $errores[] = "Item {$index}: Precio excede el límite máximo";
            }
        }
        
        if (!empty($errores)) {
            Log::error('❌ Errores de validación para Alegra', [
                'errores' => $errores,
                'items' => $items
            ]);
        }
        
        return $errores;
    }
    
    /**
     * Genera un reporte de conversiones realizadas
     * 
     * @param Venta $venta
     * @return array
     */
    public function generarReporteConversiones(Venta $venta): array
    {
        $reporte = [
            'venta_id' => $venta->id,
            'fecha' => $venta->fecha_venta->format('Y-m-d H:i:s'),
            'conversiones' => [],
            'productos_simples' => [],
            'total_items' => 0,
            'items_convertidos' => 0
        ];
        
        foreach ($venta->detalles as $detalle) {
            $reporte['total_items']++;
            
            if ($this->tieneEquivalencias($detalle->producto)) {
                $reporte['items_convertidos']++;
                $reporte['conversiones'][] = [
                    'producto' => $detalle->producto->nombre,
                    'cantidad_original' => $detalle->cantidad,
                    'unidad_original' => $this->obtenerUnidadMedida($detalle->producto),
                    'cantidad_base' => $detalle->cantidad * $detalle->producto->factor_stock,
                    'unidad_base' => $this->obtenerUnidadMedida($detalle->producto->productoBase),
                    'factor_conversion' => $detalle->producto->factor_stock
                ];
            } else {
                $reporte['productos_simples'][] = [
                    'producto' => $detalle->producto->nombre,
                    'cantidad' => $detalle->cantidad,
                    'unidad' => $this->obtenerUnidadMedida($detalle->producto)
                ];
            }
        }
        
        return $reporte;
    }
}
