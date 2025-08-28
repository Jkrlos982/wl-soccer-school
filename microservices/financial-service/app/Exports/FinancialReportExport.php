<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;

class FinancialReportExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    private array $data;
    private string $reportType;

    public function __construct(array $data, string $reportType)
    {
        $this->data = $data;
        $this->reportType = $reportType;
    }

    public function array(): array
    {
        switch ($this->reportType) {
            case 'income_statement':
                return $this->formatIncomeStatement();
            case 'cash_flow':
                return $this->formatCashFlow();
            case 'balance_sheet':
                return $this->formatBalanceSheet();
            case 'summary':
                return $this->formatSummary();
            default:
                return [];
        }
    }

    public function headings(): array
    {
        switch ($this->reportType) {
            case 'income_statement':
                return ['Estado de Resultados', '', ''];
            case 'cash_flow':
                return ['Flujo de Caja', '', ''];
            case 'balance_sheet':
                return ['Balance General', '', ''];
            case 'summary':
                return ['Resumen Financiero', '', ''];
            default:
                return [];
        }
    }

    public function title(): string
    {
        $titles = [
            'income_statement' => 'Estado de Resultados',
            'cash_flow' => 'Flujo de Caja',
            'balance_sheet' => 'Balance General',
            'summary' => 'Resumen Financiero'
        ];

        return $titles[$this->reportType] ?? 'Reporte Financiero';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para el título
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 16,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            // Estilo para encabezados de sección
            'A:C' => [
                'font' => [
                    'bold' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ],
        ];
    }

    private function formatIncomeStatement(): array
    {
        $rows = [];
        
        // Período
        $rows[] = ['Período:', $this->data['period']['start_date'] . ' - ' . $this->data['period']['end_date'], ''];
        $rows[] = ['', '', ''];
        
        // Ingresos
        $rows[] = ['INGRESOS', '', ''];
        $rows[] = ['Concepto', 'Monto', 'Transacciones'];
        foreach ($this->data['incomes']['details'] as $income) {
            $rows[] = [$income['concept'], $income['amount'], $income['transactions_count']];
        }
        $rows[] = ['Total Ingresos', $this->data['incomes']['total'], ''];
        $rows[] = ['', '', ''];
        
        // Gastos
        $rows[] = ['GASTOS', '', ''];
        $rows[] = ['Concepto', 'Monto', 'Transacciones'];
        foreach ($this->data['expenses']['details'] as $expense) {
            $rows[] = [$expense['concept'], $expense['amount'], $expense['transactions_count']];
        }
        $rows[] = ['Total Gastos', $this->data['expenses']['total'], ''];
        $rows[] = ['', '', ''];
        
        // Resultados
        $rows[] = ['Utilidad Neta', $this->data['net_income'], ''];
        $rows[] = ['Margen de Utilidad', $this->data['profit_margin'] . '%', ''];
        
        return $rows;
    }

    private function formatCashFlow(): array
    {
        $rows = [];
        
        // Período
        $rows[] = ['Período:', $this->data['period']['start_date'] . ' - ' . $this->data['period']['end_date'], ''];
        $rows[] = ['', '', ''];
        
        // Actividades Operativas
        $rows[] = ['ACTIVIDADES OPERATIVAS', '', ''];
        $rows[] = ['Entradas', $this->data['operating_activities']['inflows'], ''];
        $rows[] = ['Salidas', $this->data['operating_activities']['outflows'], ''];
        $rows[] = ['Flujo Neto', $this->data['operating_activities']['net_cash_flow'], ''];
        $rows[] = ['', '', ''];
        
        // Actividades de Inversión
        $rows[] = ['ACTIVIDADES DE INVERSIÓN', '', ''];
        $rows[] = ['Entradas', $this->data['investment_activities']['inflows'], ''];
        $rows[] = ['Salidas', $this->data['investment_activities']['outflows'], ''];
        $rows[] = ['Flujo Neto', $this->data['investment_activities']['net_cash_flow'], ''];
        $rows[] = ['', '', ''];
        
        // Actividades de Financiamiento
        $rows[] = ['ACTIVIDADES DE FINANCIAMIENTO', '', ''];
        $rows[] = ['Entradas', $this->data['financing_activities']['inflows'], ''];
        $rows[] = ['Salidas', $this->data['financing_activities']['outflows'], ''];
        $rows[] = ['Flujo Neto', $this->data['financing_activities']['net_cash_flow'], ''];
        $rows[] = ['', '', ''];
        
        // Flujo Total
        $rows[] = ['Flujo de Caja Neto', $this->data['net_cash_flow'], ''];
        
        return $rows;
    }

    private function formatBalanceSheet(): array
    {
        $rows = [];
        
        // Fecha
        $rows[] = ['Fecha:', $this->data['date'], ''];
        $rows[] = ['', '', ''];
        
        // Activos
        $rows[] = ['ACTIVOS', '', ''];
        $rows[] = ['Concepto', 'Saldo', ''];
        foreach ($this->data['assets']['details'] as $asset) {
            $rows[] = [$asset['concept'], $asset['balance'], ''];
        }
        $rows[] = ['Total Activos', $this->data['assets']['total'], ''];
        $rows[] = ['', '', ''];
        
        // Pasivos
        $rows[] = ['PASIVOS', '', ''];
        $rows[] = ['Concepto', 'Saldo', ''];
        foreach ($this->data['liabilities']['details'] as $liability) {
            $rows[] = [$liability['concept'], $liability['balance'], ''];
        }
        $rows[] = ['Total Pasivos', $this->data['liabilities']['total'], ''];
        $rows[] = ['', '', ''];
        
        // Patrimonio
        $rows[] = ['PATRIMONIO', '', ''];
        $rows[] = ['Concepto', 'Saldo', ''];
        foreach ($this->data['equity']['details'] as $equity) {
            $rows[] = [$equity['concept'], $equity['balance'], ''];
        }
        $rows[] = ['Total Patrimonio', $this->data['equity']['total'], ''];
        $rows[] = ['', '', ''];
        
        // Verificación
        $rows[] = ['Total Pasivos + Patrimonio', $this->data['total_liabilities_and_equity'], ''];
        $rows[] = ['Balance Cuadrado', $this->data['balance_check'] ? 'Sí' : 'No', ''];
        
        return $rows;
    }

    private function formatSummary(): array
    {
        $rows = [];
        
        // Período
        $rows[] = ['Período:', $this->data['period']['start_date'] . ' - ' . $this->data['period']['end_date'], ''];
        $rows[] = ['', '', ''];
        
        // Métricas
        $rows[] = ['MÉTRICAS', '', ''];
        $rows[] = ['Total Transacciones', $this->data['metrics']['total_transactions'], ''];
        $rows[] = ['Total Ingresos', $this->data['metrics']['total_income'], ''];
        $rows[] = ['Total Gastos', $this->data['metrics']['total_expenses'], ''];
        $rows[] = ['Utilidad Neta', $this->data['metrics']['net_income'], ''];
        $rows[] = ['Transacción Promedio', $this->data['metrics']['average_transaction'], ''];
        
        return $rows;
    }
}