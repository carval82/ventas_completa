@extends('layouts.app')

@section('title', 'Cajas Diarias')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0 text-gray-800">Cajas Diarias</h1>
        </div>
        <div class="col-md-6 text-md-end">
            @if(!App\Models\CajaDiaria::hayUnaAbierta())
                <a href="{{ route('cajas.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Abrir Nueva Caja
                </a>
            @else
                <a href="{{ route('cajas.estado-actual') }}" class="btn btn-success">
                    <i class="fas fa-cash-register"></i> Ver Caja Abierta
                </a>
            @endif
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtros</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('cajas.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Fecha desde</label>
                    <input type="date" class="form-control" name="fecha_desde" value="{{ request('fecha_desde') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha hasta</label>
                    <input type="date" class="form-control" name="fecha_hasta" value="{{ request('fecha_hasta') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select class="form-select" name="estado">
                        <option value="">Todos</option>
                        <option value="abierta" {{ request('estado') == 'abierta' ? 'selected' : '' }}>Abierta</option>
                        <option value="cerrada" {{ request('estado') == 'cerrada' ? 'selected' : '' }}>Cerrada</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <a href="{{ route('cajas.index') }}" class="btn btn-secondary">
                        <i class="fas fa-broom"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Listado de Cajas</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha Apertura</th>
                            <th>Fecha Cierre</th>
                            <th>Monto Apertura</th>
                            <th>Monto Cierre</th>
                            <th>Total Ventas</th>
                            <th>Total Gastos</th>
                            <th>Total Pagos</th>
                            <th>Diferencia</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cajas as $caja)
                            <tr>
                                <td>{{ $caja->id }}</td>
                                <td>{{ $caja->fecha_apertura->format('d/m/Y H:i') }}</td>
                                <td>{{ $caja->fecha_cierre ? $caja->fecha_cierre->format('d/m/Y H:i') : 'N/A' }}</td>
                                <td class="text-end">$ {{ number_format($caja->monto_apertura, 2) }}</td>
                                <td class="text-end">{{ $caja->monto_cierre ? '$ ' . number_format($caja->monto_cierre, 2) : 'N/A' }}</td>
                                <td class="text-end">$ {{ number_format($caja->total_ventas, 2) }}</td>
                                <td class="text-end">$ {{ number_format($caja->total_gastos, 2) }}</td>
                                <td class="text-end">$ {{ number_format($caja->total_pagos, 2) }}</td>
                                <td class="text-end {{ $caja->diferencia < 0 ? 'text-danger' : ($caja->diferencia > 0 ? 'text-success' : '') }}">
                                    {{ $caja->diferencia ? '$ ' . number_format($caja->diferencia, 2) : 'N/A' }}
                                </td>
                                <td>
                                    <span class="badge {{ $caja->estado === 'abierta' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $caja->estado === 'abierta' ? 'Abierta' : 'Cerrada' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('cajas.show', $caja) }}" class="btn btn-sm btn-info" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($caja->estado === 'abierta')
                                            <a href="{{ route('cajas.edit', $caja) }}" class="btn btn-sm btn-warning" title="Cerrar caja">
                                                <i class="fas fa-lock"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('cajas.reporte', $caja) }}" class="btn btn-sm btn-primary" title="Generar reporte">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center">No hay cajas registradas</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-end mt-3">
                {{ $cajas->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
