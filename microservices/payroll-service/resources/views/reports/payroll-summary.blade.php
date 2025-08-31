<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen de Nómina</title>
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
            border-bottom: 2px solid #4472C4;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #4472C4;
            margin: 0;
            font-size: 24px;
        }
        .header .subtitle {
            color: #666;
            margin-top: 5px;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .info-box {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #4472C4;
        }
        .info-box strong {
            color: #4472C4;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #4472C4;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .currency {
            text-align: right;
        }
        .total-row {
            background-color: #e8f0fe !important;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .summary-stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
        }
        .stat-box {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .stat-number {
            font-size: 18px;
            font-weight: bold;
            color: #4472C4;
        }
        .stat-label {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Resumen de Nómina</h1>
        <div class="subtitle">{{ $report->report_name }}</div>
    </div>

    <div class="info-section">
        <div class="info-box">
            <strong>Período:</strong> {{ $report->period_start }} - {{ $report->period_end }}
        </div>
        <div class="info-box">
            <strong>Generado:</strong> {{ $generated_at }}
        </div>
        <div class="info-box">
            <strong>Estado:</strong> {{ ucfirst($report->status) }}
        </div>
    </div>

    @if(isset($data['totals']))
    <div class="summary-stats">
        <div class="stat-box">
            <div class="stat-number">{{ $data['totals']['employees'] }}</div>
            <div class="stat-label">Total Empleados</div>
        </div>
        <div class="stat-box">
            <div class="stat-number">${{ number_format($data['totals']['gross_salary'], 2) }}</div>
            <div class="stat-label">Salario Bruto Total</div>
        </div>
        <div class="stat-box">
            <div class="stat-number">${{ number_format($data['totals']['total_deductions'], 2) }}</div>
            <div class="stat-label">Total Deducciones</div>
        </div>
        <div class="stat-box">
            <div class="stat-number">${{ number_format($data['totals']['net_salary'], 2) }}</div>
            <div class="stat-label">Salario Neto Total</div>
        </div>
    </div>
    @endif

    @if(isset($data['by_department']))
    <table>
        <thead>
            <tr>
                <th>Departamento</th>
                <th>Empleados</th>
                <th>Salario Bruto</th>
                <th>Deducciones</th>
                <th>Impuestos</th>
                <th>Salario Neto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['by_department'] as $department => $values)
            <tr>
                <td>{{ $department }}</td>
                <td style="text-align: center;">{{ $values['employees'] }}</td>
                <td class="currency">${{ number_format($values['gross_salary'], 2) }}</td>
                <td class="currency">${{ number_format($values['total_deductions'], 2) }}</td>
                <td class="currency">${{ number_format($values['total_taxes'], 2) }}</td>
                <td class="currency">${{ number_format($values['net_salary'], 2) }}</td>
            </tr>
            @endforeach
            @if(isset($data['totals']))
            <tr class="total-row">
                <td><strong>TOTAL</strong></td>
                <td style="text-align: center;"><strong>{{ $data['totals']['employees'] }}</strong></td>
                <td class="currency"><strong>${{ number_format($data['totals']['gross_salary'], 2) }}</strong></td>
                <td class="currency"><strong>${{ number_format($data['totals']['total_deductions'], 2) }}</strong></td>
                <td class="currency"><strong>${{ number_format($data['totals']['total_taxes'], 2) }}</strong></td>
                <td class="currency"><strong>${{ number_format($data['totals']['net_salary'], 2) }}</strong></td>
            </tr>
            @endif
        </tbody>
    </table>
    @endif

    <div class="footer">
        <p>Reporte generado automáticamente por el Sistema de Nómina - {{ $generated_at }}</p>
        <p>Este documento es confidencial y está destinado únicamente para uso interno de la organización.</p>
    </div>
</body>
</html>