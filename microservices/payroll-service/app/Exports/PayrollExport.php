<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PayrollExport implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    private $data;
    private $reportType;
    
    public function __construct(array $data, string $reportType)
    {
        $this->data = $data;
        $this->reportType = $reportType;
    }

    /**
     * Return array data for export
     */
    public function array(): array
    {
        switch ($this->reportType) {
            case 'payroll_summary':
                return $this->formatPayrollSummary();
            case 'detailed_payroll':
                return $this->formatDetailedPayroll();
            case 'tax_report':
                return $this->formatTaxReport();
            default:
                return [];
        }
    }

    /**
     * Return headings for the export
     */
    public function headings(): array
    {
        switch ($this->reportType) {
            case 'payroll_summary':
                return [
                    'Departamento',
                    'Empleados',
                    'Salario Bruto',
                    'Deducciones',
                    'Impuestos',
                    'Salario Neto'
                ];
            case 'detailed_payroll':
                return [
                    'Número Empleado',
                    'Nombre Completo',
                    'Documento',
                    'Departamento',
                    'Cargo',
                    'Salario Base',
                    'Días Trabajados',
                    'Horas Trabajadas',
                    'Horas Extra',
                    'Salario Bruto',
                    'Total Deducciones',
                    'Total Impuestos',
                    'Salario Neto'
                ];
            case 'tax_report':
                return [
                    'ID Empleado',
                    'Nombre Completo',
                    'Documento',
                    'Período',
                    'Salario Bruto',
                    'Retención en la Fuente',
                    'Aporte Salud',
                    'Aporte Pensión'
                ];
            default:
                return [];
        }
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        // Header row styling
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Data rows styling
        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
            $sheet->getStyle('A2:' . $sheet->getHighestColumn() . $lastRow)->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'font' => [
                    'size' => 10
                ]
            ]);

            // Alternate row colors
            for ($row = 2; $row <= $lastRow; $row++) {
                if ($row % 2 == 0) {
                    $sheet->getStyle('A' . $row . ':' . $sheet->getHighestColumn() . $row)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F2F2F2']
                        ]
                    ]);
                }
            }
        }

        // Format currency columns
        $this->formatCurrencyColumns($sheet);

        return [];
    }

    /**
     * Set worksheet title
     */
    public function title(): string
    {
        switch ($this->reportType) {
            case 'payroll_summary':
                return 'Resumen de Nómina';
            case 'detailed_payroll':
                return 'Nómina Detallada';
            case 'tax_report':
                return 'Reporte de Impuestos';
            default:
                return 'Reporte';
        }
    }

    /**
     * Format payroll summary data
     */
    private function formatPayrollSummary(): array
    {
        $rows = [];
        
        if (isset($this->data['by_department'])) {
            foreach ($this->data['by_department'] as $department => $values) {
                $rows[] = [
                    $department,
                    $values['employees'],
                    number_format($values['gross_salary'], 2),
                    number_format($values['total_deductions'], 2),
                    number_format($values['total_taxes'], 2),
                    number_format($values['net_salary'], 2)
                ];
            }
        }
        
        // Add totals row
        if (isset($this->data['totals'])) {
            $totals = $this->data['totals'];
            $rows[] = [
                'TOTAL',
                $totals['employees'],
                number_format($totals['gross_salary'], 2),
                number_format($totals['total_deductions'], 2),
                number_format($totals['total_taxes'], 2),
                number_format($totals['net_salary'], 2)
            ];
        }
        
        return $rows;
    }

    /**
     * Format detailed payroll data
     */
    private function formatDetailedPayroll(): array
    {
        $rows = [];
        
        foreach ($this->data as $item) {
            $employee = $item['employee'];
            $payroll = $item['payroll'];
            
            $rows[] = [
                $employee['employee_number'],
                $employee['name'],
                $employee['document_number'],
                $employee['department'],
                $employee['position'],
                number_format($payroll['base_salary'], 2),
                $payroll['worked_days'],
                number_format($payroll['worked_hours'], 2),
                number_format($payroll['overtime_hours'], 2),
                number_format($payroll['gross_salary'], 2),
                number_format($payroll['total_deductions'], 2),
                number_format($payroll['total_taxes'], 2),
                number_format($payroll['net_salary'], 2)
            ];
        }
        
        return $rows;
    }

    /**
     * Format tax report data
     */
    private function formatTaxReport(): array
    {
        $rows = [];
        
        if (isset($this->data['details'])) {
            foreach ($this->data['details'] as $item) {
                $rows[] = [
                    $item['employee_id'],
                    $item['employee_name'],
                    $item['document_number'],
                    $item['period'],
                    number_format($item['gross_salary'], 2),
                    number_format($item['income_tax'], 2),
                    number_format($item['health_contribution'], 2),
                    number_format($item['pension_contribution'], 2)
                ];
            }
        }
        
        // Add summary row
        if (isset($this->data['summary'])) {
            $summary = $this->data['summary'];
            $rows[] = [
                '',
                'TOTALES',
                '',
                '',
                number_format($summary['total_gross_salary'], 2),
                number_format($summary['total_income_tax'], 2),
                number_format($summary['total_health_contributions'], 2),
                number_format($summary['total_pension_contributions'], 2)
            ];
        }
        
        return $rows;
    }

    /**
     * Format currency columns
     */
    private function formatCurrencyColumns(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        
        switch ($this->reportType) {
            case 'payroll_summary':
                // Columns C to F are currency
                $sheet->getStyle('C2:F' . $lastRow)->getNumberFormat()
                    ->setFormatCode('"$"#,##0.00');
                break;
                
            case 'detailed_payroll':
                // Columns F, J, K, L, M are currency
                $sheet->getStyle('F2:F' . $lastRow)->getNumberFormat()
                    ->setFormatCode('"$"#,##0.00');
                $sheet->getStyle('J2:M' . $lastRow)->getNumberFormat()
                    ->setFormatCode('"$"#,##0.00');
                break;
                
            case 'tax_report':
                // Columns E to H are currency
                $sheet->getStyle('E2:H' . $lastRow)->getNumberFormat()
                    ->setFormatCode('"$"#,##0.00');
                break;
        }
    }
}