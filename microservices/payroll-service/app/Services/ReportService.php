<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use App\Models\Payroll;
use App\Models\Employee;
use App\Models\Department;
use App\Models\PayrollReport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use League\Csv\Writer;
use Carbon\Carbon;

class ReportService
{
    /**
     * Generate payroll summary report
     */
    public function generatePayrollSummary(PayrollPeriod $period): PayrollReport
    {
        $payrolls = Payroll::with(['employee', 'details.concept'])
            ->where('period_id', $period->id)
            ->get();

        $summary = [
            'period' => [
                'id' => $period->id,
                'name' => $period->name,
                'start_date' => $period->start_date,
                'end_date' => $period->end_date,
                'status' => $period->status
            ],
            'totals' => [
                'employees' => $payrolls->count(),
                'gross_salary' => $payrolls->sum('gross_salary'),
                'total_deductions' => $payrolls->sum('total_deductions'),
                'total_taxes' => $payrolls->sum('total_taxes'),
                'net_salary' => $payrolls->sum('net_salary')
            ],
            'by_department' => $this->getPayrollByDepartment($payrolls),
            'concepts_summary' => $this->getConceptsSummary($payrolls)
        ];

        // Save report
        $report = PayrollReport::create([
            'period_id' => $period->id,
            'report_type' => 'payroll_summary',
            'report_name' => "Resumen de Nómina - {$period->name}",
            'report_data' => json_encode($summary),
            'generated_by' => auth()->id() ?? 1,
            'status' => 'completed'
        ]);

        Log::info("Payroll summary report generated for period {$period->id}");
        
        return $report;
    }

    /**
     * Generate detailed payroll report
     */
    public function generateDetailedPayrollReport(PayrollPeriod $period): PayrollReport
    {
        $payrolls = Payroll::with([
            'employee.currentPosition.department',
            'employee.currentPosition.position',
            'details.concept'
        ])->where('period_id', $period->id)->get();

        $detailedData = [];
        
        foreach ($payrolls as $payroll) {
            $employee = $payroll->employee;
            $position = $employee->currentPosition;
            
            $employeeData = [
                'employee' => [
                    'id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                    'document_number' => $employee->document_number,
                    'department' => $position->department->name ?? 'N/A',
                    'position' => $position->position->name ?? 'N/A'
                ],
                'payroll' => [
                    'payroll_number' => $payroll->payroll_number,
                    'base_salary' => $payroll->base_salary,
                    'worked_days' => $payroll->worked_days,
                    'worked_hours' => $payroll->worked_hours,
                    'overtime_hours' => $payroll->overtime_hours,
                    'gross_salary' => $payroll->gross_salary,
                    'total_deductions' => $payroll->total_deductions,
                    'total_taxes' => $payroll->total_taxes,
                    'net_salary' => $payroll->net_salary
                ],
                'details' => []
            ];

            foreach ($payroll->details as $detail) {
                $employeeData['details'][] = [
                    'concept_code' => $detail->concept->code,
                    'concept_name' => $detail->concept->name,
                    'concept_type' => $detail->concept->type,
                    'quantity' => $detail->quantity,
                    'rate' => $detail->rate,
                    'amount' => $detail->amount
                ];
            }

            $detailedData[] = $employeeData;
        }

        $report = PayrollReport::create([
            'period_id' => $period->id,
            'report_type' => 'detailed_payroll',
            'report_name' => "Nómina Detallada - {$period->name}",
            'report_data' => json_encode($detailedData),
            'generated_by' => auth()->id() ?? 1,
            'status' => 'completed'
        ]);

        Log::info("Detailed payroll report generated for period {$period->id}");
        
        return $report;
    }

    /**
     * Generate tax report
     */
    public function generateTaxReport(Carbon $startDate, Carbon $endDate): PayrollReport
    {
        $payrolls = Payroll::with(['employee', 'period', 'details.concept'])
            ->whereHas('period', function($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate]);
            })
            ->get();

        $taxData = [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString()
            ],
            'summary' => [
                'total_employees' => $payrolls->pluck('employee_id')->unique()->count(),
                'total_gross_salary' => $payrolls->sum('gross_salary'),
                'total_income_tax' => 0,
                'total_health_contributions' => 0,
                'total_pension_contributions' => 0
            ],
            'details' => []
        ];

        foreach ($payrolls as $payroll) {
            $incomeTax = $payroll->details->where('concept.code', 'RETENCION_FUENTE')->sum('amount');
            $healthContrib = $payroll->details->where('concept.code', 'SALUD_EMP')->sum('amount');
            $pensionContrib = $payroll->details->where('concept.code', 'PENSION_EMP')->sum('amount');

            $taxData['summary']['total_income_tax'] += $incomeTax;
            $taxData['summary']['total_health_contributions'] += $healthContrib;
            $taxData['summary']['total_pension_contributions'] += $pensionContrib;

            $taxData['details'][] = [
                'employee_id' => $payroll->employee->id,
                'employee_name' => $payroll->employee->first_name . ' ' . $payroll->employee->last_name,
                'document_number' => $payroll->employee->document_number,
                'period' => $payroll->payrollPeriod->name,
                'gross_salary' => $payroll->gross_salary,
                'income_tax' => $incomeTax,
                'health_contribution' => $healthContrib,
                'pension_contribution' => $pensionContrib
            ];
        }

        $report = PayrollReport::create([
            'report_type' => 'tax_report',
            'report_name' => "Reporte de Impuestos - {$startDate->format('Y-m')} a {$endDate->format('Y-m')}",
            'report_data' => json_encode($taxData),
            'generated_by' => auth()->id() ?? 1,
            'status' => 'completed'
        ]);

        Log::info("Tax report generated for period {$startDate->toDateString()} to {$endDate->toDateString()}");
        
        return $report;
    }

    /**
     * Export report to PDF
     */
    public function exportToPdf(PayrollReport $report): string
    {
        $data = json_decode($report->report_data, true);
        $template = $this->getReportTemplate($report->report_type);
        
        $pdf = Pdf::loadView($template, [
            'report' => $report,
            'data' => $data,
            'generated_at' => now()->format('d/m/Y H:i:s')
        ]);

        $filename = $this->generateFilename($report, 'pdf');
        $path = "reports/pdf/{$filename}";
        
        Storage::disk('local')->put($path, $pdf->output());
        
        $report->update([
            'file_path' => $path,
            'file_format' => 'pdf'
        ]);

        Log::info("Report {$report->id} exported to PDF: {$path}");
        
        return $path;
    }

    /**
     * Export report to Excel
     */
    public function exportToExcel(PayrollReport $report): string
    {
        $data = json_decode($report->report_data, true);
        $filename = $this->generateFilename($report, 'xlsx');
        $path = "reports/excel/{$filename}";
        
        Excel::store(new PayrollExport($data, $report->report_type), $path, 'local');
        
        $report->update([
            'file_path' => $path,
            'file_format' => 'excel'
        ]);

        Log::info("Report {$report->id} exported to Excel: {$path}");
        
        return $path;
    }

    /**
     * Export report to CSV
     */
    public function exportToCsv(PayrollReport $report): string
    {
        $data = json_decode($report->report_data, true);
        $filename = $this->generateFilename($report, 'csv');
        $path = "reports/csv/{$filename}";
        
        $csv = Writer::createFromString('');
        $csv->insertOne($this->getCsvHeaders($report->report_type));
        
        foreach ($this->formatDataForCsv($data, $report->report_type) as $row) {
            $csv->insertOne($row);
        }
        
        Storage::disk('local')->put($path, $csv->toString());
        
        $report->update([
            'file_path' => $path,
            'file_format' => 'csv'
        ]);

        Log::info("Report {$report->id} exported to CSV: {$path}");
        
        return $path;
    }

    /**
     * Get payroll data grouped by department
     */
    private function getPayrollByDepartment($payrolls): array
    {
        $byDepartment = [];
        
        foreach ($payrolls as $payroll) {
            $department = $payroll->employee->currentPosition->department->name ?? 'Sin Departamento';
            
            if (!isset($byDepartment[$department])) {
                $byDepartment[$department] = [
                    'employees' => 0,
                    'gross_salary' => 0,
                    'total_deductions' => 0,
                    'total_taxes' => 0,
                    'net_salary' => 0
                ];
            }
            
            $byDepartment[$department]['employees']++;
            $byDepartment[$department]['gross_salary'] += $payroll->gross_salary;
            $byDepartment[$department]['total_deductions'] += $payroll->total_deductions;
            $byDepartment[$department]['total_taxes'] += $payroll->total_taxes;
            $byDepartment[$department]['net_salary'] += $payroll->net_salary;
        }
        
        return $byDepartment;
    }

    /**
     * Get concepts summary
     */
    private function getConceptsSummary($payrolls): array
    {
        $concepts = [];
        
        foreach ($payrolls as $payroll) {
            foreach ($payroll->details as $detail) {
                $conceptCode = $detail->concept->code;
                
                if (!isset($concepts[$conceptCode])) {
                    $concepts[$conceptCode] = [
                        'name' => $detail->concept->name,
                        'type' => $detail->concept->type,
                        'total_amount' => 0,
                        'employee_count' => 0
                    ];
                }
                
                $concepts[$conceptCode]['total_amount'] += $detail->amount;
                $concepts[$conceptCode]['employee_count']++;
            }
        }
        
        return $concepts;
    }

    /**
     * Get report template based on type
     */
    private function getReportTemplate(string $reportType): string
    {
        $templates = [
            'payroll_summary' => 'reports.payroll-summary',
            'detailed_payroll' => 'reports.detailed-payroll',
            'tax_report' => 'reports.tax-report'
        ];
        
        return $templates[$reportType] ?? 'reports.default';
    }

    /**
     * Generate filename for report
     */
    private function generateFilename(PayrollReport $report, string $extension): string
    {
        $sanitizedName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $report->report_name);
        $timestamp = now()->format('Y-m-d_H-i-s');
        
        return "{$sanitizedName}_{$timestamp}.{$extension}";
    }

    /**
     * Get CSV headers based on report type
     */
    private function getCsvHeaders(string $reportType): array
    {
        $headers = [
            'payroll_summary' => [
                'Departamento', 'Empleados', 'Salario Bruto', 'Deducciones', 'Impuestos', 'Salario Neto'
            ],
            'detailed_payroll' => [
                'Número Empleado', 'Nombre', 'Documento', 'Departamento', 'Cargo', 
                'Salario Base', 'Días Trabajados', 'Horas Trabajadas', 'Horas Extra',
                'Salario Bruto', 'Deducciones', 'Impuestos', 'Salario Neto'
            ],
            'tax_report' => [
                'ID Empleado', 'Nombre', 'Documento', 'Período', 'Salario Bruto',
                'Retención Fuente', 'Aporte Salud', 'Aporte Pensión'
            ]
        ];
        
        return $headers[$reportType] ?? [];
    }

    /**
     * Format data for CSV export
     */
    private function formatDataForCsv(array $data, string $reportType): array
    {
        $rows = [];
        
        switch ($reportType) {
            case 'payroll_summary':
                foreach ($data['by_department'] ?? [] as $dept => $values) {
                    $rows[] = [
                        $dept,
                        $values['employees'],
                        $values['gross_salary'],
                        $values['total_deductions'],
                        $values['total_taxes'],
                        $values['net_salary']
                    ];
                }
                break;
                
            case 'detailed_payroll':
                foreach ($data as $item) {
                    $rows[] = [
                        $item['employee']['employee_number'],
                        $item['employee']['name'],
                        $item['employee']['document_number'],
                        $item['employee']['department'],
                        $item['employee']['position'],
                        $item['payroll']['base_salary'],
                        $item['payroll']['worked_days'],
                        $item['payroll']['worked_hours'],
                        $item['payroll']['overtime_hours'],
                        $item['payroll']['gross_salary'],
                        $item['payroll']['total_deductions'],
                        $item['payroll']['total_taxes'],
                        $item['payroll']['net_salary']
                    ];
                }
                break;
                
            case 'tax_report':
                foreach ($data['details'] ?? [] as $item) {
                    $rows[] = [
                        $item['employee_id'],
                        $item['employee_name'],
                        $item['document_number'],
                        $item['period'],
                        $item['gross_salary'],
                        $item['income_tax'],
                        $item['health_contribution'],
                        $item['pension_contribution']
                    ];
                }
                break;
        }
        
        return $rows;
    }

    /**
     * Get report statistics
     */
    public function getReportStatistics(): array
    {
        return [
            'total_reports' => PayrollReport::count(),
            'reports_by_type' => PayrollReport::groupBy('report_type')
                ->selectRaw('report_type, COUNT(*) as count')
                ->pluck('count', 'report_type')
                ->toArray(),
            'reports_by_month' => PayrollReport::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->pluck('count', 'month')
                ->toArray(),
            'recent_reports' => PayrollReport::orderBy('created_at', 'desc')
                ->limit(10)
                ->get(['id', 'report_name', 'report_type', 'status', 'created_at'])
        ];
    }
}