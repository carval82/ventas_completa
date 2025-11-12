@extends('layouts.app')

@section('title', 'Editar Producto')

@section('content')
<div class="container-fluid">
    <!-- Pestañas de navegación -->
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link active" id="info-tab" data-bs-toggle="tab" href="#info">
                <i class="fas fa-info-circle"></i> Información General
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="proveedores-tab" data-bs-toggle="tab" href="#proveedores">
                <i class="fas fa-truck"></i> Proveedores
            </a>
        </li>
    </ul>

    <!-- Contenido de las pestañas -->
    <div class="tab-content">
        <!-- Pestaña de Información General -->
        <div class="tab-pane fade show active" id="info">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Editar Producto</h5>
                        <a href="{{ route('productos.index') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <form action="{{ route('productos.update', $producto) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Código</label>
                                    <input type="text" class="form-control @error('codigo') is-invalid @enderror" 
                                           name="codigo" value="{{ old('codigo', $producto->codigo) }}" required>
                                    @error('codigo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" class="form-control @error('nombre') is-invalid @enderror" 
                                           name="nombre" value="{{ old('nombre', $producto->nombre) }}" required>
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Descripción <small class="text-muted">(opcional)</small></label>
                                    <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                              name="descripcion" rows="3" placeholder="Descripción del producto (opcional)">{{ old('descripcion', $producto->descripcion) }}</textarea>
                                    @error('descripcion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label">Precio Compra</label>
                                    <input type="number" step="0.01" class="form-control @error('precio_compra') is-invalid @enderror" 
                                           name="precio_compra" id="precio_compra" value="{{ old('precio_compra', $producto->precio_compra) }}" required onchange="calcularGanancia()">
                                    @error('precio_compra')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label">Precio Final (con IVA)</label>
                                    <input type="number" step="0.01" class="form-control" 
                                           id="precio_final" name="precio_final" value="{{ old('precio_final', $producto->precio_final) }}" required onchange="calcularPrecioSinIVA()">
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label">IVA (%)</label>
                                    <input type="number" step="0.01" class="form-control @error('iva') is-invalid @enderror" 
                                           name="iva" id="iva" value="{{ old('iva', $producto->iva) }}" required onchange="calcularPrecioSinIVA()">
                                    @error('iva')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label">Precio Venta (sin IVA)</label>
                                    <input type="number" step="0.01" class="form-control @error('precio_venta') is-invalid @enderror" 
                                           name="precio_venta" id="precio_venta" value="{{ old('precio_venta', $producto->precio_venta) }}" readonly>
                                    @error('precio_venta')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label">Valor IVA</label>
                                    <input type="text" class="form-control" name="valor_iva" id="valor_iva" value="{{ old('valor_iva', $producto->valor_iva) }}" readonly>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label">% Ganancia</label>
                                    <input type="number" step="0.01" class="form-control" name="porcentaje_ganancia" id="porcentaje_ganancia" value="{{ old('porcentaje_ganancia', $producto->porcentaje_ganancia) }}" readonly>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Stock</label>
                                    <input type="number" class="form-control @error('stock') is-invalid @enderror" 
                                           name="stock" value="{{ old('stock', $producto->stock) }}" required>
                                    @error('stock')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Stock Mínimo</label>
                                    <input type="number" class="form-control @error('stock_minimo') is-invalid @enderror" 
                                           name="stock_minimo" value="{{ old('stock_minimo', $producto->stock_minimo) }}" required>
                                    @error('stock_minimo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Estado</label>
                                    <select class="form-select @error('estado') is-invalid @enderror" name="estado">
                                        <option value="1" {{ old('estado', $producto->estado) ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ old('estado', $producto->estado) ? '' : 'selected' }}>Inactivo</option>
                                    </select>
                                    @error('estado')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Unidad de Medida</label>
                                    <select name="unidad_medida" class="form-control @error('unidad_medida') is-invalid @enderror">
                                        <option value="unit" {{ old('unidad_medida', $producto->unidad_medida) == 'unit' ? 'selected' : '' }}>Unidad</option>
                                        <option value="kg" {{ old('unidad_medida', $producto->unidad_medida) == 'kg' ? 'selected' : '' }}>Kilogramo</option>
                                        <option value="g" {{ old('unidad_medida', $producto->unidad_medida) == 'g' ? 'selected' : '' }}>Gramo</option>
                                        <option value="lb" {{ old('unidad_medida', $producto->unidad_medida) == 'lb' ? 'selected' : '' }}>Libra</option>
                                        <option value="oz" {{ old('unidad_medida', $producto->unidad_medida) == 'oz' ? 'selected' : '' }}>Onza</option>
                                        <option value="l" {{ old('unidad_medida', $producto->unidad_medida) == 'l' ? 'selected' : '' }}>Litro</option>
                                        <option value="ml" {{ old('unidad_medida', $producto->unidad_medida) == 'ml' ? 'selected' : '' }}>Mililitro</option>
                                        <option value="m" {{ old('unidad_medida', $producto->unidad_medida) == 'm' ? 'selected' : '' }}>Metro</option>
                                        <option value="cm" {{ old('unidad_medida', $producto->unidad_medida) == 'cm' ? 'selected' : '' }}>Centímetro</option>
                                        <option value="box" {{ old('unidad_medida', $producto->unidad_medida) == 'box' ? 'selected' : '' }}>Caja</option>
                                        <option value="pack" {{ old('unidad_medida', $producto->unidad_medida) == 'pack' ? 'selected' : '' }}>Paquete</option>
                                        <option value="service" {{ old('unidad_medida', $producto->unidad_medida) == 'service' ? 'selected' : '' }}>Servicio</option>
                                    </select>
                                    @error('unidad_medida')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Requerido para la integración con Alegra</small>
                                </div>
                            </div>
                        </div>

                        <!-- Códigos Relacionados -->
                        <div class="card mt-4 mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Códigos Relacionados (Opcional)</h5>
                                <small class="text-muted">Agregue códigos adicionales para este producto (ej: diferentes sabores, presentaciones, etc.)</small>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> Los códigos relacionados permiten que al escanear cualquiera de estos códigos, 
                                            se seleccione automáticamente este producto. Útil para productos con diferentes presentaciones o sabores.
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="tabla-codigos-relacionados">
                                        <thead>
                                            <tr>
                                                <th>Código</th>
                                                <th>Descripción (opcional)</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($codigosRelacionados as $codigo)
                                            <tr id="codigo-row-{{ $loop->index }}">
                                                <td>
                                                    <input type="hidden" name="codigos_relacionados[{{ $loop->index }}][id]" value="{{ $codigo->id }}">
                                                    <input type="text" name="codigos_relacionados[{{ $loop->index }}][codigo]" 
                                                           class="form-control" placeholder="Código" value="{{ $codigo->codigo }}" required>
                                                </td>
                                                <td>
                                                    <input type="text" name="codigos_relacionados[{{ $loop->index }}][descripcion]" 
                                                           class="form-control" placeholder="Ej: Sabor Fresa, Tamaño Grande, etc." value="{{ $codigo->descripcion }}">
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                            onclick="eliminarCodigoRow({{ $loop->index }})">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <button type="button" class="btn btn-success" id="btn-agregar-codigo">
                                    <i class="fas fa-plus"></i> Agregar Código Relacionado
                                </button>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Pestaña de Proveedores -->
        <div class="tab-pane fade" id="proveedores">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Gestión de Proveedores</h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#asignarProveedorModal">
                            <i class="fas fa-plus"></i> Asignar Nuevo Proveedor
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Lista de proveedores actuales -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Proveedor</th>
                                    <th>Precio Compra</th>
                                    <th>Código Proveedor</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($producto->proveedores && $producto->proveedores->count() > 0)
                                    @foreach($producto->proveedores as $proveedor)
                                    <tr>
                                        <td>{{ $proveedor->razon_social }}</td>
                                        <td>${{ number_format($proveedor->pivot->precio_compra, 2) }}</td>
                                        <td>{{ $proveedor->pivot->codigo_proveedor }}</td>
                                        <td>
                                            <form action="{{ route('productos.remove-proveedor', $producto) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="proveedor_id" value="{{ $proveedor->id }}">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="4" class="text-center">No hay proveedores asignados</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Asignar Proveedor -->
<div class="modal fade" id="asignarProveedorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Asignar Proveedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('productos.asignar-proveedor', $producto) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="proveedor_id" class="form-label">Proveedor</label>
                        <select class="form-select" id="proveedor_id" name="proveedor_id" required>
                            <option value="">Seleccione un proveedor</option>
                            @foreach($proveedores as $proveedor)
                                <option value="{{ $proveedor->id }}" data-codigo="{{ $proveedor->id }}">
                                    {{ $proveedor->razon_social }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="precio_compra" class="form-label">Precio de Compra</label>
                        <input type="number" step="0.01" class="form-control" id="precio_compra" 
                               name="precio_compra" value="{{ $producto->precio_compra }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="codigo_proveedor" class="form-label">Código del Proveedor</label>
                        <input type="text" class="form-control" id="codigo_proveedor" 
                               name="codigo_proveedor" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Actualizar código del proveedor cuando cambie la selección
    document.getElementById('proveedor_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const codigo = selectedOption.dataset.codigo;
        document.getElementById('codigo_proveedor').value = codigo || '';
    });

    // Agregar fila de código relacionado
    document.getElementById('btn-agregar-codigo').addEventListener('click', function() {
        const tabla = document.getElementById('tabla-codigos-relacionados');
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td>
                <input type="text" name="codigos_relacionados[][codigo]" class="form-control" placeholder="Código" required>
            </td>
            <td>
                <input type="text" name="codigos_relacionados[][descripcion]" class="form-control" placeholder="Ej: Sabor Fresa, Tamaño Grande, etc.">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm" onclick="eliminarCodigoRow(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tabla.tBodies[0].appendChild(fila);
    });
    
    // Eliminar fila de código relacionado
    function eliminarCodigoRow(element) {
        const fila = element.parentNode.parentNode;
        fila.parentNode.removeChild(fila);
    }
    
    // Variables para almacenar los valores originales
    let precioCompraOriginal = 0;
    let precioFinalOriginal = 0;
    let ivaPorcentajeOriginal = 0;
    
    // Función para calcular el precio sin IVA a partir del precio final con IVA
    function calcularPrecioSinIVA() {
        const precioFinal = parseFloat(document.getElementById('precio_final').value);
        const ivaPorcentaje = parseFloat(document.getElementById('iva').value);
        
        if (precioFinal > 0 && !isNaN(ivaPorcentaje)) {
            // Calculamos el precio sin IVA: PrecioFinal / (1 + IVA%/100)
            const precioSinIVA = precioFinal / (1 + (ivaPorcentaje / 100));
            document.getElementById('precio_venta').value = precioSinIVA.toFixed(2);
            
            // Calculamos el valor del IVA
            const valorIVA = precioFinal - precioSinIVA;
            document.getElementById('valor_iva').value = valorIVA.toFixed(2);
            
            // Calculamos el porcentaje de ganancia
            calcularGanancia();
        }
    }
    
    // Función para calcular el porcentaje de ganancia basado en precio compra y precio final con IVA
    function calcularGanancia() {
        const precioCompra = parseFloat(document.getElementById('precio_compra').value);
        const precioFinal = parseFloat(document.getElementById('precio_final').value);
        
        if (precioCompra > 0 && precioFinal > 0) {
            // Calcular porcentaje ganancia: ((precio_final_con_iva - precio_compra) / precio_compra) * 100
            const porcentajeGanancia = ((precioFinal - precioCompra) / precioCompra) * 100;
            document.getElementById('porcentaje_ganancia').value = porcentajeGanancia.toFixed(2);
        }
    }
    
    // Función para recalcular el precio final cuando cambia el precio de compra manteniendo el % de ganancia
    function recalcularPrecioFinalDesdeCompra() {
        const precioCompra = parseFloat(document.getElementById('precio_compra').value);
        const porcentajeGanancia = parseFloat(document.getElementById('porcentaje_ganancia').value);
        const ivaPorcentaje = parseFloat(document.getElementById('iva').value);
        
        if (precioCompra > 0 && !isNaN(porcentajeGanancia) && !isNaN(ivaPorcentaje)) {
            // Si el precio de compra cambió pero el % de ganancia se mantiene,
            // calculamos el nuevo precio sin IVA basado en el % de ganancia
            const precioSinIVA = precioCompra * (1 + (porcentajeGanancia / 100));
            
            // Calculamos el nuevo precio final con IVA
            const precioFinal = precioSinIVA * (1 + (ivaPorcentaje / 100));
            
            // Actualizamos los campos
            document.getElementById('precio_venta').value = precioSinIVA.toFixed(2);
            document.getElementById('precio_final').value = precioFinal.toFixed(2);
            
            // Calculamos el valor del IVA
            const valorIVA = precioFinal - precioSinIVA;
            document.getElementById('valor_iva').value = valorIVA.toFixed(2);
        }
    }
    
    // Configurar eventos para los campos
    document.getElementById('precio_final').addEventListener('change', function() {
        // Si cambia el precio final, recalculamos precio sin IVA, valor IVA y % ganancia
        calcularPrecioSinIVA();
    });
    
    document.getElementById('iva').addEventListener('change', function() {
        // Si cambia el IVA, recalculamos precio sin IVA y valor IVA
        calcularPrecioSinIVA();
    });
    
    document.getElementById('precio_compra').addEventListener('change', function() {
        // Si cambia el precio de compra, preguntamos qué mantener
        const mantenerGanancia = confirm('¿Desea mantener el porcentaje de ganancia actual? ' +
                                       'Presione OK para mantener el % de ganancia y recalcular el precio final. ' +
                                       'Presione Cancelar para mantener el precio final y recalcular el % de ganancia.');
        
        if (mantenerGanancia) {
            // Mantener % ganancia y recalcular precio final
            recalcularPrecioFinalDesdeCompra();
        } else {
            // Mantener precio final y recalcular % ganancia
            calcularGanancia();
        }
    });
    
    // Guardar los valores originales al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        precioCompraOriginal = parseFloat(document.getElementById('precio_compra').value) || 0;
        precioFinalOriginal = parseFloat(document.getElementById('precio_final').value) || 0;
        ivaPorcentajeOriginal = parseFloat(document.getElementById('iva').value) || 0;
    });
</script>
@endpush
@endsection