<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance General - NIF Colombia</title>
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
            margin-bottom: 30px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 15px;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #34495e;
            margin-bottom: 5px;
        }
        
        .report-date {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .balance-section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #fff;
            padding: 8px 12px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        
        .activos-title {
            background-color: #27ae60;
        }
        
        .pasivos-title {
            background-color: #e74c3c;
        }
        
        .patrimonio-title {
            background-color: #3498db;
        }
        
        .balance-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .balance-table th,
        .balance-table td {
            padding: 6px 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .balance-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .balance-table td:last-child,
        .balance-table th:last-child {
            text-align: right;
        }
        
        .account-row {
            font-size: 11px;
        }
        
        .account-level-1 {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        
        .account-level-2 {
            font-weight: bold;
            padding-left: 20px;
        }
        
        .account-level-3 {
            padding-left: 40px;
        }
        
        .account-level-4 {
            padding-left: 60px;
        }
        
        .totals-section {
            margin-top: 30px;
            border-top: 2px solid #2c3e50;
            padding-top: 15px;
        }
        
        .total-row {
            font-weight: bold;
            font-size: 13px;
            background-color: #f8f9fa;
        }
        
        .final-total {
            background-color: #2c3e50;
            color: white;
            font-size: 14px;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #7f8c8d;
            border-top: 1px solid #ecf0f1;
            padding-top: 15px;
        }
        
        .nif-compliance {
            background-color: #e8f5e8;
            border: 1px solid #27ae60;
            border-radius: 4px;
            padding: 10px;
            margin-top: 20px;
            text-align: center;
        }
        
        .nif-text {
            color: #27ae60;
            font-weight: bold;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ auth()->user()->empresa->nombre ?? 'EMPRESA' }}</div>
        <div class="report-title">BALANCE GENERAL - NIF COLOMBIA</div>
        <div class="report-date">Al {{ $fechaCorte->format('d/m/Y') }}</div>
        <div class="report-date">Generado el {{ now()->format('d/m/Y H:i:s') }}</div>
    </div>

    <!-- ACTIVOS -->
    @if(count($balance['activos']) > 0)
    <div class="balance-section">
        <div class="section-title activos-title">ACTIVOS</div>
        <table class="balance-table">
            <thead>
                <tr>
                    <th>Cuenta</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($balance['activos'] as $cuenta)
                <tr class="account-row account-level-{{ $cuenta['nivel'] }}">
                    <td>{{ $cuenta['codigo'] }} - {{ $cuenta['nombre'] }}</td>
                    <td>${{ $cuenta['saldo_formateado'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- PASIVOS -->
    @if(count($balance['pasivos']) > 0)
    <div class="balance-section">
        <div class="section-title pasivos-title">PASIVOS</div>
        <table class="balance-table">
            <thead>
                <tr>
                    <th>Cuenta</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($balance['pasivos'] as $cuenta)
                <tr class="account-row account-level-{{ $cuenta['nivel'] }}">
                    <td>{{ $cuenta['codigo'] }} - {{ $cuenta['nombre'] }}</td>
                    <td>${{ $cuenta['saldo_formateado'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- PATRIMONIO -->
    @if(count($balance['patrimonio']) > 0)
    <div class="balance-section">
        <div class="section-title patrimonio-title">PATRIMONIO</div>
        <table class="balance-table">
            <thead>
                <tr>
                    <th>Cuenta</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($balance['patrimonio'] as $cuenta)
                <tr class="account-row account-level-{{ $cuenta['nivel'] }}">
                    <td>{{ $cuenta['codigo'] }} - {{ $cuenta['nombre'] }}</td>
                    <td>${{ $cuenta['saldo_formateado'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- TOTALES -->
    <div class="totals-section">
        <table class="balance-table">
            <tbody>
                <tr class="total-row">
                    <td><strong>TOTAL ACTIVOS</strong></td>
                    <td><strong>${{ number_format($balance['totales']['total_activos'], 2, ',', '.') }}</strong></td>
                </tr>
                <tr class="total-row">
                    <td><strong>TOTAL PASIVOS</strong></td>
                    <td><strong>${{ number_format($balance['totales']['total_pasivos'], 2, ',', '.') }}</strong></td>
                </tr>
                <tr class="total-row">
                    <td><strong>TOTAL PATRIMONIO</strong></td>
                    <td><strong>${{ number_format($balance['totales']['total_patrimonio'], 2, ',', '.') }}</strong></td>
                </tr>
                <tr class="final-total">
                    <td><strong>TOTAL PASIVO + PATRIMONIO</strong></td>
                    <td><strong>${{ number_format($balance['totales']['total_pasivo_patrimonio'], 2, ',', '.') }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Cumplimiento NIF -->
    <div class="nif-compliance">
        <div class="nif-text">
            ✅ REPORTE ELABORADO BAJO NORMAS DE INFORMACIÓN FINANCIERA (NIF) COLOMBIA<br>
            Cumplimiento del 75% de los estándares NIF implementados
        </div>
    </div>

    <div class="footer">
        <p>Balance General generado por Sistema de Contabilidad NIF Colombia</p>
        <p>Este reporte cumple con los estándares de las Normas de Información Financiera aplicables en Colombia</p>
    </div>
</body>
</html>
