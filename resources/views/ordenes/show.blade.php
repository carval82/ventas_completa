@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col">
            <h2>
                Orden de Compra: {{ $orden->numero_orden }}
                <span class="badge bg-{{ $orden->estado === 'pendiente' ? 'warning' : 
                    ($orden->estado === 'aprobada' ? 'info' : 
                    ($orden->estado === 'enviada' ? 'primary' : 
                    ($orden->estado === 'recibida' ? 'success' : 'danger'))) }}">
                    {{ ucfirst($orden->estado) }}
                </span>
            </h2>
        </div>
        <div class="col text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary dropdown-toggle" 
                        data-bs-toggle="dropdown">
                    Cambiar Estado
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <form action="{{ route('ordenes.update-status', $orden) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="estado" value="aprobada">
                            <button type="submit" class="dropdown-item">Aprobar</button>
                        </form>
                    </li>
                    <li>
                        <form action="{{ route('ordenes.update-status', $orden) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="estado" value="enviada">
                            <button type="submit" class="dropdown-item">Marcar como Enviada</button>
                        </form>
                    </li>
                    <li>
                        <form action="{{ route('ordenes.update-status', $orden) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="estado" value="recibida">
                            <button type="submit" class="dropdown-item">Marcar como Recibida</button>
                        </form>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('ordenes.update-status', $orden) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="estado" value="cancelada">
                            <button type="submit" class="dropdown-item text-danger">Cancelar</button>
                        </form>
                    </li>
                </ul>
                <a href="{{ route('ordenes.export', $orden) }}" class="btn btn-secondary">
                    Exportar PDF
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Información del Proveedor</h5>
                    <p class="mb-1"><strong>Nombre:</strong> {{ $orden->proveedor->razon_social }}</p>
                    <p class="mb-1"><strong>NIT:</strong> {{ $orden->proveedor->nit }}</p>
                    <p class="mb-1"><strong>Teléfono:</strong> {{ $orden->proveedor->telefono }}</p>
                    <p class="mb-0"><strong>Email:</strong> {{ $orden->proveedor->email }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Detalles de la Orden</h5>
                    <p class="mb-1"><strong>Fecha:</strong> {{ $orden->fecha_orden->format('d/m/Y') }}</p>
                    <p class="mb-1"><strong>Entrega Esperada:</strong> {{ $orden->fecha_entrega_esperada->format('d/m/Y') }}</p>
                    <p class="mb-1"><strong>Creado por:</strong> {{ $orden->usuario->name }}</p>
                    <p class="mb-0"><strong>Fecha Creación:</strong> {{ $orden->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Productos</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orden->detalles as $detalle)
                        <tr>
                            <td>{{ $detalle->producto->codigo }}</td>
                            <td>{{ $detalle->producto->nombre }}</td>
                            <td>{{ $detalle->cantidad }}</td>
                            <td>${{ number_format($detalle->precio_unitario, 2) }}</td>
                            <td>${{ number_format($detalle->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end"><strong>Total:</strong></td>
                            <td><strong>${{ number_format($orden->total, 2) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection 