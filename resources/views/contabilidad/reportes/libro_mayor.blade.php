@extends('layouts.app')

@section('title', 'Libro Mayor')

@section('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        .print-only { display: block !important; }
    }
    .print-only { display: none; }
    
    .saldo-anterior {
        background-color: #f8f9fa;
        border-left: 3px solid #0d6efd;
    }
    
    .saldo-deudor { color: #198754; }
    .saldo-acreedor { color: #dc3545; }
    
    .total-row {
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
    <h5>Libro Mayor</h5>
    @if($cuenta)
        <h6>{{ $cuenta->codigo }} - {{ $cuenta->nombre }}</h6>
        <p>Del {{ $fecha_desde->format('d/m/Y') }} al {{ $fecha_hasta->format('d/m/Y') }}</p>
    @else
        <h6>Seleccione una cuenta para ver su libro mayor</h6>
    @endif
</div>
    <!-- Filtros -->
    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Cuenta</label>
                    <select name="cuenta_id" class="form-select select2" required>
    <option value="">Seleccione cuenta...</option>
    @foreach($cuentas as $c)
        <option value="{{ $c->id }}" 
                {{ (isset($cuenta) && $cuenta && $cuenta->id == $c->id) ? 'selected' : '' }}>
            {{ $c->codigo }} - {{ $c->nombre }}
        </option>
    @endforeach
</select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Desde</label>
                    <input type="date" name="fecha_desde" class="form-control" 
                           value="{{ $fecha_desde->format('Y-m-d') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" 
                           value="{{ $fecha_hasta->format('Y-m-d') }}" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
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

    <!-- Libro Mayor -->
    <div class="card">
        <div class="card-body">
            <!-- Saldo Anterior -->
            <div class="saldo-anterior p-3 mb-3">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Saldo Anterior al {{ $fecha_desde->format('d/m/Y') }}:</strong>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="{{ $saldo_anterior >= 0 ? 'saldo-deudor' : 'saldo-acreedor' }}">
                            $ {{ number_format(abs($saldo_anterior), 2) }}
                            {{ $saldo_anterior >= 0 ? 'DB' : 'CR' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Movimientos -->
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Comprobante</th>
                            <th>Descripción</th>
                            <th class="text-end">Débito</th>
                            <th class="text-end">Crédito</th>
                            <th class="text-end">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $saldo = $saldo_anterior; @endphp
                        @foreach($movimientos as $movimiento)
                            @php 
                                $saldo += $movimiento->debito - $movimiento->credito;
                            @endphp
                            <tr>
                                <td>{{ $movimiento->fecha->format('d/m/Y') }}</td>
                                <td>
                                    {{ $movimiento->comprobante->numero }}
                                    <span class="badge bg-{{ 
                                        $movimiento->comprobante->tipo == 'Ingreso' ? 'success' : 
                                        ($movimiento->comprobante->tipo == 'Egreso' ? 'danger' : 'info') 
                                    }}">
                                        {{ $movimiento->comprobante->tipo }}
                                    </span>
                                </td>
                                <td>{{ $movimiento->descripcion }}</td>
                                <td class="text-end">
                                    @if($movimiento->debito > 0)
                                        $ {{ number_format($movimiento->debito, 2) }}
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($movimiento->credito > 0)
                                        $ {{ number_format($movimiento->credito, 2) }}
                                    @endif
                                </td>
                                <td class="text-end {{ $saldo >= 0 ? 'saldo-deudor' : 'saldo-acreedor' }}">
                                    $ {{ number_format(abs($saldo), 2) }}
                                    {{ $saldo >= 0 ? 'DB' : 'CR' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="3" class="text-end">Totales:</td>
                            <td class="text-end">$ {{ number_format($totales['debitos'], 2) }}</td>
                            <td class="text-end">$ {{ number_format($totales['creditos'], 2) }}</td>
                            <td class="text-end {{ $saldo >= 0 ? 'saldo-deudor' : 'saldo-acreedor' }}">
                                $ {{ number_format(abs($saldo), 2) }}
                                {{ $saldo >= 0 ? 'DB' : 'CR' }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Resumen -->
            <div class="card mt-4">
                <div class="card-body">
                    <h6>Resumen de Movimientos</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Saldo Anterior:</strong>
                            <span class="{{ $saldo_anterior >= 0 ? 'saldo-deudor' : 'saldo-acreedor' }}">
                                $ {{ number_format(abs($saldo_anterior), 2) }}
                                {{ $saldo_anterior >= 0 ? 'DB' : 'CR' }}
                            </span>
                        </div>
                        <div class="col-md-4">
                            <strong>Total Débitos:</strong>
                            <span class="saldo-deudor">
                                $ {{ number_format($totales['debitos'], 2) }}
                            </span>
                        </div>
                        <div class="col-md-4">
                            <strong>Total Créditos:</strong>
                            <span class="saldo-acreedor">
                                $ {{ number_format($totales['creditos'], 2) }}
                            </span>
                        </div>
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

@push('scripts')
<script>
$(document).ready(function() {
    // Inicializar Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
});
</script>
@endpush