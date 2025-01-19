@extends('layouts.app')

@section('title', 'Detalle de Cliente')

@section('content')
<div class="container-fluid">
   <div class="card">
       <div class="card-header">
           <div class="d-flex justify-content-between align-items-center">
               <h5 class="mb-0">Detalle del Cliente</h5>
               <div>
                   <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-warning">
                       <i class="fas fa-edit"></i> Editar
                   </a>
                   <a href="{{ route('clientes.index') }}" class="btn btn-primary">
                       <i class="fas fa-arrow-left"></i> Volver
                   </a>
               </div>
           </div>
       </div>

       <div class="card-body">
           <div class="row">
               <div class="col-md-6">
                   <dl class="row">
                       <dt class="col-sm-4">Nombres:</dt>
                       <dd class="col-sm-8">{{ $cliente->nombres }}</dd>

                       <dt class="col-sm-4">Apellidos:</dt>
                       <dd class="col-sm-8">{{ $cliente->apellidos }}</dd>

                       <dt class="col-sm-4">Cédula:</dt>
                       <dd class="col-sm-8">{{ $cliente->cedula }}</dd>
                   </dl>
               </div>

               <div class="col-md-6">
                   <dl class="row">
                       <dt class="col-sm-4">Teléfono:</dt>
                       <dd class="col-sm-8">{{ $cliente->telefono ?: 'No registrado' }}</dd>

                       <dt class="col-sm-4">Email:</dt>
                       <dd class="col-sm-8">{{ $cliente->email ?: 'No registrado' }}</dd>

                       <dt class="col-sm-4">Dirección:</dt>
                       <dd class="col-sm-8">{{ $cliente->direccion ?: 'No registrada' }}</dd>

                       <dt class="col-sm-4">Estado:</dt>
                       <dd class="col-sm-8">
                           @if($cliente->estado)
                               <span class="badge bg-success">Activo</span>
                           @else
                               <span class="badge bg-danger">Inactivo</span>
                           @endif
                       </dd>
                   </dl>
               </div>
           </div>

           <div class="mt-4">
               <h6>Historial de Compras</h6>
               <div class="table-responsive">
                   <table class="table">
                       <thead class="table-light">
                           <tr>
                               <th>Factura</th>
                               <th>Fecha</th>
                               <th class="text-end">Subtotal</th>
                               <th class="text-end">IVA</th>
                               <th class="text-end">Total</th>
                               <th class="text-center">Acciones</th>
                           </tr>
                       </thead>
                       <tbody>
                           @forelse($cliente->ventas as $venta)
                           <tr>
                               <td>{{ $venta->numero_factura }}</td>
                               <td>{{ $venta->fecha_venta->format('d/m/Y H:i') }}</td>
                               <td class="text-end">${{ number_format($venta->subtotal, 2) }}</td>
                               <td class="text-end">${{ number_format($venta->iva, 2) }}</td>
                               <td class="text-end">${{ number_format($venta->total, 2) }}</td>
                               <td class="text-center">
                                   <a href="{{ route('ventas.show', $venta) }}" class="btn btn-sm btn-info">
                                       <i class="fas fa-eye"></i>
                                   </a>
                                   <a href="{{ route('ventas.print', $venta) }}" class="btn btn-sm btn-secondary" target="_blank">
                                       <i class="fas fa-print"></i>
                                   </a>
                               </td>
                           </tr>
                           @empty
                           <tr>
                               <td colspan="6" class="text-center">No hay compras registradas</td>
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