@extends('layouts.app')

@section('title', 'Detalle de Proveedor')

@section('content')
<div class="container-fluid">
   <div class="card">
       <div class="card-header">
           <div class="d-flex justify-content-between align-items-center">
               <h5 class="mb-0">Información del Proveedor</h5>
               <div>
                   <a href="{{ route('proveedores.edit', $proveedor) }}" class="btn btn-warning">
                       <i class="fas fa-edit"></i> Editar
                   </a>
                   <a href="{{ route('proveedores.index') }}" class="btn btn-primary">
                       <i class="fas fa-arrow-left"></i> Volver
                   </a>
               </div>
           </div>
       </div>

       <div class="card-body">
           <div class="row">
               <div class="col-md-6">
                   <dl class="row">
                       <dt class="col-sm-4">NIT:</dt>
                       <dd class="col-sm-8">{{ $proveedor->nit }}</dd>

                       <dt class="col-sm-4">Razón Social:</dt>
                       <dd class="col-sm-8">{{ $proveedor->razon_social }}</dd>

                       <dt class="col-sm-4">Régimen:</dt>
                       <dd class="col-sm-8">{{ $proveedor->regimen ?: 'No especificado' }}</dd>

                       <dt class="col-sm-4">Tipo Identificación:</dt>
                       <dd class="col-sm-8">{{ $proveedor->tipo_identificacion ?: 'No especificado' }}</dd>

                       <dt class="col-sm-4">Ciudad:</dt>
                       <dd class="col-sm-8">{{ $proveedor->ciudad ?: 'No especificada' }}</dd>

                       <dt class="col-sm-4">Dirección:</dt>
                       <dd class="col-sm-8">{{ $proveedor->direccion }}</dd>
                   </dl>
               </div>

               <div class="col-md-6">
                   <dl class="row">
                       <dt class="col-sm-4">Teléfono:</dt>
                       <dd class="col-sm-8">{{ $proveedor->telefono }}</dd>

                       <dt class="col-sm-4">Celular:</dt>
                       <dd class="col-sm-8">{{ $proveedor->celular ?: 'No especificado' }}</dd>

                       <dt class="col-sm-4">Fax:</dt>
                       <dd class="col-sm-8">{{ $proveedor->fax ?: 'No especificado' }}</dd>

                       <dt class="col-sm-4">Email:</dt>
                       <dd class="col-sm-8">{{ $proveedor->correo_electronico ?: 'No especificado' }}</dd>

                       <dt class="col-sm-4">Contacto:</dt>
                       <dd class="col-sm-8">{{ $proveedor->contacto ?: 'No especificado' }}</dd>

                       <dt class="col-sm-4">Estado:</dt>
                       <dd class="col-sm-8">
                           @if($proveedor->estado)
                               <span class="badge bg-success">Activo</span>
                           @else
                               <span class="badge bg-danger">Inactivo</span>
                           @endif
                       </dd>
                   </dl>
               </div>
           </div>

           <!-- Historial de Compras -->
           <div class="mt-4">
               <h6>Historial de Compras</h6>
               <div class="table-responsive">
                   <table class="table">
                       <thead class="table-light">
                           <tr>
                               <th>Factura No.</th>
                               <th>Fecha</th>
                               <th class="text-end">Subtotal</th>
                               <th class="text-end">IVA</th>
                               <th class="text-end">Total</th>
                               <th class="text-center">Acciones</th>
                           </tr>
                       </thead>
                       <tbody>
                           @forelse($proveedor->compras as $compra)
                           <tr>
                               <td>{{ $compra->numero_factura }}</td>
                               <td>{{ $compra->fecha_compra->format('d/m/Y') }}</td>
                               <td class="text-end">${{ number_format($compra->subtotal, 2) }}</td>
                               <td class="text-end">${{ number_format($compra->iva, 2) }}</td>
                               <td class="text-end">${{ number_format($compra->total, 2) }}</td>
                               <td class="text-center">
                                   <a href="{{ route('compras.show', $compra) }}" class="btn btn-sm btn-info">
                                       <i class="fas fa-eye"></i>
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

           <!-- Resumen de Compras -->
           <div class="row mt-4">
               <div class="col-md-4">
                   <div class="card bg-primary text-white">
                       <div class="card-body">
                           <h6 class="card-title">Total en Compras</h6>
                           <h3 class="mb-0">${{ number_format($proveedor->total_compras, 2) }}</h3>
                       </div>
                   </div>
               </div>
               @if($proveedor->ultima_compra)
               <div class="col-md-4">
                   <div class="card bg-info text-white">
                       <div class="card-body">
                           <h6 class="card-title">Última Compra</h6>
                           <h3 class="mb-0">{{ $proveedor->ultima_compra->fecha_compra->format('d/m/Y') }}</h3>
                       </div>
                   </div>
               </div>
               @endif
           </div>
       </div>
   </div>
</div>
@endsection