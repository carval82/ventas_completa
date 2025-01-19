@extends('layouts.app')

@section('title', 'Detalle de Compra')

@section('content')
<div class="container-fluid">
   <div class="card">
       <div class="card-header">
           <div class="d-flex justify-content-between align-items-center">
               <h5 class="mb-0">Compra #{{ $compra->numero_factura }}</h5>
               <div>
                   <a href="{{ route('compras.print', $compra) }}" class="btn btn-secondary" target="_blank">
                       <i class="fas fa-print"></i> Imprimir
                   </a>
                   <a href="{{ route('compras.index') }}" class="btn btn-primary">
                       <i class="fas fa-arrow-left"></i> Volver
                   </a>
               </div>
           </div>
       </div>
       
       <div class="card-body">
           <div class="row mb-4">
               <div class="col-md-6">
                   <h6 class="mb-3">Información del Proveedor</h6>
                   <p class="mb-1"><strong>Razón Social:</strong> {{ $compra->proveedor->razon_social }}</p>
                   <p class="mb-1"><strong>NIT:</strong> {{ $compra->proveedor->nit }}</p>
                   <p class="mb-1"><strong>Teléfono:</strong> {{ $compra->proveedor->telefono }}</p>
               </div>
               <div class="col-md-6 text-md-end">
                   <h6 class="mb-3">Información de la Compra</h6>
                   <p class="mb-1"><strong>Fecha:</strong> {{ $compra->fecha_compra->format('d/m/Y h:i A') }}</p>
                   <p class="mb-1"><strong>Usuario:</strong> {{ $compra->usuario->name }}</p>
               </div>
           </div>

           <div class="table-responsive">
               <table class="table table-bordered">
                   <thead class="table-light">
                       <tr>
                           <th>Producto</th>
                           <th class="text-center">Cantidad</th>
                           <th class="text-end">Precio Unit.</th>
                           <th class="text-end">Subtotal</th>
                       </tr>
                   </thead>
                   <tbody>
                       @foreach($compra->detalles as $detalle)
                       <tr>
                           <td>{{ $detalle->producto->nombre }}</td>
                           <td class="text-center">{{ $detalle->cantidad }}</td>
                           <td class="text-end">${{ number_format($detalle->precio_unitario, 2) }}</td>
                           <td class="text-end">${{ number_format($detalle->subtotal, 2) }}</td>
                       </tr>
                       @endforeach
                   </tbody>
                   <tfoot>
                       <tr>
                           <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                           <td class="text-end">${{ number_format($compra->subtotal, 2) }}</td>
                       </tr>
                       <tr>
                           <td colspan="3" class="text-end"><strong>IVA:</strong></td>
                           <td class="text-end">${{ number_format($compra->iva, 2) }}</td>
                       </tr>
                       <tr>
                           <td colspan="3" class="text-end"><strong>Total:</strong></td>
                           <td class="text-end"><h4 class="m-0">${{ number_format($compra->total, 2) }}</h4></td>
                       </tr>
                   </tfoot>
               </table>
           </div>
       </div>
   </div>
</div>
@endsection