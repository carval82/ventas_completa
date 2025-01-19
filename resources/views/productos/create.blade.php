@extends('layouts.app')

@section('title', 'Nuevo Producto')

@section('content')
<div class="container-fluid">
   <div class="card">
       <div class="card-header">
           <div class="d-flex justify-content-between align-items-center">
               <h5 class="mb-0">Nuevo Producto</h5>
               <a href="{{ route('productos.index') }}" class="btn btn-primary">
                   <i class="fas fa-arrow-left"></i> Volver
               </a>
           </div>
       </div>

       <div class="card-body">
           <form action="{{ route('productos.store') }}" method="POST">
               @csrf
               <div class="row">
                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label">Código</label>
                           <input type="text" class="form-control @error('codigo') is-invalid @enderror" 
                                  name="codigo" value="{{ old('codigo') }}" required>
                           @error('codigo')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>

                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label">Nombre</label>
                           <input type="text" class="form-control @error('nombre') is-invalid @enderror" 
                                  name="nombre" value="{{ old('nombre') }}" required>
                           @error('nombre')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>

                   <div class="col-12">
                       <div class="mb-3">
                           <label class="form-label">Descripción</label>
                           <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                     name="descripcion" rows="3">{{ old('descripcion') }}</textarea>
                           @error('descripcion')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>

                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label">Precio Compra</label>
                           <input type="number" step="0.01" class="form-control @error('precio_compra') is-invalid @enderror" 
                                  name="precio_compra" value="{{ old('precio_compra') }}" required>
                           @error('precio_compra')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>

                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label">Precio Venta</label>
                           <input type="number" step="0.01" class="form-control @error('precio_venta') is-invalid @enderror" 
                                  name="precio_venta" value="{{ old('precio_venta') }}" required>
                           @error('precio_venta')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>

                   <div class="col-md-6">
                   <div class="mb-3">
    <label class="form-label">Stock</label>
    <input type="number" 
           class="form-control" 
           id="stock" 
           name="stock" 
           value="{{ session('return_to') === 'ventas' ? '1' : '0' }}" 
           {{ session('return_to') === 'ventas' ? 'min=1' : 'min=0' }}>
</div>
                   </div>

                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label">Stock Mínimo</label>
                           <input type="number" class="form-control @error('stock_minimo') is-invalid @enderror" 
                                  name="stock_minimo" value="{{ old('stock_minimo', 5) }}" required>
                           @error('stock_minimo')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>
               </div>

              <!-- Botones de acción -->
<div class="mt-4">
    @if(request()->has('return_to'))
        @if(request()->return_to === 'compras')
            <button type="submit" class="btn btn-primary" name="action" value="save_and_return">
                <i class="fas fa-save"></i> Guardar y Volver a Compra
            </button>
            <a href="{{ route('compras.create') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancelar
            </a>
        @elseif(request()->return_to === 'ventas')
            <button type="submit" class="btn btn-primary" name="action" value="save_and_return">
                <i class="fas fa-save"></i> Guardar y Volver a Venta
            </button>
            <a href="{{ route('ventas.create') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancelar
            </a>
        @endif
    @else
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Guardar
        </button>
        <a href="{{ route('compras.index') }}" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancelar
        </a>
    @endif
</div>
@endsection