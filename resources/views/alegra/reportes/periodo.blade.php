@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4>Reporte de Facturas por Período (Alegra)</h4>
                        <a href="{{ route('alegra.reportes.dashboard', ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'agrupamiento' => $agrupamiento]) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al Dashboard
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Filtros -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <form method="GET" action="{{ route('alegra.reportes.periodo') }}" class="row g-3">
                                        <div class="col-md-3">
                                            <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="{{ $fechaInicio }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="{{ $fechaFin }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="agrupamiento" class="form-label">Agrupamiento</label>
                                            <select class="form-control" id="agrupamiento" name="agrupamiento">
                                                <option value="diario" {{ $agrupamiento == 'diario' ? 'selected' : '' }}>Diario</option>
                                                <option value="semanal" {{ $agrupamiento == 'semanal' ? 'selected' : '' }}>Semanal</option>
                                                <option value="mensual" {{ $agrupamiento == 'mensual' ? 'selected' : '' }}>Mensual</option>
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

                    <!-- Acordeón de períodos -->
                    <div class="accordion" id="acordeonPeriodos">
                        @forelse($facturasPorPeriodo as $periodo => $datos)
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading{{ str_replace(['-', ' '], '_', $periodo) }}">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#collapse{{ str_replace(['-', ' '], '_', $periodo) }}" 
                                            aria-expanded="false" 
                                            aria-controls="collapse{{ str_replace(['-', ' '], '_', $periodo) }}">
                                        <strong>{{ $agrupamiento == 'diario' ? \Carbon\Carbon::parse($periodo)->format('d/m/Y') : 
                                                 ($agrupamiento == 'semanal' ? 'Semana ' . substr($periodo, 5) . ' de ' . substr($periodo, 0, 4) : 
                                                 \Carbon\Carbon::createFromFormat('Y-m', $periodo)->format('F Y')) }}</strong>
                                        <span class="ms-3 badge bg-primary">{{ $datos['cantidad'] }} facturas</span>
                                        <span class="ms-3 badge bg-success">${{ number_format($datos['total'], 2) }}</span>
                                    </button>
                                </h2>
                                <div id="collapse{{ str_replace(['-', ' '], '_', $periodo) }}" 
                                     class="accordion-collapse collapse" 
                                     aria-labelledby="heading{{ str_replace(['-', ' '], '_', $periodo) }}">
                                    <div class="accordion-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Número</th>
                                                        <th>Fecha</th>
                                                        <th>Cliente</th>
                                                        <th>Estado</th>
                                                        <th>Total</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($datos['facturas'] as $factura)
                                                        <tr>
                                                            <td>{{ $factura['id'] }}</td>
                                                            <td>{{ $factura['numberTemplate']['prefix'] ?? '' }}{{ $factura['number'] }}</td>
                                                            <td>{{ \Carbon\Carbon::parse($factura['date'])->format('d/m/Y') }}</td>
                                                            <td>{{ $factura['client']['name'] }}</td>
                                                            <td>
                                                                @if($factura['status'] == 'open')
                                                                    <span class="badge bg-success">Abierta</span>
                                                                @elseif($factura['status'] == 'closed')
                                                                    <span class="badge bg-primary">Cerrada</span>
                                                                @elseif($factura['status'] == 'voided')
                                                                    <span class="badge bg-danger">Anulada</span>
                                                                @else
                                                                    <span class="badge bg-secondary">{{ $factura['status'] }}</span>
                                                                @endif
                                                            </td>
                                                            <td>${{ number_format($factura['total'], 2) }}</td>
                                                            <td>
                                                                <a href="{{ route('alegra.facturas.show', $factura['id']) }}" class="btn btn-sm btn-info">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <th colspan="5" class="text-end">Total:</th>
                                                        <th>${{ number_format($datos['total'], 2) }}</th>
                                                        <th></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="alert alert-info">
                                No se encontraron facturas para el período seleccionado.
                            </div>
                        @endforelse
                    </div>

                    <!-- Botones de exportación -->
                    <div class="mt-4">
                        <button type="button" class="btn btn-success" onclick="exportarExcel()">
                            <i class="fas fa-file-excel"></i> Exportar a Excel
                        </button>
                        <button type="button" class="btn btn-danger" onclick="exportarPDF()">
                            <i class="fas fa-file-pdf"></i> Exportar a PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
    function exportarExcel() {
        const facturasPorPeriodo = @json($facturasPorPeriodo);
        const agrupamiento = @json($agrupamiento);
        const fechaInicio = @json($fechaInicio);
        const fechaFin = @json($fechaFin);
        
        // Crear un libro de trabajo
        const wb = XLSX.utils.book_new();
        
        // Para cada período, crear una hoja
        Object.entries(facturasPorPeriodo).forEach(([periodo, datos]) => {
            // Preparar los datos para la hoja
            const wsData = [
                ['Reporte de Facturas Electrónicas - Alegra'],
                [`Período: ${periodo}`, `Agrupamiento: ${agrupamiento}`, `Fecha Inicio: ${fechaInicio}`, `Fecha Fin: ${fechaFin}`],
                [],
                ['ID', 'Número', 'Fecha', 'Cliente', 'Estado', 'Total']
            ];
            
            // Agregar filas de facturas
            datos.facturas.forEach(factura => {
                wsData.push([
                    factura.id,
                    (factura.numberTemplate?.prefix || '') + factura.number,
                    new Date(factura.date).toLocaleDateString(),
                    factura.client.name,
                    factura.status,
                    factura.total
                ]);
            });
            
            // Agregar fila de total
            wsData.push(['', '', '', '', 'Total:', datos.total]);
            
            // Crear hoja y agregarla al libro
            const ws = XLSX.utils.aoa_to_sheet(wsData);
            XLSX.utils.book_append_sheet(wb, ws, periodo.substring(0, 10));
        });
        
        // Exportar el libro
        XLSX.writeFile(wb, `Reporte_Facturas_Alegra_${fechaInicio}_${fechaFin}.xlsx`);
    }
    
    function exportarPDF() {
        const { jsPDF } = window.jspdf;
        const facturasPorPeriodo = @json($facturasPorPeriodo);
        const agrupamiento = @json($agrupamiento);
        const fechaInicio = @json($fechaInicio);
        const fechaFin = @json($fechaFin);
        
        // Crear documento PDF
        const doc = new jsPDF();
        
        // Título del documento
        doc.setFontSize(18);
        doc.text('Reporte de Facturas Electrónicas - Alegra', 14, 22);
        
        // Información del reporte
        doc.setFontSize(12);
        doc.text(`Período: ${fechaInicio} a ${fechaFin}`, 14, 32);
        doc.text(`Agrupamiento: ${agrupamiento}`, 14, 38);
        
        let yPos = 50;
        
        // Para cada período, crear una tabla
        Object.entries(facturasPorPeriodo).forEach(([periodo, datos], index) => {
            // Si no es la primera tabla y no hay espacio suficiente, agregar nueva página
            if (index > 0 && yPos > 220) {
                doc.addPage();
                yPos = 20;
            }
            
            // Título del período
            doc.setFontSize(14);
            doc.text(`Período: ${periodo}`, 14, yPos);
            yPos += 10;
            
            // Crear tabla
            const tableColumn = ['ID', 'Número', 'Fecha', 'Cliente', 'Total'];
            const tableRows = [];
            
            // Agregar filas de facturas
            datos.facturas.forEach(factura => {
                const facturaData = [
                    factura.id,
                    (factura.numberTemplate?.prefix || '') + factura.number,
                    new Date(factura.date).toLocaleDateString(),
                    factura.client.name,
                    `$${factura.total.toFixed(2)}`
                ];
                tableRows.push(facturaData);
            });
            
            // Agregar tabla al documento
            doc.autoTable({
                head: [tableColumn],
                body: tableRows,
                startY: yPos,
                theme: 'striped',
                headStyles: { fillColor: [41, 128, 185] },
                foot: [['', '', '', 'Total:', `$${datos.total.toFixed(2)}`]],
                footStyles: { fillColor: [52, 152, 219] }
            });
            
            // Actualizar posición Y para la siguiente tabla
            yPos = doc.lastAutoTable.finalY + 20;
        });
        
        // Guardar el PDF
        doc.save(`Reporte_Facturas_Alegra_${fechaInicio}_${fechaFin}.pdf`);
    }
</script>
@endsection
