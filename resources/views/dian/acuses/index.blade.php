@extends('layouts.app')

@section('title', 'Acuses de Recibo - DIAN')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-receipt text-primary"></i>
                Acuses de Recibo
            </h1>
            <p class="text-muted mb-0">Gestión de acuses de facturas electrónicas</p>
        </div>
        <div>
            <a href="{{ route('dian.dashboard') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Facturas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($estadisticas['total_emails']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-invoice fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Acuses Enviados
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($estadisticas['acuses_enviados']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pendientes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($estadisticas['acuses_pendientes']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Enviados Hoy
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($estadisticas['acuses_hoy']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter"></i> Filtros de Búsqueda
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('dian.acuses.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select name="estado" id="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="nuevo" {{ $filtros['estado'] == 'nuevo' ? 'selected' : '' }}>Nuevo</option>
                        <option value="procesado" {{ $filtros['estado'] == 'procesado' ? 'selected' : '' }}>Procesado</option>
                        <option value="error" {{ $filtros['estado'] == 'error' ? 'selected' : '' }}>Error</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="proveedor" class="form-label">Proveedor</label>
                    <select name="proveedor" id="proveedor" class="form-select">
                        <option value="">Todos los proveedores</option>
                        @foreach($proveedores as $proveedor)
                            <option value="{{ $proveedor }}" {{ $filtros['proveedor'] == $proveedor ? 'selected' : '' }}>
                                {{ $proveedor }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="fecha_desde" class="form-label">Desde</label>
                    <input type="date" name="fecha_desde" id="fecha_desde" class="form-control" 
                           value="{{ $filtros['fecha_desde'] }}">
                </div>

                <div class="col-md-2">
                    <label for="fecha_hasta" class="form-label">Hasta</label>
                    <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control" 
                           value="{{ $filtros['fecha_hasta'] }}">
                </div>

                <div class="col-md-2">
                    <label for="buscar" class="form-label">Buscar</label>
                    <input type="text" name="buscar" id="buscar" class="form-control" 
                           placeholder="Asunto, proveedor..." value="{{ $filtros['buscar'] }}">
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="{{ route('dian.acuses.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Acuses -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Lista de Facturas y Acuses
            </h6>
        </div>
        <div class="card-body">
            @if($emails->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Proveedor</th>
                                <th>Asunto</th>
                                <th>Email Corporativo</th>
                                <th>Email Real</th>
                                <th>Estado Acuse</th>
                                <th>Fecha Acuse</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($emails as $email)
                                <tr>
                                    <td>
                                        <small class="text-muted">
                                            {{ $email->fecha_email->format('d/m/Y') }}<br>
                                            {{ $email->fecha_email->format('H:i') }}
                                        </small>
                                    </td>
                                    <td>
                                        <strong>{{ $email->remitente_nombre }}</strong><br>
                                        <small class="text-muted">{{ $email->remitente_email }}</small>
                                    </td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 200px;" 
                                              title="{{ $email->asunto }}">
                                            {{ $email->asunto }}
                                        </span>
                                    </td>
                                    <td>
                                        <code>{{ $email->remitente_email }}</code>
                                    </td>
                                    <td>
                                        @if($email->email_real && $email->email_real !== $email->remitente_email)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> {{ $email->email_real }}
                                            </span>
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="fas fa-exclamation-triangle"></i> No extraído
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($email->acuse_enviado)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle"></i> Enviado
                                            </span>
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock"></i> Pendiente
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($email->fecha_acuse)
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($email->fecha_acuse)->format('d/m/Y H:i') }}
                                            </small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('dian.acuses.show', $email) }}" 
                                               class="btn btn-sm btn-outline-info" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            @if($email->acuse_enviado)
                                                <button type="button" class="btn btn-sm btn-outline-warning" 
                                                        onclick="reenviarAcuse({{ $email->id }})" title="Reenviar acuse">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-sm btn-outline-success" 
                                                        onclick="enviarAcuse({{ $email->id }})" title="Enviar acuse">
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
                            Mostrando {{ $emails->firstItem() }} a {{ $emails->lastItem() }} 
                            de {{ $emails->total() }} resultados
                        </small>
                    </div>
                    <div>
                        {{ $emails->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No se encontraron facturas</h5>
                    <p class="text-muted">No hay facturas que coincidan con los filtros aplicados.</p>
                    <a href="{{ route('dian.buzon.index') }}" class="btn btn-primary">
                        <i class="fas fa-sync"></i> Ir al Buzón
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal para enviar acuse -->
<div class="modal fade" id="enviarAcuseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-paper-plane text-primary"></i>
                    Enviar Acuse de Recibo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="enviarAcuseForm">
                    <div class="mb-3">
                        <label for="email_destino" class="form-label">Email de destino</label>
                        <input type="email" class="form-control" id="email_destino" name="email_destino" required>
                        <div class="form-text">Se enviará el acuse a este email</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="confirmarEnvioAcuse()">
                    <i class="fas fa-paper-plane"></i> Enviar Acuse
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let emailIdActual = null;

function enviarAcuse(emailId) {
    emailIdActual = emailId;
    
    // Obtener email real si existe
    const fila = document.querySelector(`tr:has(button[onclick="enviarAcuse(${emailId})"])`);
    const emailReal = fila.querySelector('.badge.bg-success')?.textContent.trim();
    
    if (emailReal && emailReal !== 'No extraído') {
        document.getElementById('email_destino').value = emailReal.replace('✓ ', '');
    }
    
    const modal = new bootstrap.Modal(document.getElementById('enviarAcuseModal'));
    modal.show();
}

function reenviarAcuse(emailId) {
    if (confirm('¿Estás seguro de que deseas reenviar el acuse?')) {
        enviarAcuseAjax(emailId);
    }
}

function confirmarEnvioAcuse() {
    if (emailIdActual) {
        enviarAcuseAjax(emailIdActual);
    }
}

function enviarAcuseAjax(emailId) {
    const emailDestino = document.getElementById('email_destino')?.value || null;
    
    // Mostrar loading
    const btn = document.querySelector(`button[onclick*="${emailId}"]`);
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
    
    fetch(`/dian/acuses/${emailId}/enviar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            email_destino: emailDestino
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cerrar modal si está abierto
            const modal = bootstrap.Modal.getInstance(document.getElementById('enviarAcuseModal'));
            if (modal) modal.hide();
            
            // Mostrar éxito
            showAlert('success', `Acuse enviado exitosamente a: ${data.destinatario}`);
            
            // Recargar página después de 2 segundos
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            showAlert('error', `Error: ${data.message}`);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Error de conexión');
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    });
}

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'check-circle' : 'exclamation-triangle';
    
    const alert = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="fas fa-${icon}"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    document.querySelector('.container-fluid').insertAdjacentHTML('afterbegin', alert);
    
    // Auto-dismiss después de 5 segundos
    setTimeout(() => {
        const alertElement = document.querySelector('.alert');
        if (alertElement) {
            alertElement.remove();
        }
    }, 5000);
}
</script>
@endsection
