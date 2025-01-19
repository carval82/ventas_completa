@extends('layouts.app')

@section('title', 'Compras')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Compras</h1>
        <a href="{{ route('compras.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nueva Compra
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('compras.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Fecha Inicio</label>
                    <input type="date" class="form-control" name="fecha_inicio" value="{{ request('fecha_inicio') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Fin</label>
                    <input type="date" class="form-control" name="fecha_fin" value="{{ request('fecha_fin') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Buscar</label>
                    <input type="text" class="form-control" name="search" placeholder="N° Factura o proveedor..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Factura #</th>
                            <th>Proveedor</th>
                            <th>Fecha</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">IVA</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($compras as $compra)
                            <tr>
                                <td>{{ $compra->numero_factura }}</td>
                                <td>{{ $compra->proveedor->razon_social }}</td>
                                <td>{{ $compra->fecha_compra->format('d/m/Y h:i A') }}</td>
                                <td class="text-end">${{ number_format($compra->subtotal, 2) }}</td>
                                <td class="text-end">${{ number_format($compra->iva, 2) }}</td>
                                <td class="text-end">${{ number_format($compra->total, 2) }}</td>
                                <td class="text-center">
                                    <a href="{{ route('compras.show', $compra) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('compras.print', $compra) }}" class="btn btn-sm btn-secondary" target="_blank">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No hay compras registradas</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $compras->links() }}
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Compras del Día</h5>
                    <h2 class="mb-0">${{ number_format($comprasHoy, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Compras del Mes</h5>
                    <h2 class="mb-0">${{ number_format($comprasMes, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Compras</h5>
                    <h2 class="mb-0">${{ number_format($comprasTotal, 2) }}</h2>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection