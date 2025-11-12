// views/ventas/show.blade.php
@extends('layouts.app')

@section('title', 'Detalle de Venta')

@section('content')
<div class="container-fluid">
   <div class="card">
       <div class="card-header">
           <div class="d-flex justify-content-between align-items-center">
               <h5 class="mb-0">
                   Factura #{{ $venta->getNumeroFacturaMostrar() }}
                   @if($venta->esFacturaElectronica())
                       <span class="badge bg-success ms-2">
                           <i class="fas fa-bolt"></i> Electrónica
                       </span>
                   @else
                       <span class="badge bg-secondary ms-2">
                           <i class="fas fa-file-invoice"></i> Local
                       </span>
                   @endif
               </h5>
               @if($venta->esFacturaElectronica())
                   <small class="text-muted">ID Local: {{ $venta->numero_factura }} | Alegra ID: {{ $venta->alegra_id }}</small>
               @endif
               <div>
                   <a href="{{ route('ventas.print', $venta) }}" class="btn btn-secondary" target="_blank">
                       <i class="fas fa-print"></i> Imprimir
                   </a>
                   @if(!$venta->alegra_id)
                   <form action="{{ route('ventas.generar-factura-electronica', $venta) }}" method="POST" style="display: inline;">
                       @csrf
                       <button type="submit" class="btn btn-success">
                           <i class="fas fa-file-invoice"></i> Generar Factura Electrónica
                       </button>
                   </form>
                   @else
                   <a href="{{ route('facturas.electronicas.show', $venta->id) }}" class="btn btn-info">
                       <i class="fas fa-eye"></i> Ver Factura Electrónica
                   </a>
                   @endif
                   <a href="{{ route('ventas.index') }}" class="btn btn-primary">
                       <i class="fas fa-arrow-left"></i> Volver
                   </a>
               </div>
           </div>
       </div>
       
       <div class="card-body">
           @if (session('success'))
               <div class="alert alert-success">
                   {{ session('success') }}
               </div>
           @endif
           @if (session('error'))
               <div class="alert alert-danger">
                   {{ session('error') }}
               </div>
           @endif
           
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
                   <p class="mb-1"><strong>Método de Pago:</strong> {{ ucfirst($venta->metodo_pago) }}</p>
                   @if($venta->alegra_id)
                   <p class="mb-1"><strong>ID Alegra:</strong> {{ $venta->alegra_id }}</p>
                   @if($venta->numero_factura_alegra)
                   <p class="mb-1"><strong>Número FE:</strong> {{ $venta->numero_factura_alegra }}</p>
                   @endif
                   <p class="mb-1">
                       <strong>Estado DIAN:</strong> 
                       <span class="badge {{ $venta->estado_dian ? 'bg-success' : 'bg-warning' }}">
                           {{ $venta->estado_dian ?? 'Pendiente' }}
                       </span>
                   </p>
                   @if($venta->url_pdf_alegra)
                   <p class="mb-1">
                       <a href="{{ $venta->url_pdf_alegra }}" target="_blank" class="btn btn-sm btn-primary">
                           <i class="fas fa-file-pdf"></i> Ver PDF Alegra
                       </a>
                   </p>
                   @endif
                   @endif
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
                           <td>{{ $detalle->producto ? $detalle->producto->nombre : 'Producto no disponible' }}</td>
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

           @if($venta->alegra_id && $venta->cufe)
               <div class="mt-3">
                   <h5>Información DIAN</h5>
                   <p><strong>CUFE:</strong> {{ $venta->cufe }}</p>
                   <p><strong>Estado:</strong> {{ $venta->estado_dian }}</p>
                   @if($venta->qr_code)
                       <div class="text-center mb-3">
                           <img src="data:image/png;base64,{{ $venta->qr_code }}" alt="QR Code" style="max-width: 150px;">
                       </div>
                   @endif
                   <div class="mt-2">
                       <a href="{{ route('facturas.electronicas.descargar-pdf', $venta->id) }}" class="btn btn-sm btn-primary">
                           <i class="fas fa-file-pdf"></i> Descargar PDF
                       </a>
                       @if(!$venta->estado_dian || $venta->estado_dian == 'Pendiente')
                       <a href="{{ route('facturas.electronicas.enviar-dian', $venta->id) }}" class="btn btn-sm btn-success">
                           <i class="fas fa-paper-plane"></i> Enviar a DIAN
                       </a>
                       @endif
                   </div>
               </div>
           @endif
       </div>
   </div>
</div>
@endsection