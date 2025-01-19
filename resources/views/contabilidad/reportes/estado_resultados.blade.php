@extends('layouts.app')

@section('title', 'Estado de Resultados')

@section('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        .print-only { display: block !important; }
    }
    .print-only { display: none; }
    
    .cuenta-nivel-1 { font-weight: bold; }
    .cuenta-nivel-2 { padding-left: 20px; }
    .cuenta-nivel-3 { padding-left: 40px; }
    
    .total-grupo {
        border-top: 2px solid #000;
        font-weight: bold;
    }
    .utilidad {
        border-top: double 3px #000;
        font-weight: bold;
        font-size: 1.1em;
    }
    .valor-negativo { color: #dc3545; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Encabezado -->
    <div class="text-center mb-4">
        <h4>{{ config('app.name') }}</h4>
        <h5>Estado de Resultados</h5>
        <p>Del {{ $fecha_desde->format('d/m/Y') }} al {{ $fecha_hasta->format('d/m/Y') }}</p>
    </div>

    <!-- Filtros -->
    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Fecha Desde</label>
                    <input type="date" name="fecha_desde" class="form-control" value="{{ $fecha_desde->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" value="{{ $fecha_hasta->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nivel</label>
                    <select name="nivel" class="form-select">
                        <option value="1" {{ request('nivel') == 1 ? 'selected' : '' }}>Nivel 1</option>
                        <option value="2" {{ request('nivel') == 2 ? 'selected' : '' }}>Nivel 2</option>
                        <option value="3" {{ request('nivel') == 3 ? 'selected' : '' }}>Nivel 3</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="window.print()">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Estado de Resultados -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <tbody>
                        <!-- Ingresos -->
                        <tr class="table-primary">
                            <th colspan="3">INGRESOS</th>
                        </tr>
                        @foreach($ingresos as $cuenta)
                            <tr class="cuenta-nivel-{{ $cuenta['nivel'] }}">
                                <td>{{ $cuenta['codigo'] }}</td>
                                <td>{{ $cuenta['nombre'] }}</td>
                                <td class="text-end">
                                    @if($cuenta['es_total'])
                                        <strong>$ {{ number_format(abs($cuenta['saldo']), 2) }}</strong>
                                    @else
                                        $ {{ number_format(abs($cuenta['saldo']), 2) }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        <tr class="total-grupo">
                            <td colspan="2">Total Ingresos</td>
                            <td class="text-end">$ {{ number_format($totales['ingresos'], 2) }}</td>
                        </tr>

                        <!-- Costos -->
                        <tr class="table-danger">
                            <th colspan="3">COSTOS</th>
                        </tr>
                        @foreach($costos as $cuenta)
                            <tr class="cuenta-nivel-{{ $cuenta['nivel'] }}">
                                <td>{{ $cuenta['codigo'] }}</td>
                                <td>{{ $cuenta['nombre'] }}</td>
                                <td class="text-end">
                                    @if($cuenta['es_total'])
                                        <strong>$ {{ number_format($cuenta['saldo'], 2) }}</strong>
                                    @else
                                        $ {{ number_format($cuenta['saldo'], 2) }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        <tr class="total-grupo">
                            <td colspan="2">Total Costos</td>
                            <td class="text-end">$ {{ number_format($totales['costos'], 2) }}</td>
                        </tr>

                        <!-- Utilidad Bruta -->
                        <tr class="table-success total-grupo">
                            <td colspan="2">Utilidad Bruta</td>
                            <td class="text-end">$ {{ number_format($totales['utilidad_bruta'], 2) }}</td>
                        </tr>

                        <!-- Gastos -->
                        <tr class="table-warning">
                            <th colspan="3">GASTOS</th>
                        </tr>
                        @foreach($gastos as $cuenta)
                            <tr class="cuenta-nivel-{{ $cuenta['nivel'] }}">
                                <td>{{ $cuenta['codigo'] }}</td>
                                <td>{{ $cuenta['nombre'] }}</td>
                                <td class="text-end">
                                    @if($cuenta['es_total'])
                                        <strong>$ {{ number_format($cuenta['saldo'], 2) }}</strong>
                                    @else
                                        $ {{ number_format($cuenta['saldo'], 2) }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        <tr class="total-grupo">
                            <td colspan="2">Total Gastos</td>
                            <td class="text-end">$ {{ number_format($totales['gastos'], 2) }}</td>
                        </tr>

                        <!-- Utilidad Neta -->
                        <tr class="utilidad">
                            <td colspan="2">
                                {{ $totales['utilidad_neta'] >= 0 ? 'UTILIDAD' : 'PÉRDIDA' }} NETA DEL PERIODO
                            </td>
                            <td class="text-end {{ $totales['utilidad_neta'] < 0 ? 'valor-negativo' : '' }}">
                                $ {{ number_format(abs($totales['utilidad_neta']), 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Firmas -->
    <div class="row mt-5 print-only">
        <div class="col-md-4 text-center">
            <div>_____________________</div>
            <div>Contador</div>
        </div>
        <div class="col-md-4 text-center">
            <div>_____________________</div>
            <div>Revisor Fiscal</div>
        </div>
        <div class="col-md-4 text-center">
            <div>_____________________</div>
            <div>Gerente</div>
        </div>
    </div>
</div>
@endsection