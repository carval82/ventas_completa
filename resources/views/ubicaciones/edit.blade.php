@extends('layouts.app')

@section('title', 'Editar Ubicación')

@section('styles')
<style>
    .required:after {
        content: " *";
        color: red;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Editar Ubicación</h5>
                <a href="{{ route('ubicaciones.index') }}" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="card-body">
            <form action="{{ route('ubicaciones.update', $ubicacion) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <!-- Nombre -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Nombre</label>
                        <input type="text" 
                               class="form-control @error('nombre') is-invalid @enderror" 
                               name="nombre" 
                               value="{{ old('nombre', $ubicacion->nombre) }}"
                               required>
                        @error('nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Tipo -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Tipo</label>
                        <select class="form-control @error('tipo') is-invalid @enderror" 
                                name="tipo" 
                                required>
                            <option value="">Seleccione un tipo...</option>
                            <option value="bodega" {{ old('tipo', $ubicacion->tipo) == 'bodega' ? 'selected' : '' }}>
                                Bodega
                            </option>
                            <option value="mostrador" {{ old('tipo', $ubicacion->tipo) == 'mostrador' ? 'selected' : '' }}>
                                Mostrador
                            </option>
                        </select>
                        @error('tipo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Descripción -->
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                              name="descripcion" 
                              rows="3">{{ old('descripcion', $ubicacion->descripcion) }}</textarea>
                    @error('descripcion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Estado -->
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" 
                               class="form-check-input" 
                               name="estado" 
                               value="1" 
                               {{ old('estado', $ubicacion->estado) ? 'checked' : '' }}>
                        <label class="form-check-label">Activo</label>
                    </div>
                </div>

                <!-- Productos actuales -->
                @if($ubicacion->productos()->exists())
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Productos en esta ubicación</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Producto</th>
                                            <th class="text-center">Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($ubicacion->productos as $producto)
                                            <tr>
                                                <td>{{ $producto->codigo }}</td>
                                                <td>{{ $producto->nombre }}</td>
                                                <td class="text-center">{{ $producto->pivot->stock }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Botones -->
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <a href="{{ route('ubicaciones.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection