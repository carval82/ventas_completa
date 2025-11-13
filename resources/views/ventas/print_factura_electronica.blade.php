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
                    <p><strong>NIT: {{ $empresa->nit }}</strong></p>
                @endif
                @if($empresa->direccion)
                    <p>Dir: {{ $empresa->direccion }}</p>
                @endif
                @if($empresa->telefono)
                    <p>Propietario: tel {{ $empresa->telefono }}</p>
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
            <p><strong>No: {{ $venta->getNumeroFacturaMostrar() }}</strong></p>
            <p>Fecha generación: {{ $venta->fecha_venta->format('d/m/Y h:i A') }}</p>
            <p>Fecha vencimiento: {{ $venta->fecha_vencimiento ? $venta->fecha_vencimiento->format('d/m/Y h:i A') : $venta->fecha_venta->addDays(30)->format('d/m/Y h:i A') }}</p>
        </div>

        <!-- Información del Cliente -->
        <div class="client-info">
            <p><strong>Cliente:</strong> {{ $venta->cliente ? strtoupper($venta->cliente->nombres . ' ' . $venta->cliente->apellidos) : 'CONSUMIDOR FINAL' }}</p>
            <p><strong>C.C / NIT:</strong> {{ $venta->cliente && $venta->cliente->cedula ? $venta->cliente->cedula : '222222222222' }}</p>
            <p><strong>Dirección:</strong> {{ $venta->cliente && $venta->cliente->direccion ? $venta->cliente->direccion : 'N/A' }}</p>
        </div>

        <!-- Tabla de Productos -->
        @if($venta->detalles && count($venta->detalles) > 0)
        <table class="products-table">
            <thead>
                <tr>
                    <th style="width: 8%;">#</th>
                    <th style="width: 12%;">Cant.</th>
                    <th style="width: 20%;">Vr. Unit</th>
                    <th style="width: 25%;" class="text-right">Valor</th>
                    <th style="width: 10%;">ID</th>
                </tr>
            </thead>
            <tbody>
                @foreach($venta->detalles as $index => $detalle)
                <tr>
                    <td colspan="5" style="text-align: left; padding: 1mm; border-bottom: none;">
                        <small><strong>{{ $detalle->producto ? $detalle->producto->nombre : 'Producto' }}</strong></small>
                    </td>
                </tr>
                <tr style="border-top: none;">
                    <td>{{ $index + 1 }}</td>
                    <td>{{ number_format($detalle->cantidad, 2, '.', ',') }}</td>
                    <td>{{ number_format($detalle->precio, 2, '.', ',') }}</td>
                    <td class="text-right">{{ number_format($detalle->subtotal, 2, '.', ',') }}</td>
                    <td>{{ $detalle->producto ? ($detalle->producto->codigo ?? 'A') : 'A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- Tabla de Impuestos -->
        @php
            // Cálculos correctos según estructura de factura electrónica
            $totalBruto = $venta->total; // Total con IVA incluido
            $descuentos = $venta->descuento ?? 0;
            $subtotalDespuesDescuento = $totalBruto - $descuentos;
            
            $iva = $venta->impuesto ?? 0;
            // Base gravable = subtotal / 1.19 (para sacar el IVA del 19%)
            $baseGravable = $iva > 0 ? ($subtotalDespuesDescuento / 1.19) : $subtotalDespuesDescuento;
            $ivaCalculado = $iva > 0 ? ($baseGravable * 0.19) : 0;
            
            $totalNeto = $subtotalDespuesDescuento;
            $totalAPagar = $subtotalDespuesDescuento;
        @endphp
        
        {{-- SIEMPRE mostrar tabla de impuestos, aunque sea 0 --}}
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
                    @if($iva > 0)
                    <tr>
                        <td>IVA</td>
                        <td>19.00</td>
                        <td class="text-right">{{ number_format($baseGravable, 2, '.', ',') }}</td>
                        <td class="text-right">{{ number_format($ivaCalculado, 2, '.', ',') }}</td>
                    </tr>
                    @else
                    <tr>
                        <td colspan="4" style="text-align: center;">
                            <small><em>No responsable de IVA</em></small>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Totales -->
        <div class="totals">
            <div class="row">
                <span class="label">Total bruto:</span>
                <span class="value">{{ number_format($totalBruto, 2, '.', ',') }}</span>
            </div>
            <div class="row">
                <span class="label">Descuentos:</span>
                <span class="value">-{{ number_format($descuentos, 2, '.', ',') }}</span>
            </div>
            <div class="row">
                <span class="label">Subtotal:</span>
                <span class="value">{{ number_format($subtotalDespuesDescuento, 2, '.', ',') }}</span>
            </div>
            <div class="row">
                <span class="label">IVA 19%:</span>
                <span class="value">{{ number_format($ivaCalculado, 2, '.', ',') }}</span>
            </div>
            <div class="row">
                <span class="label">Total neto:</span>
                <span class="value">{{ number_format($totalNeto, 2, '.', ',') }}</span>
            </div>
            <div class="row highlight">
                <span class="label">Total a pagar:</span>
                <span class="value">{{ number_format($totalAPagar, 2, '.', ',') }}</span>
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
                <span><strong>{{ number_format($venta->pago, 2, '.', ',') }}</strong></span>
            </div>
            <div class="row">
                <span>Cambio:</span>
                <span><strong>{{ number_format($venta->devuelta, 2, '.', ',') }}</strong></span>
            </div>
        </div>

        <!-- CUFE -->
        @if($venta->cufe || $venta->cufe_local)
        <div class="cufe-section">
            <h3>{{ $venta->cufe ? 'CUFE' : 'CUFE LOCAL' }}</h3>
            <div class="cufe-code">{{ $venta->cufe ?? $venta->cufe_local }}</div>
        </div>
        @endif

        <!-- Código QR -->
        @if(($venta->qr_code && isset($venta->qr_code_image)) || $venta->qr_local)
        <div class="qr-section">
            <div class="qr-code">
                @if($venta->qr_code && isset($venta->qr_code_image))
                    {{-- QR de Alegra (DIAN) --}}
                    {!! $venta->qr_code_image !!}
                @elseif($venta->qr_local)
                    {{-- QR Local generado por el sistema --}}
                    <img src="data:image/png;base64,{{ $venta->qr_local }}" 
                         alt="QR Local" 
                         style="width: 45mm; height: 45mm;">
                @endif
            </div>
        </div>
        @endif

        <!-- Texto Legal -->
        <div class="legal-text">
            <p>
                Al ser ésta una <strong>Factura Electrónica de Venta</strong> no requiere firma autógrafa 
                y se asimila en todos sus efectos a lo regulado en la Ley 527 de 1999, el Decreto Reglamentario 1929 de 2007,  
                la Resolución 000042 de 05 de mayo de 2020 de la DIAN por la cual se reglamenta el Sistema de Facturación Electrónica y 
                el Artículo 616-1 del Estatuto Tributario.
            </p>
            <p style="margin-top: 2mm;">
                <strong>Numeración Autorización</strong>: @if($empresa->numero_resolucion)Resolución {{ $empresa->numero_resolucion }} 
                del {{ $empresa->fecha_resolucion ? $empresa->fecha_resolucion->format('d-m-Y') : 'N/A' }}. @endif
                Prefijo <strong>{{ $empresa->prefijo_factura ?? 'FE' }}</strong> autorizado desde 
                <strong>{{ $empresa->numeracion_desde ?? '1' }}</strong> hasta <strong>{{ $empresa->numeracion_hasta ?? '10000' }}</strong>. 
                Modalidad: Factura Electrónica de Venta. Vigencia: Indefinida.
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
