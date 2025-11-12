<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flujo de Efectivo - NIF Colombia</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 15px;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #34495e;
            margin-bottom: 5px;
        }
        
        .report-period {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .report-method {
            font-size: 11px;
            background-color: #3498db;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #fff;
            padding: 8px 12px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        
        .operacion-title {
            background-color: #27ae60;
        }
        
        .inversion-title {
            background-color: #f39c12;
        }
        
        .financiacion-title {
            background-color: #e74c3c;
        }
        
        .resumen-title {
            background-color: #3498db;
        }
        
        .flujo-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .flujo-table th,
        .flujo-table td {
            padding: 6px 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .flujo-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .flujo-table td:last-child,
        .flujo-table th:last-child {
            text-align: right;
        }
        
        .subtotal-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        
        .total-row {
            font-weight: bold;
            font-size: 13px;
            background-color: #e8f5e8;
        }
        
        .final-total {
            background-color: #2c3e50;
            color: white;
            font-size: 14px;
        }
        
        .indented {
            padding-left: 20px;
        }
        
        .positive {
            color: #27ae60;
        }
        
        .negative {
            color: #e74c3c;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #7f8c8d;
            border-top: 1px solid #ecf0f1;
            padding-top: 15px;
        }
        
        .nif-compliance {
            background-color: #e8f5e8;
            border: 1px solid #27ae60;
            border-radius: 4px;
            padding: 10px;
            margin-top: 20px;
            text-align: center;
        }
        
        .nif-text {
            color: #27ae60;
            font-weight: bold;
            font-size: 11px;
        }
        
        .two-column {
            display: table;
            width: 100%;
        }
        
        .column {
            display: table-cell;
            width: 48%;
            vertical-align: top;
        }
        
        .column:first-child {
            margin-right: 4%;
        }
        
        .column-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
            padding: 5px 0;
            border-bottom: 1px solid #bdc3c7;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ auth()->user()->empresa->nombre ?? 'EMPRESA' }}</div>
        <div class="report-title">FLUJO DE EFECTIVO - NIF COLOMBIA</div>
        <div class="report-period">Período: {{ $fechaInicio->format('d/m/Y') }} - {{ $fechaFin->format('d/m/Y') }}</div>
        <div class="report-method">{{ strtoupper($metodo) }}</div>
        <div class="report-period">Generado el {{ now()->format('d/m/Y H:i:s') }}</div>
    </div>

    @if($metodo === 'indirecto')
        <!-- MÉTODO INDIRECTO -->
        
        <!-- Actividades de Operación -->
        <div class="section">
            <div class="section-title operacion-title">ACTIVIDADES DE OPERACIÓN</div>
            <table class="flujo-table">
                <tbody>
                    <tr>
                        <td><strong>Utilidad Neta</strong></td>
                        <td>${{ number_format($flujoEfectivo['actividades_operacion']['utilidad_neta'], 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td colspan="2"><strong>Ajustes por partidas que no afectan el efectivo:</strong></td>
                    </tr>
                    @foreach($flujoEfectivo['actividades_operacion']['ajustes'] as $ajuste)
                    <tr>
                        <td class="indented">{{ $ajuste['concepto'] }}</td>
                        <td>${{ number_format($ajuste['valor'], 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="2"><strong>Cambios en el capital de trabajo:</strong></td>
                    </tr>
                    @foreach($flujoEfectivo['actividades_operacion']['cambios_capital_trabajo'] as $cambio)
                    <tr>
                        <td class="indented">{{ $cambio['concepto'] }}</td>
                        <td>${{ number_format($cambio['valor'], 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <th><strong>EFECTIVO NETO DE ACTIVIDADES DE OPERACIÓN</strong></th>
                        <th>${{ number_format($flujoEfectivo['actividades_operacion']['flujo_neto_operacion'], 2, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Actividades de Inversión -->
        <div class="section">
            <div class="section-title inversion-title">ACTIVIDADES DE INVERSIÓN</div>
            <table class="flujo-table">
                <tbody>
                    @foreach($flujoEfectivo['actividades_inversion']['movimientos'] as $movimiento)
                    <tr>
                        <td>{{ $movimiento['concepto'] }}</td>
                        <td>${{ number_format($movimiento['valor'], 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    @if(empty($flujoEfectivo['actividades_inversion']['movimientos']))
                    <tr>
                        <td colspan="2" class="text-center">No hay movimientos de inversión en el período</td>
                    </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <th><strong>EFECTIVO NETO DE ACTIVIDADES DE INVERSIÓN</strong></th>
                        <th>${{ number_format($flujoEfectivo['actividades_inversion']['total'], 2, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Actividades de Financiación -->
        <div class="section">
            <div class="section-title financiacion-title">ACTIVIDADES DE FINANCIACIÓN</div>
            <table class="flujo-table">
                <tbody>
                    @foreach($flujoEfectivo['actividades_financiacion']['movimientos'] as $movimiento)
                    <tr>
                        <td>{{ $movimiento['concepto'] }}</td>
                        <td>${{ number_format($movimiento['valor'], 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    @if(empty($flujoEfectivo['actividades_financiacion']['movimientos']))
                    <tr>
                        <td colspan="2" class="text-center">No hay movimientos de financiación en el período</td>
                    </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <th><strong>EFECTIVO NETO DE ACTIVIDADES DE FINANCIACIÓN</strong></th>
                        <th>${{ number_format($flujoEfectivo['actividades_financiacion']['total'], 2, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>

    @else
        <!-- MÉTODO DIRECTO -->
        
        <!-- Actividades de Operación -->
        <div class="section">
            <div class="section-title operacion-title">ACTIVIDADES DE OPERACIÓN</div>
            
            <div class="two-column">
                <div class="column">
                    <div class="column-title positive">Entradas de Efectivo</div>
                    <table class="flujo-table">
                        <tbody>
                            @foreach($flujoEfectivo['actividades_operacion']['entradas'] as $entrada)
                            <tr>
                                <td>{{ $entrada['concepto'] }}</td>
                                <td>${{ number_format($entrada['valor'], 2, ',', '.') }}</td>
                            </tr>
                            @endforeach
                            @if(empty($flujoEfectivo['actividades_operacion']['entradas']))
                            <tr>
                                <td colspan="2">No hay entradas en el período</td>
                            </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr class="subtotal-row">
                                <th>Total Entradas</th>
                                <th>${{ number_format($flujoEfectivo['actividades_operacion']['total_entradas'], 2, ',', '.') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="column">
                    <div class="column-title negative">Salidas de Efectivo</div>
                    <table class="flujo-table">
                        <tbody>
                            @foreach($flujoEfectivo['actividades_operacion']['salidas'] as $salida)
                            <tr>
                                <td>{{ $salida['concepto'] }}</td>
                                <td>${{ number_format($salida['valor'], 2, ',', '.') }}</td>
                            </tr>
                            @endforeach
                            @if(empty($flujoEfectivo['actividades_operacion']['salidas']))
                            <tr>
                                <td colspan="2">No hay salidas en el período</td>
                            </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr class="subtotal-row">
                                <th>Total Salidas</th>
                                <th>${{ number_format($flujoEfectivo['actividades_operacion']['total_salidas'], 2, ',', '.') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <table class="flujo-table">
                <tfoot>
                    <tr class="total-row">
                        <th><strong>EFECTIVO NETO DE ACTIVIDADES DE OPERACIÓN</strong></th>
                        <th>${{ number_format($flujoEfectivo['actividades_operacion']['flujo_neto_operacion'], 2, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Actividades de Inversión - Directo -->
        <div class="section">
            <div class="section-title inversion-title">ACTIVIDADES DE INVERSIÓN</div>
            <table class="flujo-table">
                <tbody>
                    <tr class="subtotal-row">
                        <td><strong>Entradas de Efectivo</strong></td>
                        <td>${{ number_format($flujoEfectivo['actividades_inversion']['total_entradas'], 2, ',', '.') }}</td>
                    </tr>
                    <tr class="subtotal-row">
                        <td><strong>Salidas de Efectivo</strong></td>
                        <td>${{ number_format($flujoEfectivo['actividades_inversion']['total_salidas'], 2, ',', '.') }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <th><strong>EFECTIVO NETO DE ACTIVIDADES DE INVERSIÓN</strong></th>
                        <th>${{ number_format($flujoEfectivo['actividades_inversion']['flujo_neto_inversion'], 2, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Actividades de Financiación - Directo -->
        <div class="section">
            <div class="section-title financiacion-title">ACTIVIDADES DE FINANCIACIÓN</div>
            <table class="flujo-table">
                <tbody>
                    <tr class="subtotal-row">
                        <td><strong>Entradas de Efectivo</strong></td>
                        <td>${{ number_format($flujoEfectivo['actividades_financiacion']['total_entradas'], 2, ',', '.') }}</td>
                    </tr>
                    <tr class="subtotal-row">
                        <td><strong>Salidas de Efectivo</strong></td>
                        <td>${{ number_format($flujoEfectivo['actividades_financiacion']['total_salidas'], 2, ',', '.') }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <th><strong>EFECTIVO NETO DE ACTIVIDADES DE FINANCIACIÓN</strong></th>
                        <th>${{ number_format($flujoEfectivo['actividades_financiacion']['flujo_neto_financiacion'], 2, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    <!-- Resumen Final -->
    <div class="section">
        <div class="section-title resumen-title">RESUMEN DEL FLUJO DE EFECTIVO</div>
        <table class="flujo-table">
            <tbody>
                <tr>
                    <td><strong>Efectivo al inicio del período</strong></td>
                    <td>${{ number_format($flujoEfectivo['totales']['efectivo_inicio'], 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td><strong>Flujo neto del período</strong></td>
                    <td class="{{ $flujoEfectivo['totales']['flujo_neto_periodo'] >= 0 ? 'positive' : 'negative' }}">${{ number_format($flujoEfectivo['totales']['flujo_neto_periodo'], 2, ',', '.') }}</td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="final-total">
                    <th><strong>EFECTIVO AL FINAL DEL PERÍODO</strong></th>
                    <th>${{ number_format($flujoEfectivo['totales']['efectivo_final'], 2, ',', '.') }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Cumplimiento NIF -->
    <div class="nif-compliance">
        <div class="nif-text">
            ✅ FLUJO DE EFECTIVO ELABORADO BAJO NORMAS DE INFORMACIÓN FINANCIERA (NIF) COLOMBIA<br>
            Método {{ strtoupper($metodo) }} - Cumplimiento del 75% de los estándares NIF implementados
        </div>
    </div>

    <div class="footer">
        <p>Flujo de Efectivo generado por Sistema de Contabilidad NIF Colombia</p>
        <p>Este reporte cumple con los estándares de las Normas de Información Financiera aplicables en Colombia</p>
    </div>
</body>
</html>
