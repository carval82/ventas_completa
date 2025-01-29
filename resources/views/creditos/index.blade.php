@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Gestión de Créditos</h3>
                </div>
                <div class="card-body">
                    <!-- Resumen de créditos -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5>Total Pendiente</h5>
                                    <h3>${{ number_format($totalPendiente, 2) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5>Total Parcial</h5>
                                    <h3>${{ number_format($totalParcial, 2) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5>Total Cobrado</h5>
                                    <h3>${{ number_format($totalCobrado, 2) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de créditos -->
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Factura</th>
                                    <th>Cliente</th>
                                    <th>Monto Total</th>
                                    <th>Saldo Pendiente</th>
                                    <th>Fecha Vencimiento</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($creditos as $credito)
                                <tr>
                                    <td>{{ $credito->venta->numero_factura }}</td>
                                    <td>{{ $credito->cliente->nombres }} {{ $credito->cliente->apellidos }}</td>
                                    <td>${{ number_format($credito->monto_total, 2) }}</td>
                                    <td>${{ number_format($credito->saldo_pendiente, 2) }}</td>
                                    <td>{{ $credito->fecha_vencimiento->format('d/m/Y') }}</td>
                                    <td>
                                        @switch($credito->estado)
                                            @case('pendiente')
                                                <span class="badge bg-warning">Pendiente</span>
                                                @break
                                            @case('parcial')
                                                <span class="badge bg-info">Pago Parcial</span>
                                                @break
                                            @case('pagado')
                                                <span class="badge bg-success">Pagado</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>
                                        <a href="{{ route('creditos.show', $credito) }}" 
                                           class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                        @can('registrar pagos')
                                            <button type="button" 
                                                    class="btn btn-sm btn-success"
                                                    onclick="mostrarModalPago({{ $credito->id }}, {{ $credito->saldo_pendiente }})">
                                                <i class="fas fa-dollar-sign"></i> Pagar
                                            </button>
                                        @endcan
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">No hay créditos registrados</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        {{ $creditos->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Pago -->
<div class="modal fade" id="modalPago" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formPago">
                    <input type="hidden" id="credito_id">
                    <div class="mb-3">
                        <label>Monto a Pagar:</label>
                        <input type="number" class="form-control" id="monto" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label>Fecha de Pago:</label>
                        <input type="date" class="form-control" id="fecha_pago" required>
                    </div>
                    <div class="mb-3">
                        <label>Observaciones:</label>
                        <textarea class="form-control" id="observacion"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="registrarPago()">Confirmar Pago</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function mostrarModalPago(creditoId, saldoPendiente) {
    $('#credito_id').val(creditoId);
    $('#monto').attr('max', saldoPendiente);
    $('#fecha_pago').val(new Date().toISOString().split('T')[0]);
    $('#modalPago').modal('show');
}

function registrarPago() {
    const creditoId = $('#credito_id').val();
    const data = {
        monto: $('#monto').val(),
        fecha_pago: $('#fecha_pago').val(),
        observacion: $('#observacion').val()
    };

    $.ajax({
        url: `/creditos/${creditoId}/pago`,
        method: 'POST',
        data: data,
        success: function(response) {
            if (response.success) {
                $('#modalPago').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: response.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.reload();
                });
            }
        },
        error: function(xhr) {
            let message = 'Error al procesar el pago';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            Swal.fire('Error', message, 'error');
        }
    });
}
</script>
@endpush 