@extends('layouts.app')

@section('title', 'Productos')

@section('styles')
<style>
    /* Ajustes específicos para iconos de paginación */
    nav[role="navigation"] svg {
        width: 20px !important;
        height: 20px !important;
    }

    .pagination {
        gap: 5px;
    }

    .pagination .page-item .page-link {
        border-radius: 4px;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 36px;
        min-width: 36px;
        color: #6c757d;
        background-color: #fff;
        border: 1px solid #dee2e6;
        margin: 0;
    }

    .pagination .page-item.active .page-link {
        background-color: #198754;
        border-color: #198754;
        color: white;
    }

    .pagination .page-item.disabled .page-link {
        background-color: #e9ecef;
        border-color: #dee2e6;
    }

    /* Ajustes para el contenedor de la tabla */
    .table-container {
        margin-bottom: 1rem;
    }

    /* Ajustes para los botones de acción */
    .btn-action {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
        border-radius: 0.2rem;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Productos</h5>
                <a href="{{ route('productos.create') }}" class="btn btn-light">
                    <i class="fas fa-plus"></i> Nuevo Producto
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- Barra de búsqueda -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <form action="{{ route('productos.index') }}" method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" 
                               placeholder="Buscar por código o nombre..." 
                               value="{{ request('search') }}">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Tabla de productos -->
            <div class="table-container table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th class="text-end">Precio Compra</th>
                            <th class="text-end">Precio Venta</th>
                            <th class="text-center">Stock</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productos as $producto)
                        <tr>
                            <td>{{ $producto->codigo }}</td>
                            <td>{{ $producto->nombre }}</td>
                            <td class="text-end">${{ number_format($producto->precio_compra, 2) }}</td>
                            <td class="text-end">${{ number_format($producto->precio_venta, 2) }}</td>
                            <td class="text-center">
                                <span class="badge {{ $producto->stock <= $producto->stock_minimo ? 'bg-danger' : 'bg-success' }}">
                                    {{ $producto->stock }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $producto->estado ? 'bg-success' : 'bg-danger' }}">
                                    {{ $producto->estado ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('productos.edit', $producto) }}" 
                                   class="btn btn-action btn-primary btn-sm me-1">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="{{ route('productos.show', $producto) }}" 
                                   class="btn btn-action btn-info btn-sm me-1">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('productos.barcode', $producto->id) }}" 
   class="btn btn-sm btn-secondary"
   target="_blank">
    <i class="fas fa-barcode"></i> Imprimir Código
</a>
                                <form action="{{ route('productos.destroy', $producto) }}" 
                                      method="POST" 
                                      class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="btn btn-action btn-danger btn-sm"
                                            onclick="return confirm('¿Está seguro de eliminar este producto?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="d-flex justify-content-end">
                {{ $productos->links() }}
            </div>
        </div>
    </div>
</div>
@endsection