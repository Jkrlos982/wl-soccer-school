<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\PayrollConcept;
use App\Models\PayrollDetail;
use App\Models\Attendance;
use App\Models\EmployeePosition;
use App\Models\EmployeeBenefit;
use App\Models\LeaveRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class PayrollService
{
    /**
     * Calculate payroll for a specific employee and period
     */
    public function calculatePayroll(Employee $employee, PayrollPeriod $period): Payroll
    {
        // Validate employee data
        if ($employee->base_salary <= 0) {
            throw new \Exception('Employee base salary must be greater than 0');
        }
        
        DB::beginTransaction();
        
        try {
            // Check if payroll already exists
            $payroll = Payroll::where('employee_id', $employee->id)
                             ->where('payroll_period_id', $period->id)
                             ->first();
            
            if (!$payroll) {
                $payroll = new Payroll([
                    'employee_id' => $employee->id,
                    'payroll_period_id' => $period->id,
                    'payroll_number' => $this->generatePayrollNumber($employee, $period),
                    'base_salary' => $employee->base_salary,
                    'gross_salary' => $employee->base_salary,
                    'net_salary' => $employee->base_salary,
                    'status' => 'draft',
                ]);
                $payroll->save();
            }
            
            // Clear existing details if recalculating
            $payroll->payrollDetails()->delete();
            
            // Calculate base salary and hours
            $workingData = $this->calculateWorkingHours($employee, $period);
            $baseSalary = $this->calculateBaseSalary($employee, $workingData);
            
            // Update payroll with working data
            $payroll->update([
                'base_salary' => $baseSalary,
                'regular_hours' => $workingData['regular_hours'],
                'overtime_hours' => $workingData['overtime_hours'],
                'worked_days' => $workingData['total_days'],
            ]);

            // Calculate earnings (using existing method but with new data)
            $earnings = $this->calculateEarnings($payroll, $workingData);
            
            // Calculate deductions (using existing method)
            $deductions = $this->calculateDeductions($payroll, $earnings['gross_salary']);
            
            // Calculate taxes (using existing method)
            $taxes = $this->calculateTaxes($payroll, $earnings['gross_salary']);
            
            // Calculate totals
            $grossSalary = $earnings['gross_salary'];
            $totalDeductions = $deductions['total_deductions'] + $taxes['total_taxes'];
            $netSalary = $grossSalary - $totalDeductions;
            
            // Update payroll totals
            $payroll->update([
                'gross_salary' => $grossSalary,
                'total_earnings' => $earnings['total_earnings'],
                'total_deductions' => $deductions['total_deductions'],
                'total_taxes' => $taxes['total_taxes'],
                'net_salary' => $netSalary,
                'status' => 'calculated',
            ]);
            
            DB::commit();
            
            Log::info("Payroll calculated successfully for employee {$employee->id}, period {$period->id}");
            
            return $payroll->fresh(['details.payrollConcept']);
            
        } catch (Exception $e) {
            Log::error("Error calculating payroll: " . $e->getMessage());
            throw $e;
        }
        
        return $payroll;
    }

    /**
     * Get or create a payroll concept by name and type.
     */
    protected function getOrCreateConcept(string $name, string $type): PayrollConcept
    {
        return PayrollConcept::firstOrCreate(
            ['name' => $name, 'type' => $type],
            [
                'code' => strtoupper(str_replace(' ', '_', $name)),
                'description' => $name,
                'status' => 'active',
                'is_taxable' => $type === 'earning',
                'affects_social_security' => in_array($type, ['earning', 'deduction']),
            ]
        );
    }

    /**
     * Calculate working hours for an employee in a period.
     */
    protected function calculateWorkingHours(Employee $employee, PayrollPeriod $period): array
    {
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$period->start_date, $period->end_date])
            ->where('status', 'present')
            ->get();
        
        $regularHours = 0;
        $overtimeHours = 0;
        $totalDays = 0;
        
        foreach ($attendances as $attendance) {
            $totalDays++;
            $dailyHours = $attendance->total_hours ?? 0;
            $standardHours = 8; // Standard working hours per day
            
            if ($dailyHours <= $standardHours) {
                $regularHours += $dailyHours;
            } else {
                $regularHours += $standardHours;
                $overtimeHours += ($dailyHours - $standardHours);
            }
        }
        
        return [
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'total_days' => $totalDays,
            'worked_days' => $totalDays,
            'worked_hours' => $regularHours + $overtimeHours,
            'attendances' => $attendances,
        ];
    }
    
    /**
     * Calculate base salary for an employee.
     */
    protected function calculateBaseSalary(Employee $employee, array $workingData): float
    {
        $baseSalary = $employee->base_salary;
        
        // If hourly employee, calculate based on hours worked
        if ($employee->salary_type === 'hourly') {
            $hourlyRate = $employee->hourly_rate ?? ($baseSalary / 160); // Assume 160 hours per month
            return $workingData['regular_hours'] * $hourlyRate;
        }
        
        // For monthly salary, check if full month was worked
        $expectedDays = $this->getExpectedWorkingDays($employee, $workingData);
        $workedDays = $workingData['total_days'];
        
        if ($workedDays < $expectedDays) {
            // Pro-rate salary based on days worked
            return ($baseSalary / $expectedDays) * $workedDays;
        }
        
        return $baseSalary;
    }
    
    /**
     * Get expected working days in a period.
     */
    protected function getExpectedWorkingDays(Employee $employee, array $workingData): int
    {
        // This is a simplified calculation - in reality you'd calculate based on the period
        return 22; // Typical working days per month
    }
    
    /**
     * Calculate attendance data for an employee in a period (legacy method)
     */
    private function calculateAttendance(Employee $employee, PayrollPeriod $period): array
    {
        return $this->calculateWorkingHours($employee, $period);
    }

    /**
     * Calculate earnings for a payroll
     */
    private function calculateEarnings(Payroll $payroll, array $workingData): array
    {
        $employee = $payroll->employee;
        $baseSalary = $payroll->base_salary;
        $totalEarnings = $baseSalary;
        $grossSalary = $baseSalary;
        $earnings = [];

        // Base salary
        $earnings['base_salary'] = $baseSalary;

        // Calculate overtime pay
        if ($workingData['overtime_hours'] > 0) {
            $overtimeRate = $baseSalary / 240; // Assuming 240 working hours per month
            $overtimeMultiplier = config('payroll.overtime_multiplier', 1.25);
            $overtimePay = $workingData['overtime_hours'] * $overtimeRate * $overtimeMultiplier;
            
            $this->createPayrollDetail($payroll, 'HORAS_EXTRA', $workingData['overtime_hours'], $overtimeRate * $overtimeMultiplier, $overtimePay);
            $earnings['overtime_pay'] = $overtimePay;
            $totalEarnings += $overtimePay;
            $grossSalary += $overtimePay;
        }

        // Add transport allowance if applicable
        $transportAllowance = config('payroll.transport_allowance', 140606);
        if ($baseSalary <= (2 * config('payroll.minimum_wage', 1300000))) {
            $this->createPayrollDetail($payroll, 'SUBSIDIO_TRANSPORTE', 1, $transportAllowance, $transportAllowance);
            $earnings['transport_allowance'] = $transportAllowance;
            $totalEarnings += $transportAllowance;
        }

        // Active employee benefits (earnings)
        $benefits = EmployeeBenefit::where('employee_id', $employee->id)
            ->whereHas('payrollConcept', function($query) {
                $query->where('type', 'earning');
            })
            ->where('status', 'active')
            ->get();

        foreach ($benefits as $benefit) {
            $amount = $benefit->amount ?? ($baseSalary * ($benefit->percentage / 100));
            if ($amount > 0) {
                $conceptCode = $benefit->payrollConcept->code ?? 'BENEFIT_' . $benefit->id;
                $this->createPayrollDetail($payroll, $conceptCode, 1, $amount, $amount);
                $earnings[$benefit->payrollConcept->name] = $amount;
                $totalEarnings += $amount;
            }
        }

        return [
            'earnings' => $earnings,
            'total_earnings' => $totalEarnings,
            'gross_salary' => $grossSalary
        ];
    }

    /**
     * Calculate deductions for a payroll
     */
    private function calculateDeductions(Payroll $payroll, float $grossSalary): array
    {
        $employee = $payroll->employee;
        $deductions = [];
        $totalDeductions = 0;

        // Health contribution (4%)
        $healthContribution = $grossSalary * config('payroll.health_contribution_rate', 0.04);
        $this->createPayrollDetail($payroll, 'SALUD_EMP', 1, $healthContribution, $healthContribution);
        $deductions['health_contribution'] = $healthContribution;
        $totalDeductions += $healthContribution;

        // Pension contribution (4%)
        $pensionContribution = $grossSalary * config('payroll.pension_contribution_rate', 0.04);
        $this->createPayrollDetail($payroll, 'PENSION_EMP', 1, $pensionContribution, $pensionContribution);
        $deductions['pension_contribution'] = $pensionContribution;
        $totalDeductions += $pensionContribution;

        // Active employee benefits (deductions)
        $benefits = EmployeeBenefit::where('employee_id', $employee->id)
            ->whereHas('payrollConcept', function($query) {
                $query->where('type', 'deduction');
            })
            ->where('status', 'active')
            ->get();

        foreach ($benefits as $benefit) {
            $amount = $benefit->amount ?? ($grossSalary * ($benefit->percentage / 100));
            if ($amount > 0) {
                $conceptCode = $benefit->payrollConcept->code ?? 'DEDUCTION_' . $benefit->id;
                $this->createPayrollDetail($payroll, $conceptCode, 1, $amount, $amount);
                $deductions[$benefit->payrollConcept->name] = $amount;
                $totalDeductions += $amount;
            }
        }

        // Check for unpaid leave deductions
        $period = $payroll->payrollPeriod;
        if ($period) {
            $leaveRequests = LeaveRequest::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->where('is_paid', false)
                ->whereBetween('start_date', [$period->start_date, $period->end_date])
                ->get();
        } else {
            $leaveRequests = collect();
        }

        foreach ($leaveRequests as $leave) {
            $dailySalary = $employee->base_salary / 30; // Assuming 30 days per month
            $leaveDeduction = $leave->days_requested * $dailySalary;
            if ($leaveDeduction > 0) {
                $this->createPayrollDetail($payroll, 'UNPAID_LEAVE', $leave->days_requested, $dailySalary, $leaveDeduction);
                $deductions['unpaid_leave'] = ($deductions['unpaid_leave'] ?? 0) + $leaveDeduction;
                $totalDeductions += $leaveDeduction;
            }
        }

        return [
            'deductions' => $deductions,
            'total_deductions' => $totalDeductions
        ];
    }

    /**
     * Calculate taxes for a payroll
     */
    private function calculateTaxes(Payroll $payroll, float $grossSalary): array
    {
        $employee = $payroll->employee;
        $taxes = [];
        $totalTaxes = 0;
        $exemptAmount = config('payroll.income_tax_exempt_amount', 2392000);
        
        // Calculate income tax if salary exceeds exempt amount
        if ($grossSalary > $exemptAmount) {
            $taxableIncome = $grossSalary - $exemptAmount;
            $incomeTax = $this->calculateIncomeTax($taxableIncome);
            
            if ($incomeTax > 0) {
                $this->createPayrollDetail($payroll, 'RETENCION_FUENTE', 1, $incomeTax, $incomeTax);
                $taxes['income_tax'] = $incomeTax;
                $totalTaxes += $incomeTax;
            }
        }

        // Active employee benefits (taxes)
        $benefits = EmployeeBenefit::where('employee_id', $employee->id)
            ->whereHas('payrollConcept', function($query) {
                $query->where('type', 'tax');
            })
            ->where('status', 'active')
            ->get();

        foreach ($benefits as $benefit) {
            $amount = $benefit->amount ?? ($grossSalary * ($benefit->percentage / 100));
            if ($amount > 0) {
                $conceptCode = $benefit->payrollConcept->code ?? 'TAX_' . $benefit->id;
                $this->createPayrollDetail($payroll, $conceptCode, 1, $amount, $amount);
                $taxes[$benefit->payrollConcept->name] = $amount;
                $totalTaxes += $amount;
            }
        }

        return [
            'taxes' => $taxes,
            'total_taxes' => $totalTaxes
        ];
    }

    /**
     * Calculate income tax based on Colombian tax brackets
     */
    private function calculateIncomeTax(float $taxableIncome): float
    {
        // Simplified Colombian income tax calculation
        // This should be updated with current tax brackets
        $annualTaxableIncome = $taxableIncome * 12;
        
        if ($annualTaxableIncome <= 0) return 0;
        if ($annualTaxableIncome <= 1340000) return 0;
        if ($annualTaxableIncome <= 3496000) return ($annualTaxableIncome - 1340000) * 0.19;
        if ($annualTaxableIncome <= 5738000) return 410640 + (($annualTaxableIncome - 3496000) * 0.28);
        
        // For higher brackets, continue the calculation
        return 1038320 + (($annualTaxableIncome - 5738000) * 0.33);
    }

    /**
     * Create a payroll detail record
     */
    private function createPayrollDetail(Payroll $payroll, string $conceptCode, float $quantity, float $rate, float $amount): PayrollDetail
    {
        $concept = PayrollConcept::where('code', $conceptCode)
                                  ->where('status', 'active')
                                  ->first();
        
        if (!$concept) {
            throw new \Exception("Payroll concept {$conceptCode} not found");
        }

        return PayrollDetail::create([
            'payroll_id' => $payroll->id,
            'payroll_concept_id' => $concept->id,
            'quantity' => $quantity,
            'rate' => $rate,
            'amount' => $amount
        ]);
    }

    /**
     * Generate a unique payroll number
     */
    private function generatePayrollNumber(Employee $employee, PayrollPeriod $period): string
    {
        $year = Carbon::parse($period->start_date)->year;
        $month = Carbon::parse($period->start_date)->month;
        
        return sprintf('PAY-%04d%02d-%06d', $year, $month, $employee->id);
    }

    /**
     * Process payroll for all employees in a period
     */
    public function processPayrollPeriod(PayrollPeriod $period): array
    {
        $results = [
            'processed' => 0,
            'errors' => 0,
            'details' => []
        ];

        $activeEmployees = Employee::where('status', 'active')
            ->whereHas('positions', function($query) use ($period) {
                $query->where('is_current', true)
                      ->where('start_date', '<=', $period->end_date);
            })
            ->get();

        foreach ($activeEmployees as $employee) {
            try {
                $payroll = $this->calculatePayroll($employee, $period);
                $results['processed']++;
                $results['details'][] = [
                    'employee_id' => $employee->id,
                    'payroll_id' => $payroll->id,
                    'status' => 'success',
                    'net_salary' => $payroll->net_salary
                ];
            } catch (\Exception $e) {
                $results['errors']++;
                $results['details'][] = [
                    'employee_id' => $employee->id,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
                Log::error("Error processing payroll for employee {$employee->id}: " . $e->getMessage());
            }
        }

        // Update period totals
        $this->updatePeriodTotals($period);

        return $results;
    }

    /**
     * Update period totals
     */
    private function updatePeriodTotals(PayrollPeriod $period): void
    {
        $payrolls = Payroll::where('period_id', $period->id)->get();
        
        $period->update([
            'total_employees' => $payrolls->count(),
            'total_gross' => $payrolls->sum('gross_salary'),
            'total_deductions' => $payrolls->sum('total_deductions'),
            'total_net' => $payrolls->sum('net_salary')
        ]);
    }

    /**
     * Approve payroll period
     */
    public function approvePayrollPeriod(PayrollPeriod $period, int $approvedBy): PayrollPeriod
    {
        DB::beginTransaction();
        
        try {
            $period->update([
                'status' => 'approved',
                'approved_by' => $approvedBy
            ]);

            // Update all payrolls in the period
            Payroll::where('period_id', $period->id)
                   ->where('status', 'calculated')
                   ->update(['status' => 'approved']);

            DB::commit();
            
            Log::info("Payroll period {$period->id} approved by user {$approvedBy}");
            
            return $period->fresh();
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error approving payroll period: " . $e->getMessage());
            throw $e;
        }
    }
}