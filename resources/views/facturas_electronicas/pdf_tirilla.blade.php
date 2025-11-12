<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Electrónica - {{ $detallesAlegra['numberTemplate']['fullNumber'] ?? $venta->numero_factura }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            line-height: 1.2;
            width: 80mm;
            margin: 0;
            padding: 2mm;
        }
        
        .header {
            text-align: center;
            margin-bottom: 3mm;
            border-bottom: 1px dashed #000;
            padding-bottom: 2mm;
        }
        
        .empresa-nombre {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 1mm;
        }
        
        .empresa-info {
            font-size: 8px;
            line-height: 1.1;
        }
        
        .factura-info {
            margin: 3mm 0;
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 2mm;
        }
        
        .factura-numero {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 1mm;
        }
        
        .cliente-info {
            margin: 3mm 0;
            border-bottom: 1px dashed #000;
            padding-bottom: 2mm;
        }
        
        .cliente-nombre {
            font-weight: bold;
            margin-bottom: 1mm;
        }
        
        .productos {
            margin: 3mm 0;
        }
        
        .producto {
            margin-bottom: 2mm;
            border-bottom: 1px dotted #ccc;
            padding-bottom: 1mm;
        }
        
        .producto-nombre {
            font-weight: bold;
            margin-bottom: 0.5mm;
        }
        
        .producto-detalle {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
        }
        
        .totales {
            margin-top: 3mm;
            border-top: 1px dashed #000;
            padding-top: 2mm;
        }
        
        .total-linea {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1mm;
        }
        
        .total-final {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            padding-top: 1mm;
            margin-top: 2mm;
        }
        
        .dian-info {
            margin-top: 3mm;
            border-top: 1px dashed #000;
            padding-top: 2mm;
            font-size: 8px;
            text-align: center;
        }
        
        .qr-code {
            text-align: center;
            margin: 3mm 0;
        }
        
        .qr-code img {
            width: 35mm;
            height: 35mm;
            max-width: 100%;
        }
        
        .cufe {
            font-size: 6px;
            word-break: break-all;
            text-align: left;
            margin: 2mm 0;
            line-height: 1.1;
            font-family: monospace;
        }
        
        .footer {
            margin-top: 5mm;
            text-align: center;
            font-size: 8px;
            border-top: 1px dashed #000;
            padding-top: 2mm;
        }
        
        .flex-between {
            display: flex;
            justify-content: space-between;
        }
        
        .text-right {
            text-align: right;
        }
        
        .small {
            font-size: 8px;
        }
    </style>
</head>
<body>
    <!-- Header Empresa -->
    <div class="header">
        @if(isset($empresa) && $empresa->logo)
            @php
                $logoPath = storage_path('app/public/' . $empresa->logo);
                $logoBase64 = '';
                if(file_exists($logoPath)) {
                    $logoData = file_get_contents($logoPath);
                    $logoBase64 = 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoData);
                }
            @endphp
            @if($logoBase64)
                <div style="text-align: center; margin-bottom: 2mm;">
                    <img src="{{ $logoBase64 }}" 
                         alt="Logo" 
                         style="max-width: 50mm; max-height: 20mm; height: auto;">
                </div>
            @endif
        @endif
        <div class="empresa-nombre">{{ $empresa->nombre ?? 'INTERVEREDANET.CR' }}</div>
        <div class="empresa-info">
            @if($empresa->nit)
                NIT: {{ $empresa->nit }}<br>
            @else
                NIT: 8437347-6<br>
            @endif
            @if($empresa->direccion)
                {{ $empresa->direccion }}<br>
            @else
                Carrera 112a # 90a-10<br>
            @endif
            @if($empresa->telefono)
                Tel: {{ $empresa->telefono }}<br>
            @else
                Tel: 3012491020<br>
            @endif
            @if($empresa->email)
                {{ $empresa->email }}
            @else
                pcapacho24@gmail.com
            @endif
        </div>
    </div>
    
    <!-- Información de la Factura -->
    <div class="factura-info">
        <div class="factura-numero">
            FACTURA ELECTRÓNICA<br>
            {{ $detallesAlegra['numberTemplate']['fullNumber'] ?? $venta->numero_factura }}
        </div>
        <div class="small">
            Fecha: {{ \Carbon\Carbon::parse($detallesAlegra['date'] ?? $venta->fecha_venta)->format('d/m/Y H:i') }}<br>
            @if(isset($detallesAlegra['numberTemplate']['text']))
                {{ $detallesAlegra['numberTemplate']['text'] }}
            @endif
        </div>
    </div>
    
    <!-- Información del Cliente -->
    <div class="cliente-info">
        <div class="cliente-nombre">
            {{ $detallesAlegra['client']['name'] ?? $cliente->nombres }}
        </div>
        <div class="small">
            {{ $detallesAlegra['client']['identificationType'] ?? 'CC' }}: {{ $detallesAlegra['client']['identification'] ?? $cliente->cedula }}<br>
            @if(isset($detallesAlegra['client']['address']['address']))
                {{ $detallesAlegra['client']['address']['address'] }}<br>
            @endif
            @if(isset($detallesAlegra['client']['address']['city']))
                {{ $detallesAlegra['client']['address']['city'] }}
            @endif
        </div>
    </div>
    
    <!-- Productos -->
    <div class="productos">
        @foreach($detallesAlegra['items'] ?? $detalles as $item)
            <div class="producto">
                <div class="producto-nombre">
                    {{ $item['name'] ?? $item->producto->nombre }}
                </div>
                <div class="producto-detalle">
                    <span>
                        {{ $item['quantity'] ?? $item->cantidad }} x 
                        ${{ number_format($item['price'] ?? $item->precio, 0, ',', '.') }}
                    </span>
                    <span class="text-right">
                        ${{ number_format($item['total'] ?? ($item->cantidad * $item->precio), 0, ',', '.') }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>
    
    <!-- Totales -->
    <div class="totales">
        <div class="total-linea flex-between">
            <span>Subtotal:</span>
            <span>${{ number_format($detallesAlegra['subtotal'] ?? $venta->subtotal, 0, ',', '.') }}</span>
        </div>
        
        @if(($detallesAlegra['tax'] ?? $venta->iva) > 0)
            <div class="total-linea flex-between">
                <span>IVA:</span>
                <span>${{ number_format($detallesAlegra['tax'] ?? $venta->iva, 0, ',', '.') }}</span>
            </div>
        @endif
        
        <div class="total-final flex-between">
            <span>TOTAL:</span>
            <span>${{ number_format($detallesAlegra['total'] ?? $venta->total, 0, ',', '.') }}</span>
        </div>
    </div>
    
    <!-- Información DIAN -->
    @php
        // Los datos vienen como $detallesAlegra (que ya es ['data'] del controlador)
        $cufe = $venta->cufe ?? ($detallesAlegra['stamp']['cufe'] ?? null);
        $qrCode = $venta->qr_code ?? ($detallesAlegra['stamp']['barCodeContent'] ?? null);
        
        // Log para debug (se puede quitar después)
        \Log::info('Tirilla PDF - Datos QR:', [
            'venta_qr' => $venta->qr_code ? 'Presente ('.strlen($venta->qr_code).' chars)' : 'No presente',
            'venta_cufe' => $venta->cufe ? substr($venta->cufe, 0, 20).'...' : 'No presente',
            'alegra_stamp_exists' => isset($detallesAlegra['stamp']),
            'alegra_qr' => isset($detallesAlegra['stamp']['barCodeContent']) ? 'Presente ('.strlen($detallesAlegra['stamp']['barCodeContent']).' chars)' : 'No presente',
            'alegra_cufe' => isset($detallesAlegra['stamp']['cufe']) ? substr($detallesAlegra['stamp']['cufe'], 0, 20).'...' : 'No presente',
        ]);
    @endphp
    
    @if($cufe || $qrCode || $venta->estado_dian)
        <div class="dian-info">
            <div><strong>INFORMACIÓN DIAN</strong></div>
            
            @if($cufe)
                <div class="cufe">
                    <strong>CUFE:</strong><br>
                    @php
                        // Dividir CUFE en líneas de 25 caracteres para mejor legibilidad
                        $cufeLineas = str_split($cufe, 25);
                    @endphp
                    @foreach($cufeLineas as $linea)
                        {{ $linea }}<br>
                    @endforeach
                </div>
            @endif
            
            <!-- QR Code -->
            @if($cufe)
                <div class="qr-code">
                    @if(isset($qrImagePath) && file_exists($qrImagePath))
                        <!-- Usar QR desde archivo temporal (mejor compatibilidad con DomPDF) -->
                        <img src="{{ $qrImagePath }}" alt="Código QR" style="width: 30mm; height: 30mm;">
                    @elseif($venta->qr_code)
                        <!-- Fallback: intentar con base64 -->
                        <img src="data:image/png;base64,{{ $venta->qr_code }}" alt="Código QR" style="width: 30mm; height: 30mm;">
                    @elseif(isset($detallesAlegra['stamp']['barCodeContent']))
                        <!-- Fallback: usar QR de Alegra -->
                        <img src="data:image/png;base64,{{ $detallesAlegra['stamp']['barCodeContent'] }}" alt="Código QR" style="width: 30mm; height: 30mm;">
                    @else
                        <!-- Mostrar texto indicativo si no hay QR disponible -->
                        <div style="border: 1px solid #000; padding: 3mm; text-align: center; font-size: 8px; width: 30mm; height: 30mm; display: flex; align-items: center; justify-content: center;">
                            <div>
                                CÓDIGO QR<br>
                                CUFE: {{ substr($cufe, 0, 10) }}...
                            </div>
                        </div>
                    @endif
                </div>
            @endif
            
            @if($venta->estado_dian)
                <div>Estado DIAN: {{ ucfirst($venta->estado_dian) }}</div>
            @endif
            
            @if(isset($detallesAlegra['numberTemplate']['text']))
                <div class="small">
                    {{ $detallesAlegra['numberTemplate']['text'] }}
                </div>
            @endif
        </div>
    @endif
    
    <!-- Footer -->
    <div class="footer">
        <div>¡Gracias por su compra!</div>
        <div class="small">
            Factura generada el {{ now()->format('d/m/Y H:i') }}<br>
            Sistema de Facturación Electrónica
        </div>
    </div>
</body>
</html>
