@extends('layouts.app')

@section('title', 'Ubicaciones')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Ubicaciones</h5>
                <a href="{{ route('ubicaciones.create') }}" class="btn btn-light">
                    <i class="fas fa-plus"></i> Nueva Ubicación
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ubicaciones as $ubicacion)
                            <tr>
                                <td>{{ $ubicacion->nombre }}</td>
                                <td>{{ ucfirst($ubicacion->tipo) }}</td>
                                <td>{{ $ubicacion->descripcion }}</td>
                                <td>
                                    <span class="badge bg-{{ $ubicacion->estado ? 'success' : 'danger' }}">
                                        {{ $ubicacion->estado ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('ubicaciones.edit', $ubicacion) }}" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="confirmarEliminacion('{{ $ubicacion->id }}')"
                                            class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No hay ubicaciones registradas</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $ubicaciones->links() }}
            </div>
        </div>
    </div>
</div>

<form id="eliminar-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
function confirmarEliminacion(id) {
    Swal.fire({
        title: '¿Está seguro?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('eliminar-form');
            form.action = `/ubicaciones/${id}`;
            form.submit();
        }
    });
}
</script>
@endpush