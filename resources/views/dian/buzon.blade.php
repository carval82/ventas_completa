@extends('layouts.app')

@section('title', 'Buzón de Correos DIAN')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">📧 Buzón de Correos DIAN</h2>
                    <p class="text-muted mb-0">Sistema de correos estilo Outlook para procesamiento automático</p>
                </div>
                <div>
                    <a href="{{ route('dian.dashboard') }}" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left"></i> Volver al Dashboard
                    </a>
                    <form action="{{ route('dian.buzon.sincronizar') }}" method="POST" style="display: inline;" class="me-2">
                        @csrf
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-sync-alt"></i> Sincronizar
                        </button>
                    </form>
                    <form action="{{ route('dian.buzon.procesar') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-primary" title="Procesar facturas y generar acuses">
                            <i class="fas fa-cogs"></i> Procesar Facturas
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas del Buzón -->
    @if($estadisticas)
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">📧 Total Emails</h6>
                            <h3>{{ $estadisticas['total'] ?? 0 }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-envelope fa-2x"></i>
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
                            <h6 class="card-title">📄 Con Facturas</h6>
                            <h3>{{ $estadisticas['con_facturas'] ?? 0 }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-file-invoice fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">⏳ Pendientes</h6>
                            <h3>{{ $estadisticas['nuevos'] ?? 0 }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
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
                            <h6 class="card-title">📅 Hoy</h6>
                            <h3>{{ $estadisticas['hoy'] ?? 0 }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar-day fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(isset($estadisticas['ultima_sincronizacion']) && $estadisticas['ultima_sincronizacion'])
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Última sincronización:</strong> {{ $estadisticas['ultima_sincronizacion']->format('d/m/Y H:i:s') }}
                <span class="text-muted">({{ $estadisticas['ultima_sincronizacion']->diffForHumans() }})</span>
            </div>
        </div>
    </div>
    @endif
    @endif

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-filter"></i> Filtros de Búsqueda</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('dian.buzon') }}">
                        <div class="row">
                            <div class="col-md-2">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    <option value="nuevo" {{ request('estado') == 'nuevo' ? 'selected' : '' }}>Nuevo</option>
                                    <option value="procesando" {{ request('estado') == 'procesando' ? 'selected' : '' }}>Procesando</option>
                                    <option value="procesado" {{ request('estado') == 'procesado' ? 'selected' : '' }}>Procesado</option>
                                    <option value="error" {{ request('estado') == 'error' ? 'selected' : '' }}>Error</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Con Facturas</label>
                                <select name="tiene_facturas" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    <option value="1" {{ request('tiene_facturas') == '1' ? 'selected' : '' }}>Sí</option>
                                    <option value="0" {{ request('tiene_facturas') == '0' ? 'selected' : '' }}>No</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Proveedor</label>
                                <select name="proveedor" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    @foreach($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id }}" {{ request('proveedor') == $proveedor->id ? 'selected' : '' }}>
                                        {{ $proveedor->nombre_proveedor }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Fecha Desde</label>
                                <input type="date" name="fecha_desde" class="form-control form-control-sm" 
                                       value="{{ request('fecha_desde') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Fecha Hasta</label>
                                <input type="date" name="fecha_hasta" class="form-control form-control-sm" 
                                       value="{{ request('fecha_hasta') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Buscar</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="buscar" class="form-control" 
                                           placeholder="Email, nombre, asunto..." value="{{ request('buscar') }}">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-sm me-2">
                                    <i class="fas fa-filter"></i> Aplicar Filtros
                                </button>
                                <a href="{{ route('dian.buzon') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Emails -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-inbox"></i> Emails en el Buzón
                        @if($emails->total() > 0)
                        <span class="badge bg-primary ms-2">{{ $emails->total() }}</span>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    @if($emails->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Estado</th>
                                    <th>Remitente</th>
                                    <th>Asunto</th>
                                    <th>Fecha Email</th>
                                    <th>Adjuntos</th>
                                    <th>Facturas</th>
                                    <th>Procesado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($emails as $email)
                                <tr class="{{ $email->procesado ? 'table-success' : ($email->tiene_facturas ? 'table-warning' : '') }}">
                                    <td>
                                        @switch($email->estado)
                                            @case('nuevo')
                                                <span class="badge bg-primary">Nuevo</span>
                                                @break
                                            @case('procesando')
                                                <span class="badge bg-warning">Procesando</span>
                                                @break
                                            @case('procesado')
                                                <span class="badge bg-success">Procesado</span>
                                                @break
                                            @case('error')
                                                <span class="badge bg-danger">Error</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $email->remitente_nombre ?: 'Sin nombre' }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $email->remitente_email }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 300px;" title="{{ $email->asunto }}">
                                            {{ $email->asunto }}
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            {{ $email->fecha_email ? $email->fecha_email->format('d/m/Y') : 'N/A' }}
                                            <br>
                                            <small class="text-muted">
                                                {{ $email->fecha_email ? $email->fecha_email->format('H:i:s') : '' }}
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($email->tieneAdjuntos())
                                            <span class="badge bg-info">
                                                <i class="fas fa-paperclip"></i> {{ count($email->getAdjuntos()) }}
                                            </span>
                                        @else
                                            <span class="text-muted">Sin adjuntos</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($email->tiene_facturas)
                                            <span class="badge bg-success">
                                                <i class="fas fa-file-invoice"></i> Sí
                                            </span>
                                        @else
                                            <span class="text-muted">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($email->procesado)
                                            <span class="text-success">
                                                <i class="fas fa-check-circle"></i>
                                                {{ $email->fecha_procesado ? $email->fecha_procesado->format('d/m H:i') : 'Sí' }}
                                            </span>
                                        @else
                                            <span class="text-warning">
                                                <i class="fas fa-clock"></i> Pendiente
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $emails->links() }}
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay emails en el buzón</h5>
                        <p class="text-muted">Sincroniza emails para comenzar a ver el contenido del buzón</p>
                        <form action="{{ route('dian.procesar-emails') }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sync-alt"></i> Sincronizar Ahora
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Información del Sistema -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle me-3 fa-2x"></i>
                    <div>
                        <h6 class="mb-1">📧 Buzón de Correos Estilo Outlook</h6>
                        <p class="mb-0">
                            Este sistema funciona como Outlook: descarga emails automáticamente, los almacena localmente 
                            y procesa las facturas de forma inteligente. No requiere configuración OAuth2 compleja.
                        </p>
                        <small class="text-muted">
                            <strong>Cuenta configurada:</strong> {{ $configuracion->email_dian ?? 'No configurada' }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-refresh cada 5 minutos
setTimeout(function() {
    location.reload();
}, 300000);
</script>
@endpush
