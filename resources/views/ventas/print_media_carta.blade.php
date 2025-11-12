<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #{{ $venta->getNumeroFacturaMostrar() }}</title>
    <style>
        @page {
            margin: 10mm;
            size: letter;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            margin: 0;
            padding: 0;
            color: #000;
            line-height: 1.2;
        }
        .container {
            width: 100%;
            max-width: 190mm;
        }
        
        /* Header */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            text-align: center;
            border: 1px solid #000;
            padding: 8px;
        }
        .logo {
            max-width: 80px;
            height: auto;
            margin-bottom: 5px;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin: 2px 0 1px 0;
        }
        .company-details {
            font-size: 10px;
            margin: 0;
            line-height: 1.3;
            font-weight: bold;
        }
        .invoice-type {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .invoice-number {
            font-size: 14px;
            font-weight: bold;
        }
        .tax-regime {
            font-size: 8px;
            margin-top: 3px;
        }
        
        /* Client Info */
        .client-section {
            border: 1px solid #000;
            margin: 10px 0;
        }
        .client-header {
            background-color: #d0d0d0;
            padding: 3px 8px;
            font-weight: bold;
            font-size: 8px;
        }
        .client-info {
            display: table;
            width: 100%;
        }
        .client-left {
            display: table-cell;
            width: 70%;
            padding: 5px 8px;
        }
        .client-right {
            display: table-cell;
            width: 30%;
            padding: 5px 8px;
            border-left: 1px solid #000;
        }
        .client-field {
            margin: 2px 0;
            font-size: 8px;
        }
        .client-label {
            font-weight: bold;
            display: inline-block;
            width: 80px;
        }
        
        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            border: 1px solid #000;
        }
        .items-table th {
            background-color: #d0d0d0;
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            font-weight: bold;
        }
        .items-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 3px 4px;
            vertical-align: top;
            font-size: 8px;
        }
        .items-table td:first-child {
            border-left: none;
        }
        .items-table td:last-child {
            border-right: none;
        }
        .text-left {
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        
        /* Bottom Section */
        .bottom-section {
            display: table;
            width: 100%;
            margin-top: 10px;
        }
        .bottom-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }
        .bottom-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        /* Payment Info */
        .payment-section {
            border: 1px solid #000;
            padding: 8px;
            margin-bottom: 10px;
        }
        .payment-details {
            font-size: 8px;
            margin-top: 5px;
        }
        .payment-field {
            margin: 3px 0;
        }
        
        /* Totals */
        .totals-section {
            border: 1px solid #000;
        }
        .totals-row {
            display: table;
            width: 100%;
            border-bottom: 1px solid #000;
        }
        .totals-row:last-child {
            border-bottom: none;
        }
        .totals-label {
            display: table-cell;
            padding: 4px 8px;
            font-weight: bold;
            background-color: #f0f0f0;
            border-right: 1px solid #000;
            width: 70%;
        }
        .totals-value {
            display: table-cell;
            padding: 4px 8px;
            text-align: right;
            width: 30%;
        }
        
        /* Footer */
        .footer {
            margin-top: 10px;
            font-size: 7px;
            text-align: center;
            border-top: 1px solid #ccc;
            padding-top: 5px;
        }
        
        @media print {
            .no-print { 
                display: none; 
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 10px; text-align: center;">
        <button onclick="window.print()" style="padding: 5px 15px; margin: 0 5px;">Imprimir</button>
        <button onclick="window.close()" style="padding: 5px 15px; margin: 0 5px;">Cerrar</button>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                @if($empresa && $empresa->logo)
                    <img src="{{ asset('storage/' . $empresa->logo) }}" alt="Logo" class="logo">
                @endif
                
                @if($empresa)
                    <div class="company-name">{{ $empresa->nombre_comercial ?? $empresa->razon_social }}</div>
                    @if($empresa->nit)
                        <div class="company-details">NIT {{ $empresa->nit }}</div>
                    @endif
                    @if($empresa->direccion)
                        <div class="company-details">{{ $empresa->direccion }}</div>
                    @endif
                    @if($empresa->telefono)
                        <div class="company-details">Tel: {{ $empresa->telefono }}</div>
                    @endif
                    @if($empresa->email)
                        <div class="company-details">{{ $empresa->email }}</div>
                    @endif
                @endif
            </div>
            
            <div class="header-right">
                <div class="invoice-type">FACTURA DE VENTA</div>
                <div class="invoice-number">
                    No. {{ $venta->getNumeroFacturaMostrar() }}
                </div>
                <div class="tax-regime">
                    @if($empresa && $empresa->regimen_tributario)
                        {{ $empresa->regimen_tributario === 'responsable_iva' ? 'Responsable de IVA' : 'No responsable de IVA' }}
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Client Section -->
        <div class="client-section">
            <div class="client-header">SEÑOR(ES)</div>
            <div class="client-info">
                <div class="client-left">
                    @if($venta->cliente)
                        <div class="client-field" style="font-weight: bold; font-size: 9px;">
                            {{ $venta->cliente->nombres }} {{ $venta->cliente->apellidos }}
                        </div>
                        <div class="client-field">
                            <span class="client-label">
                                @php
                                    $tipoDoc = $venta->cliente->tipo_documento ?? 'CC';
                                    $labelDoc = match(strtoupper($tipoDoc)) {
                                        'NIT' => 'NIT',
                                        'CC' => 'CÉDULA',
                                        'CE' => 'C. EXTRANJERÍA',
                                        'TI' => 'T. IDENTIDAD',
                                        'PASAPORTE' => 'PASAPORTE',
                                        default => strtoupper($tipoDoc)
                                    };
                                @endphp
                                {{ $labelDoc }}:
                            </span>
                            {{ $venta->cliente->cedula ?? 'N/A' }}
                        </div>
                        @if($venta->cliente->direccion)
                            <div class="client-field">
                                <span class="client-label">DIRECCIÓN:</span>
                                {{ $venta->cliente->direccion }}
                            </div>
                        @endif
                        @if($venta->cliente->ciudad || $venta->cliente->departamento)
                            <div class="client-field">
                                <span class="client-label">CIUDAD:</span>
                                {{ $venta->cliente->ciudad ?? '' }}
                                @if($venta->cliente->departamento)
                                    , {{ $venta->cliente->departamento }}
                                @endif
                            </div>
                        @endif
                        @if($venta->cliente->telefono)
                            <div class="client-field">
                                <span class="client-label">TELÉFONO:</span>
                                {{ $venta->cliente->telefono }}
                            </div>
                        @endif
                        @if($venta->cliente->email)
                            <div class="client-field">
                                <span class="client-label">EMAIL:</span>
                                {{ $venta->cliente->email }}
                            </div>
                        @endif
                        @if($venta->cliente->regimen)
                            <div class="client-field">
                                <span class="client-label">RÉGIMEN:</span>
                                {{ ucfirst($venta->cliente->regimen) }}
                            </div>
                        @endif
                    @endif
                </div>
                <div class="client-right">
                    <div class="client-header" style="background: none; padding: 0; margin-bottom: 5px;">FECHA DEL DOCUMENTO</div>
                    <div style="font-weight: bold;">{{ $venta->fecha_venta->format('d/m/Y') }}</div>
                    <div class="client-header" style="background: none; padding: 0; margin: 5px 0;">HORA</div>
                    <div style="font-weight: bold;">{{ $venta->fecha_venta->format('H:i A') }}</div>
                    @if($venta->metodo_pago)
                        <div class="client-header" style="background: none; padding: 0; margin: 5px 0;">MÉTODO PAGO</div>
                        <div style="font-weight: bold;">{{ ucfirst($venta->metodo_pago) }}</div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Código</th>
                    <th style="width: 35%;">Producto</th>
                    <th style="width: 15%;">Precio Unit.</th>
                    <th style="width: 15%;">Cantidad</th>
                    <th style="width: 10%;">IVA</th>
                    <th style="width: 15%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @if($venta->detalles && count($venta->detalles) > 0)
                    @foreach($venta->detalles as $detalle)
                        @if($detalle->producto)
                            <tr>
                                <td class="text-center">{{ $detalle->producto->codigo }}</td>
                                <td class="text-left">{{ $detalle->producto->nombre }}</td>
                                <td class="text-right">${{ number_format($detalle->precio_unitario, 0, ',', '.') }}</td>
                                <td class="text-center">{{ $detalle->cantidad }}</td>
                                <td class="text-right">
                                    @php
                                        $productoModel = \App\Models\Producto::find($detalle->producto_id);
                                        $porcentajeIva = $productoModel ? $productoModel->iva : 0;
                                        $valorIva = $detalle->subtotal * ($porcentajeIva / 100);
                                    @endphp
                                    ${{ number_format($valorIva, 0, ',', '.') }}
                                </td>
                                <td class="text-right">${{ number_format($detalle->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                    @endforeach
                @else
                    <tr>
                        <td colspan="6" class="text-center">No hay productos en esta venta</td>
                    </tr>
                @endif
            </tbody>
        </table>
        
        <!-- Bottom Section -->
        <div class="bottom-section">
            <div class="bottom-left">
                <!-- Payment Info -->
                <div class="payment-section">
                    <div class="client-header">INFORMACIÓN DE PAGO</div>
                    <div class="payment-details">
                        <div class="payment-field">
                            <strong>Método de pago:</strong> {{ ucfirst($venta->metodo_pago ?? 'Efectivo') }}
                        </div>
                        <div class="payment-field">
                            <strong>Efectivo recibido:</strong> ${{ number_format($venta->pago, 0, ',', '.') }}
                        </div>
                        <div class="payment-field">
                            <strong>Cambio devuelto:</strong> ${{ number_format($venta->devuelta, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
                
                <!-- Additional Info -->
                <div style="font-size: 7px; margin-top: 10px;">
                    <div><strong>ID de Venta:</strong> {{ $venta->id }}</div>
                    @if($venta->usuario)
                        <div><strong>Atendió:</strong> {{ $venta->usuario->name }}</div>
                    @endif
                    @if($venta->caja_id)
                        <div><strong>Caja:</strong> #{{ $venta->caja_id }}</div>
                    @endif
                </div>
            </div>
            
            <div class="bottom-right">
                <!-- Totals -->
                <div class="totals-section">
                    <div class="totals-row">
                        <div class="totals-label">Subtotal</div>
                        <div class="totals-value">${{ number_format($venta->subtotal, 0, ',', '.') }}</div>
                    </div>
                    <div class="totals-row">
                        <div class="totals-label">IVA</div>
                        <div class="totals-value">${{ number_format($venta->iva, 0, ',', '.') }}</div>
                    </div>
                    <div class="totals-row">
                        <div class="totals-label" style="font-size: 10px;">TOTAL</div>
                        <div class="totals-value" style="font-size: 10px; font-weight: bold;">${{ number_format($venta->total, 0, ',', '.') }}</div>
                    </div>
                    <div class="totals-row">
                        <div class="totals-label">Total de productos:</div>
                        <div class="totals-value">{{ $venta->detalles->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>¡GRACIAS POR SU COMPRA!</strong></p>
            @if($empresa)
                <p>{{ $empresa->nombre_comercial }}</p>
                @if($empresa->direccion && $empresa->telefono)
                    <p>{{ $empresa->direccion }} - Tel: {{ $empresa->telefono }}</p>
                @endif
                @if($empresa->sitio_web)
                    <p>{{ $empresa->sitio_web }}</p>
                @endif
            @endif
            <p>Impreso: {{ now()->format('d/m/Y h:i A') }}</p>
        </div>
    </div>
</body>
</html>
