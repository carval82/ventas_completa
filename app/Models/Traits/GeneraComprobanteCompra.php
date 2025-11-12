<?php

namespace App\Models\Traits;

use App\Models\Comprobante;
use App\Models\MovimientoContable;
use App\Models\ConfiguracionContable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait GeneraComprobanteCompra
{
    protected function generarComprobanteCompra() 
    {
        Log::info('Iniciando generación de comprobante de compra', [
            'compra_id' => $this->id,
            'total' => $this->total,
            'subtotal' => $this->subtotal,
            'iva' => $this->iva
        ]);

        DB::transaction(function() {
            try {
                // Log número de comprobante
                $numero = $this->generarNumeroComprobante();
                Log::info('Número de comprobante generado', ['numero' => $numero]);

                Log::info('Creando comprobante de compra', [
                    'fecha' => $this->fecha_compra,
                    'numero' => $numero
                ]);

                $comprobante = Comprobante::create([
                    'fecha' => $this->fecha_compra,
                    'tipo' => 'Egreso',
                    'numero' => $numero,
                    'descripcion' => "Compra No. {$this->numero_factura}",
                    'estado' => 'Aprobado', 
                    'created_by' => Auth::id(),
                    'total_debito' => $this->total,
                    'total_credito' => $this->total
                ]);
                
                Log::info('Comprobante creado exitosamente', ['comprobante_id' => $comprobante->id]);

                // Verificar cuenta inventario
                $cuenta_inventario = ConfiguracionContable::getCuentaPorConcepto('inventario');
                Log::info('Cuenta inventario obtenida', ['cuenta' => $cuenta_inventario ? $cuenta_inventario->toArray() : null]);

                // Verificar si la cuenta de inventario existe
                if (!$cuenta_inventario) {
                    Log::error('No se encontró la cuenta de inventario configurada');
                    throw new \Exception('No se encontró la cuenta de inventario configurada. Por favor configure las cuentas contables.');
                }

                // Calcular valores basados en los detalles de la compra
                $subtotal_sin_iva = 0;
                $total_iva = 0;
                
                // Recorrer los detalles de la compra para calcular los valores correctos
                foreach ($this->detalles as $detalle) {
                    if ($detalle->tiene_iva) {
                        $subtotal_sin_iva += $detalle->subtotal;
                        $total_iva += $detalle->valor_iva;
                    } else {
                        $subtotal_sin_iva += $detalle->subtotal;
                    }
                }
                
                Log::info('Valores calculados basados en detalles', [
                    'subtotal_sin_iva' => $subtotal_sin_iva,
                    'total_iva' => $total_iva
                ]);
                
                Log::info('Generando movimientos contables - Inventario');
                MovimientoContable::create([
                    'comprobante_id' => $comprobante->id,
                    'cuenta_id' => $cuenta_inventario->id,
                    'fecha' => $this->fecha_compra,
                    'descripcion' => "Compra No. {$this->numero_factura}",
                    'debito' => $subtotal_sin_iva,
                    'credito' => 0
                ]);

                // Verificar cuenta IVA
                $cuenta_iva = ConfiguracionContable::getCuentaPorConcepto('iva_compras');
                Log::info('Cuenta IVA obtenida', ['cuenta' => $cuenta_iva ? $cuenta_iva->toArray() : null]);

                // Verificar si la cuenta de IVA existe
                if (!$cuenta_iva) {
                    Log::error('No se encontró la cuenta de IVA de compras configurada');
                    throw new \Exception('No se encontró la cuenta de IVA de compras configurada. Por favor configure las cuentas contables.');
                }

                // Solo crear movimiento de IVA si hay productos con IVA
                if ($total_iva > 0) {
                    Log::info('Generando movimientos contables - IVA compras'); 
                    MovimientoContable::create([
                        'comprobante_id' => $comprobante->id,
                        'cuenta_id' => $cuenta_iva->id,
                        'fecha' => $this->fecha_compra,
                        'descripcion' => "IVA Compra No. {$this->numero_factura}",
                        'debito' => $total_iva,
                        'credito' => 0  
                    ]);
                } else {
                    Log::info('No se generó movimiento de IVA porque ningún producto tiene IVA');
                }

                // Verificar cuenta proveedores
                $cuenta_proveedores = ConfiguracionContable::getCuentaPorConcepto('proveedores');
                Log::info('Cuenta proveedores obtenida', ['cuenta' => $cuenta_proveedores ? $cuenta_proveedores->toArray() : null]);

                Log::info('Generando movimientos contables - Proveedores');
                // Calcular el total real (subtotal + IVA)
                $total_real = $subtotal_sin_iva + $total_iva;
                
                MovimientoContable::create([
                    'comprobante_id' => $comprobante->id,
                    'cuenta_id' => $cuenta_proveedores->id,
                    'fecha' => $this->fecha_compra,
                    'descripcion' => "Compra No. {$this->numero_factura}",
                    'debito' => 0,
                    'credito' => $total_real
                ]);
                
                Log::info('Total calculado para el movimiento de proveedores', [
                    'subtotal_sin_iva' => $subtotal_sin_iva,
                    'total_iva' => $total_iva,
                    'total_real' => $total_real
                ]);

                Log::info('Comprobante de compra generado exitosamente');

            } catch(\Exception $e) {
                Log::error('Error al generar comprobante de compra', [
                    'error' => $e->getMessage(),
                    'linea' => $e->getLine(), 
                    'archivo' => $e->getFile(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        });
    }

    protected function generarNumeroComprobante()
{
    try {
        // Prefijo C para compras
        $prefijo = 'C';
        
        // Obtener el último número con este prefijo
        $ultimoNumero = DB::table('comprobantes')
            ->where('numero', 'LIKE', $prefijo . '%')
            ->orderBy('numero', 'desc')
            ->value('numero');
            
        Log::info('Último número encontrado', [
            'prefijo' => $prefijo,
            'ultimo_numero' => $ultimoNumero
        ]);
        
        if (!$ultimoNumero) {
            $siguiente = $prefijo . '000001';
        } else {
            // Extraer el número sin el prefijo
            $numero = intval(substr($ultimoNumero, 1));
            $siguiente = $prefijo . str_pad($numero + 1, 6, '0', STR_PAD_LEFT);
        }
        
        Log::info('Generando siguiente número', [
            'siguiente' => $siguiente
        ]);
        
        return $siguiente;
        
    } catch(\Exception $e) {
        Log::error('Error al generar número de comprobante', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}
}