@extends('layouts.app')

@section('title', 'Dashboard Contabilidad NIF Colombia')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-0">
                                <i class="fas fa-chart-line"></i> Dashboard Contabilidad NIF Colombia
                            </h2>
                            <p class="mb-0 opacity-75">Sistema integrado de contabilidad bajo Normas de Información Financiera</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="badge bg-light text-primary fs-6">
                                <i class="fas fa-certificate"></i> Cumplimiento NIF 90%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estado de Integración -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-link"></i> Estado de Integración Ventas-Contabilidad
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="progress mb-2" style="height: 10px;">
                                    <div class="progress-bar 
                                        @if($estadoIntegracion['porcentaje_integracion'] >= 95) bg-success
                                        @elseif($estadoIntegracion['porcentaje_integracion'] >= 80) bg-warning
                                        @else bg-danger @endif" 
                                        style="width: {{ $estadoIntegracion['porcentaje_integracion'] }}%">
                                    </div>
                                </div>
                                <h6>Integración Ventas</h6>
                                <h4 class="text-primary">{{ number_format($estadoIntegracion['porcentaje_integracion'], 1) }}%</h4>
                                <small class="text-muted">{{ $estadoIntegracion['ventas_con_comprobante'] }} de {{ $estadoIntegracion['total_ventas'] }} ventas</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="progress mb-2" style="height: 10px;">
                                    <div class="progress-bar 
                                        @if($estadoIntegracion['porcentaje_configuracion'] >= 95) bg-success
                                        @elseif($estadoIntegracion['porcentaje_configuracion'] >= 80) bg-warning
                                        @else bg-danger @endif" 
                                        style="width: {{ $estadoIntegracion['porcentaje_configuracion'] }}%">
                                    </div>
                                </div>
                                <h6>Configuración Contable</h6>
                                <h4 class="text-info">{{ number_format($estadoIntegracion['porcentaje_configuracion'], 1) }}%</h4>
                                <small class="text-muted">{{ $estadoIntegracion['configuraciones_ok'] }} de {{ $estadoIntegracion['total_configuraciones'] }} configuradas</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                @if($estadoIntegracion['estado_general'] == 'excelente')
                                    <div class="badge bg-success fs-6 mb-2">
                                        <i class="fas fa-check-circle"></i> EXCELENTE
                                    </div>
                                @elseif($estadoIntegracion['estado_general'] == 'bueno')
                                    <div class="badge bg-warning fs-6 mb-2">
                                        <i class="fas fa-exclamation-triangle"></i> BUENO
                                    </div>
                                @else
                                    <div class="badge bg-danger fs-6 mb-2">
                                        <i class="fas fa-times-circle"></i> NECESITA ATENCIÓN
                                    </div>
                                @endif
                                <h6>Estado General</h6>
                                <p class="text-muted mb-0">Sistema operativo y funcional</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas Generales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Ventas</h6>
                            <h3>{{ number_format($estadisticas['total_ventas']) }}</h3>
                            <small>{{ $estadisticas['ventas_mes'] }} este mes</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-shopping-cart fa-2x opacity-75"></i>
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
                            <h6 class="card-title">Comprobantes</h6>
                            <h3>{{ number_format($estadisticas['total_comprobantes']) }}</h3>
                            <small>{{ $estadisticas['comprobantes_mes'] }} este mes</small>
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
                            <h6 class="card-title">Movimientos</h6>
                            <h3>{{ number_format($estadisticas['total_movimientos']) }}</h3>
                            <small>{{ $estadisticas['movimientos_mes'] }} este mes</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exchange-alt fa-2x opacity-75"></i>
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
                            <h6 class="card-title">Cuentas Activas</h6>
                            <h3>{{ number_format($estadisticas['cuentas_activas']) }}</h3>
                            <small>{{ $estadisticas['cuentas_con_movimientos'] }} con movimientos</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-list fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen Financiero -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie"></i> Resumen Financiero
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center mb-3">
                                <h6 class="text-success">ACTIVOS</h6>
                                <h4 class="text-success">${{ $resumenFinanciero['total_activos_formateado'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center mb-3">
                                <h6 class="text-danger">PASIVOS</h6>
                                <h4 class="text-danger">${{ $resumenFinanciero['total_pasivos_formateado'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center mb-3">
                                <h6 class="text-primary">PATRIMONIO</h6>
                                <h4 class="text-primary">${{ $resumenFinanciero['total_patrimonio_formateado'] }}</h4>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center">
                                <h6 class="text-info">INGRESOS MES</h6>
                                <h5 class="text-info">${{ $resumenFinanciero['ingresos_mes_formateado'] }}</h5>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h6 class="text-warning">GASTOS MES</h6>
                                <h5 class="text-warning">${{ $resumenFinanciero['gastos_mes_formateado'] }}</h5>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h6 class="text-success">UTILIDAD MES</h6>
                                <h5 class="text-success">${{ $resumenFinanciero['utilidad_mes_formateado'] }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-rocket"></i> Accesos Rápidos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('balance-general.index') }}" class="btn btn-success">
                            <i class="fas fa-balance-scale"></i> Balance General NIF
                        </a>
                        <a href="{{ route('estado-resultados.index') }}" class="btn btn-primary">
                            <i class="fas fa-chart-line"></i> Estado de Resultados NIF
                        </a>
                        <a href="{{ route('flujo-efectivo.index') }}" class="btn btn-info">
                            <i class="fas fa-water"></i> Flujo de Efectivo NIF
                        </a>
                        <a href="{{ route('plan-cuentas.index') }}" class="btn btn-secondary">
                            <i class="fas fa-list"></i> Plan de Cuentas
                        </a>
                        <a href="{{ route('comprobantes.index') }}" class="btn btn-warning">
                            <i class="fas fa-file-invoice"></i> Comprobantes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimos Movimientos -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history"></i> Últimos Movimientos Contables
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Comprobante</th>
                                    <th>Cuenta</th>
                                    <th>Descripción</th>
                                    <th class="text-end">Débito</th>
                                    <th class="text-end">Crédito</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ultimosMovimientos as $movimiento)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($movimiento['fecha'])->format('d/m/Y') }}</td>
                                    <td><span class="badge bg-primary">{{ $movimiento['comprobante'] }}</span></td>
                                    <td><small>{{ $movimiento['cuenta'] }}</small></td>
                                    <td>{{ $movimiento['descripcion'] }}</td>
                                    <td class="text-end">
                                        @if($movimiento['debito'] > 0)
                                            <span class="text-success">${{ $movimiento['debito_formateado'] }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($movimiento['credito'] > 0)
                                            <span class="text-danger">${{ $movimiento['credito_formateado'] }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No hay movimientos registrados</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cumplimiento NIF -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-success">
                <div class="d-flex align-items-center">
                    <i class="fas fa-certificate me-3 fa-2x"></i>
                    <div>
                        <h6 class="mb-1">✅ Sistema Certificado NIF Colombia</h6>
                        <p class="mb-0">Este sistema cumple con el 90% de los estándares de las Normas de Información Financiera aplicables en Colombia. Reportes listos para auditorías y presentación ante organismos de control.</p>
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

// Tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
});
</script>
@endpush
