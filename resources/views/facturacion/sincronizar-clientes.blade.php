@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users"></i> 
                        Sincronizar Clientes desde {{ ucfirst($proveedor) }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('facturacion.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if($success)
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> Vista Previa de Sincronización</h5>
                            <p>Se encontraron <strong>{{ count($clientes) }}</strong> clientes en {{ ucfirst($proveedor) }}.</p>
                            <p>Revisa la lista a continuación y confirma la sincronización.</p>
                        </div>

                        @if(count($clientes) > 0)
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="info-box bg-success">
                                        <span class="info-box-icon"><i class="fas fa-plus"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Clientes Nuevos</span>
                                            <span class="info-box-number">
                                                {{ collect($clientes)->where('accion', 'Crear')->count() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box bg-warning">
                                        <span class="info-box-icon"><i class="fas fa-edit"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Clientes a Actualizar</span>
                                            <span class="info-box-number">
                                                {{ collect($clientes)->where('accion', 'Actualizar')->count() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Cédula</th>
                                            <th>Teléfono</th>
                                            <th>Email</th>
                                            <th>Ciudad</th>
                                            <th>Estado</th>
                                            <th>Acción</th>
                                            <th>Estado Local</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($clientes as $cliente)
                                            <tr class="{{ $cliente['accion'] === 'Crear' ? 'table-success' : 'table-warning' }}">
                                                <td>{{ $cliente['nombre'] }}</td>
                                                <td><code>{{ $cliente['cedula'] }}</code></td>
                                                <td>{{ $cliente['telefono'] ?: 'N/A' }}</td>
                                                <td>{{ $cliente['email'] ?: 'N/A' }}</td>
                                                <td>{{ $cliente['ciudad'] ?: 'N/A' }}</td>
                                                <td>
                                                    <span class="badge badge-{{ $cliente['estado'] === 'Activo' ? 'success' : 'danger' }}">
                                                        {{ $cliente['estado'] }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $cliente['accion'] === 'Crear' ? 'success' : 'warning' }}">
                                                        {{ $cliente['accion'] }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($cliente['existe_local'])
                                                        <i class="fas fa-check text-success"></i> Existe
                                                    @else
                                                        <i class="fas fa-times text-danger"></i> No existe
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-center mt-4">
                                <button onclick="ejecutarSincronizacion()" class="btn btn-success btn-lg">
                                    <i class="fas fa-sync"></i> Confirmar Sincronización
                                </button>
                                <a href="{{ route('facturacion.index') }}" class="btn btn-secondary btn-lg ml-2">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <h5><i class="fas fa-exclamation-triangle"></i> Sin Clientes</h5>
                                <p>No se encontraron clientes para sincronizar desde {{ ucfirst($proveedor) }}.</p>
                            </div>
                        @endif
                    @else
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-circle"></i> Error de Conexión</h5>
                            <p>{{ $error ?? 'Error desconocido al obtener clientes' }}</p>
                            <a href="{{ route('facturacion.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de progreso -->
<div class="modal fade" id="progressModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="sr-only">Sincronizando...</span>
                </div>
                <h5>Sincronizando clientes...</h5>
                <p>Por favor espera, esto puede tomar varios minutos.</p>
            </div>
        </div>
    </div>
</div>

<script>
function ejecutarSincronizacion() {
    if (!confirm('¿Estás seguro de que deseas sincronizar todos los clientes? Esta acción no se puede deshacer.')) {
        return;
    }

    // Mostrar modal de progreso
    $('#progressModal').modal({
        backdrop: 'static',
        keyboard: false
    });

    fetch('{{ route("facturacion.sincronizar-clientes") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            proveedor: '{{ $proveedor }}'
        })
    })
    .then(response => response.json())
    .then(data => {
        $('#progressModal').modal('hide');

        if (data.success) {
            alert('¡Sincronización completada exitosamente!\n\n' +
                  'Clientes creados: ' + (data.data.clientes_creados || 0) + '\n' +
                  'Clientes actualizados: ' + (data.data.clientes_actualizados || 0) + '\n' +
                  'Total procesados: ' + (data.data.total_procesados || 0));
            
            // Redirigir al índice
            window.location.href = '{{ route("facturacion.index") }}';
        } else {
            alert('Error al sincronizar clientes: ' + data.message);
        }
    })
    .catch(error => {
        $('#progressModal').modal('hide');
        console.error('Error:', error);
        alert('Error de conexión al sincronizar clientes');
    });
}
</script>
@endsection
