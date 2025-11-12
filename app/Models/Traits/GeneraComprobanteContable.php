<?php

namespace App\Models\Traits;

use App\Models\Comprobante;
use App\Models\MovimientoContable;
use App\Models\ConfiguracionContable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

trait GeneraComprobanteContable 
{
   public function generarComprobanteVenta()
   {
       try {
           Log::info('Iniciando generación de comprobante de venta', [
               'venta_id' => $this->id,
               'total' => $this->total
           ]);

           DB::transaction(function () {
               try {
                   Log::info('Creando comprobante de venta');
                   
                   // Obtener configuración contable
                   $config = ConfiguracionContable::first();
                   if (!$config) {
                       throw new \Exception('No se ha configurado la contabilidad');
                   }

                   // Generar número de comprobante
                   $ultimoNumero = Comprobante::where('prefijo', 'V')
                       ->orderBy('id', 'desc')
                       ->first();

                   $siguienteNumero = str_pad(
                       ($ultimoNumero ? intval($ultimoNumero->numero) + 1 : 1), 
                       6, 
                       '0', 
                       STR_PAD_LEFT
                   );

                   $comprobante = Comprobante::create([
                       'fecha' => Carbon::now(),  // Cambia esto
                       'tipo' => 'Ingreso',
                       'prefijo' => 'V',
                       'numero' => $siguienteNumero,
                       'descripcion' => "Venta No. {$this->numero_factura}",
                       'estado' => 'Aprobado',
                       'created_by' => Auth::id() ?? 1,
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

                   // Calcular subtotal e IVA basado en detalles
                   $subtotalSinIva = 0;
                   $totalIva = 0;
                   
                   // Verificar si hay detalles
                   if ($this->detalles && $this->detalles->count() > 0) {
                       // Iterar sobre los detalles para obtener valores precisos
                       foreach ($this->detalles as $detalle) {
                           // Si el detalle tiene los nuevos campos de IVA
                           if (isset($detalle->tiene_iva) && isset($detalle->valor_iva)) {
                               $subtotalSinIva += $detalle->subtotal;
                               $totalIva += $detalle->valor_iva;
                           } else {
                               // Compatibilidad con registros antiguos
                               $subtotalSinIva += $detalle->subtotal;
                           }
                       }
                       
                       // Si no se calculó IVA desde detalles, usar el IVA general
                       if ($totalIva == 0 && $this->iva > 0) {
                           $totalIva = $this->iva;
                       }
                   } else {
                       // Si no hay detalles, usar valores directos de la venta
                       $subtotalSinIva = $this->subtotal;
                       $totalIva = $this->iva;
                   }
                   
                   // Asegurar que el subtotal no sea cero
                   if ($subtotalSinIva == 0) {
                       $subtotalSinIva = $this->total - $totalIva;
                   }
                   
                   Log::info('Valores calculados para comprobante de venta', [
                       'subtotal_sin_iva' => $subtotalSinIva,
                       'total_iva' => $totalIva,
                       'total' => $this->total
                   ]);

                   Log::info('Generando movimientos contables - Ventas');
                   MovimientoContable::create([
                       'comprobante_id' => $comprobante->id,
                       'cuenta_id' => ConfiguracionContable::getCuentaPorConcepto('ventas')->id,
                       'fecha' => $this->fecha_venta, 
                       'descripcion' => "Venta No. {$this->numero_factura}",
                       'debito' => 0,
                       'credito' => $subtotalSinIva
                   ]);

                   // Solo generar movimiento de IVA si hay productos con IVA
                   if ($totalIva > 0) {
                       Log::info('Generando movimientos contables - IVA');
                       $cuenta_iva = ConfiguracionContable::getCuentaPorConcepto('iva_ventas');
                       
                       if (!$cuenta_iva) {
                           Log::error('No se encontró la cuenta contable para IVA ventas');
                           throw new \Exception('No se encontró la cuenta contable para IVA ventas');
                       }
                       
                       MovimientoContable::create([
                           'comprobante_id' => $comprobante->id,
                           'cuenta_id' => $cuenta_iva->id,
                           'fecha' => $this->fecha_venta,
                           'descripcion' => "IVA Venta No. {$this->numero_factura}", 
                           'debito' => 0,
                           'credito' => $totalIva
                       ]);
                   }

                   // Generar asiento de costo de ventas automáticamente
                   $this->generarAsientoCostoVentas($comprobante);

                   Log::info('Comprobante de venta generado exitosamente');

               } catch(\Exception $e) {
                   Log::error('Error al generar comprobante de venta', [
                       'error' => $e->getMessage(),
                       'trace' => $e->getTraceAsString()
                   ]);
                   throw $e;
               }
           });
       } catch (\Exception $e) {
           Log::error('Error al generar comprobante de venta', [
               'error' => $e->getMessage(),
               'trace' => $e->getTraceAsString()
           ]);
           throw $e;
       }
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

   /**
    * Generar asiento de costo de ventas automáticamente
    */
   private function generarAsientoCostoVentas($comprobante)
   {
       try {
           Log::info('Generando asiento de costo de ventas');
           
           $totalCosto = 0;
           
           // Calcular costo total basado en los productos vendidos
           if ($this->detalles && $this->detalles->count() > 0) {
               foreach ($this->detalles as $detalle) {
                   if ($detalle->producto && isset($detalle->producto->costo)) {
                       $costoUnitario = $detalle->producto->costo;
                       $cantidad = $detalle->cantidad;
                       $costoTotal = $costoUnitario * $cantidad;
                       $totalCosto += $costoTotal;
                       
                       Log::info('Calculando costo del producto', [
                           'producto' => $detalle->producto->nombre,
                           'costo_unitario' => $costoUnitario,
                           'cantidad' => $cantidad,
                           'costo_total' => $costoTotal
                       ]);
                   }
               }
           }
           
           // Solo generar asiento si hay costo
           if ($totalCosto > 0) {
               // Buscar cuentas de costo de ventas e inventario
               $cuentaCostoVentas = ConfiguracionContable::getCuentaPorConcepto('costo_ventas');
               $cuentaInventario = ConfiguracionContable::getCuentaPorConcepto('inventario');
               
               if (!$cuentaCostoVentas) {
                   // Si no existe, buscar por código
                   $cuentaCostoVentas = \App\Models\PlanCuenta::where('codigo', 'LIKE', '61%')
                                                            ->where('estado', true)
                                                            ->first();
               }
               
               if (!$cuentaInventario) {
                   // Si no existe, buscar por código
                   $cuentaInventario = \App\Models\PlanCuenta::where('codigo', 'LIKE', '14%')
                                                            ->where('estado', true)
                                                            ->first();
               }
               
               if ($cuentaCostoVentas && $cuentaInventario) {
                   // Débito: Costo de Ventas
                   MovimientoContable::create([
                       'comprobante_id' => $comprobante->id,
                       'cuenta_id' => $cuentaCostoVentas->id,
                       'fecha' => $this->fecha_venta,
                       'descripcion' => "Costo Venta No. {$this->numero_factura}",
                       'debito' => $totalCosto,
                       'credito' => 0
                   ]);
                   
                   // Crédito: Inventario
                   MovimientoContable::create([
                       'comprobante_id' => $comprobante->id,
                       'cuenta_id' => $cuentaInventario->id,
                       'fecha' => $this->fecha_venta,
                       'descripcion' => "Salida Inventario Venta No. {$this->numero_factura}",
                       'debito' => 0,
                       'credito' => $totalCosto
                   ]);
                   
                   Log::info('Asiento de costo de ventas generado', [
                       'total_costo' => $totalCosto,
                       'cuenta_costo' => $cuentaCostoVentas->codigo,
                       'cuenta_inventario' => $cuentaInventario->codigo
                   ]);
               } else {
                   Log::warning('No se pudieron encontrar cuentas para costo de ventas', [
                       'cuenta_costo_ventas' => $cuentaCostoVentas ? $cuentaCostoVentas->codigo : 'NO ENCONTRADA',
                       'cuenta_inventario' => $cuentaInventario ? $cuentaInventario->codigo : 'NO ENCONTRADA'
                   ]);
               }
           } else {
               Log::info('No hay costo de ventas para registrar');
           }
           
       } catch (\Exception $e) {
           Log::error('Error al generar asiento de costo de ventas', [
               'error' => $e->getMessage(),
               'trace' => $e->getTraceAsString()
           ]);
           // No lanzar excepción para no interrumpir el proceso principal
       }
   }
}