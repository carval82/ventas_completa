@extends('layouts.app')

@section('title', 'Nuevo Producto')

@section('content')
<div class="container-fluid">
   <div class="card">
       <div class="card-header">
           <div class="d-flex justify-content-between align-items-center">
               <h5 class="mb-0">Nuevo Producto</h5>
               <a href="{{ route('productos.index') }}" class="btn btn-primary">
                   <i class="fas fa-arrow-left"></i> Volver
               </a>
           </div>
       </div>

       <div class="card-body">
           <form action="{{ route('productos.store') }}" method="POST">
               @csrf
               <div class="row">
                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label">Código</label>
                           <input type="text" class="form-control @error('codigo') is-invalid @enderror" 
                                  name="codigo" value="{{ old('codigo') }}" required>
                           @error('codigo')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>

                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label">Nombre</label>
                           <input type="text" class="form-control @error('nombre') is-invalid @enderror" 
                                  name="nombre" value="{{ old('nombre') }}" required>
                           @error('nombre')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>

                   <div class="col-12">
                       <div class="mb-3">
                           <label class="form-label">Descripción <small class="text-muted">(opcional)</small></label>
                           <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                     name="descripcion" rows="3" placeholder="Descripción del producto (opcional)">{{ old('descripcion') }}</textarea>
                           @error('descripcion')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>

                    <div class="col-md-2">
                        <div class="mb-3">
                            <label class="form-label">Precio Compra</label>
                            <input type="number" step="0.01" class="form-control @error('precio_compra') is-invalid @enderror" 
                                   name="precio_compra" id="precio_compra" value="{{ old('precio_compra', 0) }}" required onchange="calcularGanancia()">
                            @error('precio_compra')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label class="form-label">Precio Final (con IVA)</label>
                            <input type="number" step="0.01" class="form-control" 
                                   id="precio_final" name="precio_final" value="{{ old('precio_final', 0) }}" required onchange="calcularPrecioSinIVA()">
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label class="form-label">IVA (%)</label>
                            <input type="number" step="0.01" class="form-control @error('iva') is-invalid @enderror" 
                                   name="iva" id="iva" value="{{ old('iva', 19) }}" required onchange="calcularPrecioSinIVA()">
                            @error('iva')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label class="form-label">Precio Venta (sin IVA)</label>
                            <input type="number" step="0.01" class="form-control @error('precio_venta') is-invalid @enderror" 
                                   name="precio_venta" id="precio_venta" value="{{ old('precio_venta', 0) }}" readonly>
                            @error('precio_venta')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label class="form-label">Valor IVA</label>
                            <input type="text" class="form-control" name="valor_iva" id="valor_iva" value="{{ old('valor_iva', 0) }}" readonly>
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label class="form-label">% Ganancia</label>
                            <input type="number" step="0.01" class="form-control" name="porcentaje_ganancia" id="porcentaje_ganancia" value="{{ old('porcentaje_ganancia', 0) }}" readonly>
                        </div>
                    </div>

                   <div class="col-md-6">
                   <div class="mb-3">
    <label class="form-label">Stock</label>
    <input type="number" 
           class="form-control" 
           id="stock" 
           name="stock" 
           value="{{ session('return_to') === 'ventas' ? '1' : '0' }}" 
           {{ session('return_to') === 'ventas' ? 'min=1' : 'min=0' }}>
</div>
                   </div>

                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label">Stock Mínimo</label>
                           <input type="number" class="form-control @error('stock_minimo') is-invalid @enderror" 
                                  name="stock_minimo" value="{{ old('stock_minimo', 5) }}" required>
                           @error('stock_minimo')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                       </div>
                   </div>

                   <div class="col-md-6">
                       <div class="mb-3">
                           <label class="form-label">Unidad de Medida</label>
                           <select name="unidad_medida" id="unidad_medida" class="form-control @error('unidad_medida') is-invalid @enderror" onchange="togglePesoBulto()">
                               <option value="unit" {{ old('unidad_medida') == 'unit' ? 'selected' : '' }}>Unidad</option>
                               <option value="kg" {{ old('unidad_medida') == 'kg' ? 'selected' : '' }}>Kilogramo</option>
                               <option value="g" {{ old('unidad_medida') == 'g' ? 'selected' : '' }}>Gramo</option>
                               <option value="lb" {{ old('unidad_medida') == 'lb' ? 'selected' : '' }}>Libra</option>
                               <option value="l" {{ old('unidad_medida') == 'l' ? 'selected' : '' }}>Litro</option>
                               <option value="ml" {{ old('unidad_medida') == 'ml' ? 'selected' : '' }}>Mililitro</option>
                               <option value="cc" {{ old('unidad_medida') == 'cc' ? 'selected' : '' }}>CC</option>
                               <option value="bulto" {{ old('unidad_medida') == 'bulto' ? 'selected' : '' }}>Bulto</option>
                               <option value="dozen" {{ old('unidad_medida') == 'dozen' ? 'selected' : '' }}>Docena</option>
                               <option value="box" {{ old('unidad_medida') == 'box' ? 'selected' : '' }}>Caja</option>
                           </select>
                           @error('unidad_medida')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                           <small class="form-text text-muted">Requerido para la integración con Alegra</small>
                       </div>
                   </div>

                   <!-- Campo Peso del Bulto -->
                   <div class="col-md-6" id="peso-bulto-container" style="display: none;">
                       <div class="mb-3">
                           <label class="form-label">Peso del Bulto (kg)</label>
                           <input type="number" step="0.001" class="form-control @error('peso_bulto') is-invalid @enderror" 
                                  name="peso_bulto" id="peso_bulto" value="{{ old('peso_bulto') }}" placeholder="Ej: 25.000">
                           @error('peso_bulto')
                               <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                           <small class="form-text text-muted">Especifique el peso en kilogramos de cada bulto</small>
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
                                   <!-- Aquí se agregarán dinámicamente los códigos relacionados -->
                               </tbody>
                           </table>
                       </div>
                       <button type="button" class="btn btn-success" id="btn-agregar-codigo">
                           <i class="fas fa-plus"></i> Agregar Código Relacionado
                       </button>
                   </div>
               </div>

               <!-- Equivalencias de Unidades -->
               <div class="card mt-4 mb-4">
                   <div class="card-header bg-success text-white">
                       <h5 class="mb-0"><i class="fas fa-balance-scale"></i> Equivalencias de Unidades</h5>
                       <small class="text-light">Configure las conversiones automáticas para este producto</small>
                   </div>
                   <div class="card-body">
                       <div class="row">
                           <div class="col-12">
                               <div class="alert alert-info">
                                   <i class="fas fa-info-circle"></i> 
                                   <strong>Ejemplos de equivalencias:</strong><br>
                                   • <strong>Paca de Arroz:</strong> 1 paca = 25 libras = 12.5 kilos<br>
                                   • <strong>Bulto:</strong> 1 bulto = 40 kilos = 88.18 libras<br>
                                   • <strong>Galón:</strong> 1 galón = 3.785 litros = 3785 ml
                               </div>
                           </div>
                       </div>
                       
                       <!-- Configuración rápida por tipo de producto -->
                       <div class="row mb-3">
                           <div class="col-md-4">
                               <label class="form-label">Tipo de Producto</label>
                               <select class="form-select" id="tipo-producto-equivalencia" onchange="configurarEquivalenciasRapidas()">
                                   <option value="">Configuración manual</option>
                                   <option value="paca_arroz">Paca de Arroz (25 lb)</option>
                                   <option value="bulto_40kg">Bulto de 40 kg</option>
                                   <option value="galon_liquido">Galón de Líquido</option>
                                   <option value="caja_docena">Caja por Docenas</option>
                                   <option value="personalizado">Personalizado</option>
                               </select>
                           </div>
                           <div class="col-md-8">
                               <label class="form-label">Unidad Base del Producto</label>
                               <div class="input-group">
                                   <span class="input-group-text">1</span>
                                   <select class="form-select" id="unidad-base-equivalencia" name="unidad_medida">
                                       <option value="unidad">Unidad</option>
                                       <option value="paca">Paca</option>
                                       <option value="bulto">Bulto</option>
                                       <option value="caja">Caja</option>
                                       <option value="galon">Galón</option>
                                       <option value="kg">Kilogramo</option>
                                       <option value="lb">Libra</option>
                                       <option value="l">Litro</option>
                                   </select>
                                   <span class="input-group-text">equivale a:</span>
                               </div>
                           </div>
                       </div>
                       
                       <!-- Tabla de equivalencias -->
                       <div class="table-responsive">
                           <table class="table table-bordered" id="tabla-equivalencias">
                               <thead class="table-light">
                                   <tr>
                                       <th width="25%">Cantidad</th>
                                       <th width="25%">Unidad</th>
                                       <th width="35%">Descripción</th>
                                       <th width="15%">Acciones</th>
                                   </tr>
                               </thead>
                               <tbody>
                                   <!-- Aquí se agregarán dinámicamente las equivalencias -->
                               </tbody>
                           </table>
                       </div>
                       
                       <div class="row">
                           <div class="col-md-6">
                               <button type="button" class="btn btn-success" id="btn-agregar-equivalencia">
                                   <i class="fas fa-plus"></i> Agregar Equivalencia
                               </button>
                           </div>
                           <div class="col-md-6 text-end">
                               <button type="button" class="btn btn-warning" onclick="previsualizarConversiones()">
                                   <i class="fas fa-eye"></i> Previsualizar Conversiones
                               </button>
                           </div>
                       </div>
                       
                       <!-- Preview de conversiones -->
                       <div id="preview-conversiones" class="mt-3" style="display: none;">
                           <div class="alert alert-secondary">
                               <h6><i class="fas fa-calculator"></i> Vista Previa de Conversiones:</h6>
                               <div id="preview-content"></div>
                           </div>
                       </div>
                   </div>
               </div>

              <!-- Botones de acción -->
<div class="mt-4">
    @if(request()->has('return_to'))
        @if(request()->return_to === 'compras')
            <button type="submit" class="btn btn-primary" name="action" value="save_and_return">
                <i class="fas fa-save"></i> Guardar y Volver a Compra
            </button>
            <a href="{{ route('compras.create') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancelar
            </a>
        @elseif(request()->return_to === 'ventas')
            <button type="submit" class="btn btn-primary" name="action" value="save_and_return">
                <i class="fas fa-save"></i> Guardar y Volver a Venta
            </button>
            <a href="{{ route('ventas.create') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancelar
            </a>
        @endif
    @else
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Guardar
        </button>
        <a href="{{ route('compras.index') }}" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancelar
        </a>
    @endif
</div>
</form>
       </div>
   </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Contador para IDs únicos de los campos dinámicos
        let codigoCounter = 0;
        
        // Función para agregar una nueva fila de código relacionado
        $('#btn-agregar-codigo').on('click', function() {
            addCodigoRelacionadoRow();
        });
        
        // Función para agregar fila de código relacionado
        function addCodigoRelacionadoRow() {
            const rowId = codigoCounter++;
            const newRow = `
                <tr id="codigo-row-${rowId}">
                    <td>
                        <input type="text" name="codigos_relacionados[${rowId}][codigo]" 
                               class="form-control" placeholder="Código" required>
                    </td>
                    <td>
                        <input type="text" name="codigos_relacionados[${rowId}][descripcion]" 
                               class="form-control" placeholder="Ej: Sabor Fresa, Tamaño Grande, etc.">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm" 
                                onclick="eliminarCodigoRow(${rowId})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            
            $('#tabla-codigos-relacionados tbody').append(newRow);
        }
        
        // Exponer la función de eliminar para que sea accesible globalmente
        window.eliminarCodigoRow = function(rowId) {
            $(`#codigo-row-${rowId}`).remove();
        };
        
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
        
        // Inicializar valores
        const precioFinal = parseFloat(document.getElementById('precio_final').value) || 0;
        
        // Si hay precio final, calcular precio sin IVA
        if (precioFinal > 0) {
            calcularPrecioSinIVA();
        }
    });
    
    // Función para calcular el precio sin IVA a partir del precio final con IVA
    function calcularPrecioSinIVA() {
        const precioFinal = parseFloat(document.getElementById('precio_final').value) || 0;
        const ivaPorcentaje = parseFloat(document.getElementById('iva').value) || 0;
        
        if (precioFinal > 0) {
            // Calculamos el precio sin IVA: PrecioFinal / (1 + IVA%/100)
            const precioSinIVA = precioFinal / (1 + (ivaPorcentaje / 100));
            document.getElementById('precio_venta').value = precioSinIVA.toFixed(2);
            
            // Calculamos el valor del IVA
            const valorIVA = precioFinal - precioSinIVA;
            document.getElementById('valor_iva').value = valorIVA.toFixed(2);
            
            // Calculamos el porcentaje de ganancia si tenemos precio de compra
            calcularGanancia();
        } else {
            document.getElementById('precio_venta').value = '0.00';
            document.getElementById('valor_iva').value = '0.00';
        }
    }
    
    // Función para calcular el porcentaje de ganancia basado en precio compra y precio final con IVA
    function calcularGanancia() {
        const precioCompra = parseFloat(document.getElementById('precio_compra').value) || 0;
        const precioFinal = parseFloat(document.getElementById('precio_final').value) || 0;
        
        if (precioCompra > 0 && precioFinal > 0) {
            // Calcular porcentaje ganancia: ((precio_final_con_iva - precio_compra) / precio_compra) * 100
            const porcentajeGanancia = ((precioFinal - precioCompra) / precioCompra) * 100;
            document.getElementById('porcentaje_ganancia').value = porcentajeGanancia.toFixed(2);
        } else {
            document.getElementById('porcentaje_ganancia').value = '0.00';
        }
    }
    
    // Función para recalcular el precio final cuando cambia el precio de compra, manteniendo el % de ganancia
    function recalcularPrecioFinalDesdeCompra() {
        const precioCompra = parseFloat(document.getElementById('precio_compra').value) || 0;
        const porcentajeGanancia = parseFloat(document.getElementById('porcentaje_ganancia').value) || 0;
        const ivaPorcentaje = parseFloat(document.getElementById('iva').value) || 0;
        
        if (precioCompra > 0 && porcentajeGanancia > 0) {
            // Calculamos el precio sin IVA basado en el % de ganancia
            const precioSinIVA = precioCompra * (1 + (porcentajeGanancia / 100));
            
            // Calculamos el precio final con IVA
            const precioFinal = precioSinIVA * (1 + (ivaPorcentaje / 100));
            
            // Actualizamos los campos
            document.getElementById('precio_venta').value = precioSinIVA.toFixed(2);
            document.getElementById('precio_final').value = precioFinal.toFixed(2);
            
            // Calculamos el valor del IVA
            const valorIVA = precioFinal - precioSinIVA;
            document.getElementById('valor_iva').value = valorIVA.toFixed(2);
        }
    }
    
    // Función para mostrar/ocultar el campo peso del bulto
    function togglePesoBulto() {
        const unidadMedida = document.getElementById('unidad_medida').value;
        const pesoBultoContainer = document.getElementById('peso-bulto-container');
        const pesoBultoInput = document.getElementById('peso_bulto');
        
        if (unidadMedida === 'bulto') {
            pesoBultoContainer.style.display = 'block';
            pesoBultoInput.required = true;
        } else {
            pesoBultoContainer.style.display = 'none';
            pesoBultoInput.required = false;
            pesoBultoInput.value = '';
        }
    }
    
    // Inicializar al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        togglePesoBulto();
    });
    
    // ============ SISTEMA DE EQUIVALENCIAS ============
    
    let equivalenciaCounter = 0;
    
    // Configuraciones predefinidas
    const configuracionesPredefinidas = {
        'paca_arroz': {
            unidad_base: 'paca',
            equivalencias: [
                { cantidad: 25, unidad: 'lb', descripcion: '1 paca contiene 25 libras' },
                { cantidad: 12.5, unidad: 'kg', descripcion: '1 paca contiene 12.5 kilos' },
                { cantidad: 1, unidad: 'unidad', descripcion: '1 paca = 1 unidad' }
            ]
        },
        'bulto_40kg': {
            unidad_base: 'bulto',
            equivalencias: [
                { cantidad: 40, unidad: 'kg', descripcion: '1 bulto contiene 40 kilos' },
                { cantidad: 88.18, unidad: 'lb', descripcion: '1 bulto contiene 88.18 libras' },
                { cantidad: 1, unidad: 'unidad', descripcion: '1 bulto = 1 unidad' }
            ]
        },
        'galon_liquido': {
            unidad_base: 'galon',
            equivalencias: [
                { cantidad: 3.785, unidad: 'l', descripcion: '1 galón contiene 3.785 litros' },
                { cantidad: 3785, unidad: 'ml', descripcion: '1 galón contiene 3785 mililitros' },
                { cantidad: 1, unidad: 'unidad', descripcion: '1 galón = 1 unidad' }
            ]
        },
        'caja_docena': {
            unidad_base: 'caja',
            equivalencias: [
                { cantidad: 12, unidad: 'unidad', descripcion: '1 caja contiene 12 unidades (1 docena)' },
                { cantidad: 1, unidad: 'docena', descripcion: '1 caja = 1 docena' }
            ]
        }
    };
    
    // Función para configurar equivalencias rápidas
    function configurarEquivalenciasRapidas() {
        const tipo = document.getElementById('tipo-producto-equivalencia').value;
        
        if (!tipo || tipo === 'personalizado') return;
        
        const config = configuracionesPredefinidas[tipo];
        if (!config) return;
        
        // Establecer unidad base
        document.getElementById('unidad-base-equivalencia').value = config.unidad_base;
        
        // Limpiar tabla actual
        document.getElementById('tabla-equivalencias').getElementsByTagName('tbody')[0].innerHTML = '';
        equivalenciaCounter = 0;
        
        // Agregar equivalencias predefinidas
        config.equivalencias.forEach(equiv => {
            agregarFilaEquivalencia(equiv.cantidad, equiv.unidad, equiv.descripcion);
        });
        
        Swal.fire({
            icon: 'success',
            title: 'Configuración aplicada',
            text: `Se han configurado las equivalencias para ${tipo.replace('_', ' ')}`,
            timer: 2000,
            showConfirmButton: false
        });
    }
    
    // Función para agregar fila de equivalencia
    function agregarFilaEquivalencia(cantidad = '', unidad = '', descripcion = '') {
        const rowId = equivalenciaCounter++;
        const newRow = `
            <tr id="equivalencia-row-${rowId}">
                <td>
                    <input type="number" step="0.001" min="0.001" 
                           name="equivalencias[${rowId}][cantidad]" 
                           class="form-control" placeholder="25.000" 
                           value="${cantidad}" required>
                </td>
                <td>
                    <select name="equivalencias[${rowId}][unidad]" class="form-select" required>
                        <option value="">Seleccionar...</option>
                        <option value="unidad" ${unidad === 'unidad' ? 'selected' : ''}>Unidad</option>
                        <option value="paca" ${unidad === 'paca' ? 'selected' : ''}>Paca</option>
                        <option value="bulto" ${unidad === 'bulto' ? 'selected' : ''}>Bulto</option>
                        <option value="caja" ${unidad === 'caja' ? 'selected' : ''}>Caja</option>
                        <option value="kg" ${unidad === 'kg' ? 'selected' : ''}>Kilogramo</option>
                        <option value="g" ${unidad === 'g' ? 'selected' : ''}>Gramo</option>
                        <option value="lb" ${unidad === 'lb' ? 'selected' : ''}>Libra</option>
                        <option value="l" ${unidad === 'l' ? 'selected' : ''}>Litro</option>
                        <option value="ml" ${unidad === 'ml' ? 'selected' : ''}>Mililitro</option>
                        <option value="galon" ${unidad === 'galon' ? 'selected' : ''}>Galón</option>
                        <option value="docena" ${unidad === 'docena' ? 'selected' : ''}>Docena</option>
                    </select>
                </td>
                <td>
                    <input type="text" name="equivalencias[${rowId}][descripcion]" 
                           class="form-control" placeholder="Ej: 1 paca contiene 25 libras"
                           value="${descripcion}">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm" 
                            onclick="eliminarEquivalenciaRow(${rowId})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        document.getElementById('tabla-equivalencias').getElementsByTagName('tbody')[0].insertAdjacentHTML('beforeend', newRow);
    }
    
    // Función para eliminar fila de equivalencia
    function eliminarEquivalenciaRow(rowId) {
        document.getElementById(`equivalencia-row-${rowId}`).remove();
    }
    
    // Función para previsualizar conversiones
    function previsualizarConversiones() {
        const unidadBase = document.getElementById('unidad-base-equivalencia').value;
        const filas = document.querySelectorAll('#tabla-equivalencias tbody tr');
        
        if (!unidadBase || filas.length === 0) {
            Swal.fire('Información', 'Configure al menos una equivalencia para ver la previsualización', 'info');
            return;
        }
        
        let previewHtml = `<strong>Conversiones disponibles desde 1 ${unidadBase.toUpperCase()}:</strong><br><br>`;
        
        filas.forEach(fila => {
            const cantidad = fila.querySelector('input[type="number"]').value;
            const unidad = fila.querySelector('select').value;
            const descripcion = fila.querySelector('input[type="text"]').value;
            
            if (cantidad && unidad) {
                previewHtml += `• <strong>${cantidad} ${unidad.toUpperCase()}</strong>`;
                if (descripcion) {
                    previewHtml += ` - ${descripcion}`;
                }
                previewHtml += '<br>';
            }
        });
        
        // Mostrar conversiones inversas
        previewHtml += '<br><strong>Ejemplos de conversiones inversas:</strong><br>';
        filas.forEach(fila => {
            const cantidad = parseFloat(fila.querySelector('input[type="number"]').value);
            const unidad = fila.querySelector('select').value;
            
            if (cantidad && unidad && cantidad > 0) {
                const factorInverso = (1 / cantidad).toFixed(4);
                previewHtml += `• 1 ${unidad.toUpperCase()} = ${factorInverso} ${unidadBase.toUpperCase()}<br>`;
            }
        });
        
        document.getElementById('preview-content').innerHTML = previewHtml;
        document.getElementById('preview-conversiones').style.display = 'block';
    }
    
    // Event listeners para equivalencias
    document.getElementById('btn-agregar-equivalencia').addEventListener('click', function() {
        agregarFilaEquivalencia();
    });
    
    // Exponer funciones globalmente
    window.configurarEquivalenciasRapidas = configurarEquivalenciasRapidas;
    window.eliminarEquivalenciaRow = eliminarEquivalenciaRow;
    window.previsualizarConversiones = previsualizarConversiones;
    
</script>
@endpush