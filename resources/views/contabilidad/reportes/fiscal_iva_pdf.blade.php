<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Fiscal IVA</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 14px;
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .text-right {
            text-align: right;
        }
        .section-title {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .summary-box {
            border: 1px solid #ddd;
            padding: 10px;
            margin-top: 20px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .total-row {
            font-weight: bold;
            background-color: #f2f2f2;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>REPORTE FISCAL DE IVA</h1>
            <p>Período: {{ date('d/m/Y', strtotime($fechaInicio)) }} - {{ date('d/m/Y', strtotime($fechaFin)) }}</p>
        </div>

        <!-- Resumen de Ventas -->
        <div class="section-title">
            <h3>Detalle de Ventas con IVA</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Factura</th>
                    <th>Cliente</th>
                    <th>Documento</th>
                    <th class="text-right">Base</th>
                    <th class="text-right">IVA</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resumenVentas['gravadas'] as $venta)
                <tr>
                    <td>{{ date('d/m/Y', strtotime($venta['fecha'])) }}</td>
                    <td>{{ $venta['numero'] }}</td>
                    <td>{{ $venta['cliente'] }}</td>
                    <td>{{ $venta['documento'] }}</td>
                    <td class="text-right">$ {{ number_format($venta['subtotal'], 2) }}</td>
                    <td class="text-right">$ {{ number_format($venta['iva'], 2) }}</td>
                    <td class="text-right">$ {{ number_format($venta['total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="total-row">
                <tr>
                    <th colspan="4">TOTALES</th>
                    <th class="text-right">$ {{ number_format($resumenVentas['totales']['subtotal'], 2) }}</th>
                    <th class="text-right">$ {{ number_format($resumenVentas['totales']['iva'], 2) }}</th>
                    <th class="text-right">$ {{ number_format($resumenVentas['totales']['total'], 2) }}</th>
                </tr>
            </tfoot>
        </table>

        <!-- Resumen de Compras -->
        <div class="section-title">
            <h3>Detalle de Compras con IVA</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Factura</th>
                    <th>Proveedor</th>
                    <th>NIT</th>
                    <th class="text-right">Base</th>
                    <th class="text-right">IVA</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resumenCompras['gravadas'] as $compra)
                <tr>
                    <td>{{ date('d/m/Y', strtotime($compra['fecha'])) }}</td>
                    <td>{{ $compra['numero'] }}</td>
                    <td>{{ $compra['proveedor'] }}</td>
                    <td>{{ $compra['nit'] }}</td>
                    <td class="text-right">$ {{ number_format($compra['subtotal'], 2) }}</td>
                    <td class="text-right">$ {{ number_format($compra['iva'], 2) }}</td>
                    <td class="text-right">$ {{ number_format($compra['total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="total-row">
                <tr>
                    <th colspan="4">TOTALES</th>
                    <th class="text-right">$ {{ number_format($resumenCompras['totales']['subtotal'], 2) }}</th>
                    <th class="text-right">$ {{ number_format($resumenCompras['totales']['iva'], 2) }}</th>
                    <th class="text-right">$ {{ number_format($resumenCompras['totales']['total'], 2) }}</th>
                </tr>
            </tfoot>
        </table>

        <!-- Resumen Fiscal -->
        <div class="section-title">
            <h3>Resumen Fiscal</h3>
        </div>
        <div class="summary-box">
            <div class="summary-item">
                <span>IVA Generado en Ventas:</span>
                <span>$ {{ number_format($resumenVentas['totales']['iva'], 2) }}</span>
            </div>
            <div class="summary-item">
                <span>IVA Descontable en Compras:</span>
                <span>$ {{ number_format($resumenCompras['totales']['iva'], 2) }}</span>
            </div>
            <hr>
            @if($saldoPagar > 0)
            <div class="summary-item" style="font-weight: bold;">
                <span>SALDO A PAGAR:</span>
                <span>$ {{ number_format($saldoPagar, 2) }}</span>
            </div>
            @else
            <div class="summary-item" style="font-weight: bold;">
                <span>SALDO A FAVOR:</span>
                <span>$ {{ number_format(abs($saldoPagar), 2) }}</span>
            </div>
            @endif
        </div>
    </div>
</body>
</html>
