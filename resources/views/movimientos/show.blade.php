@extends('layouts.app')

@section('title', 'Detalle de Movimiento')

@section('styles')
<style>
    .detail-label {
        font-weight: bold;
        color: #495057;
    }
    .badge-entrada { background-color: #28a745; color: white; }
    .badge-salida { background-color: #dc3545; color: white; }
    .badge-traslado { background-color: #17a2b8; color: white; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Detalle de Movimiento #{{ $movimiento->id }}</h5>
                <a href="{{ route('movimientos.index') }}" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="row">
                <!-- Información Principal -->
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Información del Movimiento</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4 detail-label">Tipo de Movimiento:</div>
                                <div class="col-md-8">
                                    <span class="badge badge-{{ $movimiento->tipo_movimiento }}">
                                        {{ ucfirst($movimiento->tipo_movimiento) }}
                                    </span>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4 detail-label">Fecha y Hora:</div>
                                <div class="col-md-8">
                                    {{ $movimiento->created_at->format('d/m/Y H:i:s') }}
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4 detail-label">Usuario:</div>
                                <div class="col-md-8">
                                    {{ $movimiento->usuario->name }}
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4 detail-label">Motivo:</div>
                                <div class="col-md-8">
                                    {{ ucfirst($movimiento->motivo) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del Producto -->
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Información del Producto</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4 detail-label">Código:</div>
                                <div class="col-md-8">
                                    {{ $movimiento->producto->codigo }}
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4 detail-label">Nombre:</div>
                                <div class="col-md-8">
                                    {{ $movimiento->producto->nombre }}
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4 detail-label">Cantidad:</div>
                                <div class="col-md-8">
                                    {{ $movimiento->cantidad }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de Ubicaciones -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Ubicaciones</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @if($movimiento->ubicacionOrigen)
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Ubicación Origen</h6>
                                        </div>
                                        <div class="card-body">
                                            {{ $movimiento->ubicacionOrigen->nombre }}
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @if($movimiento->ubicacionDestino)
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Ubicación Destino</h6>
                                        </div>
                                        <div class="card-body">
                                            {{ $movimiento->ubicacionDestino->nombre }}
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Observaciones -->
                @if($movimiento->observaciones)
                <div class="col-12 mt-3">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Observaciones</h6>
                        </div>
                        <div class="card-body">
                            {{ $movimiento->observaciones }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection