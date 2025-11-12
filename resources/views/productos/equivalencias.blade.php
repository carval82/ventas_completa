@extends('layouts.app')

@section('title', 'Equivalencias de Productos')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Gestión de Equivalencias de Productos</h5>
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#modalNuevaEquivalencia">
                    <i class="fas fa-plus"></i> Nueva Equivalencia
                </button>
            </div>
        </div>

        <div class="card-body">
            <!-- Filtros -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <select class="form-select" id="filtroProducto">
                        <option value="">Todos los productos</option>
                        @foreach($productos as $producto)
                            <option value="{{ $producto->id }}">{{ $producto->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" id="filtroUnidad" placeholder="Filtrar por unidad...">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary" onclick="filtrarEquivalencias()">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                </div>
            </div>

            <!-- Ejemplos de equivalencias -->
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle"></i> Ejemplos de Equivalencias:</h6>
                <ul class="mb-0">
                    <li><strong>Paca de Arroz:</strong> 1 paca = 25 libras = 12.5 kilos</li>
                    <li><strong>Bulto:</strong> 1 bulto = 40 kilos = 88.18 libras</li>
                    <li><strong>Galón:</strong> 1 galón = 3.785 litros = 3785 ml</li>
                </ul>
            </div>

            <!-- Tabla de equivalencias -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="tablaEquivalencias">
                    <thead class="table-light">
                        <tr>
                            <th>Producto</th>
                            <th>Unidad Origen</th>
                            <th>Unidad Destino</th>
                            <th>Factor</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($equivalencias as $equiv)
                        <tr>
                            <td>{{ $equiv->producto->nombre ?? 'N/A' }}</td>
                            <td><span class="badge bg-primary">{{ strtoupper($equiv->unidad_origen) }}</span></td>
                            <td><span class="badge bg-success">{{ strtoupper($equiv->unidad_destino) }}</span></td>
                            <td><strong>{{ $equiv->factor_conversion }}</strong></td>
                            <td>{{ $equiv->descripcion }}</td>
                            <td>
                                @if($equiv->activo)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editarEquivalencia({{ $equiv->id }})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="eliminarEquivalencia({{ $equiv->id }})">
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
</div>

<!-- Modal Nueva Equivalencia -->
<div class="modal fade" id="modalNuevaEquivalencia" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Equivalencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEquivalencia">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Producto</label>
                        <select class="form-select" name="producto_id" required>
                            <option value="">Seleccionar producto...</option>
                            @foreach($productos as $producto)
                                <option value="{{ $producto->id }}">{{ $producto->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Unidad Origen</label>
                            <select class="form-select" name="unidad_origen" required>
                                <option value="">Seleccionar...</option>
                                <option value="unidad">Unidad</option>
                                <option value="paca">Paca</option>
                                <option value="bulto">Bulto</option>
                                <option value="caja">Caja</option>
                                <option value="kg">Kilogramo</option>
                                <option value="lb">Libra</option>
                                <option value="g">Gramo</option>
                                <option value="l">Litro</option>
                                <option value="ml">Mililitro</option>
                                <option value="galon">Galón</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unidad Destino</label>
                            <select class="form-select" name="unidad_destino" required>
                                <option value="">Seleccionar...</option>
                                <option value="unidad">Unidad</option>
                                <option value="paca">Paca</option>
                                <option value="bulto">Bulto</option>
                                <option value="caja">Caja</option>
                                <option value="kg">Kilogramo</option>
                                <option value="lb">Libra</option>
                                <option value="g">Gramo</option>
                                <option value="l">Litro</option>
                                <option value="ml">Mililitro</option>
                                <option value="galon">Galón</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3 mt-3">
                        <label class="form-label">Factor de Conversión</label>
                        <input type="number" class="form-control" name="factor_conversion" 
                               step="0.0001" min="0.0001" required
                               placeholder="Ej: 25.0000 (1 paca = 25 libras)">
                        <div class="form-text">Cantidad de unidad destino que equivale a 1 unidad origen</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <input type="text" class="form-control" name="descripcion" 
                               placeholder="Ej: 1 paca contiene 25 libras">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Equivalencia</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Manejar envío del formulario
    $('#formEquivalencia').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: '/api/equivalencias',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire('Éxito', 'Equivalencia creada correctamente', 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', 'Error al crear equivalencia', 'error');
            }
        });
    });
});

function filtrarEquivalencias() {
    const producto = $('#filtroProducto').val();
    const unidad = $('#filtroUnidad').val();
    
    // Implementar filtrado (puedes usar AJAX o filtrado del lado cliente)
    console.log('Filtrar por producto:', producto, 'unidad:', unidad);
}

function editarEquivalencia(id) {
    // Implementar edición
    console.log('Editar equivalencia:', id);
}

function eliminarEquivalencia(id) {
    Swal.fire({
        title: '¿Está seguro?',
        text: 'Esta acción eliminará la equivalencia',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Implementar eliminación
            console.log('Eliminar equivalencia:', id);
        }
    });
}
</script>
@endpush
