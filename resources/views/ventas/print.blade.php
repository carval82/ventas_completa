<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #{{ $venta->numero_factura }}</title>
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
            position: relative;
            padding: 8mm;
        }
        .header {
            text-align: center;
            margin-bottom: 5mm;
        }
        .header img {
            max-width: 60mm;
            height: auto;
            margin-bottom: 2mm;
        }
        .header h2 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
            padding: 2mm;
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
            text-align: left;
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
        @media print {
            @page {
                margin: 0;
                size: auto;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .no-print { 
                display: none; 
            }
            .ticket {
                margin: 0;
                padding-bottom: 20mm;
            }
            .footer::after {
                content: '';
                display: block;
                height: 20mm;
                margin-bottom: 0;
                page-break-after: auto;
            }
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
        @if(isset($empresa) && $empresa->logo)
        <img src="{{ asset('storage/' . $empresa->logo) }}" alt="Logo de la empresa" style="max-width: 200px;">
    @endif
            
            @if(isset($empresa))
                <h2>{{ $empresa->nombre_comercial }}</h2>
                @if($empresa->razon_social)
                    <p>{{ $empresa->razon_social }}</p>
                @endif
                @if($empresa->nit)
                    <p>NIT: {{ $empresa->nit }}</p>
                @endif
                @if($empresa->direccion)
                    <p>{{ $empresa->direccion }}</p>
                @endif
                @if($empresa->telefono)
                    <p>Tel: {{ $empresa->telefono }}</p>
                @endif
                @if($empresa->email)
                    <p>Email: {{ $empresa->email }}</p>
                @endif
                @if($empresa->regimen_tributario)
                    <p>Régimen: {{ ucfirst($empresa->regimen_tributario) }}</p>
                @endif
            @endif
        </div>

        <div class="divider"></div>

        <div class="info">
            <p>Factura No: {{ $venta->numero_factura }}</p>
            <p>Fecha: {{ $venta->fecha_venta->format('d/m/Y h:i A') }}</p>
            @if($venta->cliente)
                <p>Cliente: {{ $venta->cliente->nombres }} {{ $venta->cliente->apellidos }}</p>
                @if($venta->cliente->cedula)
                    <p>Identificación: {{ $venta->cliente->cedula }}</p>
                @endif
                @if($venta->cliente->telefono)
                    <p>Teléfono: {{ $venta->cliente->telefono }}</p>
                @endif
            @endif
        </div>

        <div class="divider"></div>

        @if($venta->detalles && count($venta->detalles) > 0)
        <table class="table">
    <thead>
        <tr>
            <th>CÓD</th>
            <th>PROD</th>
            <th class="text-center">CANT</th>
            <th class="text-right">VALOR</th>
        </tr>
    </thead>
    <tbody>
        @foreach($venta->detalles as $detalle)
            <tr>
                <td>{{ $detalle->producto->codigo ?? 'N/A' }}</td>
                <td>{{ $detalle->producto->nombre ?? 'Producto no disponible' }}</td>
                <td class="text-center">{{ $detalle->cantidad }}</td>
                <td class="text-right">${{ number_format($detalle->subtotal, 2, ',', '.') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
            <div class="divider"></div>

            <div class="text-right">
                <p>SUBTOTAL: ${{ number_format($venta->subtotal, 2, ',', '.') }}</p>
                <p>IVA: ${{ number_format($venta->iva, 2, ',', '.') }}</p>
                <p><strong>TOTAL: ${{ number_format($venta->total, 2, ',', '.') }}</strong></p>
            </div>
        @endif

        <div class="divider"></div>

        <div class="text-right">
            <p>EFECTIVO RECIBIDO: ${{ number_format($venta->pago, 2, ',', '.') }}</p>
            <p><strong>CAMBIO: ${{ number_format($venta->devuelta, 2, ',', '.') }}</strong></p>
        </div>

        <div class="divider"></div>

        <div class="footer">
            <p>¡GRACIAS POR SU COMPRA!</p>
            @if(isset($empresa))
                <p>{{ $empresa->nombre_comercial }}</p>
                @if($empresa->direccion && $empresa->telefono)
                    <p>{{ $empresa->direccion }} - Tel: {{ $empresa->telefono }}</p>
                @endif
                @if($empresa->sitio_web)
                    <p>{{ $empresa->sitio_web }}</p>
                @endif
            @endif
            <p>{{ now()->format('d/m/Y h:i A') }}</p>
        </div>
    </div>
</body>
</html>
