<!-- resources/views/home.blade.php -->
@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Métricas Principales -->
    <div class="row">
        <div class="col-md-3">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Ventas Hoy</h6>
                            <h2 class="mb-0">${{ number_format($ventasHoy, 2) }}</h2>
                        </div>
                        <i class="fas fa-shopping-cart fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Ventas del Mes</h6>
                            <h2 class="mb-0">${{ number_format($ventasMes, 2) }}</h2>
                        </div>
                        <i class="fas fa-chart-line fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total Clientes</h6>
                            <h2 class="mb-0">{{ number_format($totalClientes) }}</h2>
                        </div>
                        <i class="fas fa-users fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total Productos</h6>
                            <h2 class="mb-0">{{ number_format($totalProductos) }}</h2>
                        </div>
                        <i class="fas fa-box fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Barra de Atajos de Teclado -->
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex gap-2 bg-light p-2 rounded"  style="max-height: 180px">
            @foreach($shortcuts as $category => $items)
                @foreach($items as $shortcut)
                    <div class="d-flex align-items-center">
                        <a href="{{ route($shortcut['route']) }}" 
                           class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2"
                           title="{{ $shortcut['description'] }}">
                            <i class="fas fa-{{ 
                                $shortcut['description'] === 'Nueva Venta' ? 'cart-plus' :
                                ($shortcut['description'] === 'Nueva Compra' ? 'shopping-bag' :
                                ($shortcut['description'] === 'Productos' ? 'box' :
                                ($shortcut['description'] === 'Clientes' ? 'users' :
                                'truck'))) }}"></i>
                            {{ $shortcut['description'] }}
                            <span class="badge bg-light text-dark border">{{ $shortcut['key'] }}</span>
                        </a>
                    </div>
                @endforeach
                @if(!$loop->last)
                    <div class="vr"></div>
                @endif
            @endforeach
        </div>
    </div>
</div>

<!-- Productos con Bajo Stock -->
<div class="col-md-12" style="max-height: 400px; overflow-y: auto; max-width: 100%;">
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                Productos con Bajo Stock
            </h5>
        </div>
        <div class="card-body p-0" style="max-height: 400px; overflow-y: auto; max-width: 100%;">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th class="text-center">Stock</th>
                            <th class="text-center">Mínimo</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productosBajoStock as $producto)
                        <tr>
                            <td>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-barcode me-1"></i>
                                    {{ $producto->codigo }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-box text-muted me-2"></i>
                                    <div>
                                        <div class="fw-bold">{{ $producto->nombre }}</div>
                                        <small class="text-muted">{{ Str::limit($producto->descripcion, 30) }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $producto->stock == 0 ? 'danger' : 'warning' }} rounded-pill">
                                    <i class="fas fa-{{ $producto->stock == 0 ? 'times' : 'exclamation' }} me-1"></i>
                                    {{ $producto->stock }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info rounded-pill">
                                    <i class="fas fa-level-down-alt me-1"></i>
                                    {{ $producto->stock_minimo }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($producto->estado)
                                    <span class="badge bg-success rounded-pill">
                                        <i class="fas fa-check me-1"></i>
                                        Activo
                                    </span>
                                @else
                                    <span class="badge bg-danger rounded-pill">
                                        <i class="fas fa-times me-1"></i>
                                        Inactivo
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                No hay productos con bajo stock
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<br>
    <div class="row">
        <!-- Últimas Ventas -->
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Últimas Ventas</h5>
                        <a href="{{ route('ventas.create') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Nueva Venta
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Factura #</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ventasRecientes as $venta)
                                <tr>
                                    <td>{{ $venta->numero_factura }}</td>
                                    <td>{{ $venta->cliente->nombres }}</td>
                                    <td>{{ $venta->fecha_venta->format('d/m/Y H:i') }}</td>
                                    <td class="text-end">${{ number_format($venta->total, 2) }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('ventas.show', $venta) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('ventas.print', $venta) }}" class="btn btn-sm btn-secondary" target="_blank">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-3">No hay ventas registradas</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

   
    <!-- Gráfico de Ventas -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Ventas por Mes</h5>
                </div>
                <div class="card-body">
                    <canvas id="ventasChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/chart.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('ventasChart').getContext('2d');
    const ventasPorMes = @json($ventasPorMes);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ventasPorMes.map(v => v.mes),
            datasets: [{
                label: 'Ventas Mensuales',
                data: ventasPorMes.map(v => v.total),
                borderColor: '#39A900',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush

@push('scripts')
<script>
document.addEventListener('keydown', function(event) {
    // Ventas
    if (event.ctrlKey && event.key.toLowerCase() === 'v') {
        event.preventDefault();
        window.location.href = "{{ route('ventas.create') }}";
    }
    // Compras
    if (event.ctrlKey && event.key.toLowerCase() === 'c') {
        event.preventDefault();
        window.location.href = "{{ route('compras.create') }}";
    }
    // Productos
    if (event.ctrlKey && event.key.toLowerCase() === 'd') {
        event.preventDefault();
        window.location.href = "{{ route('productos.index') }}";
    }
    // Clientes
    if (event.ctrlKey && event.key.toLowerCase() === 'e') {
        event.preventDefault();
        window.location.href = "{{ route('clientes.index') }}";
    }
    // Proveedores
    if (event.ctrlKey && event.key.toLowerCase() === 's') {
        event.preventDefault();
        window.location.href = "{{ route('proveedores.index') }}";
    }
});
</script>
@endpush