@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-balance-scale"></i> Balance General - NIF Colombia
                    </h4>
                    <div>
                        <button type="button" class="btn btn-primary" onclick="generarBalance()">
                            <i class="fas fa-calculator"></i> Generar Balance
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
                            <label class="form-label">Fecha de Corte</label>
                            <input type="date" class="form-control" id="fechaCorte" value="{{ date('Y-m-d') }}">
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
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-info me-2" onclick="mostrarComparativo()">
                                <i class="fas fa-chart-line"></i> Comparativo
                            </button>
                        </div>
                    </div>

                    <!-- Loading -->
                    <div id="loading" style="display: none;" class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Generando balance...</span>
                        </div>
                        <p class="mt-2">Generando Balance General...</p>
                    </div>

                    <!-- Resultado del Balance -->
                    <div id="resultadoBalance" style="display: none;">
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="text-center mb-4">
                                    <strong>BALANCE GENERAL</strong><br>
                                    <span id="fechaBalanceTitle"></span>
                                </h5>
                            </div>
                        </div>

                        <div class="row">
                            <!-- ACTIVOS -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-plus-circle"></i> ACTIVOS</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <tbody id="tablaActivos">
                                                    <!-- Se llena dinámicamente -->
                                                </tbody>
                                                <tfoot class="table-success">
                                                    <tr>
                                                        <th>TOTAL ACTIVOS</th>
                                                        <th class="text-end" id="totalActivos">$0</th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PASIVOS Y PATRIMONIO -->
                            <div class="col-md-6">
                                <!-- PASIVOS -->
                                <div class="card mb-3">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0"><i class="fas fa-minus-circle"></i> PASIVOS</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <tbody id="tablaPasivos">
                                                    <!-- Se llena dinámicamente -->
                                                </tbody>
                                                <tfoot class="table-danger">
                                                    <tr>
                                                        <th>TOTAL PASIVOS</th>
                                                        <th class="text-end" id="totalPasivos">$0</th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- PATRIMONIO -->
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="fas fa-building"></i> PATRIMONIO</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <tbody id="tablaPatrimonio">
                                                    <!-- Se llena dinámicamente -->
                                                </tbody>
                                                <tfoot class="table-primary">
                                                    <tr>
                                                        <th>TOTAL PATRIMONIO</th>
                                                        <th class="text-end" id="totalPatrimonio">$0</th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- TOTAL PASIVO + PATRIMONIO -->
                                <div class="card mt-3">
                                    <div class="card-body bg-light">
                                        <div class="row">
                                            <div class="col-8">
                                                <strong>TOTAL PASIVO + PATRIMONIO</strong>
                                            </div>
                                            <div class="col-4 text-end">
                                                <strong id="totalPasivoPatrimonio">$0</strong>
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

<!-- Modal para detalle de cuenta -->
<div class="modal fade" id="modalDetalleCuenta" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de Cuenta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detalleContent">
                    <!-- Se llena dinámicamente -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function generarBalance() {
    console.log('Iniciando generación de balance...');
    
    const fechaCorte = document.getElementById('fechaCorte').value;
    const nivelDetalle = document.getElementById('nivelDetalle').value;
    const mostrarCeros = document.getElementById('mostrarCeros').checked;

    console.log('Parámetros:', { fechaCorte, nivelDetalle, mostrarCeros });

    if (!fechaCorte) {
        alert('Debe seleccionar una fecha de corte');
        return;
    }

    // Mostrar loading
    document.getElementById('loading').style.display = 'block';
    document.getElementById('resultadoBalance').style.display = 'none';
    document.getElementById('btnExportar').style.display = 'none';

    console.log('Enviando petición a:', '{{ route("balance-general.generar") }}');

    // Crear FormData en lugar de JSON
    const formData = new FormData();
    formData.append('fecha_corte', fechaCorte);
    formData.append('nivel_detalle', nivelDetalle);
    formData.append('mostrar_ceros', mostrarCeros ? '1' : '0');
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

    // Realizar petición AJAX
    fetch('{{ route("balance-general.generar") }}', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Status:', response.status);
        console.log('Response OK:', response.ok);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.text(); // Primero como texto para ver qué llega
    })
    .then(text => {
        console.log('Respuesta como texto:', text);
        
        try {
            const data = JSON.parse(text);
            console.log('Datos parseados:', data);
            
            document.getElementById('loading').style.display = 'none';
            
            if (data.success) {
                mostrarBalance(data.balance, data.fecha_corte);
                document.getElementById('btnExportar').style.display = 'inline-block';
            } else {
                alert('Error: ' + (data.message || 'Error al generar el balance'));
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

function mostrarBalance(balance, fechaCorte) {
    console.log('Mostrando balance:', balance);
    
    // Actualizar título
    document.getElementById('fechaBalanceTitle').textContent = `Al ${fechaCorte}`;

    // Limpiar tablas
    document.getElementById('tablaActivos').innerHTML = '';
    document.getElementById('tablaPasivos').innerHTML = '';
    document.getElementById('tablaPatrimonio').innerHTML = '';

    // Llenar activos
    console.log('Activos:', balance.activos);
    const tablaActivos = document.getElementById('tablaActivos');
    console.log('Elemento tablaActivos:', tablaActivos);
    
    balance.activos.forEach((cuenta, index) => {
        console.log(`Procesando activo ${index}:`, cuenta);
        const fila = crearFilaCuenta(cuenta);
        console.log('Fila creada:', fila);
        tablaActivos.innerHTML += fila;
    });

    // Llenar pasivos
    console.log('Pasivos:', balance.pasivos);
    balance.pasivos.forEach(cuenta => {
        document.getElementById('tablaPasivos').innerHTML += crearFilaCuenta(cuenta);
    });

    // Llenar patrimonio
    console.log('Patrimonio:', balance.patrimonio);
    const tablaPatrimonio = document.getElementById('tablaPatrimonio');
    console.log('Elemento tablaPatrimonio:', tablaPatrimonio);
    
    balance.patrimonio.forEach((cuenta, index) => {
        console.log(`Procesando patrimonio ${index}:`, cuenta);
        const fila = crearFilaCuenta(cuenta);
        console.log('Fila patrimonio creada:', fila);
        tablaPatrimonio.innerHTML += fila;
    });

    // Actualizar totales
    console.log('Totales:', balance.totales);
    document.getElementById('totalActivos').textContent = '$' + (balance.totales.total_activos_formateado || '0');
    document.getElementById('totalPasivos').textContent = '$' + (balance.totales.total_pasivos_formateado || '0');
    document.getElementById('totalPatrimonio').textContent = '$' + (balance.totales.total_patrimonio_formateado || '0');
    document.getElementById('totalPasivoPatrimonio').textContent = '$' + (balance.totales.total_pasivo_patrimonio_formateado || '0');

    // Mostrar resultado
    document.getElementById('resultadoBalance').style.display = 'block';
    
    console.log('Balance mostrado correctamente');
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

function exportarPdf() {
    const fechaCorte = document.getElementById('fechaCorte').value;
    const nivelDetalle = document.getElementById('nivelDetalle').value;
    const mostrarCeros = document.getElementById('mostrarCeros').checked;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("balance-general.pdf") }}';
    form.target = '_blank';

    // Token CSRF
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    form.appendChild(csrfToken);

    // Parámetros
    const params = {
        fecha_corte: fechaCorte,
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

function mostrarComparativo() {
    Swal.fire({
        title: 'Comparativo de Balances',
        html: `
            <div class="row">
                <div class="col-6">
                    <label>Fecha Inicial:</label>
                    <input type="date" class="form-control" id="fechaInicialComp">
                </div>
                <div class="col-6">
                    <label>Fecha Final:</label>
                    <input type="date" class="form-control" id="fechaFinalComp">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <label>Periodicidad:</label>
                    <select class="form-select" id="periodicidadComp">
                        <option value="mensual">Mensual</option>
                        <option value="trimestral">Trimestral</option>
                        <option value="anual">Anual</option>
                    </select>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Generar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const fechaInicial = document.getElementById('fechaInicialComp').value;
            const fechaFinal = document.getElementById('fechaFinalComp').value;
            const periodicidad = document.getElementById('periodicidadComp').value;

            if (!fechaInicial || !fechaFinal) {
                Swal.showValidationMessage('Debe seleccionar ambas fechas');
                return false;
            }

            return { fechaInicial, fechaFinal, periodicidad };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Aquí implementarías la lógica del comparativo
            Swal.fire('Info', 'Funcionalidad de comparativo en desarrollo', 'info');
        }
    });
}

// Cargar balance al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Opcional: cargar balance automáticamente
    // generarBalance();
});
</script>
@endpush
