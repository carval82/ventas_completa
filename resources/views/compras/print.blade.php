<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Compra #{{ $compra->numero_factura }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            margin: 0;
            padding: 5mm;
        }
        .ticket {
            width: 80mm;
            margin: 0 auto;
            padding: 8mm;
        }
        .header {
            text-align: center;
            margin-bottom: 5mm;
        }
        .info p {
            margin: 2mm 0;
        }
        .table {
            width: 100%;
            margin: 5mm 0;
            border-collapse: collapse;
        }
        .table th, .table td {
            padding: 2mm;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .divider {
            border-bottom: 1px dashed #000;
            margin: 3mm 0;
        }
        .footer {
            text-align: center;
            margin-top: 5mm;
            font-size: 12px;
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print">
        <button onclick="window.print()">Imprimir</button>
        <button onclick="window.close()">Cerrar</button>
    </div>

    <div class="ticket">
        <div class="header">
            <h2>{{ $empresa->nombre_comercial }}</h2>
            <p>{{ $empresa->razon_social }}</p>
            <p>NIT: {{ $empresa->nit }}</p>
            <p>{{ $empresa->direccion }}</p>
            <p>Tel: {{ $empresa->telefono }}</p>
        </div>

        <div class="divider"></div>

        <div class="info">
            <p>Factura No: {{ $compra->numero_factura }}</p>
            <p>Fecha: {{ $compra->fecha_compra->format('d/m/Y h:i A') }}</p>
            <p>Proveedor: {{ $compra->proveedor->razon_social }}</p>
            <p>NIT: {{ $compra->proveedor->nit }}</p>
        </div>

        <div class="divider"></div>

        <table class="table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="text-center">Cant</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($compra->detalles as $detalle)
                <tr>
                    <td>{{ $detalle->producto->nombre }}</td>
                    <td class="text-center">{{ $detalle->cantidad }}</td>
                    <td class="text-right">${{ number_format($detalle->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="divider"></div>

        <div class="text-right">
            <p>Subtotal: ${{ number_format($compra->subtotal, 2) }}</p>
            <p>IVA: ${{ number_format($compra->iva, 2) }}</p>
            <p><strong>Total: ${{ number_format($compra->total, 2) }}</strong></p>
        </div>

        <div class="divider"></div>

        <div class="footer">
            <p>COMPROBANTE DE COMPRA</p>
            <p>{{ $empresa->nombre_comercial }}</p>
            <p>{{ now()->format('d/m/Y h:i A') }}</p>
        </div>
    </div>
</body>
</html>