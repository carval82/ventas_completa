@extends('layouts.app')

@section('title', 'Emitir Factura Electrónica')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-bolt text-warning"></i>
                        Emitir Factura Electrónica
                    </h3>
                    <div>
                        <a href="{{ route('ventas.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Información de la Venta -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-file-invoice"></i> Información de la Venta</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>Número:</strong> {{ $venta->numero_factura }}</p>
                                    <p><strong>Fecha:</strong> {{ $venta->fecha_venta->format('d/m/Y H:i') }}</p>
                                    <p><strong>Usuario:</strong> {{ $venta->usuario->name }}</p>
                                    <p><strong>Estado Actual:</strong> 
                                        <span class="badge bg-warning">{{ $venta->estado_dian ?? 'Pendiente' }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="fas fa-user"></i> Información del Cliente</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>Nombre:</strong> {{ $venta->cliente->nombres }}</p>
                                    <p><strong>Documento:</strong> {{ $venta->cliente->numero_documento }}</p>
                                    <p><strong>Email:</strong> {{ $venta->cliente->email ?? 'No especificado' }}</p>
                                    <p><strong>Teléfono:</strong> {{ $venta->cliente->telefono ?? 'No especificado' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario de Emisión -->
                    <form id="formEmisionDirecta" action="{{ route('facturas.electronicas.procesar-emision-directa', $venta->id) }}" method="POST">
                        @csrf
                        
                        <!-- Productos -->
                        <div class="card border-success mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> Productos/Servicios</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Producto/Servicio</th>
                                                <th class="text-center">Cantidad</th>
                                                <th class="text-end">Precio Unit.</th>
                                                <th class="text-end">Descuento</th>
                                                <th class="text-end">IVA</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="productosTable">
                                            @foreach($venta->detalles as $index => $detalle)
                                            <tr data-index="{{ $index }}">
                                                <td>
                                                    <input type="hidden" name="productos[{{ $index }}][producto_id]" value="{{ $detalle->producto_id }}">
                                                    <strong>{{ $detalle->producto->nombre }}</strong>
                                                    @if($detalle->producto->descripcion)
                                                        <br><small class="text-muted">{{ $detalle->producto->descripcion }}</small>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <input type="number" 
                                                           name="productos[{{ $index }}][cantidad]" 
                                                           value="{{ $detalle->cantidad }}" 
                                                           class="form-control form-control-sm text-center cantidad-input" 
                                                           min="1" step="0.01" required>
                                                </td>
                                                <td class="text-end">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number" 
                                                               name="productos[{{ $index }}][precio]" 
                                                               value="{{ $detalle->precio }}" 
                                                               class="form-control text-end precio-input" 
                                                               min="0" step="0.01" required>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" 
                                                               name="productos[{{ $index }}][descuento]" 
                                                               value="{{ $detalle->descuento ?? 0 }}" 
                                                               class="form-control text-end descuento-input" 
                                                               min="0" max="100" step="0.01">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" 
                                                               name="productos[{{ $index }}][iva]" 
                                                               value="{{ $detalle->iva_porcentaje ?? 0 }}" 
                                                               class="form-control text-end iva-input" 
                                                               min="0" max="100" step="0.01">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <strong class="total-linea">
                                                        ${{ number_format($detalle->cantidad * $detalle->precio, 2) }}
                                                    </strong>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-danger eliminar-producto" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Totales -->
                        <div class="row mb-4">
                            <div class="col-md-8"></div>
                            <div class="col-md-4">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning text-dark">
                                        <h5 class="mb-0"><i class="fas fa-calculator"></i> Totales</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Subtotal:</span>
                                            <strong id="subtotalDisplay">${{ number_format($venta->subtotal, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Descuentos:</span>
                                            <strong id="descuentosDisplay">$0.00</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>IVA:</span>
                                            <strong id="ivaDisplay">${{ number_format($venta->iva, 2) }}</strong>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <span><strong>Total:</strong></span>
                                            <strong id="totalDisplay" class="text-success fs-5">
                                                ${{ number_format($venta->total, 2) }}
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Opciones de Emisión -->
                        <div class="card border-primary mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-cogs"></i> Opciones de Emisión</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="enviar_email" id="enviarEmail" checked>
                                            <label class="form-check-label" for="enviarEmail">
                                                Enviar por email al cliente
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="enviar_dian" id="enviarDian">
                                            <label class="form-check-label" for="enviarDian">
                                                Enviar automáticamente a DIAN
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="observaciones" class="form-label">Observaciones</label>
                                            <textarea class="form-control" name="observaciones" id="observaciones" rows="3" placeholder="Observaciones adicionales para la factura..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" onclick="previewFactura()">
                                <i class="fas fa-eye"></i> Vista Previa
                            </button>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-paper-plane"></i> Emitir Factura Electrónica
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Vista Previa -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vista Previa de la Factura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Contenido de vista previa -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" onclick="confirmarEmision()">
                    <i class="fas fa-check"></i> Confirmar y Emitir
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Calcular totales en tiempo real
    function calcularTotales() {
        let subtotal = 0;
        let totalDescuentos = 0;
        let totalIva = 0;
        
        document.querySelectorAll('#productosTable tr').forEach(function(row) {
            const cantidad = parseFloat(row.querySelector('.cantidad-input')?.value || 0);
            const precio = parseFloat(row.querySelector('.precio-input')?.value || 0);
            const descuento = parseFloat(row.querySelector('.descuento-input')?.value || 0);
            const iva = parseFloat(row.querySelector('.iva-input')?.value || 0);
            
            const subtotalLinea = cantidad * precio;
            const descuentoLinea = subtotalLinea * (descuento / 100);
            const baseImponible = subtotalLinea - descuentoLinea;
            const ivaLinea = baseImponible * (iva / 100);
            const totalLinea = baseImponible + ivaLinea;
            
            // Actualizar total de la línea
            const totalLineaElement = row.querySelector('.total-linea');
            if (totalLineaElement) {
                totalLineaElement.textContent = '$' + totalLinea.toFixed(2);
            }
            
            subtotal += subtotalLinea;
            totalDescuentos += descuentoLinea;
            totalIva += ivaLinea;
        });
        
        const total = subtotal - totalDescuentos + totalIva;
        
        // Actualizar displays
        document.getElementById('subtotalDisplay').textContent = '$' + subtotal.toFixed(2);
        document.getElementById('descuentosDisplay').textContent = '$' + totalDescuentos.toFixed(2);
        document.getElementById('ivaDisplay').textContent = '$' + totalIva.toFixed(2);
        document.getElementById('totalDisplay').textContent = '$' + total.toFixed(2);
    }
    
    // Event listeners para recalcular
    document.addEventListener('input', function(e) {
        if (e.target.matches('.cantidad-input, .precio-input, .descuento-input, .iva-input')) {
            calcularTotales();
        }
    });
    
    // Eliminar producto
    document.addEventListener('click', function(e) {
        if (e.target.closest('.eliminar-producto')) {
            if (confirm('¿Está seguro de eliminar este producto?')) {
                e.target.closest('tr').remove();
                calcularTotales();
            }
        }
    });
    
    // Calcular totales inicial
    calcularTotales();
});

function previewFactura() {
    // Aquí implementarías la vista previa
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    document.getElementById('previewContent').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Generando vista previa...</div>';
    modal.show();
    
    // Simular carga de vista previa
    setTimeout(() => {
        document.getElementById('previewContent').innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Vista previa de la factura con los datos actuales del formulario.
                <br><small>Esta funcionalidad se puede expandir para mostrar el PDF real.</small>
            </div>
        `;
    }, 1000);
}

function confirmarEmision() {
    document.getElementById('previewModal').querySelector('.btn-close').click();
    document.getElementById('formEmisionDirecta').submit();
}
</script>
@endsection
