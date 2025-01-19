@extends('layouts.app')

@section('title', 'Detalle de Movimiento Masivo')

@section('styles')
<style>
    .badge-borrador { background-color: #ffc107; }
    .badge-procesado { background-color: #28a745; color: white; }
    .badge-anulado { background-color: #dc3545; color: white; }
    .detail-label {
        font-weight: bold;
        color: #495057;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    Movimiento #{{ $movimientos_masivo->numero_documento ?? 'N/A' }}
                </h5>
                <div>
                    <a href="{{ route('movimientos-masivos.index') }}" class="btn btn-light">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    @if($movimientos_masivo->estado === 'borrador')
                        <a href="{{ route('movimientos-masivos.procesar', $movimientos_masivo->id) }}" class="btn btn-primary">Procesar</a>
                        <button onclick="confirmarAnular()" class="btn btn-danger">
                            <i class="fas fa-times"></i> Anular
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="card-body">
            @if($movimientos_masivo)
                <div class="row">
                    <!-- Información Principal -->
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Información General</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-4 detail-label">Estado:</div>
                                    <div class="col-md-8">
                                        <span class="badge badge-{{ $movimientos_masivo->estado }}">
                                            {{ ucfirst($movimientos_masivo->estado) }}
                                        </span>
                                    </div>
                                </div>
                                @if($movimientos_masivo->created_at)
                                    <div class="row mb-3">
                                        <div class="col-md-4 detail-label">Fecha Creación:</div>
                                        <div class="col-md-8">
                                            {{ $movimientos_masivo->created_at->format('d/m/Y H:i:s') }}
                                        </div>
                                    </div>
                                @endif
                                @if($movimientos_masivo->usuario)
                                    <div class="row mb-3">
                                        <div class="col-md-4 detail-label">Usuario:</div>
                                        <div class="col-md-8">
                                            {{ $movimientos_masivo->usuario->name }}
                                        </div>
                                    </div>
                                @endif
                                @if($movimientos_masivo->fecha_proceso)
                                    <div class="row mb-3">
                                        <div class="col-md-4 detail-label">Fecha Proceso:</div>
                                        <div class="col-md-8">
                                            {{ $movimientos_masivo->fecha_proceso->format('d/m/Y H:i:s') }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Información de Ubicación -->
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Detalles del Movimiento</h6>
                            </div>
                            <div class="card-body">
                                @if($movimientos_masivo->ubicacionDestino)
                                    <div class="row mb-3">
                                        <div class="col-md-4 detail-label">Ubicación Destino:</div>
                                        <div class="col-md-8">
                                            {{ $movimientos_masivo->ubicacionDestino->nombre }}
                                        </div>
                                    </div>
                                @endif
                                <div class="row mb-3">
                                    <div class="col-md-4 detail-label">Motivo:</div>
                                    <div class="col-md-8">
                                        {{ $movimientos_masivo->motivo }}
                                    </div>
                                </div>
                                @if($movimientos_masivo->observaciones)
                                    <div class="row mb-3">
                                        <div class="col-md-4 detail-label">Observaciones:</div>
                                        <div class="col-md-8">
                                            {{ $movimientos_masivo->observaciones }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Productos -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Productos</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Código</th>
                                                <th>Producto</th>
                                                <th>Cantidad</th>
                                                <th>Costo Unitario</th>
                                                <th>Subtotal</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($movimientos_masivo->detalles as $detalle)
                                                <tr>
                                                    <td>{{ $detalle->producto->codigo ?? 'N/A' }}</td>
                                                    <td>{{ $detalle->producto->nombre ?? 'N/A' }}</td>
                                                    <td>{{ $detalle->cantidad }}</td>
                                                    <td>{{ number_format($detalle->costo_unitario, 2) }}</td>
                                                    <td>{{ number_format($detalle->cantidad * $detalle->costo_unitario, 2) }}</td>
                                                    <td>{{ $detalle->procesado ? 'Procesado' : 'Pendiente' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center">No hay productos en este movimiento</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-warning">
                    No se encontró el movimiento solicitado.
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Formularios para procesar/anular -->
<form id="procesar-form" action="{{ route('movimientos-masivos.procesar', $movimientos_masivo) }}" method="POST" style="display: none;">
    @csrf
    @method('PUT')
</form>

<form id="anular-form" action="{{ route('movimientos-masivos.anular', $movimientos_masivo) }}" method="POST" style="display: none;">
    @csrf
    @method('PUT')
</form>
@endsection

@push('scripts')
<script>
function confirmarProcesar() {
    Swal.fire({
        title: '¿Está seguro?',
        text: "¿Desea procesar este movimiento? Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, procesar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('procesar-form').submit();
        }
    });
}

function confirmarAnular() {
    Swal.fire({
        title: '¿Está seguro?',
        text: "¿Desea anular este movimiento?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('anular-form').submit();
        }
    });
}
</script>
@endpush