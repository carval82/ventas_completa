@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-chart-line"></i> Estado de Resultados - NIF Colombia
                    </h4>
                    <div>
                        <button type="button" class="btn btn-primary" onclick="generarEstado()">
                            <i class="fas fa-calculator"></i> Generar Estado
                        </button>
                        <button type="button" class="btn btn-success" onclick="exportarPdf()" id="btnExportar" style="display: none;">
                            <i class="fas fa-file-pdf"></i> Exportar PDF
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Formulario de parámetros -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Fecha Inicio</label>
                            <input type="date" class="form-control" id="fechaInicio" value="{{ date('Y-01-01') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Fin</label>
                            <input type="date" class="form-control" id="fechaFin" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Nivel de Detalle</label>
                            <select class="form-select" id="nivelDetalle">
                                <option value="1">1 - Clase</option>
                                <option value="2">2 - Grupo</option>
                                <option value="3">3 - Cuenta</option>
                                <option value="4" selected>4 - Subcuenta</option>
                                <option value="5">5 - Auxiliar</option>
                                <option value="6">6 - Máximo Detalle</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Opciones</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="mostrarCeros">
                                <label class="form-check-label" for="mostrarCeros">
                                    Mostrar cuentas con saldo cero
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Loading -->
                    <div id="loading" style="display: none;" class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Generando estado...</span>
                        </div>
                        <p class="mt-2">Generando Estado de Resultados...</p>
                    </div>

                    <!-- Resultado del Estado -->
                    <div id="resultadoEstado" style="display: none;">
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="text-center mb-4">
                                    <strong>ESTADO DE RESULTADOS INTEGRAL</strong><br>
                                    <span id="periodoTitle"></span>
                                </h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8 mx-auto">
                                <div class="card">
                                    <div class="card-body">
                                        <!-- INGRESOS OPERACIONALES -->
                                        <div class="mb-4">
                                            <h6 class="bg-success text-white p-2 mb-3">
                                                <i class="fas fa-plus-circle"></i> INGRESOS OPERACIONALES
                                            </h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <tbody id="tablaIngresosOp">
                                                        <!-- Se llena dinámicamente -->
                                                    </tbody>
                                                    <tfoot class="table-success">
                                                        <tr>
                                                            <th>TOTAL INGRESOS OPERACIONALES</th>
                                                            <th class="text-end" id="totalIngresosOp">$0</th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- COSTOS DE VENTAS -->
                                        <div class="mb-4">
                                            <h6 class="bg-warning text-dark p-2 mb-3">
                                                <i class="fas fa-minus-circle"></i> COSTOS DE VENTAS
                                            </h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <tbody id="tablaCostos">
                                                        <!-- Se llena dinámicamente -->
                                                    </tbody>
                                                    <tfoot class="table-warning">
                                                        <tr>
                                                            <th>TOTAL COSTOS DE VENTAS</th>
                                                            <th class="text-end" id="totalCostos">$0</th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- UTILIDAD BRUTA -->
                                        <div class="mb-4">
                                            <div class="bg-info text-white p-2">
                                                <div class="row">
                                                    <div class="col-8">
                                                        <strong>UTILIDAD BRUTA</strong>
                                                    </div>
                                                    <div class="col-4 text-end">
                                                        <strong id="utilidadBruta">$0</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- GASTOS OPERACIONALES -->
                                        <div class="mb-4">
                                            <h6 class="bg-danger text-white p-2 mb-3">
                                                <i class="fas fa-minus-circle"></i> GASTOS OPERACIONALES
                                            </h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <tbody id="tablaGastosOp">
                                                        <!-- Se llena dinámicamente -->
                                                    </tbody>
                                                    <tfoot class="table-danger">
                                                        <tr>
                                                            <th>TOTAL GASTOS OPERACIONALES</th>
                                                            <th class="text-end" id="totalGastosOp">$0</th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- UTILIDAD OPERACIONAL -->
                                        <div class="mb-4">
                                            <div class="bg-primary text-white p-2">
                                                <div class="row">
                                                    <div class="col-8">
                                                        <strong>UTILIDAD OPERACIONAL</strong>
                                                    </div>
                                                    <div class="col-4 text-end">
                                                        <strong id="utilidadOperacional">$0</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- INGRESOS NO OPERACIONALES -->
                                        <div class="mb-4" id="seccionIngresosNoOp" style="display: none;">
                                            <h6 class="bg-success bg-opacity-75 text-dark p-2 mb-3">
                                                <i class="fas fa-plus-circle"></i> INGRESOS NO OPERACIONALES
                                            </h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <tbody id="tablaIngresosNoOp">
                                                        <!-- Se llena dinámicamente -->
                                                    </tbody>
                                                    <tfoot class="table-success">
                                                        <tr>
                                                            <th>TOTAL INGRESOS NO OPERACIONALES</th>
                                                            <th class="text-end" id="totalIngresosNoOp">$0</th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- GASTOS NO OPERACIONALES -->
                                        <div class="mb-4" id="seccionGastosNoOp" style="display: none;">
                                            <h6 class="bg-danger bg-opacity-75 text-dark p-2 mb-3">
                                                <i class="fas fa-minus-circle"></i> GASTOS NO OPERACIONALES
                                            </h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <tbody id="tablaGastosNoOp">
                                                        <!-- Se llena dinámicamente -->
                                                    </tbody>
                                                    <tfoot class="table-danger">
                                                        <tr>
                                                            <th>TOTAL GASTOS NO OPERACIONALES</th>
                                                            <th class="text-end" id="totalGastosNoOp">$0</th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- UTILIDAD NETA -->
                                        <div class="mb-4">
                                            <div class="bg-dark text-white p-3">
                                                <div class="row">
                                                    <div class="col-8">
                                                        <h5 class="mb-0">UTILIDAD NETA</h5>
                                                    </div>
                                                    <div class="col-4 text-end">
                                                        <h5 class="mb-0" id="utilidadNeta">$0</h5>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ANÁLISIS DE MÁRGENES -->
                                        <div class="mt-4">
                                            <h6 class="bg-light p-2 mb-3">
                                                <i class="fas fa-percentage"></i> ANÁLISIS DE MÁRGENES
                                            </h6>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="text-center">
                                                        <div class="h4 text-success" id="margenBruto">0%</div>
                                                        <small>Margen Bruto</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="text-center">
                                                        <div class="h4 text-primary" id="margenOperacional">0%</div>
                                                        <small>Margen Operacional</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="text-center">
                                                        <div class="h4 text-dark" id="margenNeto">0%</div>
                                                        <small>Margen Neto</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
function generarEstado() {
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;
    const nivelDetalle = document.getElementById('nivelDetalle').value;
    const mostrarCeros = document.getElementById('mostrarCeros').checked;

    if (!fechaInicio || !fechaFin) {
        Swal.fire('Error', 'Debe seleccionar las fechas del período', 'error');
        return;
    }

    if (fechaInicio > fechaFin) {
        Swal.fire('Error', 'La fecha de inicio debe ser menor a la fecha fin', 'error');
        return;
    }

    // Mostrar loading
    document.getElementById('loading').style.display = 'block';
    document.getElementById('resultadoEstado').style.display = 'none';
    document.getElementById('btnExportar').style.display = 'none';

    // Realizar petición AJAX
    fetch('{{ route("estado-resultados.generar") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin,
            nivel_detalle: parseInt(nivelDetalle),
            mostrar_ceros: mostrarCeros
        })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('loading').style.display = 'none';
        
        if (data.success) {
            mostrarEstado(data.estado_resultados, data.periodo);
            document.getElementById('btnExportar').style.display = 'inline-block';
        } else {
            Swal.fire('Error', data.message || 'Error al generar el estado de resultados', 'error');
        }
    })
    .catch(error => {
        document.getElementById('loading').style.display = 'none';
        console.error('Error:', error);
        Swal.fire('Error', 'Error de conexión', 'error');
    });
}

function mostrarEstado(estado, periodo) {
    // Actualizar título
    document.getElementById('periodoTitle').textContent = periodo;

    // Limpiar tablas
    document.getElementById('tablaIngresosOp').innerHTML = '';
    document.getElementById('tablaCostos').innerHTML = '';
    document.getElementById('tablaGastosOp').innerHTML = '';
    document.getElementById('tablaIngresosNoOp').innerHTML = '';
    document.getElementById('tablaGastosNoOp').innerHTML = '';

    // Llenar ingresos operacionales
    estado.ingresos_operacionales.forEach(cuenta => {
        document.getElementById('tablaIngresosOp').innerHTML += crearFilaCuenta(cuenta);
    });

    // Llenar costos
    estado.costos_ventas.forEach(cuenta => {
        document.getElementById('tablaCostos').innerHTML += crearFilaCuenta(cuenta);
    });

    // Llenar gastos operacionales
    estado.gastos_operacionales.forEach(cuenta => {
        document.getElementById('tablaGastosOp').innerHTML += crearFilaCuenta(cuenta);
    });

    // Llenar ingresos no operacionales
    if (estado.ingresos_no_operacionales.length > 0) {
        document.getElementById('seccionIngresosNoOp').style.display = 'block';
        estado.ingresos_no_operacionales.forEach(cuenta => {
            document.getElementById('tablaIngresosNoOp').innerHTML += crearFilaCuenta(cuenta);
        });
    }

    // Llenar gastos no operacionales
    if (estado.gastos_no_operacionales.length > 0) {
        document.getElementById('seccionGastosNoOp').style.display = 'block';
        estado.gastos_no_operacionales.forEach(cuenta => {
            document.getElementById('tablaGastosNoOp').innerHTML += crearFilaCuenta(cuenta);
        });
    }

    // Actualizar totales
    const totales = estado.totales;
    document.getElementById('totalIngresosOp').textContent = '$' + totales.total_ingresos_operacionales_formateado;
    document.getElementById('totalCostos').textContent = '$' + totales.total_costos_ventas_formateado;
    document.getElementById('utilidadBruta').textContent = '$' + totales.utilidad_bruta_formateado;
    document.getElementById('totalGastosOp').textContent = '$' + totales.total_gastos_operacionales_formateado;
    document.getElementById('utilidadOperacional').textContent = '$' + totales.utilidad_operacional_formateado;
    document.getElementById('totalIngresosNoOp').textContent = '$' + totales.total_ingresos_no_operacionales_formateado;
    document.getElementById('totalGastosNoOp').textContent = '$' + totales.total_gastos_no_operacionales_formateado;
    document.getElementById('utilidadNeta').textContent = '$' + totales.utilidad_neta_formateado;

    // Calcular y mostrar márgenes
    calcularMargenes(totales);

    // Mostrar resultado
    document.getElementById('resultadoEstado').style.display = 'block';
}

function crearFilaCuenta(cuenta) {
    const indentacion = '&nbsp;'.repeat((cuenta.nivel - 1) * 4);
    const clase = cuenta.nivel <= 2 ? 'fw-bold' : '';
    
    return `
        <tr class="${clase}">
            <td>${indentacion}${cuenta.codigo} - ${cuenta.nombre}</td>
            <td class="text-end">$${cuenta.saldo_formateado}</td>
        </tr>
    `;
}

function calcularMargenes(totales) {
    const ingresos = totales.total_ingresos_operacionales;
    
    if (ingresos > 0) {
        const margenBruto = ((totales.utilidad_bruta / ingresos) * 100).toFixed(2);
        const margenOperacional = ((totales.utilidad_operacional / ingresos) * 100).toFixed(2);
        const margenNeto = ((totales.utilidad_neta / ingresos) * 100).toFixed(2);

        document.getElementById('margenBruto').textContent = margenBruto + '%';
        document.getElementById('margenOperacional').textContent = margenOperacional + '%';
        document.getElementById('margenNeto').textContent = margenNeto + '%';
    }
}

function exportarPdf() {
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;
    const nivelDetalle = document.getElementById('nivelDetalle').value;
    const mostrarCeros = document.getElementById('mostrarCeros').checked;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("estado-resultados.pdf") }}';
    form.target = '_blank';

    // Token CSRF
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    form.appendChild(csrfToken);

    // Parámetros
    const params = {
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin,
        nivel_detalle: nivelDetalle,
        mostrar_ceros: mostrarCeros
    };

    Object.keys(params).forEach(key => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = params[key];
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
</script>
@endpush
