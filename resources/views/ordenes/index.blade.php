@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col">
            <h2>Órdenes de Compra</h2>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Proveedor</th>
                            <th>Fecha</th>
                            <th>Entrega Esperada</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Creado por</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ordenes as $orden)
                        <tr>
                            <td>{{ $orden->numero_orden }}</td>
                            <td>{{ $orden->proveedor->razon_social }}</td>
                            <td>{{ $orden->fecha_orden->format('d/m/Y') }}</td>
                            <td>{{ $orden->fecha_entrega_esperada->format('d/m/Y') }}</td>
                            <td>${{ number_format($orden->total, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $orden->estado === 'pendiente' ? 'warning' : 
                                    ($orden->estado === 'aprobada' ? 'info' : 
                                    ($orden->estado === 'enviada' ? 'primary' : 
                                    ($orden->estado === 'recibida' ? 'success' : 'danger'))) }}">
                                    {{ ucfirst($orden->estado) }}
                                </span>
                            </td>
                            <td>{{ $orden->usuario->name }}</td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('ordenes.show', $orden) }}" 
                                       class="btn btn-sm btn-info">
                                        Ver
                                    </a>
                                    <a href="{{ route('ordenes.export', $orden) }}" 
                                       class="btn btn-sm btn-secondary">
                                        PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $ordenes->links() }}
        </div>
    </div>
</div>
@endsection 