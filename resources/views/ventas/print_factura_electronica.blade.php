<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Electrónica #{{ $venta->getNumeroFacturaMostrar() }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #000;
            background: #fff;
        }
        
        .ticket {
            width: 80mm;
            margin: 0 auto;
            padding: 3mm;
        }
        
        /* Encabezado */
        .header {
            text-align: center;
            margin-bottom: 4mm;
            padding-bottom: 3mm;
            border-bottom: 1px dashed #333;
        }
        
        .header .logo {
            width: 20mm;
            height: 20mm;
            margin: 0 auto 2mm;
        }
        
        .header h1 {
            font-size: 13px;
            font-weight: bold;
            margin: 1mm 0;
            text-transform: uppercase;
        }
        
        .header p {
            font-size: 9px;
            margin: 0.5mm 0;
        }
        
        /* Información de factura */
        .invoice-header {
            text-align: center;
            margin: 3mm 0;
            padding: 2mm 0;
            border-top: 1px dashed #333;
            border-bottom: 1px dashed #333;
        }
        
        .invoice-header h2 {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 1mm;
        }
        
        .invoice-header p {
            font-size: 9px;
            margin: 0.5mm 0;
        }
        
        /* Información del cliente */
        .client-info {
            margin: 3mm 0;
            font-size: 9px;
        }
        
        .client-info p {
            margin: 1mm 0;
        }
        
        .client-info strong {
            font-weight: bold;
            display: inline-block;
            width: 20mm;
        }
        
        /* Tabla de productos */
        .products-table {
            width: 100%;
            margin: 3mm 0;
            border-collapse: collapse;
            font-size: 8px;
        }
        
        .products-table thead {
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
        }
        
        .products-table th {
            padding: 1.5mm 0.5mm;
            text-align: center;
            font-weight: bold;
            font-size: 8px;
        }
        
        .products-table td {
            padding: 1.5mm 0.5mm;
            vertical-align: top;
            text-align: center;
            border-bottom: 1px dotted #ccc;
        }
        
        .products-table td.text-left {
            text-align: left;
        }
        
        .products-table td.text-right {
            text-align: right;
        }
        
        /* Tabla de impuestos */
        .tax-section {
            margin: 2mm 0;
        }
        
        .tax-section h3 {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 1mm;
        }
        
        .tax-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        
        .tax-table thead {
            border-bottom: 1px solid #333;
        }
        
        .tax-table th {
            padding: 1mm 0.5mm;
            text-align: center;
            font-weight: bold;
        }
        
        .tax-table td {
            padding: 1mm 0.5mm;
            text-align: center;
        }
        
        .tax-table td.text-right {
            text-align: right;
        }
        
        /* Totales */
        .totals {
            margin: 3mm 0;
            font-size: 9px;
            border-top: 1px dashed #333;
            padding-top: 2mm;
        }
        
        .totals .row {
            display: flex;
            justify-content: space-between;
            margin: 1mm 0;
        }
        
        .totals .row.highlight {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #333;
            border-bottom: 2px solid #333;
            padding: 2mm 0;
            margin: 2mm 0;
        }
        
        .totals .label {
            text-align: left;
        }
        
        .totals .value {
            text-align: right;
            font-weight: bold;
        }
        
        /* Información de pago */
        .payment-info {
            margin: 3mm 0;
            font-size: 9px;
            border-top: 1px dashed #333;
            padding-top: 2mm;
        }
        
        .payment-info .row {
            display: flex;
            justify-content: space-between;
            margin: 1mm 0;
        }
        
        /* CUFE */
        .cufe-section {
            margin: 3mm 0;
            padding: 2mm 0;
            border-top: 1px dashed #333;
        }
        
        .cufe-section h3 {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 1mm;
            text-align: center;
        }
        
        .cufe-section .cufe-code {
            font-size: 7px;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            text-align: justify;
            line-height: 1.2;
        }
        
        /* Código QR */
        .qr-section {
            text-align: center;
            margin: 4mm 0;
            padding: 3mm 0;
            border-top: 1px dashed #333;
        }
        
        .qr-section .qr-code {
            width: 45mm;
            height: 45mm;
            margin: 2mm auto;
        }
        
        /* Texto legal */
        .legal-text {
            font-size: 7px;
            text-align: justify;
            line-height: 1.3;
            margin: 3mm 0;
            padding: 2mm 0;
            border-top: 1px dashed #333;
        }
        
        .legal-text p {
            margin: 1mm 0;
        }
        
        /* Proveedor tecnológico */
        .tech-provider {
            text-align: center;
            font-size: 7px;
            margin: 2mm 0;
            padding: 2mm 0;
            border-top: 1px dashed #333;
        }
        
        .tech-provider p {
            margin: 0.5mm 0;
        }
        
        @media print {
            @page {
                margin: 0;
                size: 80mm auto;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .no-print { 
                display: none; 
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="text-align: center; padding: 5mm; background: #f0f0f0;">
        <button onclick="window.print()" style="padding: 5mm; margin: 2mm; font-size: 12px;">Imprimir</button>
        <button onclick="window.close()" style="padding: 5mm; margin: 2mm; font-size: 12px;">Cerrar</button>
    </div>

    <div class="ticket">
        <!-- Encabezado con Logo y Datos de la Empresa -->
        <div class="header">
            @if(isset($empresa) && $empresa->logo)
                <img src="{{ asset('storage/' . $empresa->logo) }}" alt="Logo" class="logo">
            @else
                <div class="logo" style="border: 2px solid #333; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <span style="font-size: 12px; font-weight: bold;">LOGO</span>
                </div>
            @endif
            
            @if(isset($empresa))
                <h1>{{ strtoupper($empresa->nombre_comercial ?? $empresa->razon_social) }}</h1>
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
                    <p>{{ $empresa->email }}</p>
                @endif
                @if($empresa->regimen_tributario)
                    <p>{{ ucfirst(str_replace('_', ' ', $empresa->regimen_tributario)) }}</p>
                @endif
            @endif
        </div>

        <!-- Información de la Factura -->
        <div class="invoice-header">
            <h2>Factura electrónica de venta</h2>
            <p>No: {{ $venta->getNumeroFacturaMostrar() }}</p>
            <p>Fecha generación: {{ $venta->fecha_venta->format('d/m/Y h:i A') }}</p>
            @if($venta->fecha_vencimiento)
                <p>Fecha vencimiento: {{ $venta->fecha_vencimiento->format('d/m/Y h:i A') }}</p>
            @endif
        </div>

        <!-- Información del Cliente -->
        <div class="client-info">
            <p><strong>Cliente:</strong> {{ $venta->cliente ? ($venta->cliente->nombres . ' ' . $venta->cliente->apellidos) : 'CLIENTE GENÉRICO' }}</p>
            @if($venta->cliente && $venta->cliente->cedula)
                <p><strong>C.C / NIT:</strong> {{ $venta->cliente->cedula }}</p>
            @else
                <p><strong>C.C / NIT:</strong> 222222222222</p>
            @endif
            @if($venta->cliente && $venta->cliente->direccion)
                <p><strong>Dirección:</strong> {{ $venta->cliente->direccion }}</p>
            @endif
        </div>

        <!-- Tabla de Productos -->
        @if($venta->detalles && count($venta->detalles) > 0)
        <table class="products-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cant.</th>
                    <th class="text-left">Vr. Unit</th>
                    <th class="text-right">Valor</th>
                    <th>ID</th>
                </tr>
            </thead>
            <tbody>
                @foreach($venta->detalles as $index => $detalle)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ number_format($detalle->cantidad, 2) }}</td>
                    <td class="text-left">
                        {{ $detalle->producto ? $detalle->producto->nombre : 'Producto' }}<br>
                        <small>${{ number_format($detalle->precio, 2) }}</small>
                    </td>
                    <td class="text-right">${{ number_format($detalle->subtotal, 2) }}</td>
                    <td><small>{{ $detalle->producto ? $detalle->producto->id : '-' }}</small></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- Tabla de Impuestos -->
        @php
            $iva = $venta->impuesto ?? 0;
            $subtotal = $venta->total - $iva;
        @endphp
        
        @if($iva > 0)
        <div class="tax-section">
            <h3>Impuestos</h3>
            <table class="tax-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>%</th>
                        <th class="text-right">Base</th>
                        <th class="text-right">Impuesto</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>IVA</td>
                        <td>19%</td>
                        <td class="text-right">${{ number_format($subtotal, 2) }}</td>
                        <td class="text-right">${{ number_format($iva, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

        <!-- Totales -->
        <div class="totals">
            <div class="row">
                <span class="label">Total Bruto:</span>
                <span class="value">${{ number_format($venta->total, 2) }}</span>
            </div>
            <div class="row">
                <span class="label">Descuentos:</span>
                <span class="value">${{ number_format($venta->descuento ?? 0, 2) }}</span>
            </div>
            <div class="row">
                <span class="label">Subtotal:</span>
                <span class="value">${{ number_format($subtotal, 2) }}</span>
            </div>
            <div class="row">
                <span class="label">IVA:</span>
                <span class="value">${{ number_format($iva, 2) }}</span>
            </div>
            <div class="row">
                <span class="label">Total neto:</span>
                <span class="value">${{ number_format($venta->total, 2) }}</span>
            </div>
            <div class="row highlight">
                <span class="label">Total a pagar:</span>
                <span class="value">${{ number_format($venta->total, 2) }}</span>
            </div>
        </div>

        <!-- Información de Pago -->
        <div class="payment-info">
            <div class="row">
                <span>Forma de pago:</span>
                <span><strong>Contado</strong></span>
            </div>
            <div class="row">
                <span>Método de pago:</span>
                <span><strong>Efectivo</strong></span>
            </div>
            <div class="row">
                <span>Total recibido:</span>
                <span><strong>${{ number_format($venta->pago, 2) }}</strong></span>
            </div>
            <div class="row">
                <span>Cambio:</span>
                <span><strong>${{ number_format($venta->devuelta, 2) }}</strong></span>
            </div>
        </div>

        <!-- CUFE -->
        @if($venta->cufe)
        <div class="cufe-section">
            <h3>CUFE</h3>
            <div class="cufe-code">{{ $venta->cufe }}</div>
        </div>
        @endif

        <!-- Código QR -->
        @if($venta->qr_code && isset($venta->qr_code_image))
        <div class="qr-section">
            <div class="qr-code">
                {!! $venta->qr_code_image !!}
            </div>
        </div>
        @endif

        <!-- Texto Legal -->
        <div class="legal-text">
            <p>
                Al ser ésta</ una <strong>Factura Electrónica de Venta</strong>, no requiere firma autógrafa, 
                se asimila en todos sus efectos a la Ley 527 de 1999, el Decreto Reglamentario 1929 de 2007, 
                la resolución 14465 de 2007 de la DIAN y el Artículo 616-1 del Estatuto Tributario.
            </p>
            <p style="margin-top: 2mm;">
                <strong>Numeración Autorización</strong>: Resolución {{ $empresa->numero_resolucion ?? 'N/A' }} 
                del {{ $empresa->fecha_resolucion ? $empresa->fecha_resolucion->format('d-m-Y') : 'N/A' }}. 
                Prefijo {{ $empresa->prefijo_factura ?? 'FE' }} autorizado desde 
                {{ $empresa->numeracion_desde ?? '1' }} hasta {{ $empresa->numeracion_hasta ?? '10000' }}.
            </p>
        </div>

        <!-- Proveedor Tecnológico -->
        <div class="tech-provider">
            <p><strong>Facturación de conformidad con el procedimiento establecido en el art 616-1 del E.T.</strong></p>
            <p>Proveedor Tecnológico: {{ config('app.name', 'Sistema de Facturación') }}</p>
            <p>NIT: {{ $empresa->nit ?? 'N/A' }}</p>
            <p>Software ID: {{ config('alegra.software_id', 'N/A') }}</p>
        </div>
    </div>
</body>
</html>
