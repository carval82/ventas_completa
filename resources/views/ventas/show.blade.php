// views/ventas/show.blade.php
@extends('layouts.app')

@section('title', 'Detalle de Venta')

@section('content')
<div class="container-fluid">
   <div class="card">
       <div class="card-header">
           <div class="d-flex justify-content-between align-items-center">
               <h5 class="mb-0">Venta #{{ $venta->numero_factura }}</h5>
               <div>
                   <a href="{{ route('ventas.print', $venta) }}" class="btn btn-secondary" target="_blank">
                       <i class="fas fa-print"></i> Imprimir
                   </a>
                   <a href="{{ route('ventas.index') }}" class="btn btn-primary">
                       <i class="fas fa-arrow-left"></i> Volver
                   </a>
               </div>
           </div>
       </div>
       
       <div class="card-body">
           <div class="row mb-4">
               <div class="col-md-6">
                   <h6 class="mb-3">Información del Cliente</h6>
                   <p class="mb-1"><strong>Nombre:</strong> {{ $venta->cliente->nombres }}</p>
                   <p class="mb-1"><strong>Cédula:</strong> {{ $venta->cliente->cedula }}</p>
                   <p class="mb-1"><strong>Teléfono:</strong> {{ $venta->cliente->telefono }}</p>
               </div>
               <div class="col-md-6 text-md-end">
                   <h6 class="mb-3">Información de la Venta</h6>
                   <p class="mb-1"><strong>Fecha:</strong> {{ $venta->fecha_venta->format('d/m/Y h:i A') }}</p>
                   <p class="mb-1"><strong>Vendedor:</strong> {{ $venta->usuario->name }}</p>
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
                       @foreach($venta->detalles as $detalle)
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
                           <td class="text-end">${{ number_format($venta->subtotal, 2) }}</td>
                       </tr>
                       <tr>
                           <td colspan="3" class="text-end"><strong>IVA:</strong></td>
                           <td class="text-end">${{ number_format($venta->iva, 2) }}</td>
                       </tr>
                       <tr>
                           <td colspan="3" class="text-end"><strong>Total:</strong></td>
                           <td class="text-end"><h4 class="m-0">${{ number_format($venta->total, 2) }}</h4></td>
                       </tr>
                   </tfoot>
               </table>
           </div>

           @if($venta->tipo_factura === 'electronica' && !$venta->cufe)
               <form action="{{ route('ventas.dian', $venta) }}" method="POST" class="d-inline">
                   @csrf
                   <button type="submit" class="btn btn-primary">
                       <i class="fas fa-paper-plane"></i> Enviar a DIAN
                   </button>
               </form>
           @endif

           @if($venta->cufe)
               <div class="mt-3">
                   <h5>Información DIAN</h5>
                   <p><strong>CUFE:</strong> {{ $venta->cufe }}</p>
                   <p><strong>Estado:</strong> {{ $venta->estado_dian }}</p>
                   @if($venta->url_pdf)
                       <a href="{{ $venta->url_pdf }}" target="_blank" class="btn btn-sm btn-info">
                           <i class="fas fa-file-pdf"></i> Ver PDF DIAN
                       </a>
                   @endif
                   @if($venta->url_xml)
                       <a href="{{ $venta->url_xml }}" target="_blank" class="btn btn-sm btn-secondary">
                           <i class="fas fa-file-code"></i> Ver XML DIAN
                       </a>
                   @endif
               </div>
           @endif
       </div>
   </div>
</div>
@endsection