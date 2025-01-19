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
        'stock',
        'stock_minimo',
       
        'estado'
    ];

    protected $casts = [
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'stock' => 'integer',
        'stock_minimo' => 'integer',
        'estado' => 'boolean'
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
   
    
}