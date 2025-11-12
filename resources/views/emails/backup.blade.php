<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup de Base de Datos</title>
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
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 0 0 5px 5px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .info-table th,
        .info-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .info-table th {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .alert {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 12px;
            border-radius: 4px;
            margin: 15px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🗄️ Backup de Base de Datos</h1>
        <p>Sistema de Ventas e Inventario</p>
    </div>
    
    <div class="content">
        <p>Estimado usuario,</p>
        
        <p>Se ha generado un nuevo backup de la base de datos del sistema de ventas. A continuación encontrará los detalles:</p>
        
        <table class="info-table">
            <tr>
                <th>Archivo:</th>
                <td>{{ $filename }}</td>
            </tr>
            <tr>
                <th>Tamaño:</th>
                <td>{{ $size }}</td>
            </tr>
            <tr>
                <th>Fecha y Hora:</th>
                <td>{{ $date }}</td>
            </tr>
        </table>
        
        <div class="alert">
            <strong>📎 Archivo Adjunto:</strong> El backup se encuentra adjunto a este correo electrónico.
        </div>
        
        <h3>Recomendaciones de Seguridad:</h3>
        <ul>
            <li>Guarde este backup en un lugar seguro</li>
            <li>No comparta este archivo con personas no autorizadas</li>
            <li>Verifique periódicamente la integridad de sus backups</li>
            <li>Mantenga múltiples copias en diferentes ubicaciones</li>
        </ul>
        
        <h3>¿Cómo restaurar este backup?</h3>
        <p>Para restaurar este backup:</p>
        <ol>
            <li>Acceda al panel de administración del sistema</li>
            <li>Vaya a la sección "Backups"</li>
            <li>Suba el archivo de backup</li>
            <li>Seleccione la opción de restauración deseada</li>
        </ol>
    </div>
    
    <div class="footer">
        <p>Este es un mensaje automático del Sistema de Ventas e Inventario</p>
        <p>Por favor, no responda a este correo electrónico</p>
    </div>
</body>
</html>
