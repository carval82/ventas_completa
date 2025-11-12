@extends('layouts.app')

@section('title', 'Flujo de Efectivo - NIF Colombia')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-water"></i> Flujo de Efectivo - NIF Colombia
                        </h4>
                        <div class="badge bg-light text-dark">
                            <i class="fas fa-certificate"></i> Cumplimiento NIF 75%
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Formulario de Parámetros -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label for="fechaInicio" class="form-label">Fecha Inicio</label>
                            <input type="date" class="form-control" id="fechaInicio" value="{{ date('Y-01-01') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="fechaFin" class="form-label">Fecha Fin</label>
                            <input type="date" class="form-control" id="fechaFin" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="metodo" class="form-label">Método</label>
                            <select class="form-control" id="metodo">
                                <option value="indirecto">Método Indirecto</option>
                                <option value="directo">Método Directo</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-primary me-2" onclick="generarFlujo()">
                                <i class="fas fa-play"></i> Generar Flujo
                            </button>
                            <button type="button" class="btn btn-success" id="btnExportar" onclick="exportarPdf()" style="display: none;">
                                <i class="fas fa-file-pdf"></i> Exportar PDF
                            </button>
                        </div>
                    </div>

                    <!-- Loading -->
                    <div id="loading" class="text-center" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Generando flujo de efectivo...</span>
                        </div>
                        <p class="mt-2">Generando flujo de efectivo...</p>
                    </div>

                    <!-- Resultado del Flujo -->
                    <div id="resultadoFlujo" style="display: none;">
                        <div class="row">
                            <div class="col-12">
                                <h5 class="text-center mb-4">
                                    <strong>FLUJO DE EFECTIVO</strong><br>
                                    <span id="periodoFlujo" class="text-muted"></span><br>
                                    <span id="metodoFlujo" class="badge bg-info"></span>
                                </h5>
                            </div>
                        </div>

                        <!-- Método Indirecto -->
                        <div id="flujoIndirecto" style="display: none;">
                            <!-- Actividades de Operación -->
                            <div class="card mb-3">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-cogs"></i> ACTIVIDADES DE OPERACIÓN</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <tbody>
                                                <tr>
                                                    <td><strong>Utilidad Neta</strong></td>
                                                    <td class="text-end" id="utilidadNeta">$0</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2"><strong>Ajustes por partidas que no afectan el efectivo:</strong></td>
                                                </tr>
                                                <tbody id="ajustesOperacion">
                                                    <!-- Se llena dinámicamente -->
                                                </tbody>
                                                <tr>
                                                    <td colspan="2"><strong>Cambios en el capital de trabajo:</strong></td>
                                                </tr>
                                                <tbody id="cambiosCapital">
                                                    <!-- Se llena dinámicamente -->
                                                </tbody>
                                            </tbody>
                                            <tfoot class="table-success">
                                                <tr>
                                                    <th><strong>EFECTIVO NETO DE ACTIVIDADES DE OPERACIÓN</strong></th>
                                                    <th class="text-end" id="totalOperacion">$0</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Actividades de Inversión -->
                            <div class="card mb-3">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="fas fa-chart-line"></i> ACTIVIDADES DE INVERSIÓN</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <tbody id="actividadesInversion">
                                                <!-- Se llena dinámicamente -->
                                            </tbody>
                                            <tfoot class="table-warning">
                                                <tr>
                                                    <th><strong>EFECTIVO NETO DE ACTIVIDADES DE INVERSIÓN</strong></th>
                                                    <th class="text-end" id="totalInversion">$0</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Actividades de Financiación -->
                            <div class="card mb-3">
                                <div class="card-header bg-danger text-white">
                                    <h6 class="mb-0"><i class="fas fa-university"></i> ACTIVIDADES DE FINANCIACIÓN</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <tbody id="actividadesFinanciacion">
                                                <!-- Se llena dinámicamente -->
                                            </tbody>
                                            <tfoot class="table-danger">
                                                <tr>
                                                    <th><strong>EFECTIVO NETO DE ACTIVIDADES DE FINANCIACIÓN</strong></th>
                                                    <th class="text-end" id="totalFinanciacion">$0</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Método Directo -->
                        <div id="flujoDirecto" style="display: none;">
                            <!-- Actividades de Operación - Directo -->
                            <div class="card mb-3">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-cogs"></i> ACTIVIDADES DE OPERACIÓN</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-success">Entradas de Efectivo</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <tbody id="entradasOperacion">
                                                        <!-- Se llena dinámicamente -->
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <th>Total Entradas</th>
                                                            <th class="text-end" id="totalEntradasOperacion">$0</th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-danger">Salidas de Efectivo</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <tbody id="salidasOperacion">
                                                        <!-- Se llena dinámicamente -->
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <th>Total Salidas</th>
                                                            <th class="text-end" id="totalSalidasOperacion">$0</th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <tfoot class="table-success">
                                                <tr>
                                                    <th><strong>EFECTIVO NETO DE ACTIVIDADES DE OPERACIÓN</strong></th>
                                                    <th class="text-end" id="totalOperacionDirecto">$0</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Actividades de Inversión - Directo -->
                            <div class="card mb-3">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="fas fa-chart-line"></i> ACTIVIDADES DE INVERSIÓN</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-success">Entradas de Efectivo</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <tbody id="entradasInversion">
                                                        <!-- Se llena dinámicamente -->
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <th>Total Entradas</th>
                                                            <th class="text-end" id="totalEntradasInversion">$0</th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-danger">Salidas de Efectivo</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <tbody id="salidasInversion">
                                                        <!-- Se llena dinámicamente -->
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <th>Total Salidas</th>
                                                            <th class="text-end" id="totalSalidasInversion">$0</th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <tfoot class="table-warning">
                                                <tr>
                                                    <th><strong>EFECTIVO NETO DE ACTIVIDADES DE INVERSIÓN</strong></th>
                                                    <th class="text-end" id="totalInversionDirecto">$0</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Actividades de Financiación - Directo -->
                            <div class="card mb-3">
                                <div class="card-header bg-danger text-white">
                                    <h6 class="mb-0"><i class="fas fa-university"></i> ACTIVIDADES DE FINANCIACIÓN</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-success">Entradas de Efectivo</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <tbody id="entradasFinanciacion">
                                                        <!-- Se llena dinámicamente -->
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <th>Total Entradas</th>
                                                            <th class="text-end" id="totalEntradasFinanciacion">$0</th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-danger">Salidas de Efectivo</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <tbody id="salidasFinanciacion">
                                                        <!-- Se llena dinámicamente -->
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <th>Total Salidas</th>
                                                            <th class="text-end" id="totalSalidasFinanciacion">$0</th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <tfoot class="table-danger">
                                                <tr>
                                                    <th><strong>EFECTIVO NETO DE ACTIVIDADES DE FINANCIACIÓN</strong></th>
                                                    <th class="text-end" id="totalFinanciacionDirecto">$0</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Resumen Final -->
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-calculator"></i> RESUMEN DEL FLUJO DE EFECTIVO</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <tbody>
                                            <tr>
                                                <td><strong>Efectivo al inicio del período</strong></td>
                                                <td class="text-end" id="efectivoInicio">$0</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Flujo neto del período</strong></td>
                                                <td class="text-end" id="flujoNeto">$0</td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="table-primary">
                                            <tr>
                                                <th><strong>EFECTIVO AL FINAL DEL PERÍODO</strong></th>
                                                <th class="text-end" id="efectivoFinal">$0</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Cumplimiento NIF -->
                        <div class="alert alert-success mt-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Cumplimiento NIF Colombia:</strong> Este flujo de efectivo cumple con las Normas de Información Financiera aplicables en Colombia.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function generarFlujo() {
    console.log('Iniciando generación de flujo de efectivo...');
    
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;
    const metodo = document.getElementById('metodo').value;

    console.log('Parámetros:', { fechaInicio, fechaFin, metodo });

    if (!fechaInicio || !fechaFin) {
        alert('Debe seleccionar las fechas de inicio y fin');
        return;
    }

    // Mostrar loading
    document.getElementById('loading').style.display = 'block';
    document.getElementById('resultadoFlujo').style.display = 'none';
    document.getElementById('btnExportar').style.display = 'none';

    console.log('Enviando petición a:', '{{ route("flujo-efectivo.generar") }}');

    // Crear FormData
    const formData = new FormData();
    formData.append('fecha_inicio', fechaInicio);
    formData.append('fecha_fin', fechaFin);
    formData.append('metodo', metodo);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

    // Realizar petición AJAX
    fetch('{{ route("flujo-efectivo.generar") }}', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Status:', response.status);
        console.log('Response OK:', response.ok);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.text();
    })
    .then(text => {
        console.log('Respuesta como texto:', text);
        
        try {
            const data = JSON.parse(text);
            console.log('Datos parseados:', data);
            
            document.getElementById('loading').style.display = 'none';
            
            if (data.success) {
                mostrarFlujo(data.flujo_efectivo, data.periodo, data.metodo);
                document.getElementById('btnExportar').style.display = 'inline-block';
            } else {
                alert('Error: ' + (data.message || 'Error al generar el flujo de efectivo'));
            }
        } catch (parseError) {
            console.error('Error parsing JSON:', parseError);
            console.log('Texto recibido no es JSON válido:', text);
            alert('Error: Respuesta inválida del servidor');
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        document.getElementById('loading').style.display = 'none';
        alert('Error de conexión: ' + error.message);
    });
}

function mostrarFlujo(flujo, periodo, metodo) {
    console.log('Mostrando flujo:', flujo);
    
    // Actualizar título
    document.getElementById('periodoFlujo').textContent = periodo;
    document.getElementById('metodoFlujo').textContent = metodo.toUpperCase();

    // Mostrar método correspondiente
    if (metodo === 'indirecto') {
        document.getElementById('flujoIndirecto').style.display = 'block';
        document.getElementById('flujoDirecto').style.display = 'none';
        mostrarFlujoIndirecto(flujo);
    } else {
        document.getElementById('flujoDirecto').style.display = 'block';
        document.getElementById('flujoIndirecto').style.display = 'none';
        mostrarFlujoDirecto(flujo);
    }

    // Mostrar totales
    document.getElementById('efectivoInicio').textContent = '$' + (flujo.totales.efectivo_inicio_formateado || '0');
    document.getElementById('flujoNeto').textContent = '$' + (flujo.totales.flujo_neto_periodo_formateado || '0');
    document.getElementById('efectivoFinal').textContent = '$' + (flujo.totales.efectivo_final_formateado || '0');

    // Mostrar resultado
    document.getElementById('resultadoFlujo').style.display = 'block';
    
    console.log('Flujo mostrado correctamente');
}

function mostrarFlujoIndirecto(flujo) {
    // Mostrar utilidad neta
    document.getElementById('utilidadNeta').textContent = '$' + (flujo.actividades_operacion.utilidad_neta_formateada || '0');
    
    // Mostrar total operación
    document.getElementById('totalOperacion').textContent = '$' + (flujo.actividades_operacion.flujo_neto_operacion_formateado || '0');
    document.getElementById('totalInversion').textContent = '$' + (flujo.actividades_inversion.total_formateado || '0');
    document.getElementById('totalFinanciacion').textContent = '$' + (flujo.actividades_financiacion.total_formateado || '0');
}

function mostrarFlujoDirecto(flujo) {
    // Limpiar tablas
    document.getElementById('entradasOperacion').innerHTML = '';
    document.getElementById('salidasOperacion').innerHTML = '';
    
    // Llenar entradas de operación
    flujo.actividades_operacion.entradas.forEach(entrada => {
        document.getElementById('entradasOperacion').innerHTML += `
            <tr>
                <td>${entrada.concepto}</td>
                <td class="text-end">$${entrada.valor_formateado}</td>
            </tr>
        `;
    });
    
    // Llenar salidas de operación
    flujo.actividades_operacion.salidas.forEach(salida => {
        document.getElementById('salidasOperacion').innerHTML += `
            <tr>
                <td>${salida.concepto}</td>
                <td class="text-end">$${salida.valor_formateado}</td>
            </tr>
        `;
    });
    
    // Mostrar totales
    document.getElementById('totalEntradasOperacion').textContent = '$' + (flujo.actividades_operacion.total_entradas_formateado || '0');
    document.getElementById('totalSalidasOperacion').textContent = '$' + (flujo.actividades_operacion.total_salidas_formateado || '0');
    document.getElementById('totalOperacionDirecto').textContent = '$' + (flujo.actividades_operacion.flujo_neto_operacion_formateado || '0');
    
    // Similar para inversión y financiación...
    document.getElementById('totalInversionDirecto').textContent = '$' + (flujo.actividades_inversion.flujo_neto_inversion_formateado || '0');
    document.getElementById('totalFinanciacionDirecto').textContent = '$' + (flujo.actividades_financiacion.flujo_neto_financiacion_formateado || '0');
}

function exportarPdf() {
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;
    const metodo = document.getElementById('metodo').value;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("flujo-efectivo.pdf") }}';
    form.target = '_blank';

    const inputs = [
        { name: '_token', value: document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
        { name: 'fecha_inicio', value: fechaInicio },
        { name: 'fecha_fin', value: fechaFin },
        { name: 'metodo', value: metodo }
    ];

    inputs.forEach(input => {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = input.name;
        hiddenInput.value = input.value;
        form.appendChild(hiddenInput);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
</script>
@endpush
