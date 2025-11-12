@extends('layouts.app')

@section('title', 'Facturas DIAN Procesadas')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">📄 Facturas DIAN Procesadas</h1>
                    <p class="text-muted">Gestión y seguimiento de facturas electrónicas procesadas</p>
                </div>
                <div>
                    <a href="{{ route('dian.dashboard') }}" class="btn btn-outline-primary me-2">
                        <i class="fas fa-arrow-left"></i> Dashboard
                    </a>
                    <a href="{{ route('dian.configuracion') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-cog"></i> Configuración
                    </a>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0">{{ $estadisticas['total'] ?? 0 }}</h4>
                                    <p class="mb-0">Total Facturas</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-file-invoice fa-2x"></i>
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
                                    <h4 class="mb-0">{{ $estadisticas['procesadas'] ?? 0 }}</h4>
                                    <p class="mb-0">Procesadas</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x"></i>
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
                                    <h4 class="mb-0">{{ $estadisticas['pendientes'] ?? 0 }}</h4>
                                    <p class="mb-0">Pendientes</p>
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
                                    <h4 class="mb-0">{{ $estadisticas['hoy'] ?? 0 }}</h4>
                                    <p class="mb-0">Hoy</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar-day fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('dian.facturas') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="fecha_desde" class="form-label">Fecha Desde</label>
                            <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" 
                                   value="{{ request('fecha_desde') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                            <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" 
                                   value="{{ request('fecha_hasta') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="">Todos los estados</option>
                                <option value="procesada" {{ request('estado') == 'procesada' ? 'selected' : '' }}>Procesada</option>
                                <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                <option value="error" {{ request('estado') == 'error' ? 'selected' : '' }}>Error</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="buscar" class="form-label">Buscar</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="buscar" name="buscar" 
                                       placeholder="CUFE, remitente..." value="{{ request('buscar') }}">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de Facturas -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">📋 Lista de Facturas</h5>
                    <div>
                        <button class="btn btn-sm btn-success" onclick="procesarEmails()">
                            <i class="fas fa-sync-alt"></i> Procesar Emails
                        </button>
                        <button class="btn btn-sm btn-info" onclick="exportarFacturas()">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if($facturas->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha Email</th>
                                        <th>Remitente</th>
                                        <th>Asunto</th>
                                        <th>CUFE</th>
                                        <th>Estado</th>
                                        <th>Acuse</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($facturas as $factura)
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">#{{ $factura->id }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $factura->fecha_email ? $factura->fecha_email->format('d/m/Y H:i') : 'N/A' }}
                                            </small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    <i class="fas fa-user text-muted"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-medium">{{ Str::limit($factura->remitente_email, 25) }}</div>
                                                    @if($factura->remitente_nombre)
                                                        <small class="text-muted">{{ Str::limit($factura->remitente_nombre, 20) }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-medium">{{ Str::limit($factura->asunto_email, 30) }}</div>
                                            @if($factura->numero_factura)
                                                <small class="text-primary">Nº {{ $factura->numero_factura }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($factura->cufe)
                                                <code class="small">{{ Str::limit($factura->cufe, 20) }}...</code>
                                            @else
                                                <span class="text-muted">Sin CUFE</span>
                                            @endif
                                        </td>
                                        <td>
                                            @switch($factura->estado)
                                                @case('procesada')
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check"></i> Procesada
                                                    </span>
                                                    @break
                                                @case('pendiente')
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-clock"></i> Pendiente
                                                    </span>
                                                    @break
                                                @case('error')
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times"></i> Error
                                                    </span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">{{ $factura->estado }}</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            @if($factura->acuse_enviado)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-paper-plane"></i> Enviado
                                                </span>
                                            @else
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-clock"></i> Pendiente
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-primary" 
                                                        onclick="verDetalle({{ $factura->id }})" 
                                                        title="Ver detalle">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                @if($factura->archivo_xml)
                                                    <button type="button" class="btn btn-outline-success" 
                                                            onclick="descargarXML({{ $factura->id }})" 
                                                            title="Descargar XML">
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                @endif
                                                @if(!$factura->acuse_enviado)
                                                    <button type="button" class="btn btn-outline-info" 
                                                            onclick="enviarAcuse({{ $factura->id }})" 
                                                            title="Enviar acuse">
                                                        <i class="fas fa-paper-plane"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <small class="text-muted">
                                    Mostrando {{ $facturas->firstItem() }} a {{ $facturas->lastItem() }} 
                                    de {{ $facturas->total() }} facturas
                                </small>
                            </div>
                            <div>
                                {{ $facturas->appends(request()->query())->links() }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay facturas procesadas</h5>
                            <p class="text-muted">Las facturas aparecerán aquí cuando se procesen emails con facturas electrónicas.</p>
                            <button class="btn btn-primary" onclick="procesarEmails()">
                                <i class="fas fa-sync-alt"></i> Procesar Emails Ahora
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalle Factura -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">📄 Detalle de Factura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalDetalleBody">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function procesarEmails() {
    if (confirm('¿Deseas procesar los emails para buscar nuevas facturas?')) {
        const btn = event.target;
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        
        fetch('{{ route("dian.procesar-emails") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Error procesando emails');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
}

function verDetalle(facturaId) {
    const modal = new bootstrap.Modal(document.getElementById('modalDetalle'));
    const modalBody = document.getElementById('modalDetalleBody');
    
    modalBody.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    fetch(`{{ route('dian.facturas') }}/${facturaId}/detalle`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalBody.innerHTML = data.html;
            } else {
                modalBody.innerHTML = '<div class="alert alert-danger">Error cargando detalle</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = '<div class="alert alert-danger">Error de conexión</div>';
        });
}

function descargarXML(facturaId) {
    window.open(`{{ route('dian.facturas') }}/${facturaId}/xml`, '_blank');
}

function enviarAcuse(facturaId) {
    if (confirm('¿Deseas enviar el acuse de recibido para esta factura?')) {
        fetch(`{{ route('dian.facturas') }}/${facturaId}/acuse`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Acuse enviado correctamente');
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Error enviando acuse');
        });
    }
}

function exportarFacturas() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.open(`{{ route('dian.facturas') }}?${params.toString()}`, '_blank');
}
</script>
@endpush
