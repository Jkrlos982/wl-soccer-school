<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PayrollService;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollConcept;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Exception;

class PayrollServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PayrollService $payrollService;
    protected Employee $employee;
    protected Department $department;
    protected Position $position;
    protected PayrollPeriod $payrollPeriod;
    protected PayrollConcept $salaryBaseConcept;
    protected PayrollConcept $healthDeductionConcept;
    protected PayrollConcept $pensionDeductionConcept;
    protected PayrollConcept $transportAllowanceConcept;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->payrollService = new PayrollService();
        
        // Create test data
        $this->department = Department::factory()->create();
        $this->position = Position::factory()->create();
        
        $this->employee = Employee::factory()->create([
            'base_salary' => 2500000 // 2.5M COP
        ]);
        
        // Attach position to employee through pivot table
        $this->employee->positions()->attach($this->position->id, [
            'start_date' => now(),
            'salary' => 2500000,
            'status' => 'active'
        ]);
        
        $this->payrollPeriod = PayrollPeriod::factory()->create([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'pay_date' => '2024-02-01'
        ]);
        
        // Create payroll concepts
        $this->salaryBaseConcept = PayrollConcept::factory()->create([
            'code' => 'SALARY_BASE',
            'name' => 'Salario Básico',
            'type' => 'earning',
            'calculation_type' => 'fixed',
            'default_value' => 0,
            'status' => 'active'
        ]);
        
        $this->healthDeductionConcept = PayrollConcept::factory()->create([
            'code' => 'SALUD_EMP',
            'name' => 'Descuento Salud',
            'type' => 'deduction',
            'calculation_type' => 'percentage',
            'default_value' => 4.0, // 4%
            'status' => 'active'
        ]);
        
        $this->pensionDeductionConcept = PayrollConcept::factory()->create([
            'code' => 'PENSION_EMP',
            'name' => 'Descuento Pensión',
            'type' => 'deduction',
            'calculation_type' => 'percentage',
            'default_value' => 4.0, // 4%
            'status' => 'active'
        ]);
        
        $this->transportAllowanceConcept = PayrollConcept::factory()->create([
            'code' => 'SUBSIDIO_TRANSPORTE',
            'name' => 'Auxilio de Transporte',
            'type' => 'earning',
            'calculation_type' => 'fixed',
            'default_value' => 140606, // 2024 transport allowance
            'status' => 'active'
        ]);
        
        // Create attendance records for the employee
        for ($day = 1; $day <= 22; $day++) {
            \App\Models\Attendance::factory()->create([
                'employee_id' => $this->employee->id,
                'date' => '2024-01-' . str_pad($day, 2, '0', STR_PAD_LEFT),
                'status' => 'present',
                'worked_hours' => 8,
                'overtime_hours' => 0
            ]);
        }
    }

    /** @test */
    public function it_calculates_payroll_for_employee()
    {
        $payroll = $this->payrollService->calculatePayroll(
            $this->employee,
            $this->payrollPeriod
        );
        
        $this->assertInstanceOf(\App\Models\Payroll::class, $payroll);
        $this->assertEquals($this->employee->id, $payroll->employee_id);
        $this->assertEquals($this->payrollPeriod->id, $payroll->payroll_period_id);
        $this->assertEquals('calculated', $payroll->status);
    }

    /** @test */
    public function it_prevents_duplicate_payroll_calculation()
    {
        // First calculation
        $payroll1 = $this->payrollService->calculatePayroll(
            $this->employee,
            $this->payrollPeriod
        );
        
        // Second calculation should update the same payroll
        $payroll2 = $this->payrollService->calculatePayroll(
            $this->employee,
            $this->payrollPeriod
        );
        
        $this->assertEquals($payroll1->id, $payroll2->id);
    }

    /** @test */
    public function it_calculates_payroll_with_correct_totals()
    {
        $payroll = $this->payrollService->calculatePayroll(
            $this->employee,
            $this->payrollPeriod
        );
        
        $this->assertGreaterThan(0, $payroll->gross_salary);
        $this->assertGreaterThanOrEqual(0, $payroll->total_deductions);
        $this->assertGreaterThanOrEqual(0, $payroll->net_salary);
        $this->assertEquals(
            $payroll->gross_salary - $payroll->total_deductions - $payroll->total_taxes,
            $payroll->net_salary
        );
    }

    /** @test */
    public function it_creates_payroll_details_when_calculating()
    {
        $payroll = $this->payrollService->calculatePayroll(
            $this->employee,
            $this->payrollPeriod
        );
        
        $this->assertGreaterThan(0, $payroll->details()->count());
    }

    /** @test */
    public function it_generates_unique_payroll_numbers()
    {
        $payroll1 = $this->payrollService->calculatePayroll(
            $this->employee,
            $this->payrollPeriod
        );
        
        $employee2 = Employee::factory()->create([
            'base_salary' => 3000000
        ]);
        
        $payroll2 = $this->payrollService->calculatePayroll(
            $employee2,
            $this->payrollPeriod
        );
        
        $this->assertNotEquals($payroll1->payroll_number, $payroll2->payroll_number);
    }

    /** @test */
    public function it_handles_payroll_calculation_errors_gracefully()
    {
        // Create an employee with invalid data (very low salary)
        $invalidEmployee = Employee::factory()->create([
            'base_salary' => 0 // Invalid salary
        ]);
        
        $this->expectException(Exception::class);
        
        $this->payrollService->calculatePayroll(
            $invalidEmployee,
            $this->payrollPeriod
        );
    }

    /** @test */
    public function it_updates_payroll_status_correctly()
    {
        $payroll = $this->payrollService->calculatePayroll(
            $this->employee,
            $this->payrollPeriod
        );
        
        $this->assertEquals('calculated', $payroll->status);
    }
}