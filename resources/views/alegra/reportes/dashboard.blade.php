@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Dashboard de Facturas Electrónicas (Alegra)</h4>
                </div>

                <div class="card-body">
                    <!-- Filtros -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <form method="GET" action="{{ route('alegra.reportes.dashboard') }}" class="row g-3">
                                        <div class="col-md-3">
                                            <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="{{ $fechaInicio }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="{{ $fechaFin }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="agrupamiento" class="form-label">Agrupamiento</label>
                                            <select class="form-control" id="agrupamiento" name="agrupamiento">
                                                <option value="diario" {{ $agrupamiento == 'diario' ? 'selected' : '' }}>Diario</option>
                                                <option value="semanal" {{ $agrupamiento == 'semanal' ? 'selected' : '' }}>Semanal</option>
                                                <option value="mensual" {{ $agrupamiento == 'mensual' ? 'selected' : '' }}>Mensual</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i> Filtrar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mensajes de alerta -->
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Tarjetas de resumen -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Facturas</h5>
                                    <h2 class="card-text">{{ number_format($estadisticas['total_facturas']) }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Monto</h5>
                                    <h2 class="card-text">${{ number_format($estadisticas['total_monto'], 2) }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Promedio por Factura</h5>
                                    <h2 class="card-text">${{ number_format($estadisticas['promedio_monto'], 2) }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráficos -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Facturas por Período</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="facturasPorPeriodoChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Montos por Período</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="montosPorPeriodoChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enlaces a reportes detallados -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Reportes Detallados</h5>
                                </div>
                                <div class="card-body">
                                    <a href="{{ route('alegra.reportes.periodo', ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'agrupamiento' => $agrupamiento]) }}" class="btn btn-primary">
                                        <i class="fas fa-file-alt"></i> Ver Reporte Detallado por Período
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Datos para gráficos
        const periodos = @json($estadisticas['periodos']);
        const facturasPorPeriodo = @json(array_values($estadisticas['facturas_por_periodo']));
        const montosPorPeriodo = @json(array_values($estadisticas['montos_por_periodo']));
        
        // Gráfico de facturas por período
        const facturasPorPeriodoCtx = document.getElementById('facturasPorPeriodoChart').getContext('2d');
        new Chart(facturasPorPeriodoCtx, {
            type: 'bar',
            data: {
                labels: periodos,
                datasets: [{
                    label: 'Cantidad de Facturas',
                    data: facturasPorPeriodo,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        
        // Gráfico de montos por período
        const montosPorPeriodoCtx = document.getElementById('montosPorPeriodoChart').getContext('2d');
        new Chart(montosPorPeriodoCtx, {
            type: 'line',
            data: {
                labels: periodos,
                datasets: [{
                    label: 'Monto Total ($)',
                    data: montosPorPeriodo,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
@endsection
