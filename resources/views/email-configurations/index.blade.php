@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-envelope-open-text"></i>
                        Configuraciones de Email
                    </h4>
                    <a href="{{ route('email-configurations.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Configuración
                    </a>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Estadísticas Generales -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Total Configuraciones</h6>
                                            <h3>{{ $configuraciones->count() }}</h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-cogs fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Activas</h6>
                                            <h3>{{ $configuraciones->where('activo', true)->count() }}</h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-check-circle fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Emails Hoy</h6>
                                            <h3>{{ $configuraciones->sum('emails_enviados_hoy') }}</h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-paper-plane fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Proveedores</h6>
                                            <h3>{{ $configuraciones->pluck('proveedor')->unique()->count() }}</h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-server fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Configuraciones -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Proveedor</th>
                                    <th>Email From</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Emails Hoy</th>
                                    <th>Último Envío</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($configuraciones as $config)
                                    <tr>
                                        <td>
                                            <strong>{{ $config->nombre }}</strong>
                                        </td>
                                        <td>
                                            @switch($config->proveedor)
                                                @case('sendgrid')
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-paper-plane"></i> SendGrid
                                                    </span>
                                                    @break
                                                @case('smtp')
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-server"></i> SMTP
                                                    </span>
                                                    @break
                                                @case('mailgun')
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-envelope"></i> Mailgun
                                                    </span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">{{ ucfirst($config->proveedor) }}</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $config->from_address }}</small><br>
                                            <strong>{{ $config->from_name }}</strong>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                @if($config->es_backup)
                                                    <span class="badge bg-info mb-1">
                                                        <i class="fas fa-database"></i> Backup
                                                    </span>
                                                @endif
                                                @if($config->es_acuses)
                                                    <span class="badge bg-success mb-1">
                                                        <i class="fas fa-receipt"></i> Acuses
                                                    </span>
                                                @endif
                                                @if($config->es_notificaciones)
                                                    <span class="badge bg-warning mb-1">
                                                        <i class="fas fa-bell"></i> Notificaciones
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if($config->activo)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle"></i> Activa
                                                </span>
                                            @else
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-times-circle"></i> Inactiva
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="me-2">{{ $config->emails_enviados_hoy }}</span>
                                                @if($config->limite_diario)
                                                    <div class="progress" style="width: 60px; height: 8px;">
                                                        @php
                                                            $porcentaje = ($config->emails_enviados_hoy / $config->limite_diario) * 100;
                                                        @endphp
                                                        <div class="progress-bar 
                                                            @if($porcentaje >= 90) bg-danger
                                                            @elseif($porcentaje >= 70) bg-warning
                                                            @else bg-success
                                                            @endif" 
                                                            style="width: {{ min($porcentaje, 100) }}%">
                                                        </div>
                                                    </div>
                                                    <small class="text-muted ms-1">/{{ $config->limite_diario }}</small>
                                                @else
                                                    <small class="text-muted">Sin límite</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if($config->ultimo_envio)
                                                <small class="text-muted">
                                                    {{ $config->ultimo_envio->diffForHumans() }}
                                                </small>
                                            @else
                                                <small class="text-muted">Nunca</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('email-configurations.show', $config) }}" 
                                                   class="btn btn-sm btn-outline-info" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('email-configurations.edit', $config) }}" 
                                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-success" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#probarModal{{ $config->id }}" title="Probar">
                                                    <i class="fas fa-vial"></i>
                                                </button>
                                                <form method="POST" action="{{ route('email-configurations.toggle', $config) }}" 
                                                      style="display: inline;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-outline-{{ $config->activo ? 'warning' : 'success' }}" 
                                                            title="{{ $config->activo ? 'Desactivar' : 'Activar' }}">
                                                        <i class="fas fa-{{ $config->activo ? 'pause' : 'play' }}"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('email-configurations.destroy', $config) }}" 
                                                      style="display: inline;" 
                                                      onsubmit="return confirm('¿Estás seguro de eliminar esta configuración?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Modal para Probar Configuración -->
                                    <div class="modal fade" id="probarModal{{ $config->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ route('email-configurations.probar', $config) }}">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-vial"></i> Probar Configuración: {{ $config->nombre }}
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label for="email_prueba{{ $config->id }}" class="form-label">
                                                                Email de Prueba
                                                            </label>
                                                            <input type="email" 
                                                                   class="form-control" 
                                                                   id="email_prueba{{ $config->id }}" 
                                                                   name="email_prueba" 
                                                                   required
                                                                   placeholder="ejemplo@correo.com">
                                                            <div class="form-text">
                                                                Se enviará un email de prueba a esta dirección
                                                            </div>
                                                        </div>
                                                        <div class="alert alert-info">
                                                            <i class="fas fa-info-circle"></i>
                                                            <strong>Proveedor:</strong> {{ ucfirst($config->proveedor) }}<br>
                                                            <strong>Desde:</strong> {{ $config->from_name }} &lt;{{ $config->from_address }}&gt;
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                            Cancelar
                                                        </button>
                                                        <button type="submit" class="btn btn-success">
                                                            <i class="fas fa-paper-plane"></i> Enviar Prueba
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                                <h5>No hay configuraciones de email</h5>
                                                <p>Crea tu primera configuración para comenzar a enviar emails</p>
                                                <a href="{{ route('email-configurations.create') }}" class="btn btn-primary">
                                                    <i class="fas fa-plus"></i> Crear Primera Configuración
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Auto-cerrar alertas después de 5 segundos
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
</script>
@endsection
