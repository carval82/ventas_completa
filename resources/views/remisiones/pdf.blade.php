<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remisión {{ $remision->numero_remision }}</title>
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
            border-bottom: 2px solid #28a745;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .company-info {
            float: left;
            width: 60%;
        }
        
        .company-info h1 {
            color: #28a745;
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        
        .company-info p {
            margin: 2px 0;
            font-size: 11px;
        }
        
        .remision-info {
            float: right;
            width: 35%;
            text-align: right;
        }
        
        .remision-info h2 {
            color: #28a745;
            margin: 0 0 15px 0;
            font-size: 20px;
        }
        
        .remision-number {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        
        .client-section {
            margin: 30px 0;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        
        .client-section h3 {
            margin: 0 0 10px 0;
            color: #28a745;
            font-size: 14px;
        }
        
        .client-info {
            display: table;
            width: 100%;
        }
        
        .client-left, .client-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .info-row {
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
        }
        
        .transport-section {
            margin: 20px 0;
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
        }
        
        .transport-section h3 {
            margin: 0 0 10px 0;
            color: #1976d2;
            font-size: 14px;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .products-table th {
            background-color: #28a745;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
        }
        
        .products-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }
        
        .products-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-end { text-align: right; }
        
        .totals-section {
            margin-top: 20px;
            float: right;
            width: 300px;
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
            background-color: #28a745;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        
        .notes-section {
            clear: both;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        
        .notes-section h4 {
            color: #28a745;
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
            border-top: 2px solid #28a745;
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
        .status-en_transito { background-color: #17a2b8; color: #fff; }
        .status-entregada { background-color: #28a745; color: #fff; }
        .status-devuelta { background-color: #dc3545; color: #fff; }
        .status-cancelada { background-color: #6c757d; color: #fff; }
        
        .tipo-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .tipo-venta { background-color: #007bff; color: #fff; }
        .tipo-traslado { background-color: #17a2b8; color: #fff; }
        .tipo-devolucion { background-color: #ffc107; color: #000; }
        .tipo-muestra { background-color: #6c757d; color: #fff; }
        
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header clearfix">
        <div class="company-info">
            <h1>{{ $empresa->nombre_comercial ?? 'Mi Empresa' }}</h1>
            <p><strong>{{ $empresa->razon_social ?? 'Razón Social' }}</strong></p>
            <p>NIT: {{ $empresa->nit ?? '000000000-0' }}</p>
            <p>{{ $empresa->direccion ?? 'Dirección' }}</p>
            <p>Tel: {{ $empresa->telefono ?? '000000000' }} | Email: {{ $empresa->email ?? 'email@empresa.com' }}</p>
        </div>
        
        <div class="remision-info">
            <h2>REMISIÓN</h2>
            <div class="remision-number">{{ $remision->numero_remision }}</div>
            <p><strong>Fecha:</strong> {{ $remision->fecha_remision->format('d/m/Y') }}</p>
            @if($remision->fecha_entrega)
                <p><strong>Entrega:</strong> {{ $remision->fecha_entrega->format('d/m/Y') }}</p>
            @endif
            <p><strong>Tipo:</strong> 
                <span class="tipo-badge tipo-{{ $remision->tipo }}">
                    {{ ucfirst($remision->tipo) }}
                </span>
            </p>
            <p><strong>Estado:</strong> 
                <span class="status-badge status-{{ $remision->estado }}">
                    {{ str_replace('_', ' ', ucfirst($remision->estado)) }}
                </span>
            </p>
        </div>
    </div>

    <!-- Cliente -->
    <div class="client-section">
        <h3>INFORMACIÓN DEL CLIENTE</h3>
        <div class="client-info">
            <div class="client-left">
                <div class="info-row">
                    <span class="info-label">Cliente:</span>
                    {{ $remision->cliente->nombres }} {{ $remision->cliente->apellidos }}
                </div>
                <div class="info-row">
                    <span class="info-label">Documento:</span>
                    {{ $remision->cliente->cedula }}
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    {{ $remision->cliente->email ?? 'N/A' }}
                </div>
                <div class="info-row">
                    <span class="info-label">Teléfono:</span>
                    {{ $remision->cliente->telefono ?? 'N/A' }}
                </div>
            </div>
            <div class="client-right">
                <div class="info-row">
                    <span class="info-label">Dirección:</span>
                    {{ $remision->cliente->direccion ?? 'N/A' }}
                </div>
                <div class="info-row">
                    <span class="info-label">Dir. Entrega:</span>
                    {{ $remision->direccion_entrega ?? 'Misma dirección' }}
                </div>
                <div class="info-row">
                    <span class="info-label">Vendedor:</span>
                    {{ $remision->vendedor->name ?? 'N/A' }}
                </div>
                @if($remision->venta_id)
                    <div class="info-row">
                        <span class="info-label">Venta:</span>
                        ID {{ $remision->venta_id }}
                    </div>
                @endif
                @if($remision->cotizacion_id)
                    <div class="info-row">
                        <span class="info-label">Cotización:</span>
                        {{ $remision->cotizacion->numero_cotizacion ?? 'N/A' }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Información de Transporte -->
    @if($remision->transportador || $remision->vehiculo || $remision->conductor)
        <div class="transport-section">
            <h3>INFORMACIÓN DE TRANSPORTE</h3>
            <div class="client-info">
                <div class="client-left">
                    @if($remision->transportador)
                        <div class="info-row">
                            <span class="info-label">Transportador:</span>
                            {{ $remision->transportador }}
                        </div>
                    @endif
                    @if($remision->vehiculo)
                        <div class="info-row">
                            <span class="info-label">Vehículo:</span>
                            {{ $remision->vehiculo }}
                        </div>
                    @endif
                </div>
                <div class="client-right">
                    @if($remision->conductor)
                        <div class="info-row">
                            <span class="info-label">Conductor:</span>
                            {{ $remision->conductor }}
                        </div>
                    @endif
                    @if($remision->cedula_conductor)
                        <div class="info-row">
                            <span class="info-label">Cédula:</span>
                            {{ $remision->cedula_conductor }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Productos -->
    <table class="products-table">
        <thead>
            <tr>
                <th style="width: 8%">Código</th>
                <th style="width: 30%">Descripción</th>
                <th style="width: 8%" class="text-center">Cant.</th>
                <th style="width: 8%" class="text-center">Unidad</th>
                <th style="width: 8%" class="text-center">Entregada</th>
                <th style="width: 8%" class="text-center">Pendiente</th>
                @if($remision->total > 0)
                    <th style="width: 10%" class="text-right">Precio Unit.</th>
                    <th style="width: 10%" class="text-right">Subtotal</th>
                    <th style="width: 10%" class="text-right">Total</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($remision->detalles as $detalle)
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
                    <td class="text-center">{{ number_format($detalle->cantidad_entregada, 3) }}</td>
                    <td class="text-center">{{ number_format($detalle->cantidadPendiente(), 3) }}</td>
                    @if($remision->total > 0)
                        <td class="text-right">${{ number_format($detalle->precio_unitario, 0, ',', '.') }}</td>
                        <td class="text-right">${{ number_format($detalle->subtotal, 0, ',', '.') }}</td>
                        <td class="text-right"><strong>${{ number_format($detalle->total, 0, ',', '.') }}</strong></td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totales (solo si hay valores monetarios) -->
    @if($remision->total > 0)
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td><strong>Subtotal:</strong></td>
                    <td class="text-right">${{ number_format($remision->subtotal, 0, ',', '.') }}</td>
                </tr>
                @if($remision->descuento > 0)
                    <tr>
                        <td><strong>Descuento:</strong></td>
                        <td class="text-right">-${{ number_format($remision->descuento, 0, ',', '.') }}</td>
                    </tr>
                @endif
                @if($remision->impuestos > 0)
                    <tr>
                        <td><strong>IVA:</strong></td>
                        <td class="text-right">${{ number_format($remision->impuestos, 0, ',', '.') }}</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td><strong>TOTAL:</strong></td>
                    <td class="text-right"><strong>${{ number_format($remision->total, 0, ',', '.') }}</strong></td>
                </tr>
            </table>
        </div>
    @endif

    <!-- Observaciones -->
    @if($remision->observaciones)
        <div class="notes-section">
            <h4>OBSERVACIONES</h4>
            <div class="notes-content">
                {{ $remision->observaciones }}
            </div>
        </div>
    @endif

    <!-- Información adicional -->
    <div class="notes-section">
        <h4>INFORMACIÓN ADICIONAL</h4>
        <div class="notes-content">
            <p><strong>Tipo de Remisión:</strong> {{ ucfirst($remision->tipo) }}</p>
            <p><strong>Estado Actual:</strong> {{ str_replace('_', ' ', ucfirst($remision->estado)) }}</p>
            <p><strong>Fecha de Generación:</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
            <p><strong>Total de Productos:</strong> {{ $remision->detalles->count() }} ítems</p>
            
            @if($remision->tipo === 'muestra')
                <p style="color: #dc3545; font-weight: bold;">⚠️ MERCANCÍA EN CALIDAD DE MUESTRA - SIN VALOR COMERCIAL</p>
            @endif
        </div>
    </div>

    <!-- Firmas -->
    <div style="margin-top: 60px; clear: both;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 33%; text-align: center; border-top: 1px solid #333; padding-top: 10px;">
                    <strong>ENTREGADO POR</strong><br>
                    <small>Nombre y Firma</small>
                </td>
                <td style="width: 33%; text-align: center; border-top: 1px solid #333; padding-top: 10px;">
                    <strong>RECIBIDO POR</strong><br>
                    <small>Nombre y Firma</small>
                </td>
                <td style="width: 33%; text-align: center; border-top: 1px solid #333; padding-top: 10px;">
                    <strong>FECHA Y HORA</strong><br>
                    <small>___ / ___ / _____ ___:___</small>
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Esta remisión fue generada automáticamente por el sistema de {{ $empresa->nombre_comercial ?? 'Mi Empresa' }}</p>
        <p>Para cualquier consulta, contáctenos al {{ $empresa->telefono ?? '000000000' }} o {{ $empresa->email ?? 'email@empresa.com' }}</p>
    </div>
</body>
</html>
