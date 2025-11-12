<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura Electrónica {{ isset($detallesAlegra['data']['numberTemplate']['fullNumber']) ? $detallesAlegra['data']['numberTemplate']['fullNumber'] : $numeroFactura }}</title>
    <style>
        @page {
            margin: 10mm;
            size: A4;
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
            max-width: 100px;
            max-height: 50px;
            height: auto;
            margin-bottom: 5px;
            object-fit: contain;
        }
        .company-name {
            font-size: 12px;
            font-weight: bold;
            margin: 2px 0;
        }
        .company-details {
            font-size: 8px;
            margin: 1px 0;
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
        
        /* Date Section */
        .date-section {
            background-color: #d0d0d0;
            padding: 3px 8px;
            text-align: center;
            font-size: 8px;
            font-weight: bold;
            margin: 5px 0;
        }
        
        /* Items Table */
        .items-area {
            border: 1px solid #000;
            height: 180px;
            position: relative;
        }
        .items-header {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin: 0;
        }
        .items-header th {
            background-color: #d0d0d0;
            border-bottom: 1px solid #000;
            border-right: 1px solid #000;
            padding: 4px;
            text-align: center;
            font-weight: bold;
            position: relative;
        }
        .items-header th:last-child {
            border-right: none;
        }
        
        /* Líneas verticales que se extienden hasta abajo */
        .items-header th:not(:last-child)::after {
            content: '';
            position: absolute;
            right: -1px;
            top: 0;
            bottom: -180px;
            width: 1px;
            background-color: #000;
            z-index: 10;
        }
        
        .items-content {
            height: 150px;
            padding: 4px;
            overflow: hidden;
            position: relative;
        }
        .items-row {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-bottom: 2px;
        }
        .items-cell {
            display: table-cell;
            padding: 2px 4px;
            vertical-align: top;
            font-size: 8px;
        }
        .items-cell.text-left {
            text-align: left;
        }
        .items-cell.text-right {
            text-align: right;
        }
        .items-cell.text-center {
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
        
        /* QR and DIAN Info */
        .qr-section {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            margin-bottom: 10px;
        }
        .qr-code {
            margin: 5px 0;
        }
        .dian-details {
            font-size: 7px;
            text-align: left;
            margin-top: 5px;
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
        
        /* CUFE */
        .cufe-section {
            margin-top: 10px;
            font-size: 7px;
        }
        .cufe-text {
            font-family: monospace;
            word-break: break-all;
            background-color: #f5f5f5;
            padding: 5px;
            border: 1px solid #ccc;
        }
        
        /* Terms */
        .terms {
            font-size: 7px;
            margin-top: 10px;
            text-align: justify;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                @if($empresa && $empresa->logo)
                    @php
                        $logoPath = storage_path('app/public/' . $empresa->logo);
                        $logoBase64 = '';
                        if(file_exists($logoPath)) {
                            $logoData = file_get_contents($logoPath);
                            $logoBase64 = 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoData);
                        }
                    @endphp
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Logo" class="logo">
                    @endif
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
                <div class="invoice-type">FACTURA ELECTRÓNICA DE VENTA</div>
                <div class="invoice-number">
                    No. {{ isset($detallesAlegra['data']['numberTemplate']['fullNumber']) ? $detallesAlegra['data']['numberTemplate']['fullNumber'] : $numeroFactura }}
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
                    @if(isset($detallesAlegra['data']['client']))
                        @php $cliente = $detallesAlegra['data']['client']; @endphp
                        <div class="client-field">{{ $cliente['name'] ?? 'N/A' }}</div>
                        <div class="client-field">
                            <span class="client-label">DIRECCIÓN</span>
                            {{ isset($cliente['address']) ? $cliente['address']['address'] . ', ' . $cliente['address']['city'] . ', ' . $cliente['address']['department'] : 'N/A' }}
                        </div>
                    @endif
                </div>
                <div class="client-right">
                    <div class="client-header" style="background: none; padding: 0; margin-bottom: 5px;">FECHA DEL DOCUMENTO (DD/MM/AA)</div>
                    @if(isset($detallesAlegra['data']['date']))
                        <div>{{ \Carbon\Carbon::parse($detallesAlegra['data']['date'])->format('d/m/Y') }}</div>
                    @endif
                    <div class="client-header" style="background: none; padding: 0; margin: 5px 0;">FECHA DE VENCIMIENTO</div>
                    @if(isset($detallesAlegra['data']['dueDate']))
                        <div>{{ \Carbon\Carbon::parse($detallesAlegra['data']['dueDate'])->format('d/m/Y') }}</div>
                    @endif
                </div>
            </div>
            <div style="padding: 5px 8px; border-top: 1px solid #000;">
                <div class="client-field">
                    <span class="client-label">TELÉFONO</span>
                    @if(isset($detallesAlegra['data']['client']['mobile']))
                        {{ $detallesAlegra['data']['client']['mobile'] }}
                    @endif
                    <span style="margin-left: 50px;">
                        <strong>{{ isset($detallesAlegra['data']['client']['identificationType']) ? $detallesAlegra['data']['client']['identificationType'] : 'CC' }}</strong>
                        {{ isset($detallesAlegra['data']['client']['identification']) ? $detallesAlegra['data']['client']['identification'] : '' }}
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Items Table -->
        <div class="items-area">
            <!-- Header de la tabla con líneas verticales extendidas -->
            <table class="items-header">
                <thead>
                    <tr>
                        <th style="width: 8%;">ID</th>
                        <th style="width: 32%;">Ítem</th>
                        <th style="width: 12%;">Unidad</th>
                        <th style="width: 12%;">Precio</th>
                        <th style="width: 12%;">Cantidad</th>
                        <th style="width: 12%;">Descuento</th>
                        <th style="width: 12%;">Total</th>
                    </tr>
                </thead>
            </table>
            
            <!-- Contenido de productos sin líneas horizontales -->
            <div class="items-content">
                @if(isset($detallesAlegra['data']['items']) && count($detallesAlegra['data']['items']) > 0)
                    @foreach($detallesAlegra['data']['items'] as $item)
                        <div class="items-row">
                            <div class="items-cell text-center" style="width: 8%;">{{ $item['id'] ?? '' }}</div>
                            <div class="items-cell text-left" style="width: 32%;">{{ $item['name'] ?? '' }}</div>
                            <div class="items-cell text-center" style="width: 12%;">{{ ucfirst($item['unit'] ?? 'Servicio') }}</div>
                            <div class="items-cell text-right" style="width: 12%;">${{ number_format($item['price'] ?? 0, 0, ',', '.') }}</div>
                            <div class="items-cell text-center" style="width: 12%;">{{ $item['quantity'] ?? 1 }}</div>
                            <div class="items-cell text-right" style="width: 12%;">${{ number_format($item['discount'] ?? 0, 0, ',', '.') }}</div>
                            <div class="items-cell text-right" style="width: 12%;">${{ number_format($item['total'] ?? 0, 0, ',', '.') }}</div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
        
        <!-- Bottom Section -->
        <div class="bottom-section">
            <div class="bottom-left">
                <!-- QR Code and DIAN Info -->
                <div class="qr-section">
                    @if($qrCodeBase64)
                        <img src="data:image/png;base64,{{ $qrCodeBase64 }}" alt="QR Code" class="qr-code" style="width: 100px; height: 100px;">
                    @elseif(isset($detallesAlegra['data']['stamp']['barCodeContent']))
                        @php
                            $qrData = $detallesAlegra['data']['stamp']['barCodeContent'];
                            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=' . urlencode($qrData);
                        @endphp
                        <img src="{{ $qrUrl }}" alt="QR Code" class="qr-code" style="width: 100px; height: 100px;">
                    @endif
                    
                    <div class="dian-details">
                        @if(isset($detallesAlegra['data']['stamp']))
                            @php $stamp = $detallesAlegra['data']['stamp']; @endphp
                            <div><strong>Moneda:</strong> COP</div>
                            <div><strong>Generado:</strong> {{ isset($stamp['date']) ? \Carbon\Carbon::parse($stamp['date'])->format('Y-m-d H:i:s') : '' }}</div>
                            <div><strong>Validación DIAN:</strong> {{ isset($stamp['date']) ? \Carbon\Carbon::parse($stamp['date'])->format('Y-m-d H:i:s') : '' }}</div>
                            <div><strong>Tipo de operación:</strong> Estándar</div>
                            <div><strong>Forma de pago:</strong> Contado</div>
                            <div><strong>Medio de pago:</strong> Efectivo</div>
                        @endif
                    </div>
                </div>
                
                <!-- CUFE -->
                @if(isset($detallesAlegra['data']['stamp']['cufe']))
                    <div class="cufe-section">
                        <strong>CUFE:</strong> {{ $detallesAlegra['data']['stamp']['cufe'] }}
                    </div>
                @endif
                
                <!-- Terms -->
                @if(isset($detallesAlegra['data']['termsConditions']))
                    <div class="terms">
                        {{ $detallesAlegra['data']['termsConditions'] }}
                    </div>
                @endif
            </div>
            
            <div class="bottom-right">
                <!-- Totals -->
                <div class="totals-section">
                    @if(isset($detallesAlegra['data']))
                        @php $factura = $detallesAlegra['data']; @endphp
                        <div class="totals-row">
                            <div class="totals-label">Subtotal</div>
                            <div class="totals-value">${{ number_format($factura['subtotal'] ?? 0, 0, ',', '.') }}</div>
                        </div>
                        <div class="totals-row">
                            <div class="totals-label">Total</div>
                            <div class="totals-value">${{ number_format($factura['total'] ?? 0, 0, ',', '.') }}</div>
                        </div>
                        <div class="totals-row">
                            <div class="totals-label">Total de líneas:</div>
                            <div class="totals-value">{{ isset($factura['items']) ? count($factura['items']) : 0 }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>
