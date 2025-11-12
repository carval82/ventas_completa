<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #{{ $venta->getNumeroFacturaMostrar() }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 2mm;
        }
        .ticket {
            width: 58mm;
            margin: 0 auto;
            position: relative;
            padding: 3mm;
        }
        .header {
            text-align: center;
            margin-bottom: 3mm;
        }
        .header img {
            max-width: 45mm;
            height: auto;
            margin-bottom: 1mm;
        }
        .header h2 {
            margin: 0 0 0.5mm 0;
            font-size: 14px;
            font-weight: bold;
        }
        .header p {
            margin: 0;
            font-size: 10px;
            line-height: 1.3;
            font-weight: bold;
        }
        .info p {
            margin: 1mm 0;
            font-size: 9px;
        }
        .table {
            width: 100%;
            margin: 3mm 0;
            border-collapse: collapse;
            font-size: 8px;
        }
        .table th, .table td {
            padding: 1mm;
            text-align: left;
        }
        .table th {
            font-size: 8px;
            font-weight: bold;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .divider {
            border-bottom: 1px dashed #000;
            margin: 2mm 0;
        }
        .footer {
            text-align: center;
            margin-top: 3mm;
            font-size: 8px;
        }
        .totales {
            font-size: 9px;
        }
        .totales p {
            margin: 1mm 0;
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
                padding-bottom: 15mm;
            }
            .footer::after {
                content: '';
                display: block;
                height: 15mm;
                margin-bottom: 0;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="padding: 5mm; background: #f0f0f0; margin-bottom: 5mm;">
        <button onclick="window.print()" style="padding: 5px 10px; margin-right: 5px;">Imprimir</button>
        <button onclick="window.close()" style="padding: 5px 10px;">Cerrar</button>
    </div>

    <div class="ticket">
        <div class="header">
            @if(isset($empresa) && $empresa->logo)
                <img src="{{ asset('storage/' . $empresa->logo) }}" alt="Logo" style="max-width: 45mm; margin-bottom: 1mm;">
            @endif
            
            @if(isset($empresa))
                <h2>{{ $empresa->nombre_comercial }}</h2>
                <p style="font-weight: bold;">
                    @if($empresa->nit)NIT: {{ $empresa->nit }}<br>@endif
                    @if($empresa->direccion){{ $empresa->direccion }}<br>@endif
                    @if($empresa->telefono)Tel: {{ $empresa->telefono }}@endif
                </p>
            @endif
        </div>

        <div class="divider"></div>

        <div class="info">
            <p><strong>FACTURA: {{ $venta->getNumeroFacturaMostrar() }}</strong></p>
            @if($venta->esFacturaElectronica())
                <p style="font-size: 8px;">FACTURA ELECTRÓNICA</p>
                @if($venta->alegra_id)
                    <p style="font-size: 7px;">Alegra ID: {{ $venta->alegra_id }}</p>
                @endif
                @if($venta->estado_dian)
                    <p style="font-size: 7px;">DIAN: {{ ucfirst($venta->estado_dian) }}</p>
                @endif
            @endif
            <p>Fecha: {{ $venta->fecha_venta->format('d/m/Y h:i A') }}</p>
            @if($venta->cliente)
                <p>Cliente: {{ $venta->cliente->nombres }} {{ $venta->cliente->apellidos }}</p>
                @if($venta->cliente->cedula)
                    <p>CC: {{ $venta->cliente->cedula }}</p>
                @endif
            @endif
        </div>

        <div class="divider"></div>

        @if($venta->detalles && count($venta->detalles) > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>PRODUCTO</th>
                        <th class="text-center">CANT</th>
                        <th class="text-right">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($venta->detalles as $detalle)
                        @if($detalle->producto)
                        <tr>
                            <td colspan="3" style="font-weight: bold;">{{ $detalle->producto->nombre }}</td>
                        </tr>
                        <tr>
                            <td style="font-size: 7px; padding-left: 2mm;">P/U: ${{ number_format($detalle->precio_unitario, 0, ',', '.') }}</td>
                            <td class="text-center">{{ $detalle->cantidad }}</td>
                            <td class="text-right">${{ number_format($detalle->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
            
            <div class="divider"></div>

            <div class="text-right totales">
                <p>SUBTOTAL: ${{ number_format($venta->subtotal, 0, ',', '.') }}</p>
                @if($venta->iva > 0)
                    <p>IVA: ${{ number_format($venta->iva, 0, ',', '.') }}</p>
                @endif
                <p><strong style="font-size: 11px;">TOTAL: ${{ number_format($venta->total, 0, ',', '.') }}</strong></p>
            </div>
        @endif

        <div class="divider"></div>

        <div class="text-right totales">
            <p>EFECTIVO: ${{ number_format($venta->pago, 0, ',', '.') }}</p>
            <p><strong>CAMBIO: ${{ number_format($venta->devuelta, 0, ',', '.') }}</strong></p>
        </div>

        <div class="divider"></div>

        @if($venta->qr_code || $venta->qr_local)
            <div class="text-center" style="margin: 3mm 0;">
                @if($venta->qr_code)
                    <p style="font-size: 7px;"><strong>QR DIAN</strong></p>
                    <img src="data:image/png;base64,{{ $venta->qr_code }}" 
                         alt="QR DIAN" 
                         style="width: 30mm; height: 30mm;">
                    @if($venta->cufe)
                        <div style="font-family: monospace; font-size: 8px; font-weight: bold; word-break: break-all; max-width: 50mm; margin: 1mm auto;">
                            CUFE: {{ $venta->cufe }}
                        </div>
                    @endif
                @elseif($venta->qr_local)
                    <p style="font-size: 7px;"><strong>QR Verificación</strong></p>
                    <img src="data:image/png;base64,{{ $venta->qr_local }}" 
                         alt="QR Local" 
                         style="width: 30mm; height: 30mm;">
                    @if($venta->cufe_local)
                        <div style="font-family: monospace; font-size: 8px; font-weight: bold; word-break: break-all; max-width: 50mm; margin: 1mm auto;">
                            CUFE: {{ $venta->cufe_local }}
                        </div>
                    @endif
                @endif
            </div>
            <div class="divider"></div>
        @endif

        <div class="footer">
            <p><strong>¡GRACIAS POR SU COMPRA!</strong></p>
            @if($venta->esFacturaElectronica())
                <p style="font-size: 7px;">Factura Electrónica Válida ante DIAN</p>
            @endif
            @if(isset($empresa))
                <p>{{ $empresa->nombre_comercial }}</p>
                @if($empresa->sitio_web)
                    <p>{{ $empresa->sitio_web }}</p>
                @endif
            @endif
            <p style="font-size: 7px;">{{ now()->format('d/m/Y h:i A') }}</p>
            <p style="font-size: 6px; margin-top: 3mm;">FORMATO 58MM</p>
        </div>
    </div>
</body>
</html>
