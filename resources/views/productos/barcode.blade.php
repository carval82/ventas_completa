
<!DOCTYPE html>
<html>
<head>
    <title>Código de Barras - {{ $producto->nombre }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: 8pt;
        }
        .page {
            width: 210mm; /* Ancho de página A4 */
            padding: 5mm;
        }
        .etiquetas-fila {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 0;
        }
        .etiqueta {
            width: 50mm;
            height: 25mm;
            padding: 1mm;
            margin: 0;
            text-align: center;
            display: inline-block;
            overflow: hidden;
        }
        .nombre-producto {
            font-size: 7pt;
            margin-bottom: 1mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .barcode {
            height: 12mm;
            margin: 1mm 0;
        }
        .barcode img {
            max-height: 100%;
            width: auto;
        }
        .codigo {
            font-size: 7pt;
            margin-bottom: 1mm;
        }
        .precio {
            font-weight: bold;
            font-size: 8pt;
        }
        @media print {
            @page {
                size: A4;
                margin: 0;
            }
            .page {
                page-break-after: always;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        @for ($i = 0; $i < 40; $i += 2) {{-- 40 etiquetas por página, 2 columnas --}}
            <div class="etiquetas-fila">
                <div class="etiqueta">
                    <div class="nombre-producto">{{ $producto->nombre }}</div>
                    <div class="barcode">
                        <img src="data:image/png;base64,{{ base64_encode($producto->generarCodigoBarras()) }}">
                    </div>
                    <div class="codigo">{{ $producto->codigo }}</div>
                    <div class="precio">${{ number_format($producto->precio_venta, 2) }}</div>
                </div>
                <div class="etiqueta">
                    <div class="nombre-producto">{{ $producto->nombre }}</div>
                    <div class="barcode">
                        <img src="data:image/png;base64,{{ base64_encode($producto->generarCodigoBarras()) }}">
                    </div>
                    <div class="codigo">{{ $producto->codigo }}</div>
                    <div class="precio">${{ number_format($producto->precio_venta, 2) }}</div>
                </div>
            </div>
        @endfor
    </div>
</body>
</html>