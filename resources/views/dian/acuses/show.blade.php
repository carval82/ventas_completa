@extends('layouts.app')

@section('title', 'Detalles del Acuse - DIAN')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-receipt text-primary"></i>
                Detalles del Acuse
            </h1>
            <p class="text-muted mb-0">Información completa del acuse de recibo</p>
        </div>
        <div>
            <a href="{{ route('dian.acuses.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Acuses
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Información del Email -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-envelope"></i> Información del Email
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">Datos Básicos</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>ID Email:</strong></td>
                                    <td>{{ $email->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha Recepción:</strong></td>
                                    <td>{{ $email->fecha_email->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Estado:</strong></td>
                                    <td>
                                        @switch($email->estado)
                                            @case('nuevo')
                                                <span class="badge bg-info">Nuevo</span>
                                                @break
                                            @case('procesado')
                                                <span class="badge bg-success">Procesado</span>
                                                @break
                                            @case('error')
                                                <span class="badge bg-danger">Error</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ ucfirst($email->estado) }}</span>
                                        @endswitch
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Tiene Facturas:</strong></td>
                                    <td>
                                        @if($email->tiene_facturas)
                                            <span class="badge bg-success"><i class="fas fa-check"></i> Sí</span>
                                        @else
                                            <span class="badge bg-warning"><i class="fas fa-times"></i> No</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary">Proveedor</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Nombre:</strong></td>
                                    <td>{{ $email->remitente_nombre }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email Corporativo:</strong></td>
                                    <td><code>{{ $email->remitente_email }}</code></td>
                                </tr>
                                @if($email->email_real && $email->email_real !== $email->remitente_email)
                                <tr>
                                    <td><strong>Email Real:</strong></td>
                                    <td>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check"></i> {{ $email->email_real }}
                                        </span>
                                    </td>
                                </tr>
                                @endif
                                @if(!empty($email->datos_proveedor['nit']))
                                <tr>
                                    <td><strong>NIT:</strong></td>
                                    <td>{{ $email->datos_proveedor['nit'] }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <div class="mt-3">
                        <h6 class="text-primary">Asunto del Email</h6>
                        <div class="alert alert-light">
                            <i class="fas fa-envelope-open-text"></i>
                            {{ $email->asunto }}
                        </div>
                    </div>

                    @if(!empty($email->datos_proveedor['cufe']))
                    <div class="mt-3">
                        <h6 class="text-primary">CUFE Extraído</h6>
                        <div class="alert alert-info">
                            <i class="fas fa-key"></i>
                            <code>{{ $email->datos_proveedor['cufe'] }}</code>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Estado del Acuse -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-paper-plane"></i> Estado del Acuse
                    </h6>
                </div>
                <div class="card-body">
                    @if($email->acuse_enviado)
                        <div class="text-center mb-3">
                            <i class="fas fa-check-circle fa-3x text-success mb-2"></i>
                            <h5 class="text-success">Acuse Enviado</h5>
                        </div>
                        
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Enviado a:</strong></td>
                                <td>
                                    <code>{{ $email->email_acuse }}</code>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Fecha Envío:</strong></td>
                                <td>
                                    {{ \Carbon\Carbon::parse($email->fecha_acuse)->format('d/m/Y H:i:s') }}
                                </td>
                            </tr>
                            @if(isset($metadatos['usuario_envio']))
                            <tr>
                                <td><strong>Enviado por:</strong></td>
                                <td>{{ $metadatos['usuario_envio'] }}</td>
                            </tr>
                            @endif
                            @if(isset($metadatos['envio_manual']) && $metadatos['envio_manual'])
                            <tr>
                                <td><strong>Tipo:</strong></td>
                                <td><span class="badge bg-warning">Manual</span></td>
                            </tr>
                            @else
                            <tr>
                                <td><strong>Tipo:</strong></td>
                                <td><span class="badge bg-info">Automático</span></td>
                            </tr>
                            @endif
                        </table>

                        <div class="mt-3">
                            <button type="button" class="btn btn-warning btn-sm w-100" 
                                    onclick="reenviarAcuse({{ $email->id }})">
                                <i class="fas fa-redo"></i> Reenviar Acuse
                            </button>
                        </div>
                    @else
                        <div class="text-center mb-3">
                            <i class="fas fa-clock fa-3x text-warning mb-2"></i>
                            <h5 class="text-warning">Acuse Pendiente</h5>
                            <p class="text-muted">El acuse aún no ha sido enviado</p>
                        </div>

                        <div class="mt-3">
                            <button type="button" class="btn btn-success btn-sm w-100" 
                                    onclick="enviarAcuse({{ $email->id }})">
                                <i class="fas fa-paper-plane"></i> Enviar Acuse
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Diferencias de Email -->
            @if(!empty($email->diferencia_emails))
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-exchange-alt"></i> Mapeo de Emails
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">Email Corporativo:</small><br>
                        <code>{{ $email->diferencia_emails['email_corporativo'] ?? $email->remitente_email }}</code>
                    </div>
                    <div class="text-center my-2">
                        <i class="fas fa-arrow-down text-muted"></i>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Email Real Usado:</small><br>
                        <span class="badge bg-success">
                            {{ $email->diferencia_emails['email_real_usado'] ?? $email->email_acuse }}
                        </span>
                    </div>
                    @if(isset($email->diferencia_emails['extraido_de_xml']) && $email->diferencia_emails['extraido_de_xml'])
                    <div class="mt-2">
                        <small class="badge bg-info">
                            <i class="fas fa-file-code"></i> Extraído del XML
                        </small>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Metadatos Técnicos -->
    @if(!empty($metadatos))
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-secondary">
                <i class="fas fa-cogs"></i> Información Técnica
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                @if(isset($metadatos['archivos_procesados']))
                <div class="col-md-6">
                    <h6 class="text-secondary">Archivos Procesados</h6>
                    @foreach($metadatos['archivos_procesados'] as $archivo)
                    <div class="alert alert-light">
                        <i class="fas fa-file-alt"></i>
                        <strong>{{ $archivo['nombre'] }}</strong><br>
                        <small class="text-muted">
                            Email extraído: {{ $archivo['email_extraido'] ?? 'N/A' }}<br>
                            Procesado: {{ \Carbon\Carbon::parse($archivo['fecha_procesado'])->format('d/m/Y H:i:s') }}
                        </small>
                    </div>
                    @endforeach
                </div>
                @endif

                <div class="col-md-6">
                    <h6 class="text-secondary">Datos del Proveedor (XML)</h6>
                    @if(!empty($email->datos_proveedor))
                    <table class="table table-sm">
                        @if(isset($email->datos_proveedor['nombre']))
                        <tr>
                            <td><strong>Nombre:</strong></td>
                            <td>{{ $email->datos_proveedor['nombre'] }}</td>
                        </tr>
                        @endif
                        @if(isset($email->datos_proveedor['nit']))
                        <tr>
                            <td><strong>NIT:</strong></td>
                            <td>{{ $email->datos_proveedor['nit'] }}</td>
                        </tr>
                        @endif
                        @if(isset($email->datos_proveedor['email']))
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td><code>{{ $email->datos_proveedor['email'] }}</code></td>
                        </tr>
                        @endif
                        @if(isset($email->datos_proveedor['cufe']))
                        <tr>
                            <td><strong>CUFE:</strong></td>
                            <td><code>{{ substr($email->datos_proveedor['cufe'], 0, 20) }}...</code></td>
                        </tr>
                        @endif
                    </table>
                    @else
                    <p class="text-muted">No hay datos extraídos del XML</p>
                    @endif
                </div>
            </div>

            @if(isset($metadatos['fecha_extraccion']))
            <div class="mt-3">
                <small class="text-muted">
                    <i class="fas fa-clock"></i>
                    Datos extraídos: {{ \Carbon\Carbon::parse($metadatos['fecha_extraccion'])->format('d/m/Y H:i:s') }}
                </small>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>

<!-- Modal para enviar/reenviar acuse -->
<div class="modal fade" id="enviarAcuseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-paper-plane text-primary"></i>
                    <span id="modalTitle">Enviar Acuse de Recibo</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="enviarAcuseForm">
                    <div class="mb-3">
                        <label for="email_destino" class="form-label">Email de destino</label>
                        <input type="email" class="form-control" id="email_destino" name="email_destino" 
                               value="{{ $email->email_real ?? $email->remitente_email }}" required>
                        <div class="form-text">Se enviará el acuse a este email</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Información del acuse:</strong><br>
                        • Proveedor: {{ $email->remitente_nombre }}<br>
                        • Factura: FE-2024-{{ str_pad($email->id, 6, '0', STR_PAD_LEFT) }}<br>
                        @if(!empty($email->datos_proveedor['cufe']))
                        • CUFE: {{ substr($email->datos_proveedor['cufe'], 0, 20) }}...<br>
                        @endif
                        • Fecha: {{ $email->fecha_email->format('d/m/Y') }}
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="confirmarEnvioAcuse()">
                    <i class="fas fa-paper-plane"></i> <span id="btnText">Enviar Acuse</span>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function enviarAcuse(emailId) {
    document.getElementById('modalTitle').textContent = 'Enviar Acuse de Recibo';
    document.getElementById('btnText').textContent = 'Enviar Acuse';
    
    const modal = new bootstrap.Modal(document.getElementById('enviarAcuseModal'));
    modal.show();
}

function reenviarAcuse(emailId) {
    document.getElementById('modalTitle').textContent = 'Reenviar Acuse de Recibo';
    document.getElementById('btnText').textContent = 'Reenviar Acuse';
    
    const modal = new bootstrap.Modal(document.getElementById('enviarAcuseModal'));
    modal.show();
}

function confirmarEnvioAcuse() {
    const emailDestino = document.getElementById('email_destino').value;
    const emailId = {{ $email->id }};
    
    if (!emailDestino) {
        alert('Por favor ingresa un email de destino');
        return;
    }
    
    // Mostrar loading
    const btn = document.querySelector('#enviarAcuseModal .btn-primary');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
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
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('enviarAcuseModal'));
            modal.hide();
            
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
