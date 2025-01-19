@extends('layouts.app')

@section('title', 'Libro Diario')

@section('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        .print-only { display: block !important; }
        .page-break { page-break-after: always; }
    }
    .print-only { display: none; }
    
    .comprobante-header {
        background-color: #f8f9fa;
        border-left: 3px solid #0d6efd;
        margin-top: 1rem;
    }
    
    .movimientos {
        padding-left: 20px;
    }
    
    .table > tbody > tr.total-row {
        border-top: 2px solid #000;
        font-weight: bold;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Encabezado -->
    <div class="text-center mb-4">
        <h4>{{ config('app.name') }}</h4>
        <h5>Libro Diario</h5>
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
                    <label class="form-label">Tipo Comprobante</label>
                    <select name="tipo" class="form-select">
                        <option value="">Todos</option>
                        <option value="Ingreso" {{ request('tipo') == 'Ingreso' ? 'selected' : '' }}>Ingreso</option>
                        <option value="Egreso" {{ request('tipo') == 'Egreso' ? 'selected' : '' }}>Egreso</option>
                        <option value="Diario" {{ request('tipo') == 'Diario' ? 'selected' : '' }}>Diario</option>
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

    <!-- Libro Diario -->
    <div class="card">
        <div class="card-body">
            @foreach($comprobantes as $comprobante)
                <!-- Encabezado del Comprobante -->
                <div class="comprobante-header p-3">
                    <div class="row">
                        <div class="col-md-2">
                            <strong>Comprobante:</strong><br>
                            {{ $comprobante->numero }}
                        </div>
                        <div class="col-md-2">
                            <strong>Fecha:</strong><br>
                            {{ $comprobante->fecha->format('d/m/Y') }}
                        </div>
                        <div class="col-md-2">
                            <strong>Tipo:</strong><br>
                            <span class="badge bg-{{ 
                                $comprobante->tipo == 'Ingreso' ? 'success' : 
                                ($comprobante->tipo == 'Egreso' ? 'danger' : 'info') 
                            }}">
                                {{ $comprobante->tipo }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Descripción:</strong><br>
                            {{ $comprobante->descripcion }}
                        </div>
                    </div>
                </div>

                <!-- Movimientos -->
                <div class="movimientos">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                @foreach($comprobante->movimientos as $movimiento)
                                    <tr>
                                        <td width="15%">{{ $movimiento->cuenta->codigo }}</td>
                                        <td>{{ $movimiento->cuenta->nombre }}</td>
                                        <td width="15%" class="text-end">
                                            @if($movimiento->debito > 0)
                                                $ {{ number_format($movimiento->debito, 2) }}
                                            @endif
                                        </td>
                                        <td width="15%" class="text-end">
                                            @if($movimiento->credito > 0)
                                                $ {{ number_format($movimiento->credito, 2) }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td colspan="2" class="text-end">Totales:</td>
                                    <td class="text-end">$ {{ number_format($comprobante->total_debito, 2) }}</td>
                                    <td class="text-end">$ {{ number_format($comprobante->total_credito, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endforeach

            <!-- Totales del Período -->
            <div class="card mt-4">
                <div class="card-body">
                    <h6>Totales del Período</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th class="text-end">Cantidad</th>
                                    <th class="text-end">Débitos</th>
                                    <th class="text-end">Créditos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($totales['por_tipo'] as $tipo => $total)
                                    <tr>
                                        <td>{{ $tipo }}</td>
                                        <td class="text-end">{{ $total['cantidad'] }}</td>
                                        <td class="text-end">$ {{ number_format($total['debitos'], 2) }}</td>
                                        <td class="text-end">$ {{ number_format($total['creditos'], 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="total-row">
                                    <td>TOTAL</td>
                                    <td class="text-end">{{ $totales['general']['cantidad'] }}</td>
                                    <td class="text-end">$ {{ number_format($totales['general']['debitos'], 2) }}</td>
                                    <td class="text-end">$ {{ number_format($totales['general']['creditos'], 2) }}</td>
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
            <div>Elaborado por</div>
        </div>
        <div class="col-md-4 text-center">
            <div>_____________________</div>
            <div>Revisado por</div>
        </div>
        <div class="col-md-4 text-center">
            <div>_____________________</div>
            <div>Contador</div>
        </div>
    </div>
</div>
@endsection