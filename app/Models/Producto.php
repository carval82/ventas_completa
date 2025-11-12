<?php

namespace App\Models;
use App\Models\StockUbicacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Picqer\Barcode\BarcodeGeneratorPNG;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'precio_compra',
        'precio_venta',
        'precio_final',
        'valor_iva',
        'porcentaje_ganancia',
        'stock',
        'stock_minimo',
        'estado',
        'id_alegra',
        'unidad_medida',
        'peso_bulto',
        'permite_conversiones',
        'peso_por_unidad',
        'volumen_por_unidad',
        'unidades_por_bulto',
        'peso_por_bulto',
        'unidad_venta_alternativa',
        'producto_base_id',
        'factor_stock',
        'es_producto_base'
    ];

    protected $casts = [
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'precio_final' => 'decimal:2',
        'valor_iva' => 'decimal:2',
        'porcentaje_ganancia' => 'decimal:2',
        'stock' => 'integer',
        'stock_minimo' => 'integer',
        'estado' => 'boolean',
        'peso_bulto' => 'decimal:3',
        'permite_conversiones' => 'boolean',
        'peso_por_unidad' => 'decimal:4',
        'volumen_por_unidad' => 'decimal:4',
        'unidades_por_bulto' => 'integer',
        'peso_por_bulto' => 'decimal:4',
        'factor_stock' => 'decimal:6',
        'es_producto_base' => 'boolean'
    ];

    // Relación con ubicaciones a través de stock_ubicaciones
    public function ubicaciones()
    {
        return $this->belongsToMany(Ubicacion::class, 'stock_ubicaciones')
                    ->withPivot('stock')
                    ->withTimestamps();
    }

    // Obtener stock total sumando todas las ubicaciones
    public function getStockTotalAttribute()
    {
        return $this->ubicaciones()->sum('stock');
    }

    // Obtener stock en una ubicación específica
    public function getStockEnUbicacion($ubicacion_id)
    {
        return $this->ubicaciones()
                    ->where('ubicacion_id', $ubicacion_id)
                    ->first()
                    ->pivot
                    ->stock ?? 0;
    }

    // Relaciones existentes
    public function detalleVentas()
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function detalleCompras()
    {
        return $this->hasMany(DetalleCompra::class);
    }

    public function stockUbicaciones()
{
    return $this->hasMany(StockUbicacion::class, 'producto_id');
}

public function proveedores()
{
    return $this->belongsToMany(Proveedor::class, 'producto_proveedor')
        ->withPivot('precio_compra', 'codigo_proveedor')
        ->withTimestamps();
}

public function movimientos()
{
    return $this->hasMany(Movimiento::class);
}

/**
 * Obtiene los códigos relacionados asociados a este producto.
 */
public function codigosRelacionados()
{
    return $this->hasMany(CodigoRelacionado::class);
}

/**
 * Sincroniza el producto con Alegra
 * Si el producto ya tiene un id_alegra, se actualiza
 * Si no, se crea un nuevo producto en Alegra
 * 
 * @return array Resultado de la operación
 */
public function syncToAlegra()
{
    try {
        // Si ya tiene un ID de Alegra, no es necesario sincronizar
        if ($this->id_alegra) {
            return [
                'success' => true,
                'message' => 'Producto ya sincronizado con Alegra',
                'id_alegra' => $this->id_alegra
            ];
        }

        // Obtener servicio de Alegra
        $alegraService = app(\App\Http\Services\AlegraService::class);
        
        // Primero buscar si el producto ya existe en Alegra por su referencia
        $productosAlegra = $alegraService->obtenerProductos();
        
        if ($productosAlegra['success']) {
            foreach ($productosAlegra['data'] as $productoAlegra) {
                // Verificar si existe un producto con la misma referencia
                if (isset($productoAlegra['reference']) && $productoAlegra['reference'] == $this->codigo) {
                    // Guardar el ID de Alegra en el producto
                    $this->id_alegra = $productoAlegra['id'];
                    $this->save();
                    
                    \Log::info('Producto encontrado en Alegra', [
                        'producto_id' => $this->id,
                        'alegra_id' => $this->id_alegra
                    ]);
                    
                    return [
                        'success' => true,
                        'message' => 'Producto encontrado y vinculado con Alegra',
                        'id_alegra' => $this->id_alegra
                    ];
                }
            }
        }
        
        // Si no existe, crear producto en Alegra
        $result = $alegraService->crearProductoAlegra($this);
        
        if ($result['success']) {
            // Guardar el ID de Alegra en el producto
            $this->id_alegra = $result['data']['id'];
            $this->save();
            
            return [
                'success' => true,
                'message' => 'Producto sincronizado con Alegra',
                'id_alegra' => $this->id_alegra
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Error al sincronizar producto con Alegra',
            'error' => $result['error'] ?? 'Error desconocido'
        ];
    } catch (\Exception $e) {
        \Log::error('Error al sincronizar producto con Alegra', [
            'producto_id' => $this->id,
            'error' => $e->getMessage()
        ]);
        
        return [
            'success' => false,
            'message' => 'Error al sincronizar producto con Alegra',
            'error' => $e->getMessage()
        ];
    }
}

public static function regularizarProductos()
{
    try {
        Log::info("Iniciando proceso de regularización de productos");
        
        DB::beginTransaction();

        // Obtener todos los productos con sus stocks
        $productos = self::with(['stockUbicaciones'])->get();
        
        foreach ($productos as $producto) {
            Log::info("Regularizando producto", [
                'id' => $producto->id,
                'codigo' => $producto->codigo,
            ]);

            // Calcular stock total
            $stockTotal = $producto->stockUbicaciones->sum('stock');
            
            // Obtener el último movimiento con costo
            $ultimoMovimiento = MovimientoMasivoDetalle::where('producto_id', $producto->id)
                ->where('costo_unitario', '>', 0)
                ->orderBy('created_at', 'desc')
                ->first();

            // Actualizar producto
            $producto->update([
                'stock' => $stockTotal,
                'precio_compra' => $ultimoMovimiento ? $ultimoMovimiento->costo_unitario : $producto->precio_compra,
                'estado' => $stockTotal > 0 ? 1 : 0
            ]);

            Log::info("Producto regularizado", [
                'id' => $producto->id,
                'nuevo_stock' => $stockTotal,
                'nuevo_precio' => $producto->precio_compra,
                'nuevo_estado' => $producto->estado
            ]);
        }

        DB::commit();
        Log::info("Proceso de regularización completado exitosamente");

        return true;

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Error en regularización de productos", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}

public function generarCodigoBarras()
    {
        $generator = new BarcodeGeneratorPNG();
        // Ajustamos altura y ancho para el sticker
        return $generator->getBarcode(
            $this->codigo, 
            $generator::TYPE_CODE_128,
            2,  // grosor
            30, // altura
            [0, 0, 0] // color negro
        );
    }

    /**
     * Convertir cantidad de una unidad a otra
     */
    public function convertirCantidad($cantidad, $unidadDestino)
    {
        if (!$this->permite_conversiones) {
            return null;
        }

        // Si es la misma unidad, no hay conversión
        if ($this->unidad_medida === $unidadDestino) {
            return $cantidad;
        }

        // Usar conversiones estándar
        $cantidadConvertida = ConversionUnidad::convertir(
            $cantidad, 
            $this->unidad_medida, 
            $unidadDestino
        );

        if ($cantidadConvertida !== null) {
            return $cantidadConvertida;
        }

        // Conversiones específicas del producto
        return $this->convertirCantidadEspecifica($cantidad, $unidadDestino);
    }

    /**
     * Conversiones específicas basadas en los campos del producto
     */
    private function convertirCantidadEspecifica($cantidad, $unidadDestino)
    {
        $unidadOrigen = $this->unidad_medida;

        // Conversiones de unidad a peso
        if ($unidadOrigen === 'unit' && in_array($unidadDestino, ['kg', 'g']) && $this->peso_por_unidad) {
            $pesoTotal = $cantidad * $this->peso_por_unidad;
            return $unidadDestino === 'kg' ? $pesoTotal : $pesoTotal * 1000;
        }

        // Conversiones de peso a unidad
        if (in_array($unidadOrigen, ['kg', 'g']) && $unidadDestino === 'unit' && $this->peso_por_unidad) {
            $pesoEnKg = $unidadOrigen === 'kg' ? $cantidad : $cantidad / 1000;
            return $pesoEnKg / $this->peso_por_unidad;
        }

        // Conversiones de bulto a unidades
        if ($unidadOrigen === 'bulto' && $unidadDestino === 'unit' && $this->unidades_por_bulto) {
            return $cantidad * $this->unidades_por_bulto;
        }

        // Conversiones de unidades a bulto
        if ($unidadOrigen === 'unit' && $unidadDestino === 'bulto' && $this->unidades_por_bulto) {
            return $cantidad / $this->unidades_por_bulto;
        }

        // Conversiones de bulto a peso
        if ($unidadOrigen === 'bulto' && in_array($unidadDestino, ['kg', 'g']) && $this->peso_por_bulto) {
            $pesoTotal = $cantidad * $this->peso_por_bulto;
            return $unidadDestino === 'kg' ? $pesoTotal : $pesoTotal * 1000;
        }

        // Conversiones de peso a bulto
        if (in_array($unidadOrigen, ['kg', 'g']) && $unidadDestino === 'bulto' && $this->peso_por_bulto) {
            $pesoEnKg = $unidadOrigen === 'kg' ? $cantidad : $cantidad / 1000;
            return $pesoEnKg / $this->peso_por_bulto;
        }

        // Conversiones de unidad a volumen
        if ($unidadOrigen === 'unit' && in_array($unidadDestino, ['l', 'ml', 'cc']) && $this->volumen_por_unidad) {
            $volumenTotal = $cantidad * $this->volumen_por_unidad;
            if ($unidadDestino === 'l') return $volumenTotal;
            if ($unidadDestino === 'ml' || $unidadDestino === 'cc') return $volumenTotal * 1000;
        }

        // Conversiones de volumen a unidad
        if (in_array($unidadOrigen, ['l', 'ml', 'cc']) && $unidadDestino === 'unit' && $this->volumen_por_unidad) {
            $volumenEnLitros = $unidadOrigen === 'l' ? $cantidad : $cantidad / 1000;
            return $volumenEnLitros / $this->volumen_por_unidad;
        }

        return null;
    }

    /**
     * Calcular precio por unidad convertida
     */
    public function calcularPrecioPorUnidad($unidadDestino)
    {
        if (!$this->permite_conversiones || $this->unidad_medida === $unidadDestino) {
            return $this->precio_final;
        }

        $factorConversion = $this->convertirCantidad(1, $unidadDestino);
        
        if ($factorConversion === null) {
            return null;
        }

        return $this->precio_final / $factorConversion;
    }

    /**
     * Obtener unidades de conversión disponibles para este producto
     */
    public function getUnidadesDisponibles()
    {
        if (!$this->permite_conversiones) {
            return [$this->unidad_medida];
        }

        $unidades = [$this->unidad_medida];

        // Agregar conversiones estándar
        $conversionesEstandar = ConversionUnidad::obtenerConversionesDisponibles($this->unidad_medida);
        foreach ($conversionesEstandar as $conversion) {
            $unidades[] = $conversion['unidad'];
        }

        // Agregar conversiones específicas del producto
        if ($this->peso_por_unidad && $this->unidad_medida === 'unit') {
            $unidades = array_merge($unidades, ['kg', 'g']);
        }

        if ($this->volumen_por_unidad && $this->unidad_medida === 'unit') {
            $unidades = array_merge($unidades, ['l', 'ml', 'cc']);
        }

        if ($this->unidades_por_bulto && $this->unidad_medida === 'bulto') {
            $unidades[] = 'unit';
        }

        if ($this->peso_por_bulto && $this->unidad_medida === 'bulto') {
            $unidades = array_merge($unidades, ['kg', 'g']);
        }

        return array_unique($unidades);
    }

    /**
     * Validar si hay suficiente stock para una venta con conversión
     */
    public function validarStockConversion($cantidadSolicitada, $unidadSolicitada)
    {
        // Convertir la cantidad solicitada a la unidad base del producto
        $cantidadEnUnidadBase = $this->convertirCantidad($cantidadSolicitada, $this->unidad_medida);
        
        if ($cantidadEnUnidadBase === null) {
            return false;
        }

        return $this->stock >= $cantidadEnUnidadBase;
    }
   
    
    // ==================== RELACIONES PARA PRODUCTOS EQUIVALENTES ====================
    
    /**
     * Relación con el producto base (padre)
     */
    public function productoBase()
    {
        return $this->belongsTo(Producto::class, 'producto_base_id');
    }
    
    /**
     * Relación con productos equivalentes (hijos)
     */
    public function productosEquivalentes()
    {
        return $this->hasMany(Producto::class, 'producto_base_id');
    }
    
    /**
     * Obtener el stock unificado (del producto base)
     */
    public function getStockUnificadoAttribute()
    {
        if ($this->es_producto_base) {
            return $this->stock;
        }
        
        return $this->productoBase ? $this->productoBase->stock : $this->stock;
    }
    
    /**
     * Actualizar stock unificado
     */
    public function actualizarStockUnificado($cantidad, $operacion = 'restar')
    {
        $productoBase = $this->es_producto_base ? $this : $this->productoBase;
        
        if (!$productoBase) {
            return false;
        }
        
        // Convertir cantidad a unidades del producto base
        $cantidadEnUnidadBase = $cantidad * $this->factor_stock;
        
        if ($operacion === 'restar') {
            $productoBase->stock -= $cantidadEnUnidadBase;
        } else {
            $productoBase->stock += $cantidadEnUnidadBase;
        }
        
        return $productoBase->save();
    }
    
    /**
     * Verificar si hay suficiente stock unificado
     */
    public function tieneStockUnificado($cantidad)
    {
        $productoBase = $this->es_producto_base ? $this : $this->productoBase;
        
        if (!$productoBase) {
            return false;
        }
        
        $cantidadEnUnidadBase = $cantidad * $this->factor_stock;
        return $productoBase->stock >= $cantidadEnUnidadBase;
    }
   
}