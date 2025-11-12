@extends('layouts.app')

@section('title', 'Procesar Emails - DIAN')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-cogs text-primary"></i>
                Procesar Emails
            </h1>
            <p class="text-muted mb-0">Sincroniza y procesa facturas electrónicas automáticamente</p>
        </div>
        <div>
            <a href="{{ route('dian.dashboard') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </div>

    <!-- Instrucciones -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> ¿Cómo funciona el procesamiento?</h5>
                <ol class="mb-0">
                    <li><strong>Sincronizar:</strong> Descarga emails nuevos desde el servidor IMAP</li>
                    <li><strong>Detectar:</strong> Identifica emails con facturas electrónicas</li>
                    <li><strong>Extraer:</strong> Lee los archivos XML/ZIP y extrae datos</li>
                    <li><strong>Almacenar:</strong> Guarda la información en la base de datos</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Opciones de Procesamiento -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-sync-alt"></i> Sincronizar Emails
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Descarga emails nuevos desde tu buzón de correo DIAN. Esta acción conecta 
                        con el servidor IMAP y busca emails con facturas electrónicas.
                    </p>
                    
                    <div class="bg-light p-3 rounded mb-3">
                        <h6 class="text-primary">Configuración actual:</h6>
                        @if($configuracion)
                            <p class="mb-1"><strong>Email:</strong> {{ $configuracion->email_dian }}</p>
                            <p class="mb-1"><strong>Servidor:</strong> {{ $configuracion->servidor_imap }}:{{ $configuracion->puerto_imap }}</p>
                            <p class="mb-0"><strong>Estado:</strong> 
                                @if($configuracion->activo)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-warning">Inactivo</span>
                                @endif
                            </p>
                        @else
                            <p class="text-danger mb-0">
                                <i class="fas fa-exclamation-triangle"></i>
                                No hay configuración DIAN. 
                                <a href="{{ route('dian.configuracion') }}">Configurar ahora</a>
                            </p>
                        @endif
                    </div>

                    <form action="{{ route('dian.buzon.sincronizar') }}" method="POST" id="formSincronizar">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Cantidad de emails a sincronizar:</label>
                            <select name="cantidad" class="form-select">
                                <option value="10">Últimos 10 emails</option>
                                <option value="50" selected>Últimos 50 emails</option>
                                <option value="100">Últimos 100 emails</option>
                                <option value="all">Todos los emails nuevos</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" 
                                @if(!$configuracion) disabled @endif>
                            <i class="fas fa-download"></i> Sincronizar Ahora
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-file-invoice"></i> Procesar Facturas
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Extrae información de los emails sincronizados. Lee archivos XML, 
                        extrae CUFEs, datos del proveedor y almacena todo en la base de datos.
                    </p>

                    <div class="bg-light p-3 rounded mb-3">
                        <h6 class="text-success">Emails pendientes de procesar:</h6>
                        @if(isset($estadisticas))
                            <p class="mb-1">
                                <strong>Nuevos:</strong> 
                                <span class="badge bg-info">{{ $estadisticas['nuevos'] ?? 0 }}</span>
                            </p>
                            <p class="mb-1">
                                <strong>Con facturas:</strong> 
                                <span class="badge bg-success">{{ $estadisticas['con_facturas'] ?? 0 }}</span>
                            </p>
                            <p class="mb-0">
                                <strong>Procesados:</strong> 
                                <span class="badge bg-secondary">{{ $estadisticas['procesados'] ?? 0 }}</span>
                            </p>
                        @else
                            <p class="mb-0">Cargando estadísticas...</p>
                        @endif
                    </div>

                    <form action="{{ route('dian.buzon.procesar') }}" method="POST" id="formProcesar">
                        @csrf
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="solo_nuevos" 
                                   id="soloNuevos" checked>
                            <label class="form-check-label" for="soloNuevos">
                                Procesar solo emails nuevos (no procesados anteriormente)
                            </label>
                        </div>

                        <button type="submit" class="btn btn-success w-100"
                                @if(!$configuracion) disabled @endif>
                            <i class="fas fa-cogs"></i> Procesar Facturas
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Procesamiento Completo -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-magic"></i> Procesamiento Completo (Todo en Uno)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <h5 class="text-primary">Sincronizar + Procesar Automáticamente</h5>
                            <p class="text-muted mb-lg-0">
                                Esta opción ejecuta todo el flujo completo: sincroniza emails desde 
                                tu buzón y luego procesa automáticamente todas las facturas encontradas. 
                                Es la forma más rápida de actualizar todo el sistema.
                            </p>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <form action="{{ route('dian.procesar-emails') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-lg btn-gradient-primary"
                                        @if(!$configuracion) disabled @endif>
                                    <i class="fas fa-bolt"></i> Ejecutar Proceso Completo
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimos Resultados -->
    @if(session('resultados_sincronizacion') || session('resultados_procesamiento'))
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar"></i> Resultados del Último Procesamiento
                    </h5>
                </div>
                <div class="card-body">
                    @if(session('resultados_sincronizacion'))
                        <h6 class="text-primary">Sincronización:</h6>
                        <pre class="bg-light p-3 rounded">{{ print_r(session('resultados_sincronizacion'), true) }}</pre>
                    @endif

                    @if(session('resultados_procesamiento'))
                        <h6 class="text-success mt-3">Procesamiento:</h6>
                        <pre class="bg-light p-3 rounded">{{ print_r(session('resultados_procesamiento'), true) }}</pre>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
.btn-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
}
.btn-gradient-primary:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    color: white;
}
</style>
@endsection

@section('scripts')
<script>
document.getElementById('formSincronizar')?.addEventListener('submit', function(e) {
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sincronizando...';
});

document.getElementById('formProcesar')?.addEventListener('submit', function(e) {
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
});
</script>
@endsection
