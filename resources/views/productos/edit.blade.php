@extends('layouts.app')

@section('title', 'Editar Producto')

@section('content')
<div class="container-fluid">
    <!-- Pestañas de navegación -->
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link active" id="info-tab" data-bs-toggle="tab" href="#info">
                <i class="fas fa-info-circle"></i> Información General
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="proveedores-tab" data-bs-toggle="tab" href="#proveedores">
                <i class="fas fa-truck"></i> Proveedores
            </a>
        </li>
    </ul>

    <!-- Contenido de las pestañas -->
    <div class="tab-content">
        <!-- Pestaña de Información General -->
        <div class="tab-pane fade show active" id="info">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Editar Producto</h5>
                        <a href="{{ route('productos.index') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <form action="{{ route('productos.update', $producto) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Código</label>
                                    <input type="text" class="form-control @error('codigo') is-invalid @enderror" 
                                           name="codigo" value="{{ old('codigo', $producto->codigo) }}" required>
                                    @error('codigo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" class="form-control @error('nombre') is-invalid @enderror" 
                                           name="nombre" value="{{ old('nombre', $producto->nombre) }}" required>
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Descripción</label>
                                    <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                              name="descripcion" rows="3">{{ old('descripcion', $producto->descripcion) }}</textarea>
                                    @error('descripcion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Precio Compra</label>
                                    <input type="number" step="0.01" class="form-control @error('precio_compra') is-invalid @enderror" 
                                           name="precio_compra" value="{{ old('precio_compra', $producto->precio_compra) }}" required>
                                    @error('precio_compra')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Precio Venta</label>
                                    <input type="number" step="0.01" class="form-control @error('precio_venta') is-invalid @enderror" 
                                           name="precio_venta" value="{{ old('precio_venta', $producto->precio_venta) }}" required>
                                    @error('precio_venta')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Stock</label>
                                    <input type="number" class="form-control @error('stock') is-invalid @enderror" 
                                           name="stock" value="{{ old('stock', $producto->stock) }}" required>
                                    @error('stock')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Stock Mínimo</label>
                                    <input type="number" class="form-control @error('stock_minimo') is-invalid @enderror" 
                                           name="stock_minimo" value="{{ old('stock_minimo', $producto->stock_minimo) }}" required>
                                    @error('stock_minimo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Estado</label>
                                    <select class="form-select @error('estado') is-invalid @enderror" name="estado">
                                        <option value="1" {{ old('estado', $producto->estado) ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ !old('estado', $producto->estado) ? 'selected' : '' }}>Inactivo</option>
                                    </select>
                                    @error('estado')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Pestaña de Proveedores -->
        <div class="tab-pane fade" id="proveedores">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Gestión de Proveedores</h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#asignarProveedorModal">
                            <i class="fas fa-plus"></i> Asignar Nuevo Proveedor
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Lista de proveedores actuales -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Proveedor</th>
                                    <th>Precio Compra</th>
                                    <th>Código Proveedor</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($producto->proveedores && $producto->proveedores->count() > 0)
                                    @foreach($producto->proveedores as $proveedor)
                                    <tr>
                                        <td>{{ $proveedor->razon_social }}</td>
                                        <td>${{ number_format($proveedor->pivot->precio_compra, 2) }}</td>
                                        <td>{{ $proveedor->pivot->codigo_proveedor }}</td>
                                        <td>
                                            <form action="{{ route('productos.remove-proveedor', $producto) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="proveedor_id" value="{{ $proveedor->id }}">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="4" class="text-center">No hay proveedores asignados</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Asignar Proveedor -->
<div class="modal fade" id="asignarProveedorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Asignar Proveedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('productos.asignar-proveedor', $producto) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="proveedor_id" class="form-label">Proveedor</label>
                        <select class="form-select" id="proveedor_id" name="proveedor_id" required>
                            <option value="">Seleccione un proveedor</option>
                            @foreach($proveedores as $proveedor)
                                <option value="{{ $proveedor->id }}" data-codigo="{{ $proveedor->id }}">
                                    {{ $proveedor->razon_social }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="precio_compra" class="form-label">Precio de Compra</label>
                        <input type="number" step="0.01" class="form-control" id="precio_compra" 
                               name="precio_compra" value="{{ $producto->precio_compra }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="codigo_proveedor" class="form-label">Código del Proveedor</label>
                        <input type="text" class="form-control" id="codigo_proveedor" 
                               name="codigo_proveedor" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Actualizar código del proveedor cuando cambie la selección
document.getElementById('proveedor_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const codigo = selectedOption.dataset.codigo;
    document.getElementById('codigo_proveedor').value = codigo || '';
});
</script>
@endpush
@endsection