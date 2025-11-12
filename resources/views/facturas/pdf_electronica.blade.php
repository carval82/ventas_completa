<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura Electrónica {{ $numeroFactura }}</title>
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
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header img {
            max-width: 200px;
            height: auto;
            margin-bottom: 10px;
        }
        .company-info {
            margin-bottom: 10px;
        }
        .company-info h1 {
            margin: 0;
            font-size: 18px;
            color: #2c5aa0;
        }
        .invoice-title {
            background-color: #2c5aa0;
            color: white;
            padding: 10px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-left, .info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 10px;
        }
        .info-box {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
        }
        .info-box h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #2c5aa0;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .info-box p {
            margin: 3px 0;
            font-size: 11px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table .text-center {
            text-align: center;
        }
        .totals {
            float: right;
            width: 300px;
            margin-top: 20px;
        }
        .totals table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals td {
            padding: 5px 10px;
            border: 1px solid #ddd;
        }
        .totals .total-label {
            font-weight: bold;
            background-color: #f5f5f5;
        }
        .totals .total-final {
            font-weight: bold;
            background-color: #2c5aa0;
            color: white;
        }
        .dian-info {
            clear: both;
            margin-top: 40px;
            padding: 15px;
            border: 2px solid #2c5aa0;
            background-color: #f8f9fa;
        }
        .dian-info h3 {
            margin: 0 0 10px 0;
            color: #2c5aa0;
        }
        .qr-section {
            text-align: center;
            margin: 20px 0;
        }
        .cufe-text {
            font-family: monospace;
            font-size: 10px;
            word-break: break-all;
            background-color: #f5f5f5;
            padding: 10px;
            border: 1px solid #ddd;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        .electronic-badge {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .status-approved {
            background-color: #28a745;
            color: white;
        }
        .status-observations {
            background-color: #ffc107;
            color: #212529;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
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
                <img src="{{ $logoBase64 }}" alt="Logo">
            @endif
        @endif
        
        <div class="company-info">
            @if($empresa)
                <h1>{{ $empresa->nombre_comercial }}</h1>
                @if($empresa->razon_social && $empresa->razon_social !== $empresa->nombre_comercial)
                    <p><strong>{{ $empresa->razon_social }}</strong></p>
                @endif
                @if($empresa->nit)
                    <p><strong>NIT:</strong> {{ $empresa->nit }}</p>
                @endif
                @if($empresa->direccion)
                    <p>{{ $empresa->direccion }}</p>
                @endif
                @if($empresa->telefono)
                    <p><strong>Tel:</strong> {{ $empresa->telefono }}</p>
                @endif
                @if($empresa->email)
                    <p><strong>Email:</strong> {{ $empresa->email }}</p>
                @endif
                @if($empresa->regimen_tributario)
                    <p><strong>Régimen:</strong> {{ ucwords(str_replace('_', ' ', $empresa->regimen_tributario)) }}</p>
                @endif
            @endif
        </div>
    </div>

    <!-- Invoice Title -->
    <div class="invoice-title">
        <span class="electronic-badge">FACTURA ELECTRÓNICA DE VENTA</span>
        <br>
        <strong>No. {{ $numeroFactura }}</strong>
        @if(isset($detallesAlegra['data']['stamp']['legalStatus']))
            <span class="status-badge {{ strpos($detallesAlegra['data']['stamp']['legalStatus'], 'ACCEPTED') !== false ? 'status-approved' : 'status-observations' }}">
                {{ $detallesAlegra['data']['stamp']['legalStatus'] }}
            </span>
        @endif
    </div>

    <!-- Info Section -->
    <div class="info-section">
        <div class="info-left">
            <div class="info-box">
                <h3>INFORMACIÓN DEL CLIENTE</h3>
                @if(isset($detallesAlegra['data']['client']))
                    @php $cliente = $detallesAlegra['data']['client']; @endphp
                    <p><strong>Nombre:</strong> {{ $cliente['name'] ?? 'N/A' }}</p>
                    <p><strong>Identificación:</strong> {{ ($cliente['identificationType'] ?? 'CC') . ': ' . ($cliente['identification'] ?? 'N/A') }}</p>
                    @if(isset($cliente['address']))
                        <p><strong>Dirección:</strong> {{ $cliente['address']['address'] ?? 'N/A' }}</p>
                        <p><strong>Ciudad:</strong> {{ ($cliente['address']['city'] ?? 'N/A') . ', ' . ($cliente['address']['department'] ?? 'N/A') }}</p>
                    @endif
                    @if($cliente['mobile'] ?? null)
                        <p><strong>Teléfono:</strong> {{ $cliente['mobile'] }}</p>
                    @endif
                    @if($cliente['email'] ?? null)
                        <p><strong>Email:</strong> {{ $cliente['email'] }}</p>
                    @endif
                    <p><strong>Régimen:</strong> {{ $cliente['regime'] ?? 'N/A' }}</p>
                @endif
            </div>
        </div>
        
        <div class="info-right">
            <div class="info-box">
                <h3>INFORMACIÓN DE LA FACTURA</h3>
                @if(isset($detallesAlegra['data']))
                    @php $factura = $detallesAlegra['data']; @endphp
                    <p><strong>Fecha de Emisión:</strong> {{ \Carbon\Carbon::parse($factura['date'])->format('d/m/Y') }}</p>
                    <p><strong>Fecha de Vencimiento:</strong> {{ \Carbon\Carbon::parse($factura['dueDate'])->format('d/m/Y') }}</p>
                    <p><strong>Hora:</strong> {{ \Carbon\Carbon::parse($factura['datetime'])->format('H:i:s') }}</p>
                    <p><strong>Término de Pago:</strong> {{ $factura['term'] ?? 'De contado' }}</p>
                    <p><strong>Forma de Pago:</strong> {{ $factura['paymentForm'] ?? 'CASH' }}</p>
                    @if(isset($factura['numberTemplate']['text']))
                        <p><strong>Resolución DIAN:</strong></p>
                        <p style="font-size: 9px;">{{ $factura['numberTemplate']['text'] }}</p>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 10%;">CÓDIGO</th>
                <th style="width: 40%;">DESCRIPCIÓN</th>
                <th style="width: 10%;">UNIDAD</th>
                <th style="width: 10%;">CANTIDAD</th>
                <th style="width: 15%;">PRECIO UNIT.</th>
                <th style="width: 15%;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($detallesAlegra['data']['items']))
                @foreach($detallesAlegra['data']['items'] as $item)
                    <tr>
                        <td class="text-center">{{ $item['id'] ?? 'N/A' }}</td>
                        <td>
                            <strong>{{ $item['name'] ?? 'N/A' }}</strong>
                            @if($item['description'] ?? null)
                                <br><small>{{ $item['description'] }}</small>
                            @endif
                        </td>
                        <td class="text-center">{{ ucfirst($item['unit'] ?? 'unidad') }}</td>
                        <td class="text-center">{{ number_format($item['quantity'] ?? 0, 2) }}</td>
                        <td class="text-right">${{ number_format($item['price'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right">${{ number_format($item['total'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals">
        <table>
            @if(isset($detallesAlegra['data']))
                @php $factura = $detallesAlegra['data']; @endphp
                <tr>
                    <td class="total-label">SUBTOTAL:</td>
                    <td class="text-right">${{ number_format($factura['subtotal'] ?? 0, 0, ',', '.') }}</td>
                </tr>
                @if(($factura['discount'] ?? 0) > 0)
                    <tr>
                        <td class="total-label">DESCUENTO:</td>
                        <td class="text-right">-${{ number_format($factura['discount'], 0, ',', '.') }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="total-label">IVA:</td>
                    <td class="text-right">${{ number_format($factura['tax'] ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="total-final">TOTAL:</td>
                    <td class="text-right total-final">${{ number_format($factura['total'] ?? 0, 0, ',', '.') }}</td>
                </tr>
            @endif
        </table>
    </div>

    <!-- DIAN Information -->
    @if(isset($detallesAlegra['data']['stamp']))
        @php $stamp = $detallesAlegra['data']['stamp']; @endphp
        <div class="dian-info">
            <h3>INFORMACIÓN DIAN - FACTURA ELECTRÓNICA</h3>
            
            @if($stamp['cufe'] ?? null)
                <p><strong>CUFE (Código Único de Facturación Electrónica):</strong></p>
                <div class="cufe-text">{{ $stamp['cufe'] }}</div>
            @endif
            
            @if($stamp['barCodeContent'] ?? null)
                <div class="qr-section">
                    <p><strong>Información del Código QR:</strong></p>
                    <div class="cufe-text" style="text-align: left; white-space: pre-line;">{{ $stamp['barCodeContent'] }}</div>
                </div>
            @endif
            
            <p><strong>Estado Legal:</strong> {{ $stamp['legalStatus'] ?? 'N/A' }}</p>
            <p><strong>Fecha de Timbrado:</strong> {{ isset($stamp['date']) ? \Carbon\Carbon::parse($stamp['date'])->format('d/m/Y H:i:s') : 'N/A' }}</p>
            
            @if(isset($stamp['warnings']) && is_array($stamp['warnings']) && count($stamp['warnings']) > 0)
                <p><strong>Observaciones DIAN:</strong></p>
                <ul style="font-size: 10px; margin: 5px 0; padding-left: 20px;">
                    @foreach($stamp['warnings'] as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <!-- Terms and Conditions -->
    @if(isset($detallesAlegra['data']['termsConditions']) && $detallesAlegra['data']['termsConditions'])
        <div style="margin-top: 20px; font-size: 10px; border: 1px solid #ddd; padding: 10px;">
            <strong>Términos y Condiciones:</strong><br>
            {{ $detallesAlegra['data']['termsConditions'] }}
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p><strong>FACTURA ELECTRÓNICA VÁLIDA ANTE LA DIAN</strong></p>
        <p>Generada el {{ now()->format('d/m/Y H:i:s') }}</p>
        @if($empresa)
            <p>{{ $empresa->nombre_comercial }} - {{ $empresa->direccion ?? '' }} - Tel: {{ $empresa->telefono ?? '' }}</p>
        @endif
    </div>
</body>
</html>
