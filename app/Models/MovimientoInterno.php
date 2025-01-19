<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MovimientoInterno extends Model
{
    protected $table = 'movimientos_internos';

    protected $fillable = [
        'producto_id',
        'ubicacion_origen_id',
        'ubicacion_destino_id',
        'tipo_movimiento',
        'cantidad',
        'motivo',
        'observaciones',
        'user_id'
    ];

    // Constantes para tipos de movimiento
    const TIPO_ENTRADA = 'entrada';
    const TIPO_SALIDA = 'salida';
    const TIPO_TRASLADO = 'traslado';

    // Constantes para motivos
    const MOTIVO_AJUSTE = 'ajuste';
    const MOTIVO_AVERIA = 'averia';
    const MOTIVO_TRASLADO = 'traslado';
    const MOTIVO_VENTA = 'venta';

    // Relaciones
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function ubicacionOrigen()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_origen_id');
    }

    public function ubicacionDestino()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_destino_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes
    public function scopeFiltrarPorFecha($query, $fechaInicio, $fechaFin)
    {
        if ($fechaInicio && $fechaFin) {
            return $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
        }
        return $query;
    }

    public function scopeFiltrarPorTipo($query, $tipo)
    {
        if ($tipo) {
            return $query->where('tipo_movimiento', $tipo);
        }
        return $query;
    }

    // Método principal para realizar movimientos
    public static function realizarMovimiento($data)
    {
        Log::info('Iniciando realizarMovimiento', ['data' => $data]);
        
        DB::beginTransaction();
        try {
            Log::info('Creando registro de movimiento');
            $movimiento = self::create($data);
            
            Log::info('Movimiento creado, procesando según tipo', [
                'movimiento_id' => $movimiento->id,
                'tipo' => $data['tipo_movimiento']
            ]);

            // Procesar según tipo de movimiento
            switch ($data['tipo_movimiento']) {
                case self::TIPO_ENTRADA:
                    $movimiento->procesarEntrada();
                    break;
                case self::TIPO_SALIDA:
                    $movimiento->procesarSalida();
                    break;
                case self::TIPO_TRASLADO:
                    $movimiento->procesarTraslado();
                    break;
                default:
                    throw new \Exception('Tipo de movimiento no válido');
            }

            DB::commit();
            Log::info('Movimiento completado exitosamente', ['movimiento_id' => $movimiento->id]);
            
            return $movimiento;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error en realizarMovimiento', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    protected function procesarEntrada()
    {
        Log::info('Procesando entrada', [
            'movimiento_id' => $this->id,
            'producto_id' => $this->producto_id,
            'ubicacion_destino_id' => $this->ubicacion_destino_id,
            'cantidad' => $this->cantidad
        ]);

        $stockUbicacion = StockUbicacion::firstOrNew([
            'producto_id' => $this->producto_id,
            'ubicacion_id' => $this->ubicacion_destino_id
        ]);

        $stockUbicacion->stock = ($stockUbicacion->stock ?? 0) + $this->cantidad;
        $stockUbicacion->save();

        // Actualizar stock general del producto
        $this->producto->increment('stock', $this->cantidad);

        Log::info('Entrada procesada', [
            'nuevo_stock_ubicacion' => $stockUbicacion->stock,
            'nuevo_stock_general' => $this->producto->stock
        ]);
    }

    protected function procesarSalida()
    {
        Log::info('Procesando salida', [
            'movimiento_id' => $this->id,
            'producto_id' => $this->producto_id,
            'ubicacion_origen_id' => $this->ubicacion_origen_id,
            'cantidad' => $this->cantidad
        ]);

        $stockUbicacion = StockUbicacion::where([
            'producto_id' => $this->producto_id,
            'ubicacion_id' => $this->ubicacion_origen_id
        ])->firstOrFail();

        if ($stockUbicacion->stock < $this->cantidad) {
            Log::error('Stock insuficiente', [
                'stock_actual' => $stockUbicacion->stock,
                'cantidad_requerida' => $this->cantidad
            ]);
            throw new \Exception('Stock insuficiente en la ubicación de origen');
        }

        $stockUbicacion->stock -= $this->cantidad;
        $stockUbicacion->save();

        // Actualizar stock general del producto
        $this->producto->decrement('stock', $this->cantidad);

        Log::info('Salida procesada', [
            'nuevo_stock_ubicacion' => $stockUbicacion->stock,
            'nuevo_stock_general' => $this->producto->stock
        ]);
    }

    protected function procesarTraslado()
    {
        Log::info('Procesando traslado', [
            'movimiento_id' => $this->id,
            'producto_id' => $this->producto_id,
            'origen_id' => $this->ubicacion_origen_id,
            'destino_id' => $this->ubicacion_destino_id,
            'cantidad' => $this->cantidad
        ]);

        // Verificar que origen y destino sean diferentes
        if ($this->ubicacion_origen_id === $this->ubicacion_destino_id) {
            throw new \Exception('La ubicación de origen y destino no pueden ser la misma');
        }

        // Reducir stock en origen
        $stockOrigen = StockUbicacion::where([
            'producto_id' => $this->producto_id,
            'ubicacion_id' => $this->ubicacion_origen_id
        ])->firstOrFail();

        if ($stockOrigen->stock < $this->cantidad) {
            throw new \Exception('Stock insuficiente para realizar el traslado');
        }

        $stockOrigen->stock -= $this->cantidad;
        $stockOrigen->save();

        // Aumentar stock en destino
        $stockDestino = StockUbicacion::firstOrNew([
            'producto_id' => $this->producto_id,
            'ubicacion_id' => $this->ubicacion_destino_id
        ]);

        $stockDestino->stock = ($stockDestino->stock ?? 0) + $this->cantidad;
        $stockDestino->save();

        Log::info('Traslado completado', [
            'nuevo_stock_origen' => $stockOrigen->stock,
            'nuevo_stock_destino' => $stockDestino->stock
        ]);
    }

    // Método para obtener el resumen de movimientos
    public static function obtenerResumen($fechaInicio = null, $fechaFin = null)
    {
        $query = self::with(['producto', 'ubicacionOrigen', 'ubicacionDestino', 'usuario']);

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
        }

        return $query->latest()->get()->groupBy('tipo_movimiento');
    }

    // Getter para mostrar el tipo de movimiento formateado
    public function getTipoMovimientoFormateadoAttribute()
    {
        return ucfirst($this->tipo_movimiento);
    }

    // Getter para mostrar la fecha formateada
    public function getFechaFormateadaAttribute()
    {
        return $this->created_at->format('d/m/Y H:i:s');
    }
}