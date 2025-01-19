<!-- resources/views/configuracion/empresa/show.blade.php -->
@extends('layouts.app')

@section('title', 'Detalles del Usuario')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Detalles del Usuario</h5>
                <a href="{{ route('users.index') }}" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th class="w-25">Nombre:</th>
                            <td>{{ $usuario->name }}</td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td>{{ $usuario->email }}</td>
                        </tr>
                        <tr>
                            <th>Rol:</th>
                            <td>{{ $usuario->roles->first()->name ?? 'Sin rol asignado' }}</td>
                        </tr>
                        <tr>
                            <th>Estado:</th>
                            <td>
                                @if($usuario->estado)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-danger">Inactivo</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Fecha Registro:</th>
                            <td>{{ $usuario->created_at ? $usuario->created_at->format('d/m/Y H:i') : 'No disponible' }}</td>
                        </tr>
                        <tr>
                            <th>Último Acceso:</th>
                            <td>{{ $usuario->last_login ? $usuario->last_login->format('d/m/Y H:i') : 'Nunca' }}</td>
                        </tr>
                    </table>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Permisos del Usuario</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                @forelse($usuario->getAllPermissions() as $permission)
                                    <li class="list-group-item">{{ $permission->name }}</li>
                                @empty
                                    <li class="list-group-item">No tiene permisos asignados</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('users.edit', $usuario) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Editar
                </a>
                @if($usuario->id !== auth()->id())
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                    <form id="delete-form" action="{{ route('users.destroy', $usuario) }}" method="POST" class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmDelete() {
    if (confirm('¿Está seguro de eliminar este usuario?')) {
        document.getElementById('delete-form').submit();
    }
}
</script>
@endpush
@endsection