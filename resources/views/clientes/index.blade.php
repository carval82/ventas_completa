@extends('layouts.app')

@section('title', 'Clientes')

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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Clientes</h1>
        <a href="{{ route('clientes.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo Cliente
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <!-- Barra de búsqueda -->
            <div class="row mb-3">
                <div class="col-md-8">
                    <form action="{{ route('clientes.index') }}" method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" 
                               placeholder="Buscar por nombre, apellido o cédula..." 
                               value="{{ request('search') }}">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        @if(request('search'))
                            <a href="{{ route('clientes.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        @endif
                    </form>
                </div>
                <div class="col-md-4 text-end">
                    @if(request('search'))
                        <small class="text-muted">
                            Mostrando resultados para: <strong>"{{ request('search') }}"</strong>
                        </small>
                    @endif
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Cédula</th>
                            <th>Nombres</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clientes as $cliente)
                        <tr>
                            <td>{{ $cliente->cedula }}</td>
                            <td>{{ $cliente->nombres }} {{ $cliente->apellidos }}</td>
                            <td>{{ $cliente->telefono }}</td>
                            <td>{{ $cliente->email }}</td>
                            <td>
                                @if($cliente->estado)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-danger">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-action btn-info btn-sm me-1">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-action btn-warning btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                @if(request('search'))
                                    <div class="text-muted">
                                        <i class="fas fa-search fa-2x mb-3"></i>
                                        <h5>No se encontraron clientes</h5>
                                        <p>No hay clientes que coincidan con "<strong>{{ request('search') }}</strong>"</p>
                                        <a href="{{ route('clientes.index') }}" class="btn btn-primary">
                                            <i class="fas fa-list"></i> Ver todos los clientes
                                        </a>
                                    </div>
                                @else
                                    <div class="text-muted">
                                        <i class="fas fa-users fa-2x mb-3"></i>
                                        <h5>No hay clientes registrados</h5>
                                        <p>Comienza agregando tu primer cliente</p>
                                        <a href="{{ route('clientes.create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Crear primer cliente
                                        </a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Información de resultados y paginación -->
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    @if($clientes->total() > 0)
                        Mostrando {{ $clientes->firstItem() }} - {{ $clientes->lastItem() }} 
                        de {{ $clientes->total() }} clientes
                        @if(request('search'))
                            para la búsqueda "<strong>{{ request('search') }}</strong>"
                        @endif
                    @endif
                </div>
                <div>
                    {{ $clientes->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection