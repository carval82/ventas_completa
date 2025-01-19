@extends('layouts.app')

@section('title', 'Proveedores')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Gestión de Proveedores</h5>
                <a href="{{ route('proveedores.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Proveedor
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- Filtros de búsqueda -->
            <form action="{{ route('proveedores.index') }}" method="GET" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" 
                               class="form-control" 
                               name="search" 
                               placeholder="Buscar por NIT o razón social..."
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="estado">
                            <option value="">Todos los estados</option>
                            <option value="1" {{ request('estado') == '1' ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ request('estado') == '0' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>
            </form>

            <!-- Tabla -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>NIT</th>
                            <th>Razón Social</th>
                            <th>Teléfono</th>
                            <th>Contacto</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($proveedores as $proveedor)
                            <tr>
                                <td>{{ $proveedor->nit }}</td>
                                <td>{{ $proveedor->razon_social }}</td>
                                <td>{{ $proveedor->telefono }}</td>
                                <td>{{ $proveedor->contacto }}</td>
                                <td>
                                    @if($proveedor->estado)
                                        <span class="badge bg-success">Activo</span>
                                    @else
                                        <span class="badge bg-danger">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('proveedores.show', $proveedor) }}" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('proveedores.edit', $proveedor) }}" 
                                       class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if($proveedor->estado)
                                        <button type="button" 
                                                class="btn btn-sm btn-danger"
                                                onclick="confirmarEliminacion({{ $proveedor->id }})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <form id="eliminar-{{ $proveedor->id }}" 
                                              action="{{ route('proveedores.destroy', $proveedor) }}" 
                                              method="POST" 
                                              class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No hay proveedores registrados</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="mt-4">
                {{ $proveedores->links() }}
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmarEliminacion(id) {
    Swal.fire({
        title: '¿Está seguro?',
        text: "¿Desea desactivar este proveedor?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, desactivar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('eliminar-' + id).submit();
        }
    });
}
</script>
@endpush
@endsection