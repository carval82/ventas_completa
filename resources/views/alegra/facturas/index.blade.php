@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Gestión de Facturas Electrónicas (Alegra)</h4>
                </div>

                <div class="card-body">
                    <!-- Filtros -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <form method="GET" action="{{ route('alegra.facturas.index') }}" class="row g-3">
                                        <div class="col-md-3">
                                            <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="{{ $filtros['fecha_inicio'] }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="{{ $filtros['fecha_fin'] }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Filtros rápidos</label>
                                            <div class="d-flex gap-2">
                                                <a href="{{ route('alegra.facturas.index', ['fecha_inicio' => date('Y-m-d', strtotime('-7 days')), 'fecha_fin' => date('Y-m-d')]) }}" class="btn btn-sm btn-outline-primary">Última semana</a>
                                                <a href="{{ route('alegra.facturas.index', ['fecha_inicio' => date('Y-m-d', strtotime('-30 days')), 'fecha_fin' => date('Y-m-d')]) }}" class="btn btn-sm btn-outline-primary">Último mes</a>
                                                <a href="{{ route('alegra.facturas.index', ['fecha_inicio' => date('Y-m-d', strtotime('-90 days')), 'fecha_fin' => date('Y-m-d')]) }}" class="btn btn-sm btn-outline-primary">Último trimestre</a>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="estado" class="form-label">Estado</label>
                                            <select class="form-control" id="estado" name="estado">
                                                <option value="">Todos</option>
                                                <option value="open" {{ $filtros['estado'] == 'open' ? 'selected' : '' }}>Abierta</option>
                                                <option value="closed" {{ $filtros['estado'] == 'closed' ? 'selected' : '' }}>Cerrada</option>
                                                <option value="voided" {{ $filtros['estado'] == 'voided' ? 'selected' : '' }}>Anulada</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i> Filtrar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mensajes de alerta -->
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    
                    @if(isset($facturas[0]['demo']) && $facturas[0]['demo'])
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Atención:</strong> Se están mostrando datos de ejemplo porque no se pudo conectar con Alegra. Verifique las credenciales en la configuración de la empresa.
                        </div>
                    @endif

                    <!-- Resumen del filtro aplicado -->
                    <div class="alert alert-info mt-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Facturas del periodo:</strong> {{ \Carbon\Carbon::parse($filtros['fecha_inicio'])->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($filtros['fecha_fin'])->format('d/m/Y') }}
                            </div>
                            <div>
                                <span class="badge bg-primary">{{ count($facturas) }} facturas encontradas</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabla de facturas -->
                    <div class="table-responsive mt-4">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Número</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Estado Electrónico</th>
                                    <th>Venta Local</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($facturas as $factura)
                                <tr>
                                    <td>{{ $factura['id'] ?? 'N/A' }}</td>
                                    <td>{{ $factura['numberTemplate']['prefix'] ?? '' }}{{ $factura['numberTemplate']['number'] ?? ($factura['number'] ?? 'N/A') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($factura['date'] ?? now())->format('d/m/Y') }}</td>
                                    <td>{{ $factura['client']['name'] ?? 'Cliente no especificado' }}</td>
                                    <td>{{ number_format($factura['total'] ?? 0, 2) }}</td>
                                    <td>
                                        @if(isset($factura['status']))
                                            @if($factura['status'] == 'open')
                                                <span class="badge bg-success">Abierta</span>
                                            @elseif($factura['status'] == 'closed')
                                                <span class="badge bg-primary">Cerrada</span>
                                            @elseif($factura['status'] == 'voided')
                                                <span class="badge bg-danger">Anulada</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $factura['status'] }}</span>
                                            @endif
                                        @else
                                            <span class="badge bg-warning">Desconocido</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info estado-electronico" data-id="{{ $factura['id'] ?? 0 }}">
                                            <i class="fas fa-sync fa-spin"></i> Consultando...
                                        </span>
                                    </td>
                                    <td>
                                        @if(isset($factura['venta_id']))
                                            <a href="{{ route('ventas.show', $factura['venta_id']) }}" class="btn btn-sm btn-outline-primary">
                                                Ver Venta #{{ $factura['venta_id'] }}
                                            </a>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#vincularModal" 
                                                data-alegra-id="{{ $factura['id'] ?? '' }}" 
                                                data-numero="{{ $factura['numberTemplate']['prefix'] ?? '' }}{{ $factura['numberTemplate']['number'] ?? ($factura['number'] ?? 'N/A') }}">
                                                Vincular a Venta
                                            </button>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('alegra.facturas.show', $factura['id'] ?? 0) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center">No se encontraron facturas</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para vincular factura con venta -->
<div class="modal fade" id="vincularModal" tabindex="-1" aria-labelledby="vincularModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vincularModalLabel">Vincular Factura con Venta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('alegra.facturas.vincular') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="alegra_id" id="alegra_id">
                    <input type="hidden" name="numero_factura" id="numero_factura">
                    
                    <div class="mb-3">
                        <label for="venta_id" class="form-label">Seleccione la Venta</label>
                        <select class="form-control" id="venta_id" name="venta_id" required>
                            <option value="">-- Seleccione una venta --</option>
                            @foreach($ventas as $venta)
                                <option value="{{ $venta->id }}">
                                    Venta #{{ $venta->id }} - {{ $venta->numero_factura }} - 
                                    {{ \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y') }} - 
                                    ${{ number_format($venta->total, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Vincular</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configurar modal de vinculación
        const vincularModal = document.getElementById('vincularModal');
        if (vincularModal) {
            vincularModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const alegraId = button.getAttribute('data-alegra-id');
                const numero = button.getAttribute('data-numero');
                
                document.getElementById('alegra_id').value = alegraId;
                document.getElementById('numero_factura').value = numero;
            });
        }
        
        // Consultar estado electrónico de cada factura
        const estadoElectronico = document.querySelectorAll('.estado-electronico');
        estadoElectronico.forEach(function(elemento) {
            const facturaId = elemento.getAttribute('data-id');
            
            fetch(`/alegra/facturas/${facturaId}/estado`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        elemento.innerHTML = `<span class="text-danger">Error</span>`;
                        return;
                    }
                    
                    if (data.status === 'successful') {
                        elemento.className = 'badge bg-success';
                        elemento.innerHTML = 'Validada';
                    } else if (data.status === 'processing') {
                        elemento.className = 'badge bg-warning';
                        elemento.innerHTML = 'En proceso';
                    } else if (data.status === 'failed') {
                        elemento.className = 'badge bg-danger';
                        elemento.innerHTML = 'Fallida';
                    } else {
                        elemento.className = 'badge bg-secondary';
                        elemento.innerHTML = data.status || 'No emitida';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    elemento.innerHTML = 'Error';
                });
        });
    });
</script>
@endsection
