@extends('layouts.app')

@section('title', 'Ver Comprobante')

@section('styles')
<style>
    @media print {
        .no-print {
            display: none !important;
        }
        .print-only {
            display: block !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
    }
    .print-only {
        display: none;
    }
    .badge-outline {
        background-color: transparent;
        border: 1px solid;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white no-print">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-file-invoice"></i> 
                    Comprobante {{ $comprobante->prefijo ? $comprobante->prefijo . '-' : '' }}{{ $comprobante->numero }}
                </h5>
                <div>
                    @if($comprobante->estado == 'Borrador')
                        <form action="{{ route('comprobantes.aprobar', $comprobante) }}" 
                              method="POST" 
                              class="d-inline">
                            @csrf
                            <button type="button" 
                                    class="btn btn-success" 
                                    onclick="confirmarAprobacion(this)">
                                <i class="fas fa-check"></i> Aprobar
                            </button>
                        </form>
                    @endif

                    @if($comprobante->estado != 'Anulado')
                        <form action="{{ route('comprobantes.anular', $comprobante) }}" 
                              method="POST" 
                              class="d-inline">
                            @csrf
                            <button type="button" 
                                    class="btn btn-danger" 
                                    onclick="confirmarAnulacion(this)">
                                <i class="fas fa-ban"></i> Anular
                            </button>
                        </form>
                    @endif

                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Encabezado de Impresión -->
            <div class="text-center mb-4 print-only">
                <h4>{{ config('app.name') }}</h4>
                <h5>Comprobante Contable</h5>
            </div>

            <!-- Información del Comprobante -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <label class="fw-bold">Prefijo:</label>
                    <div>{{ $comprobante->prefijo ?: 'N/A' }}</div>
                </div>
                <div class="col-md-2">
                    <label class="fw-bold">Número:</label>
                    <div>{{ $comprobante->numero }}</div>
                </div>
                <div class="col-md-3">
                    <label class="fw-bold">Fecha:</label>
                    <div>{{ $comprobante->fecha->format('d/m/Y') }}</div>
                </div>
                <div class="col-md-2">
                    <label class="fw-bold">Tipo:</label>
                    <div>
                        <span class="badge bg-{{ 
                            $comprobante->tipo == 'Ingreso' ? 'success' : 
                            ($comprobante->tipo == 'Egreso' ? 'danger' : 'info') 
                        }}">
                            {{ $comprobante->tipo }}
                        </span>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="fw-bold">Estado:</label>
                    <div>
                        <span class="badge bg-{{ 
                            $comprobante->estado == 'Aprobado' ? 'success' : 
                            ($comprobante->estado == 'Anulado' ? 'danger' : 'warning') 
                        }}">
                            {{ $comprobante->estado }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <label class="fw-bold">Descripción:</label>
                    <div>{{ $comprobante->descripcion }}</div>
                </div>
            </div>

            <!-- Movimientos -->
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Cuenta</th>
                            <th>Descripción</th>
                            <th class="text-end">Débito</th>
                            <th class="text-end">Crédito</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comprobante->movimientos as $movimiento)
                            <tr>
                                <td>{{ $movimiento->cuenta->codigo }}</td>
                                <td>{{ $movimiento->cuenta->nombre }}</td>
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
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">Totales:</th>
                            <th class="text-end">$ {{ number_format($comprobante->total_debito, 2) }}</th>
                            <th class="text-end">$ {{ number_format($comprobante->total_credito, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Firmas -->
            <div class="row mt-5">
                <div class="col-md-4 text-center">
                    <div>_____________________</div>
                    <div>Elaborado por</div>
                    <div class="small">{{ $comprobante->creadoPor->name }}</div>
                    <div class="small">{{ $comprobante->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <div class="col-md-4 text-center">
                    @if($comprobante->approved_by)
                        <div>_____________________</div>
                        <div>Aprobado por</div>
                        <div class="small">{{ $comprobante->aprobadoPor->name }}</div>
                        <div class="small">{{ $comprobante->updated_at->format('d/m/Y H:i') }}</div>
                    @endif
                </div>
                <div class="col-md-4 text-center">
                    <div>_____________________</div>
                    <div>Revisado por</div>
                </div>
            </div>
        </div>

        <div class="card-footer text-end no-print">
            <a href="{{ route('comprobantes.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmarAprobacion(button) {
    Swal.fire({
        title: '¿Aprobar comprobante?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, aprobar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            button.closest('form').submit();
        }
    });
}

function confirmarAnulacion(button) {
    Swal.fire({
        title: '¿Anular comprobante?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            button.closest('form').submit();
        }
    });
}
</script>
@endpush