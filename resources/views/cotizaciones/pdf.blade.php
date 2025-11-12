<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización {{ $cotizacion->numero_cotizacion }}</title>
    <style>
        @page {
            margin: 20px;
        }
        
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .header {
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .company-info h1 {
            color: #007bff;
            margin: 0 0 8px 0;
            font-size: 18px;
            font-weight: bold;
        }
        
        .company-info p {
            margin: 1px 0;
            font-size: 10px;
        }
        
        .quote-info {
            text-align: right;
            vertical-align: top;
        }
        
        .quote-info h2 {
            color: #007bff;
            margin: 0 0 10px 0;
            font-size: 16px;
            font-weight: bold;
        }
        
        .quote-number {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }
        
        .client-section {
            margin: 20px 0;
            background-color: #f8f9fa;
            padding: 10px;
            border: 1px solid #ddd;
        }
        
        .client-section h3 {
            margin: 0 0 8px 0;
            color: #007bff;
            font-size: 12px;
            font-weight: bold;
        }
        
        .client-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .client-left, .client-right {
            width: 50%;
            vertical-align: top;
            padding: 0 5px;
        }
        
        .info-row {
            margin-bottom: 3px;
            font-size: 10px;
        }
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 80px;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .products-table th {
            background-color: #007bff;
            color: white;
            padding: 6px 4px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            border: 1px solid #0056b3;
        }
        
        .products-table td {
            padding: 5px 4px;
            border: 1px solid #ddd;
            font-size: 9px;
        }
        
        .products-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-end { text-align: right; }
        
        .totals-section {
            margin-top: 20px;
            width: 300px;
            margin-left: auto;
        }
        
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .totals-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #ddd;
        }
        
        .totals-table .total-row {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        
        .notes-section {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        
        .notes-section h4 {
            color: #007bff;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .notes-content {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-size: 11px;
            line-height: 1.5;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #007bff;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pendiente { background-color: #ffc107; color: #000; }
        .status-aprobada { background-color: #28a745; color: #fff; }
        .status-rechazada { background-color: #dc3545; color: #fff; }
        .status-vencida { background-color: #6c757d; color: #fff; }
        .status-convertida { background-color: #17a2b8; color: #fff; }
        
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    <div class="company-info">
                        <h1>{{ $empresa->nombre_comercial ?? 'Mi Empresa' }}</h1>
                        <p><strong>{{ $empresa->razon_social ?? 'Razón Social' }}</strong></p>
                        <p>NIT: {{ $empresa->nit ?? '000000000-0' }}</p>
                        <p>{{ $empresa->direccion ?? 'Dirección' }}</p>
                        <p>Tel: {{ $empresa->telefono ?? '000000000' }} | Email: {{ $empresa->email ?? 'email@empresa.com' }}</p>
                    </div>
                </td>
                <td style="width: 40%; vertical-align: top;">
                    <div class="quote-info">
                        <h2>COTIZACIÓN</h2>
                        <div class="quote-number">{{ $cotizacion->numero_cotizacion }}</div>
                        <p><strong>Fecha:</strong> {{ $cotizacion->fecha_cotizacion->format('d/m/Y') }}</p>
                        <p><strong>Vencimiento:</strong> {{ $cotizacion->fecha_vencimiento->format('d/m/Y') }}</p>
                        <p><strong>Estado:</strong> 
                            <span class="status-badge status-{{ $cotizacion->estado }}">
                                {{ ucfirst($cotizacion->estado) }}
                            </span>
                        </p>
                        @if($cotizacion->estaVencida())
                            <p style="color: #dc3545; font-weight: bold;">⚠️ VENCIDA</p>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Cliente -->
    <div class="client-section">
        <h3>INFORMACIÓN DEL CLIENTE</h3>
        <table class="client-table">
            <tr>
                <td class="client-left">
                    <div class="info-row">
                        <span class="info-label">Cliente:</span>
                        {{ $cotizacion->cliente->nombres }} {{ $cotizacion->cliente->apellidos }}
                    </div>
                    <div class="info-row">
                        <span class="info-label">Documento:</span>
                        {{ $cotizacion->cliente->cedula }}
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        {{ $cotizacion->cliente->email ?? 'N/A' }}
                    </div>
                </td>
                <td class="client-right">
                    <div class="info-row">
                        <span class="info-label">Teléfono:</span>
                        {{ $cotizacion->cliente->telefono ?? 'N/A' }}
                    </div>
                    <div class="info-row">
                        <span class="info-label">Dirección:</span>
                        {{ $cotizacion->cliente->direccion ?? 'N/A' }}
                    </div>
                    <div class="info-row">
                        <span class="info-label">Vendedor:</span>
                        {{ $cotizacion->vendedor->name ?? 'N/A' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Productos -->
    <table class="products-table">
        <thead>
            <tr>
                <th style="width: 8%">Código</th>
                <th style="width: 35%">Descripción</th>
                <th style="width: 8%" class="text-center">Cant.</th>
                <th style="width: 8%" class="text-center">Unidad</th>
                <th style="width: 12%" class="text-right">Precio Unit.</th>
                <th style="width: 8%" class="text-center">Desc. %</th>
                <th style="width: 10%" class="text-right">Subtotal</th>
                @if($cotizacion->impuestos > 0)
                    <th style="width: 8%" class="text-center">IVA %</th>
                    <th style="width: 10%" class="text-right">IVA</th>
                @endif
                <th style="width: 12%" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cotizacion->detalles as $detalle)
                <tr>
                    <td>{{ $detalle->producto->codigo ?? 'N/A' }}</td>
                    <td>
                        <strong>{{ $detalle->producto->nombre }}</strong>
                        @if($detalle->observaciones)
                            <br><small style="color: #666;">{{ $detalle->observaciones }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($detalle->cantidad, 3) }}</td>
                    <td class="text-center">{{ $detalle->unidad_medida }}</td>
                    <td class="text-right">${{ number_format($detalle->precio_unitario, 0, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($detalle->descuento_porcentaje, 2) }}%</td>
                    <td class="text-right">${{ number_format($detalle->subtotal, 0, ',', '.') }}</td>
                    @if($cotizacion->impuestos > 0)
                        <td class="text-center">{{ number_format($detalle->impuesto_porcentaje, 2) }}%</td>
                        <td class="text-right">${{ number_format($detalle->impuesto_valor, 0, ',', '.') }}</td>
                    @endif
                    <td class="text-right"><strong>${{ number_format($detalle->total, 0, ',', '.') }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totales -->
    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td><strong>Subtotal:</strong></td>
                <td class="text-right">${{ number_format($cotizacion->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if($cotizacion->descuento > 0)
                <tr>
                    <td><strong>Descuento:</strong></td>
                    <td class="text-right">-${{ number_format($cotizacion->descuento, 0, ',', '.') }}</td>
                </tr>
            @endif
            @if($cotizacion->impuestos > 0)
                <tr>
                    <td><strong>IVA:</strong></td>
                    <td class="text-right">${{ number_format($cotizacion->impuestos, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td><strong>TOTAL:</strong></td>
                <td class="text-right"><strong>${{ number_format($cotizacion->total, 0, ',', '.') }}</strong></td>
            </tr>
        </table>
    </div>

    <!-- Observaciones y Condiciones -->
    @if($cotizacion->observaciones || $cotizacion->condiciones_comerciales)
        <div class="notes-section">
            @if($cotizacion->observaciones)
                <h4>OBSERVACIONES</h4>
                <div class="notes-content">
                    {{ $cotizacion->observaciones }}
                </div>
            @endif

            @if($cotizacion->condiciones_comerciales)
                <h4>CONDICIONES COMERCIALES</h4>
                <div class="notes-content">
                    {{ $cotizacion->condiciones_comerciales }}
                </div>
            @endif
        </div>
    @endif

    <!-- Información adicional -->
    <div class="notes-section">
        <h4>INFORMACIÓN ADICIONAL</h4>
        <div class="notes-content">
            <p><strong>Forma de Pago:</strong> {{ ucfirst($cotizacion->forma_pago ?? 'Por definir') }}</p>
            <p><strong>Validez de la Oferta:</strong> {{ $cotizacion->dias_validez }} días</p>
            <p><strong>Fecha de Generación:</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
            @if($cotizacion->venta_id)
                <p><strong>Convertida a Venta:</strong> Sí (ID: {{ $cotizacion->venta_id }})</p>
            @endif
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Esta cotización fue generada automáticamente por el sistema de {{ $empresa->nombre_comercial ?? 'Mi Empresa' }}</p>
        <p>Para cualquier consulta, contáctenos al {{ $empresa->telefono ?? '000000000' }} o {{ $empresa->email ?? 'email@empresa.com' }}</p>
    </div>
</body>
</html>
