@extends('layouts.app')

@section('title', 'Módulo DIAN - Procesamiento Automático de Facturas')

@section('content')
<div class="container-fluid">
    <!-- Header del Módulo -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-success text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-0">
                                <i class="fas fa-robot"></i> Módulo DIAN - Procesamiento Automático
                            </h2>
                            <p class="mb-0 opacity-75">Sistema automatizado de procesamiento de facturas electrónicas</p>
                        </div>
                        <div class="col-md-4 text-end">
                            @if($configuracion && $configuracion->activo)
                                <div class="badge bg-light text-success fs-6">
                                    <i class="fas fa-check-circle"></i> ACTIVO
                                </div>
                            @else
                                <div class="badge bg-warning text-dark fs-6">
                                    <i class="fas fa-pause-circle"></i> INACTIVO
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estado de Configuración -->
    @if(!$configuracion)
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-3 fa-2x"></i>
                    <div>
                        <h6 class="mb-1">⚙️ Configuración Requerida</h6>
                        <p class="mb-2">Para comenzar a usar el módulo DIAN, necesitas configurar la conexión al email.</p>
                        <a href="{{ route('dian.configuracion') }}" class="btn btn-warning">
                            <i class="fas fa-cog"></i> Configurar Ahora
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Estadísticas del Buzón de Correos -->
    @if($estadisticasBuzon)
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3"><i class="fas fa-inbox"></i> Buzón de Correos Electrónicos</h5>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Emails</h6>
                            <h3>{{ number_format($estadisticasBuzon['total_emails'] ?? 0) }}</h3>
                            <small>en el buzón</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-envelope fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Con Facturas</h6>
                            <h3>{{ number_format($estadisticasBuzon['emails_con_facturas'] ?? 0) }}</h3>
                            <small>facturas detectadas</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-file-invoice fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Hoy</h6>
                            <h3>{{ number_format($estadisticasBuzon['emails_hoy'] ?? 0) }}</h3>
                            <small>emails recibidos</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar-day fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Proveedores</h6>
                            <h3>{{ number_format($estadisticasBuzon['proveedores_activos'] ?? 0) }}</h3>
                            <small>autorizados</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-building fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Acciones Rápidas -->
    @if($configuracion)
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt"></i> Acciones Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('dian.buzon') }}" class="btn btn-info w-100">
                                <i class="fas fa-inbox"></i> Ver Buzón
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('dian.acuses.index') }}" class="btn btn-success w-100">
                                <i class="fas fa-receipt"></i> Ver Acuses
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('dian.procesar-emails.vista') }}" class="btn btn-primary w-100">
                                <i class="fas fa-cogs"></i> Procesar Emails
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('dian.enviar-acuses.vista') }}" class="btn btn-success w-100">
                                <i class="fas fa-paper-plane"></i> Enviar Acuses
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('dian.configuracion') }}" class="btn btn-secondary w-100">
                                <i class="fas fa-cog"></i> Configuración
                            </a>
                        </div>
                        <div class="col-md-6">
                            <form action="{{ route('dian.toggle-activacion') }}" method="POST" style="display: inline;">
                                @csrf
                                @if($configuracion->activo)
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="fas fa-pause"></i> Desactivar
                                    </button>
                                @else
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-play"></i> Activar
                                    </button>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i> Estado del Sistema
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Email DIAN:</strong><br>
                        <small class="text-muted">{{ $configuracion->email_dian }}</small>
                    </div>
                    <div class="mb-3">
                        <strong>Frecuencia:</strong><br>
                        <small class="text-muted">Cada {{ $configuracion->frecuencia_minutos }} minutos</small>
                    </div>
                    <div class="mb-3">
                        <strong>Horario:</strong><br>
                        <small class="text-muted">{{ $configuracion->hora_inicio }} - {{ $configuracion->hora_fin }}</small>
                    </div>
                    @if($estadisticas['ultimo_procesamiento'])
                    <div>
                        <strong>Último Procesamiento:</strong><br>
                        <small class="text-muted">{{ $estadisticas['ultimo_procesamiento']->format('d/m/Y H:i:s') }}</small>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Últimas Facturas Procesadas -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-history"></i> Últimas Facturas Procesadas
                    </h5>
                    <a href="{{ route('dian.facturas') }}" class="btn btn-sm btn-outline-primary">
                        Ver Todas
                    </a>
                </div>
                <div class="card-body">
                    @if($facturas->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Fecha Email</th>
                                    <th>CUFE</th>
                                    <th>Emisor</th>
                                    <th>Valor</th>
                                    <th>Estado</th>
                                    <th>Acuse</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($facturas as $email)
                                @php
                                    $metadatos = is_string($email->metadatos) ? json_decode($email->metadatos, true) : ($email->metadatos ?? []);
                                    $datosProveedor = $metadatos['datos_proveedor_xml'] ?? [];
                                    $acuseEnviado = $metadatos['acuse_enviado'] ?? false;
                                @endphp
                                <tr>
                                    <td>{{ $email->fecha_email->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <small class="font-monospace">
                                            {{ isset($datosProveedor['cufe']) ? Str::limit($datosProveedor['cufe'], 20) : 'N/A' }}
                                        </small>
                                    </td>
                                    <td>
                                        <strong>{{ $datosProveedor['nombre'] ?? $email->remitente_nombre }}</strong><br>
                                        <small class="text-muted">{{ $datosProveedor['nit'] ?? 'N/A' }}</small>
                                    </td>
                                    <td>
                                        @if(isset($datosProveedor['valor_total']))
                                            ${{ number_format($datosProveedor['valor_total'], 2) }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($email->estado)
                                            @case('procesado')
                                                <span class="badge bg-success">Procesado</span>
                                                @break
                                            @case('error')
                                                <span class="badge bg-danger">Error</span>
                                                @break
                                            @case('nuevo')
                                                <span class="badge bg-info">Nuevo</span>
                                                @break
                                            @case('procesando')
                                                <span class="badge bg-warning">Procesando</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ ucfirst($email->estado) }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        @if($acuseEnviado)
                                            <i class="fas fa-check-circle text-success" title="Acuse enviado"></i>
                                        @else
                                            <i class="fas fa-clock text-warning" title="Pendiente"></i>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('dian.acuses.show', $email) }}" 
                                           class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay facturas procesadas aún</p>
                        @if($configuracion)
                        <form action="{{ route('dian.procesar-emails') }}" method="POST" style="display: inline;">
                            @csrf
                            <button class="btn btn-success" onclick="procesarEmails()">
                                <i class="fas fa-sync-alt"></i> Procesar Emails
                            </button>
                        </form>
                        <a href="{{ route('dian.buzon') }}" class="btn btn-warning ms-2">
                            <i class="fas fa-inbox"></i> Ver Buzón
                        </a>
                        <button class="btn btn-primary ms-2" onclick="mostrarSubirXML()">
                            <i class="fas fa-upload"></i> Subir XML
                        </button>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Información del Módulo -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle me-3 fa-2x"></i>
                    <div>
                        <h6 class="mb-1">🤖 Módulo de Procesamiento Automático DIAN</h6>
                        <p class="mb-0">Este módulo conecta automáticamente al email asociado a su cuenta DIAN, descarga las facturas de proveedores, extrae los códigos CUFE y envía acuses de recibido automáticamente. Configurado para funcionar 24/7 con procesamiento inteligente.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Subir XML -->
<div class="modal fade" id="modalSubirXML" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">📄 Subir Factura XML</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('dian.subir-xml') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="archivo_xml" class="form-label">Archivo XML de Factura *</label>
                        <input type="file" 
                               class="form-control" 
                               id="archivo_xml" 
                               name="archivo_xml" 
                               accept=".xml" 
                               required>
                        <div class="form-text">
                            <i class="fas fa-info-circle"></i> 
                            Selecciona un archivo XML de factura electrónica válida
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb"></i> ¿Cómo obtener facturas XML?</h6>
                        <ul class="mb-0">
                            <li>Descarga facturas XML de tu email</li>
                            <li>Extrae archivos XML de ZIP recibidos</li>
                            <li>Obtén XML desde portales de proveedores</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Procesar XML
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Auto-refresh cada 2 minutos
setTimeout(function() {
    location.reload();
}, 120000);

// Confirmaciones para acciones críticas
function mostrarSubirXML() {
    const modal = new bootstrap.Modal(document.getElementById('modalSubirXML'));
    modal.show();
}
document.querySelectorAll('form[action*="toggle-activacion"]').forEach(form => {
    form.addEventListener('submit', function(e) {
        const button = this.querySelector('button');
        const action = button.textContent.includes('Desactivar') ? 'desactivar' : 'activar';
        
        if (!confirm(`¿Está seguro que desea ${action} el módulo DIAN?`)) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
