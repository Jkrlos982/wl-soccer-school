<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Financiero</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 24px;
        }
        .period {
            text-align: center;
            margin-bottom: 20px;
            font-style: italic;
            color: #666;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            background-color: #f8f9fa;
            padding: 8px 12px;
            border-left: 4px solid #007bff;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .amount {
            text-align: right;
        }
        .positive {
            color: #28a745;
        }
        .negative {
            color: #dc3545;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>
            @switch($reportType)
                @case('income_statement')
                    Estado de Resultados
                    @break
                @case('cash_flow')
                    Flujo de Caja
                    @break
                @case('balance_sheet')
                    Balance General
                    @break
                @case('summary')
                    Resumen Financiero
                    @break
                @default
                    Reporte Financiero
            @endswitch
        </h1>
    </div>

    @if($reportType === 'income_statement')
        <div class="period">
            Período: {{ $reportData['period']['start_date'] }} - {{ $reportData['period']['end_date'] }}
        </div>

        <div class="section">
            <div class="section-title">INGRESOS</div>
            <table>
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th class="amount">Monto</th>
                        <th class="amount">Transacciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['incomes']['details'] as $income)
                    <tr>
                        <td>{{ $income['concept'] }}</td>
                        <td class="amount positive">${{ number_format($income['amount'], 2) }}</td>
                        <td class="amount">{{ $income['transactions_count'] }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td>Total Ingresos</td>
                        <td class="amount positive">${{ number_format($reportData['incomes']['total'], 2) }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">GASTOS</div>
            <table>
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th class="amount">Monto</th>
                        <th class="amount">Transacciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['expenses']['details'] as $expense)
                    <tr>
                        <td>{{ $expense['concept'] }}</td>
                        <td class="amount negative">${{ number_format($expense['amount'], 2) }}</td>
                        <td class="amount">{{ $expense['transactions_count'] }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td>Total Gastos</td>
                        <td class="amount negative">${{ number_format($reportData['expenses']['total'], 2) }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <table>
                <tr class="total-row">
                    <td>Utilidad Neta</td>
                    <td class="amount {{ $reportData['net_income'] >= 0 ? 'positive' : 'negative' }}">
                        ${{ number_format($reportData['net_income'], 2) }}
                    </td>
                </tr>
                <tr>
                    <td>Margen de Utilidad</td>
                    <td class="amount">{{ $reportData['profit_margin'] }}%</td>
                </tr>
            </table>
        </div>

    @elseif($reportType === 'cash_flow')
        <div class="period">
            Período: {{ $reportData['period']['start_date'] }} - {{ $reportData['period']['end_date'] }}
        </div>

        <div class="section">
            <div class="section-title">ACTIVIDADES OPERATIVAS</div>
            <table>
                <tr>
                    <td>Entradas</td>
                    <td class="amount positive">${{ number_format($reportData['operating_activities']['inflows'], 2) }}</td>
                </tr>
                <tr>
                    <td>Salidas</td>
                    <td class="amount negative">${{ number_format($reportData['operating_activities']['outflows'], 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>Flujo Neto Operativo</td>
                    <td class="amount {{ $reportData['operating_activities']['net_cash_flow'] >= 0 ? 'positive' : 'negative' }}">
                        ${{ number_format($reportData['operating_activities']['net_cash_flow'], 2) }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">ACTIVIDADES DE INVERSIÓN</div>
            <table>
                <tr>
                    <td>Entradas</td>
                    <td class="amount positive">${{ number_format($reportData['investment_activities']['inflows'], 2) }}</td>
                </tr>
                <tr>
                    <td>Salidas</td>
                    <td class="amount negative">${{ number_format($reportData['investment_activities']['outflows'], 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>Flujo Neto de Inversión</td>
                    <td class="amount {{ $reportData['investment_activities']['net_cash_flow'] >= 0 ? 'positive' : 'negative' }}">
                        ${{ number_format($reportData['investment_activities']['net_cash_flow'], 2) }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">ACTIVIDADES DE FINANCIAMIENTO</div>
            <table>
                <tr>
                    <td>Entradas</td>
                    <td class="amount positive">${{ number_format($reportData['financing_activities']['inflows'], 2) }}</td>
                </tr>
                <tr>
                    <td>Salidas</td>
                    <td class="amount negative">${{ number_format($reportData['financing_activities']['outflows'], 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>Flujo Neto de Financiamiento</td>
                    <td class="amount {{ $reportData['financing_activities']['net_cash_flow'] >= 0 ? 'positive' : 'negative' }}">
                        ${{ number_format($reportData['financing_activities']['net_cash_flow'], 2) }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <table>
                <tr class="total-row">
                    <td><strong>Flujo de Caja Neto</strong></td>
                    <td class="amount {{ $reportData['net_cash_flow'] >= 0 ? 'positive' : 'negative' }}">
                        <strong>${{ number_format($reportData['net_cash_flow'], 2) }}</strong>
                    </td>
                </tr>
            </table>
        </div>

    @elseif($reportType === 'balance_sheet')
        <div class="period">
            Fecha: {{ $reportData['date'] }}
        </div>

        <div class="section">
            <div class="section-title">ACTIVOS</div>
            <table>
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th class="amount">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['assets']['details'] as $asset)
                    <tr>
                        <td>{{ $asset['concept'] }}</td>
                        <td class="amount">${{ number_format($asset['balance'], 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td>Total Activos</td>
                        <td class="amount">${{ number_format($reportData['assets']['total'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">PASIVOS</div>
            <table>
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th class="amount">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['liabilities']['details'] as $liability)
                    <tr>
                        <td>{{ $liability['concept'] }}</td>
                        <td class="amount">${{ number_format($liability['balance'], 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td>Total Pasivos</td>
                        <td class="amount">${{ number_format($reportData['liabilities']['total'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">PATRIMONIO</div>
            <table>
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th class="amount">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['equity']['details'] as $equity)
                    <tr>
                        <td>{{ $equity['concept'] }}</td>
                        <td class="amount">${{ number_format($equity['balance'], 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td>Total Patrimonio</td>
                        <td class="amount">${{ number_format($reportData['equity']['total'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <table>
                <tr class="total-row">
                    <td>Total Pasivos + Patrimonio</td>
                    <td class="amount">${{ number_format($reportData['total_liabilities_and_equity'], 2) }}</td>
                </tr>
                <tr>
                    <td>Balance Cuadrado</td>
                    <td class="amount {{ $reportData['balance_check'] ? 'positive' : 'negative' }}">
                        {{ $reportData['balance_check'] ? 'Sí' : 'No' }}
                    </td>
                </tr>
            </table>
        </div>

    @elseif($reportType === 'summary')
        <div class="period">
            Período: {{ $reportData['period']['start_date'] }} - {{ $reportData['period']['end_date'] }}
        </div>

        <div class="section">
            <div class="section-title">MÉTRICAS FINANCIERAS</div>
            <table>
                <tr>
                    <td>Total Transacciones</td>
                    <td class="amount">{{ number_format($reportData['metrics']['total_transactions']) }}</td>
                </tr>
                <tr>
                    <td>Total Ingresos</td>
                    <td class="amount positive">${{ number_format($reportData['metrics']['total_income'], 2) }}</td>
                </tr>
                <tr>
                    <td>Total Gastos</td>
                    <td class="amount negative">${{ number_format($reportData['metrics']['total_expenses'], 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>Utilidad Neta</td>
                    <td class="amount {{ $reportData['metrics']['net_income'] >= 0 ? 'positive' : 'negative' }}">
                        ${{ number_format($reportData['metrics']['net_income'], 2) }}
                    </td>
                </tr>
                <tr>
                    <td>Transacción Promedio</td>
                    <td class="amount">${{ number_format($reportData['metrics']['average_transaction'], 2) }}</td>
                </tr>
            </table>
        </div>
    @endif

    <div class="footer">
        <p>Reporte generado el {{ date('d/m/Y H:i:s') }}</p>
        <p>Sistema de Gestión Financiera - WL School</p>
    </div>
</body>
</html>