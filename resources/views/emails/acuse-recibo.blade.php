<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acuse de Recibo - Factura Electrónica</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 30px;
            border: 1px solid #dee2e6;
        }
        .footer {
            background-color: #6c757d;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 0 0 5px 5px;
            font-size: 12px;
        }
        .info-box {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 20px 0;
        }
        .success-box {
            background-color: #e8f5e8;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin: 20px 0;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .data-table th, .data-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📧 Acuse de Recibo</h1>
        <h2>Factura Electrónica Recibida</h2>
    </div>
    
    <div class="content">
        <div class="success-box">
            <h3>✅ Factura Recibida Exitosamente</h3>
            <p>Confirmamos la recepción de su factura electrónica en nuestro sistema.</p>
        </div>
        
        <h3>📄 Información de la Factura</h3>
        <table class="data-table">
            <tr>
                <th>Número de Factura</th>
                <td><span class="highlight">{{ $datosFactura['numero_factura'] ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <th>CUFE</th>
                <td><code>{{ $datosFactura['cufe'] ?? 'N/A' }}</code></td>
            </tr>
            <tr>
                <th>Fecha de Factura</th>
                <td>{{ $datosFactura['fecha_factura'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Fecha de Recepción</th>
                <td>{{ isset($email->fecha_email) ? $email->fecha_email->format('d/m/Y H:i:s') : date('d/m/Y H:i:s') }}</td>
            </tr>
            <tr>
                <th>Fecha de Acuse</th>
                <td>{{ $fechaAcuse ?? date('d/m/Y H:i:s') }}</td>
            </tr>
        </table>
        
        <h3>🏢 Información del Proveedor</h3>
        <table class="data-table">
            <tr>
                <th>Nombre/Razón Social</th>
                <td>{{ $datosFactura['proveedor']['nombre'] ?? ($email->remitente_nombre ?? 'N/A') }}</td>
            </tr>
            <tr>
                <th>NIT</th>
                <td>{{ $datosFactura['proveedor']['nit'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $datosFactura['email_proveedor'] ?? ($email->remitente_email ?? 'N/A') }}</td>
            </tr>
        </table>
        
        <h3>🏢 Información del Cliente</h3>
        <table class="data-table">
            <tr>
                <th>Nombre/Razón Social</th>
                <td>{{ $empresa->nombre ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>NIT</th>
                <td>{{ $empresa->nit ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $email->cuenta_email ?? 'N/A' }}</td>
            </tr>
        </table>
        
        @if(isset($datosFactura['totales']) && $datosFactura['totales']['total'] > 0)
        <h3>💰 Totales de la Factura</h3>
        <table class="data-table">
            <tr>
                <th>Subtotal</th>
                <td>${{ number_format($datosFactura['totales']['subtotal'], 2) }}</td>
            </tr>
            <tr>
                <th>IVA</th>
                <td>${{ number_format($datosFactura['totales']['iva'], 2) }}</td>
            </tr>
            <tr>
                <th>Total</th>
                <td><strong>${{ number_format($datosFactura['totales']['total'], 2) }}</strong></td>
            </tr>
        </table>
        @endif
        
        <div class="info-box">
            <h4>📋 Detalles del Procesamiento</h4>
            <ul>
                <li><strong>Email Original:</strong> {{ $email->asunto ?? 'N/A' }}</li>
                <li><strong>Archivos Adjuntos:</strong> {{ count($email->archivos_adjuntos ?? []) }}</li>
                <li><strong>Estado:</strong> {{ ucfirst($email->estado ?? 'procesado') }}</li>
                <li><strong>ID del Email:</strong> {{ $email->id ?? 'N/A' }}</li>
                <li><strong>Mensaje ID:</strong> {{ $email->mensaje_id ?? 'N/A' }}</li>
            </ul>
        </div>
        
        <div class="success-box">
            <h4>✅ Confirmación</h4>
            <p>Este acuse de recibo confirma que:</p>
            <ul>
                <li>✅ Su factura electrónica ha sido recibida correctamente</li>
                <li>✅ El CUFE ha sido validado y registrado</li>
                <li>✅ Los datos han sido procesados en nuestro sistema</li>
                <li>✅ Se ha generado este acuse automáticamente</li>
            </ul>
        </div>
        
        <p><strong>Nota:</strong> Este es un acuse de recibo automático generado por nuestro sistema de procesamiento de facturas electrónicas. No requiere respuesta.</p>
    </div>
    
    <div class="footer">
        <p><strong>{{ $empresa->nombre ?? 'Sistema DIAN' }}</strong></p>
        <p>Sistema Automático de Procesamiento de Facturas Electrónicas</p>
        <p>Generado automáticamente el {{ $fechaAcuse ?? date('d/m/Y H:i:s') }}</p>
        <p>Este email fue enviado desde: {{ $email->cuenta_email ?? 'sistema@empresa.com' }}</p>
    </div>
</body>
</html>
