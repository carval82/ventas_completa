@extends('layouts.app')

@section('title', 'Balance General')

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
    .total-final {
        border-top: double 3px #000;
        font-weight: bold;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Encabezado -->
    <div class="text-center mb-4">
        <h4>{{ config('app.name') }}</h4>
        <h5>Balance General</h5>
        <p>Al {{ $fecha_corte->format('d/m/Y') }}</p>
    </div>

    <!-- Filtros -->
    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Fecha de Corte</label>
                    <input type="date" name="fecha_corte" class="form-control" value="{{ $fecha_corte->format('Y-m-d') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nivel</label>
                    <select name="nivel" class="form-select">
                        <option value="1" {{ request('nivel') == 1 ? 'selected' : '' }}>Nivel 1</option>
                        <option value="2" {{ request('nivel') == 2 ? 'selected' : '' }}>Nivel 2</option>
                        <option value="3" {{ request('nivel') == 3 ? 'selected' : '' }}>Nivel 3</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
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

    <div class="row">
        <!-- Activos -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Activos</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <tbody>
                                @foreach($activos as $cuenta)
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
                                    <td colspan="2">Total Activos</td>
                                    <td class="text-end">$ {{ number_format($totales['activos'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pasivos y Patrimonio -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Pasivos y Patrimonio</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <tbody>
                                <!-- Pasivos -->
                                @foreach($pasivos as $cuenta)
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
                                    <td colspan="2">Total Pasivos</td>
                                    <td class="text-end">$ {{ number_format($totales['pasivos'], 2) }}</td>
                                </tr>

                                <!-- Patrimonio -->
                                @foreach($patrimonio as $cuenta)
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
                                    <td colspan="2">Total Patrimonio</td>
                                    <td class="text-end">$ {{ number_format($totales['patrimonio'], 2) }}</td>
                                </tr>

                                <!-- Total Pasivo + Patrimonio -->
                                <tr class="total-final">
                                    <td colspan="2">Total Pasivo y Patrimonio</td>
                                    <td class="text-end">$ {{ number_format($totales['pasivos'] + $totales['patrimonio'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
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