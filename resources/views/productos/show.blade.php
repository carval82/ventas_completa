@extends('layouts.app')

@section('title', 'Detalle de Producto')

@section('content')
<div class="container-fluid">
   <div class="card">
       <div class="card-header">
           <div class="d-flex justify-content-between align-items-center">
               <h5 class="mb-0">Detalle del Producto</h5>
               <div>
                   <a href="{{ route('productos.edit', $producto) }}" class="btn btn-warning">
                       <i class="fas fa-edit"></i> Editar
                   </a>
                   <a href="{{ route('productos.index') }}" class="btn btn-primary">
                       <i class="fas fa-arrow-left"></i> Volver
                   </a>
               </div>
           </div>
       </div>

       <div class="card-body">
           <div class="row">
               <div class="col-md-6">
                   <dl class="row">
                       <dt class="col-sm-4">Código:</dt>
                       <dd class="col-sm-8">{{ $producto->codigo }}</dd>

                       <dt class="col-sm-4">Nombre:</dt>
                       <dd class="col-sm-8">{{ $producto->nombre }}</dd>

                       <dt class="col-sm-4">Descripción:</dt>
                       <dd class="col-sm-8">{{ $producto->descripcion ?: 'Sin descripción' }}</dd>
                   </dl>
               </div>

               <div class="col-md-6">
                   <dl class="row">
                       <dt class="col-sm-4">Precio Compra:</dt>
                       <dd class="col-sm-8">${{ number_format($producto->precio_compra, 2) }}</dd>

                       <dt class="col-sm-4">Precio Venta:</dt>
                       <dd class="col-sm-8">${{ number_format($producto->precio_venta, 2) }}</dd>

                       <dt class="col-sm-4">Stock Actual:</dt>
                       <dd class="col-sm-8">
                           @if($producto->stock <= $producto->stock_minimo)
                               <span class="badge bg-danger">{{ $producto->stock }}</span>
                           @else
                               <span class="badge bg-success">{{ $producto->stock }}</span>
                           @endif
                       </dd>

                       <dt class="col-sm-4">Stock Mínimo:</dt>
                       <dd class="col-sm-8">{{ $producto->stock_minimo }}</dd>

                       <dt class="col-sm-4">Estado:</dt>
                       <dd class="col-sm-8">
                           @if($producto->estado)
                               <span class="badge bg-success">Activo</span>
                           @else
                               <span class="badge bg-danger">Inactivo</span>
                           @endif
                       </dd>
                   </dl>
               </div>
           </div>

           <div class="mt-4">
               <h6>Historial de Ventas</h6>
               <div class="table-responsive">
                   <table class="table">
                       <thead class="table-light">
                           <tr>
                               <th>Factura</th>
                               <th>Fecha</th>
                               <th class="text-center">Cantidad</th>
                               <th class="text-end">Precio</th>
                               <th class="text-end">Subtotal</th>
                           </tr>
                       </thead>
                       <tbody>
                           @forelse($producto->detalleVentas as $detalle)
                           <tr>
                               <td>{{ $detalle->venta ? $detalle->venta->numero_factura : 'N/A' }}</td>
                               <td>{{ $detalle->venta && $detalle->venta->fecha_venta ? $detalle->venta->fecha_venta->format('d/m/Y') : 'N/A' }}</td>
                               <td class="text-center">{{ $detalle->cantidad }}</td>
                               <td class="text-end">${{ number_format($detalle->precio_unitario, 2) }}</td>
                               <td class="text-end">${{ number_format($detalle->subtotal, 2) }}</td>
                           </tr>
                           @empty
                           <tr>
                               <td colspan="5" class="text-center">No hay ventas registradas</td>
                           </tr>
                           @endforelse
                       </tbody>
                   </table>
               </div>
           </div>
       </div>
   </div>
</div>
@endsection