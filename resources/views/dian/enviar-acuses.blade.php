@extends('layouts.app')

@section('title', 'Enviar Acuses - DIAN')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-paper-plane text-success"></i>
                Enviar Acuses de Recibo
            </h1>
            <p class="text-muted mb-0">Gestiona el envío de acuses de recibo a proveedores</p>
        </div>
        <div>
            <a href="{{ route('dian.dashboard') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </div>

    <!-- Estadísticas de Acuses -->
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
                                {{ $estadisticas['total'] ?? 0 }}
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
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pendientes de Acuse
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $estadisticas['pendientes'] ?? 0 }}
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
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Acuses Enviados
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $estadisticas['enviados'] ?? 0 }}
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
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Tasa de Envío
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $estadisticas['porcentaje'] ?? 0 }}%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-pie fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Información -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> Sobre los Acuses de Recibo</h5>
                <p class="mb-0">
                    Los acuses de recibo son documentos que confirman la recepción de una factura electrónica.
                    Se envían automáticamente al email <strong>real</strong> del proveedor extraído del XML,
                    no al email corporativo genérico. Esto asegura que el proveedor correcto reciba la notificación.
                </p>
            </div>
        </div>
    </div>

    <!-- Opciones de Envío -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-paper-plane"></i> Enviar Acuses Pendientes
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Envía automáticamente acuses de recibo a todas las facturas que aún no tienen 
                        acuse enviado. El sistema usa el email real extraído del XML de cada factura.
                    </p>

                    <div class="bg-light p-3 rounded mb-3">
                        <h6 class="text-success">Facturas pendientes:</h6>
                        <p class="mb-1">
                            <strong>{{ $estadisticas['pendientes'] ?? 0 }}</strong> facturas esperando acuse
                        </p>
                        @if(($estadisticas['pendientes'] ?? 0) > 0)
                            <small class="text-muted">
                                Los acuses se enviarán usando el sistema de email dinámico configurado
                            </small>
                        @endif
                    </div>

                    <form action="{{ route('dian.enviar-acuses') }}" method="POST" id="formEnviarTodos">
                        @csrf
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="solo_con_email_real" 
                                   id="soloEmailReal" checked>
                            <label class="form-check-label" for="soloEmailReal">
                                Enviar solo a facturas con email real extraído
                            </label>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100"
                                @if(($estadisticas['pendientes'] ?? 0) == 0) disabled @endif>
                            <i class="fas fa-paper-plane"></i> 
                            Enviar {{ $estadisticas['pendientes'] ?? 0 }} Acuses Pendientes
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> Gestión Individual
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Administra los acuses individualmente desde la vista dedicada. 
                        Puedes ver detalles, enviar o reenviar acuses específicos.
                    </p>

                    <div class="d-grid gap-2">
                        <a href="{{ route('dian.acuses.index') }}" class="btn btn-primary">
                            <i class="fas fa-list"></i> Ver Lista de Acuses
                        </a>
                        <a href="{{ route('dian.buzon') }}" class="btn btn-outline-primary">
                            <i class="fas fa-inbox"></i> Ver Buzón de Correos
                        </a>
                        <a href="{{ route('email-configurations.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-cog"></i> Configurar Emails
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimas Facturas Pendientes -->
    @if($facturasPendientes->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock"></i> Últimas Facturas Pendientes de Acuse
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Proveedor</th>
                                    <th>CUFE</th>
                                    <th>Email Real</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($facturasPendientes as $email)
                                    @php
                                        $metadatos = is_string($email->metadatos) ? 
                                                    json_decode($email->metadatos, true) : 
                                                    ($email->metadatos ?? []);
                                        $datosProveedor = $metadatos['datos_proveedor_xml'] ?? [];
                                        $emailReal = $metadatos['email_real_proveedor'] ?? null;
                                    @endphp
                                    <tr>
                                        <td>{{ $email->fecha_email->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <strong>{{ $datosProveedor['nombre'] ?? $email->remitente_nombre }}</strong><br>
                                            <small class="text-muted">{{ $email->remitente_email }}</small>
                                        </td>
                                        <td>
                                            <small class="font-monospace">
                                                {{ isset($datosProveedor['cufe']) ? Str::limit($datosProveedor['cufe'], 20) : 'N/A' }}
                                            </small>
                                        </td>
                                        <td>
                                            @if($emailReal)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check"></i> {{ $emailReal }}
                                                </span>
                                            @else
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-exclamation-triangle"></i> No extraído
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-success" 
                                                    onclick="enviarAcuseIndividual({{ $email->id }})">
                                                <i class="fas fa-paper-plane"></i> Enviar
                                            </button>
                                            <a href="{{ route('dian.acuses.show', $email) }}" 
                                               class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-eye"></i> Ver
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $facturasPendientes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Resultados del Último Envío -->
    @if(session('resultados_acuses'))
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-success">
                <h5><i class="fas fa-check-circle"></i> Resultados del Último Envío</h5>
                @php $resultados = session('resultados_acuses'); @endphp
                <p class="mb-0">
                    <strong>Acuses enviados:</strong> {{ $resultados['acuses_enviados'] ?? 0 }}<br>
                    @if(!empty($resultados['errores']))
                        <strong class="text-danger">Errores:</strong> {{ count($resultados['errores']) }}
                    @endif
                </p>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
document.getElementById('formEnviarTodos')?.addEventListener('submit', function(e) {
    if (!confirm('¿Estás seguro de enviar todos los acuses pendientes?')) {
        e.preventDefault();
        return;
    }
    
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando acuses...';
});

function enviarAcuseIndividual(emailId) {
    if (!confirm('¿Enviar acuse de recibo para esta factura?')) {
        return;
    }

    fetch(`/dian/acuses/${emailId}/enviar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Acuse enviado exitosamente a: ' + data.destinatario);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}
</script>
@endsection
