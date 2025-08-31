<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nómina Detallada</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 15px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #4472C4;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #4472C4;
            margin: 0;
            font-size: 22px;
        }
        .header .subtitle {
            color: #666;
            margin-top: 5px;
            font-size: 12px;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .info-box {
            background: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            border-left: 3px solid #4472C4;
            font-size: 10px;
        }
        .info-box strong {
            color: #4472C4;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: left;
        }
        th {
            background-color: #4472C4;
            color: white;
            font-weight: bold;
            text-align: center;
            font-size: 8px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .currency {
            text-align: right;
        }
        .center {
            text-align: center;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }
        .page-break {
            page-break-after: always;
        }
        .summary-section {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .summary-title {
            font-weight: bold;
            color: #4472C4;
            margin-bottom: 8px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-value {
            font-weight: bold;
            font-size: 11px;
            color: #333;
        }
        .summary-label {
            font-size: 8px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Nómina Detallada</h1>
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
            <strong>Total Empleados:</strong> {{ count($data) }}
        </div>
    </div>

    @php
        $totalGross = collect($data)->sum('payroll.gross_salary');
        $totalDeductions = collect($data)->sum('payroll.total_deductions');
        $totalTaxes = collect($data)->sum('payroll.total_taxes');
        $totalNet = collect($data)->sum('payroll.net_salary');
        $totalHours = collect($data)->sum('payroll.worked_hours');
        $totalOvertime = collect($data)->sum('payroll.overtime_hours');
    @endphp

    <div class="summary-section">
        <div class="summary-title">Resumen General</div>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-value">${{ number_format($totalGross, 2) }}</div>
                <div class="summary-label">Salario Bruto Total</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">${{ number_format($totalDeductions, 2) }}</div>
                <div class="summary-label">Total Deducciones</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">${{ number_format($totalTaxes, 2) }}</div>
                <div class="summary-label">Total Impuestos</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">${{ number_format($totalNet, 2) }}</div>
                <div class="summary-label">Salario Neto Total</div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No. Emp</th>
                <th>Nombre Completo</th>
                <th>Documento</th>
                <th>Departamento</th>
                <th>Cargo</th>
                <th>Sal. Base</th>
                <th>Días</th>
                <th>Horas</th>
                <th>H. Extra</th>
                <th>Sal. Bruto</th>
                <th>Deducciones</th>
                <th>Impuestos</th>
                <th>Sal. Neto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $item)
            <tr>
                <td class="center">{{ $item['employee']['employee_number'] }}</td>
                <td>{{ $item['employee']['name'] }}</td>
                <td class="center">{{ $item['employee']['document_number'] }}</td>
                <td>{{ $item['employee']['department'] }}</td>
                <td>{{ $item['employee']['position'] }}</td>
                <td class="currency">${{ number_format($item['payroll']['base_salary'], 0) }}</td>
                <td class="center">{{ $item['payroll']['worked_days'] }}</td>
                <td class="center">{{ number_format($item['payroll']['worked_hours'], 1) }}</td>
                <td class="center">{{ number_format($item['payroll']['overtime_hours'], 1) }}</td>
                <td class="currency">${{ number_format($item['payroll']['gross_salary'], 0) }}</td>
                <td class="currency">${{ number_format($item['payroll']['total_deductions'], 0) }}</td>
                <td class="currency">${{ number_format($item['payroll']['total_taxes'], 0) }}</td>
                <td class="currency"><strong>${{ number_format($item['payroll']['net_salary'], 0) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #e8f0fe; font-weight: bold;">
                <td colspan="5" class="center"><strong>TOTALES</strong></td>
                <td class="currency"><strong>${{ number_format(collect($data)->sum('payroll.base_salary'), 0) }}</strong></td>
                <td class="center"><strong>{{ collect($data)->sum('payroll.worked_days') }}</strong></td>
                <td class="center"><strong>{{ number_format($totalHours, 1) }}</strong></td>
                <td class="center"><strong>{{ number_format($totalOvertime, 1) }}</strong></td>
                <td class="currency"><strong>${{ number_format($totalGross, 0) }}</strong></td>
                <td class="currency"><strong>${{ number_format($totalDeductions, 0) }}</strong></td>
                <td class="currency"><strong>${{ number_format($totalTaxes, 0) }}</strong></td>
                <td class="currency"><strong>${{ number_format($totalNet, 0) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Reporte generado automáticamente por el Sistema de Nómina - {{ $generated_at }}</p>
        <p>Este documento es confidencial y contiene información sensible de nómina.</p>
    </div>
</body>
</html>