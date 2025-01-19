<?php

namespace App\Models\Traits;

use App\Models\Comprobante;
use App\Models\MovimientoContable;
use App\Models\ConfiguracionContable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait GeneraComprobanteContable 
{
   protected function generarComprobanteVenta()
   {
       Log::info('Iniciando generación de comprobante de venta', [
           'venta_id' => $this->id,
           'total' => $this->total
       ]);

       DB::transaction(function() {
           try {
               Log::info('Creando comprobante de venta');
               $comprobante = Comprobante::create([
                   'fecha' => $this->fecha_venta,
                   'tipo' => 'Ingreso', 
                   'numero' => $this->generarNumeroComprobante(),
                   'descripcion' => "Venta No. {$this->numero_factura}",
                   'estado' => 'Aprobado',
                   'created_by' => Auth::id(),
                   'total_debito' => $this->total,
                   'total_credito' => $this->total
               ]);

               Log::info('Generando movimientos contables - Caja');
               MovimientoContable::create([
                   'comprobante_id' => $comprobante->id,
                   'cuenta_id' => ConfiguracionContable::getCuentaPorConcepto('caja')->id,
                   'fecha' => $this->fecha_venta,
                   'descripcion' => "Venta No. {$this->numero_factura}",
                   'debito' => $this->total,
                   'credito' => 0
               ]);

               Log::info('Generando movimientos contables - Ventas');
               MovimientoContable::create([
                   'comprobante_id' => $comprobante->id,
                   'cuenta_id' => ConfiguracionContable::getCuentaPorConcepto('ventas')->id,
                   'fecha' => $this->fecha_venta, 
                   'descripcion' => "Venta No. {$this->numero_factura}",
                   'debito' => 0,
                   'credito' => $this->subtotal
               ]);

               Log::info('Generando movimientos contables - IVA');
               MovimientoContable::create([
                   'comprobante_id' => $comprobante->id,
                   'cuenta_id' => ConfiguracionContable::getCuentaPorConcepto('iva_ventas')->id,
                   'fecha' => $this->fecha_venta,
                   'descripcion' => "IVA Venta No. {$this->numero_factura}", 
                   'debito' => 0,
                   'credito' => $this->iva
               ]);

               Log::info('Comprobante de venta generado exitosamente');

           } catch(\Exception $e) {
               Log::error('Error al generar comprobante de venta', [
                   'error' => $e->getMessage(),
                   'trace' => $e->getTraceAsString()
               ]);
               throw $e;
           }
       });
   }

   protected function generarNumeroComprobante()
   {
       try {
           // Prefijo V para ventas
           $prefijo = 'V';
           
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