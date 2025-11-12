<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #{{ $venta->getNumeroFacturaMostrar() }}</title>
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
            margin: 0 0 1mm 0;
            font-size: 18px;
            font-weight: bold;
        }
        .header p {
            margin: 0.5mm 0;
            line-height: 1.3;
            font-weight: bold;
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
                <img src="{{ asset('storage/' . $empresa->logo) }}" alt="Logo de la empresa" style="max-width: 200px; margin-bottom: 2mm;">
            @endif
            
            @if(isset($empresa))
                <h2>{{ $empresa->nombre_comercial }}</h2>
                <p style="font-size: 13px; font-weight: bold;">
                    @if($empresa->razon_social)
                        {{ $empresa->razon_social }}<br>
                    @endif
                    @if($empresa->nit)
                        NIT: {{ $empresa->nit }}<br>
                    @endif
                    @if($empresa->direccion)
                        {{ $empresa->direccion }}<br>
                    @endif
                    @if($empresa->telefono)
                        Tel: {{ $empresa->telefono }}
                        @if($empresa->email)
                            - Email: {{ $empresa->email }}<br>
                        @else
                            <br>
                        @endif
                    @elseif($empresa->email)
                        Email: {{ $empresa->email }}<br>
                    @endif
                    @if($empresa->regimen_tributario)
                        {{ ucfirst(str_replace('_', ' ', $empresa->regimen_tributario)) }}
                    @endif
                </p>
            @endif
        </div>

        <div class="divider"></div>

        <div class="info">
            <p><strong>Factura No: {{ $venta->getNumeroFacturaMostrar() }}</strong></p>
            @if($venta->esFacturaElectronica())
                <p><small>Factura Electrónica - Alegra ID: {{ $venta->alegra_id }}</small></p>
                @if($venta->cufe)
                    <p><small>CUFE: {{ substr($venta->cufe, 0, 20) }}...</small></p>
                @endif
                @if($venta->estado_dian)
                    <p><small>Estado DIAN: {{ ucfirst($venta->estado_dian) }}</small></p>
                @endif
            @endif
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
            <th class="text-right">PRECIO</th>
            <th class="text-right">TOTAL</th>
        </tr>
    </thead>
    <tbody>
        @foreach($venta->detalles as $detalle)
            @if($detalle->producto)
            <tr>
                <td>{{ $detalle->producto->codigo }}</td>
                <td>{{ $detalle->producto->nombre }}</td>
                <td class="text-center">{{ $detalle->cantidad }}</td>
                <td class="text-right">${{ number_format($detalle->precio_unitario, 2, ',', '.') }}</td>
                <td class="text-right">${{ number_format($detalle->subtotal, 2, ',', '.') }}</td>
            </tr>
            @endif
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

        @if($venta->qr_code || $venta->qr_local)
            <div class="text-center" style="margin: 5mm 0;">
                @if($venta->qr_code)
                    <p><small><strong>Código QR DIAN (Factura Electrónica)</strong></small></p>
                    <img src="data:image/png;base64,{{ $venta->qr_code }}" 
                         alt="QR DIAN" 
                         style="width: 40mm; height: 40mm; margin: 2mm auto;">
                    @if($venta->cufe)
                        <div style="font-family: monospace; font-size: 10px; font-weight: bold; word-break: break-all; max-width: 60mm; margin: 2mm auto;">
                            CUFE: {{ $venta->cufe }}
                        </div>
                    @endif
                @elseif($venta->qr_local)
                    <p><small><strong>Código QR de Verificación</strong></small></p>
                    <img src="data:image/png;base64,{{ $venta->qr_local }}" 
                         alt="QR Local" 
                         style="width: 40mm; height: 40mm; margin: 2mm auto;">
                    @if($venta->cufe_local)
                        <div style="font-family: monospace; font-size: 10px; font-weight: bold; word-break: break-all; max-width: 60mm; margin: 2mm auto;">
                            CUFE: {{ $venta->cufe_local }}
                        </div>
                    @endif
                @endif
            </div>
            <div class="divider"></div>
        @endif

        <div class="footer">
            <p>¡GRACIAS POR SU COMPRA!</p>
            @if($venta->esFacturaElectronica())
                <p><small>Factura Electrónica Válida ante la DIAN</small></p>
            @endif
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
