<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Impuestos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
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
            font-size: 14px;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
        }
        .info-box {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            border-left: 4px solid #4472C4;
            flex: 1;
            margin: 0 5px;
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
        .center {
            text-align: center;
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
        .tax-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .tax-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            text-align: center;
        }
        .tax-amount {
            font-size: 18px;
            font-weight: bold;
            color: #4472C4;
            margin-bottom: 5px;
        }
        .tax-label {
            font-size: 11px;
            color: #666;
        }
        .tax-percentage {
            font-size: 10px;
            color: #28a745;
            margin-top: 3px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #4472C4;
            margin: 25px 0 15px 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .highlight {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Impuestos y Contribuciones</h1>
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
        <div class="info-box">
            <strong>Empleados:</strong> {{ isset($data['details']) ? count($data['details']) : 0 }}
        </div>
    </div>

    @if(isset($data['summary']))
    <div class="section-title">Resumen de Impuestos y Contribuciones</div>
    
    <div class="tax-summary">
        <div class="tax-box">
            <div class="tax-amount">${{ number_format($data['summary']['total_gross_salary'], 2) }}</div>
            <div class="tax-label">Salario Bruto Total</div>
        </div>
        <div class="tax-box">
            <div class="tax-amount">${{ number_format($data['summary']['total_income_tax'], 2) }}</div>
            <div class="tax-label">Retención en la Fuente</div>
            <div class="tax-percentage">{{ number_format(($data['summary']['total_income_tax'] / $data['summary']['total_gross_salary']) * 100, 2) }}%</div>
        </div>
        <div class="tax-box">
            <div class="tax-amount">${{ number_format($data['summary']['total_health_contributions'], 2) }}</div>
            <div class="tax-label">Aportes Salud</div>
            <div class="tax-percentage">{{ number_format(($data['summary']['total_health_contributions'] / $data['summary']['total_gross_salary']) * 100, 2) }}%</div>
        </div>
        <div class="tax-box">
            <div class="tax-amount">${{ number_format($data['summary']['total_pension_contributions'], 2) }}</div>
            <div class="tax-label">Aportes Pensión</div>
            <div class="tax-percentage">{{ number_format(($data['summary']['total_pension_contributions'] / $data['summary']['total_gross_salary']) * 100, 2) }}%</div>
        </div>
    </div>

    <div class="highlight">
        <strong>Total de Impuestos y Contribuciones:</strong> 
        ${{ number_format($data['summary']['total_income_tax'] + $data['summary']['total_health_contributions'] + $data['summary']['total_pension_contributions'], 2) }}
        ({{ number_format((($data['summary']['total_income_tax'] + $data['summary']['total_health_contributions'] + $data['summary']['total_pension_contributions']) / $data['summary']['total_gross_salary']) * 100, 2) }}% del salario bruto)
    </div>
    @endif

    @if(isset($data['details']))
    <div class="section-title">Detalle por Empleado</div>
    
    <table>
        <thead>
            <tr>
                <th>ID Empleado</th>
                <th>Nombre Completo</th>
                <th>Documento</th>
                <th>Período</th>
                <th>Salario Bruto</th>
                <th>Retención Fuente</th>
                <th>Aporte Salud</th>
                <th>Aporte Pensión</th>
                <th>Total Impuestos</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['details'] as $item)
            @php
                $totalTaxes = $item['income_tax'] + $item['health_contribution'] + $item['pension_contribution'];
            @endphp
            <tr>
                <td class="center">{{ $item['employee_id'] }}</td>
                <td>{{ $item['employee_name'] }}</td>
                <td class="center">{{ $item['document_number'] }}</td>
                <td class="center">{{ $item['period'] }}</td>
                <td class="currency">${{ number_format($item['gross_salary'], 2) }}</td>
                <td class="currency">${{ number_format($item['income_tax'], 2) }}</td>
                <td class="currency">${{ number_format($item['health_contribution'], 2) }}</td>
                <td class="currency">${{ number_format($item['pension_contribution'], 2) }}</td>
                <td class="currency"><strong>${{ number_format($totalTaxes, 2) }}</strong></td>
            </tr>
            @endforeach
            
            @if(isset($data['summary']))
            <tr class="total-row">
                <td colspan="4" class="center"><strong>TOTALES</strong></td>
                <td class="currency"><strong>${{ number_format($data['summary']['total_gross_salary'], 2) }}</strong></td>
                <td class="currency"><strong>${{ number_format($data['summary']['total_income_tax'], 2) }}</strong></td>
                <td class="currency"><strong>${{ number_format($data['summary']['total_health_contributions'], 2) }}</strong></td>
                <td class="currency"><strong>${{ number_format($data['summary']['total_pension_contributions'], 2) }}</strong></td>
                <td class="currency"><strong>${{ number_format($data['summary']['total_income_tax'] + $data['summary']['total_health_contributions'] + $data['summary']['total_pension_contributions'], 2) }}</strong></td>
            </tr>
            @endif
        </tbody>
    </table>
    @endif

    <div class="section-title">Información Adicional</div>
    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; font-size: 10px;">
        <p><strong>Notas importantes:</strong></p>
        <ul>
            <li>Los porcentajes de retención pueden variar según la legislación vigente y los rangos salariales.</li>
            <li>Las contribuciones de salud y pensión se calculan sobre el salario base según las tarifas establecidas.</li>
            <li>Este reporte debe ser utilizado únicamente para fines internos y cumplimiento fiscal.</li>
            <li>Para consultas específicas sobre cálculos tributarios, consulte con el departamento de contabilidad.</li>
        </ul>
    </div>

    <div class="footer">
        <p>Reporte generado automáticamente por el Sistema de Nómina - {{ $generated_at }}</p>
        <p>Este documento contiene información fiscal confidencial y debe ser manejado con la debida seguridad.</p>
    </div>
</body>
</html>