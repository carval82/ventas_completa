@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Detalle del Crédito</h3>
            <div>
                @if($credito->estado !== 'pagado')
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalPago">
                        <i class="fas fa-dollar-sign"></i> Registrar Pago
                    </button>
                @endif
                <a href="{{ route('creditos.index') }}" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Información del Crédito -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Información del Crédito</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="40%">Factura:</th>
                                    <td>{{ $credito->venta->numero_factura }}</td>
                                </tr>
                                <tr>
                                    <th>Cliente:</th>
                                    <td>{{ $credito->cliente->nombres }} {{ $credito->cliente->apellidos }}</td>
                                </tr>
                                <tr>
                                    <th>Monto Total:</th>
                                    <td>${{ number_format($credito->monto_total, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>Saldo Pendiente:</th>
                                    <td>${{ number_format($credito->saldo_pendiente, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>Fecha Vencimiento:</th>
                                    <td>{{ $credito->fecha_vencimiento->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Estado:</th>
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
                                </tr>
                                @if($diasAtraso > 0)
                                    <tr>
                                        <th>Días de Atraso:</th>
                                        <td class="text-danger">{{ $diasAtraso }} días</td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Resumen de Pagos</h5>
                            <div class="progress mb-3" style="height: 25px;">
                                @php
                                    $porcentajePagado = (($credito->monto_total - $credito->saldo_pendiente) / $credito->monto_total) * 100;
                                @endphp
                                <div class="progress-bar bg-success" 
                                     role="progressbar" 
                                     style="width: {{ $porcentajePagado }}%"
                                     aria-valuenow="{{ $porcentajePagado }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    {{ number_format($porcentajePagado, 1) }}%
                                </div>
                            </div>
                            <div class="row text-center">
                                <div class="col">
                                    <h6>Monto Total</h6>
                                    <h4>${{ number_format($credito->monto_total, 2) }}</h4>
                                </div>
                                <div class="col">
                                    <h6>Pagado</h6>
                                    <h4>${{ number_format($credito->monto_total - $credito->saldo_pendiente, 2) }}</h4>
                                </div>
                                <div class="col">
                                    <h6>Pendiente</h6>
                                    <h4>${{ number_format($credito->saldo_pendiente, 2) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historial de Pagos -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Historial de Pagos</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Monto</th>
                                    <th>Comprobante</th>
                                    <th>Observación</th>
                                    <th>Registrado por</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($credito->pagos as $pago)
                                    <tr>
                                        <td>{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                                        <td>${{ number_format($pago->monto, 2) }}</td>
                                        <td>{{ $pago->comprobante ?? 'N/A' }}</td>
                                        <td>{{ $pago->observacion }}</td>
                                        <td>{{ $pago->user->name ?? 'Sistema' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No hay pagos registrados</td>
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

<!-- Modal de Pago -->
<div class="modal fade" id="modalPago" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPago">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Monto Pendiente</label>
                        <input type="text" class="form-control" value="${{ number_format($credito->saldo_pendiente, 2) }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Monto a Pagar</label>
                        <input type="number" class="form-control" id="monto" name="monto" 
                               step="0.01" max="{{ $credito->saldo_pendiente }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Fecha de Pago</label>
                        <input type="date" class="form-control" id="fecha_pago" name="fecha_pago" 
                               value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observación</label>
                        <textarea class="form-control" id="observacion" name="observacion" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Registrar Pago</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#formPago').on('submit', function(e) {
        e.preventDefault();
        
        const data = {
            monto: $('#monto').val(),
            fecha_pago: $('#fecha_pago').val(),
            comprobante: $('#comprobante').val(),
            observacion: $('#observacion').val()
        };

        $.ajax({
            url: '{{ route("creditos.pago", $credito->id) }}',
            method: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: 'Pago registrado correctamente',
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
    });

    // Validación del monto
    $('#monto').on('input', function() {
        const monto = parseFloat($(this).val()) || 0;
        const saldoPendiente = {{ $credito->saldo_pendiente }};
        
        if (monto > saldoPendiente) {
            $(this).val(saldoPendiente);
        }
    });
});
</script>
@endpush 