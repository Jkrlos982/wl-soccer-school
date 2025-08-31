<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Payroll;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use App\Models\PayrollPeriod;
use App\Models\PayrollConcept;
use Illuminate\Http\Response;

class PayrollControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Department $department;
    protected Position $position;
    protected Employee $employee;
    protected PayrollPeriod $payrollPeriod;
    protected PayrollConcept $salaryBaseConcept;
    protected PayrollConcept $healthDeductionConcept;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->department = Department::factory()->create();
        $this->position = Position::factory()->create();
        $this->employee = Employee::factory()->create([
            'base_salary' => 2500000
        ]);
        
        // Attach position to employee through pivot table
        $this->employee->positions()->attach($this->position->id, [
            'start_date' => now(),
            'salary' => 2500000,
            'status' => 'active'
        ]);
        
        $this->payrollPeriod = PayrollPeriod::factory()->create([
            'name' => 'Enero 2024',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'pay_date' => '2024-02-01',
            'status' => 'draft'
        ]);
        
        $this->salaryBaseConcept = PayrollConcept::factory()->create([
            'code' => 'SALARY_BASE',
            'name' => 'Salario Básico',
            'type' => 'earning',
            'calculation_type' => 'fixed',
            'default_value' => 0,
            'status' => 'active'
        ]);
        
        $this->healthDeductionConcept = PayrollConcept::factory()->create([
            'code' => 'HEALTH_DEDUCTION',
            'name' => 'Descuento Salud',
            'type' => 'deduction',
            'calculation_type' => 'percentage',
            'default_value' => 4.0,
            'status' => 'active'
        ]);
        
        // Create transport allowance concept that PayrollService expects
        PayrollConcept::factory()->create([
            'code' => 'SUBSIDIO_TRANSPORTE',
            'name' => 'Subsidio de Transporte',
            'type' => 'earning',
            'calculation_type' => 'fixed',
            'default_value' => 140606,
            'status' => 'active'
        ]);
        
        // Create health contribution concept
        PayrollConcept::factory()->create([
            'code' => 'SALUD_EMP',
            'name' => 'Aporte Salud Empleado',
            'type' => 'deduction',
            'calculation_type' => 'percentage',
            'default_value' => 4.0,
            'status' => 'active'
        ]);
        
        // Create pension contribution concept
        PayrollConcept::factory()->create([
            'code' => 'PENSION_EMP',
            'name' => 'Aporte Pensión Empleado',
            'type' => 'deduction',
            'calculation_type' => 'percentage',
            'default_value' => 4.0,
            'status' => 'active'
        ]);
    }

    /** @test */
    public function it_can_list_payrolls()
    {
        // Create additional employees to avoid unique constraint violation
        $employee2 = Employee::factory()->create(['base_salary' => 2500000]);
        $employee3 = Employee::factory()->create(['base_salary' => 2500000]);
        
        // Attach positions to new employees
        $employee2->positions()->attach($this->position->id, [
            'start_date' => now(),
            'salary' => 2500000,
            'status' => 'active'
        ]);
        $employee3->positions()->attach($this->position->id, [
            'start_date' => now(),
            'salary' => 2500000,
            'status' => 'active'
        ]);
        
        // Create payrolls for different employees
        Payroll::factory()->create([
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $this->payrollPeriod->id
        ]);
        Payroll::factory()->create([
            'employee_id' => $employee2->id,
            'payroll_period_id' => $this->payrollPeriod->id
        ]);
        Payroll::factory()->create([
            'employee_id' => $employee3->id,
            'payroll_period_id' => $this->payrollPeriod->id
        ]);

        $response = $this->getJson('/api/v1/payrolls');

        $response->assertStatus(Response::HTTP_OK)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'employee',
                                'payroll_period',
                                'gross_salary',
                                'total_deductions',
                                'net_salary',
                                'status'
                            ]
                        ],
                        'current_page',
                        'total',
                        'per_page'
                    ],
                    'message'
                ]);
    }

    /** @test */
    public function it_can_create_a_payroll()
    {
        $payrollData = [
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $this->payrollPeriod->id,
            'worked_days' => 30,
            'worked_hours' => 240,
            'overtime_hours' => 10,
            'concepts' => [
                [
                    'payroll_concept_id' => $this->salaryBaseConcept->id,
                    'value' => 2500000,
                    'quantity' => 1
                ],
                [
                    'payroll_concept_id' => $this->healthDeductionConcept->id,
                    'value' => 100000,
                    'quantity' => 1
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/payrolls', $payrollData);

        $response->assertStatus(Response::HTTP_CREATED)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'employee_id',
                        'payroll_period_id',
                        'gross_salary',
                        'total_deductions',
                        'net_salary'
                    ],
                    'message'
                ]);

        $this->assertDatabaseHas('payrolls', [
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $this->payrollPeriod->id
        ]);
    }

    /** @test */
    public function it_can_show_a_payroll()
    {
        $payroll = Payroll::factory()->create([
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $this->payrollPeriod->id
        ]);

        $response = $this->getJson("/api/v1/payrolls/{$payroll->id}");

        $response->assertStatus(Response::HTTP_OK)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'employee',
                        'payroll_period',
                        'gross_salary',
                        'total_deductions',
                        'net_salary',
                        'concepts'
                    ]
                ]);
    }

    /** @test */
    public function it_can_update_a_payroll()
    {
        $payroll = Payroll::factory()->create([
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $this->payrollPeriod->id,
            'status' => 'draft'
        ]);

        $updateData = [
            'worked_days' => 28,
            'worked_hours' => 224,
            'overtime_hours' => 5
        ];

        $response = $this->putJson("/api/v1/payrolls/{$payroll->id}", $updateData);

        $response->assertStatus(Response::HTTP_OK)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'worked_days',
                        'worked_hours',
                        'overtime_hours'
                    ],
                    'message'
                ]);

        $this->assertDatabaseHas('payrolls', [
            'id' => $payroll->id,
            'worked_days' => 28,
            'worked_hours' => 224,
            'overtime_hours' => 5
        ]);
    }

    /** @test */
    public function it_can_delete_a_payroll()
    {
        $payroll = Payroll::factory()->create([
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $this->payrollPeriod->id,
            'status' => 'draft'
        ]);

        $response = $this->deleteJson("/api/v1/payrolls/{$payroll->id}");

        $response->assertStatus(Response::HTTP_OK)
                ->assertJson([
                    'message' => 'Payroll deleted successfully'
                ]);

        $this->assertDatabaseMissing('payrolls', [
            'id' => $payroll->id
        ]);
    }

    /** @test */
    public function it_can_calculate_payroll()
    {
        $response = $this->postJson('/api/v1/payrolls/calculate', [
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $this->payrollPeriod->id,
            'worked_days' => 30,
            'worked_hours' => 240,
            'overtime_hours' => 0
        ]);

        $response->assertStatus(Response::HTTP_OK)
                ->assertJsonStructure([
                    'data' => [
                        'employee_id',
                        'payroll_period_id',
                        'gross_salary',
                        'total_deductions',
                        'net_salary',
                        'concepts' => [
                            '*' => [
                                'concept',
                                'value',
                                'type'
                            ]
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_can_approve_payroll()
    {
        $payroll = Payroll::factory()->create([
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $this->payrollPeriod->id,
            'status' => 'calculated'
        ]);

        $response = $this->postJson("/api/v1/payrolls/{$payroll->id}/approve");

        $response->assertStatus(Response::HTTP_OK)
                ->assertJson([
                    'message' => 'Nómina aprobada exitosamente'
                ]);

        $this->assertDatabaseHas('payrolls', [
            'id' => $payroll->id,
            'status' => 'approved'
        ]);
    }

    /** @test */
    public function it_can_reject_payroll()
    {
        $payroll = Payroll::factory()->create([
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $this->payrollPeriod->id,
            'status' => 'calculated'
        ]);

        $response = $this->postJson("/api/v1/payrolls/{$payroll->id}/reject", [
            'rejection_reason' => 'Datos incorrectos en horas trabajadas'
        ]);

        $response->assertStatus(Response::HTTP_OK)
                ->assertJson([
                    'message' => 'Nómina rechazada exitosamente'
                ]);

        $this->assertDatabaseHas('payrolls', [
            'id' => $payroll->id,
            'status' => 'rechazada'
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_payroll()
    {
        $response = $this->postJson('/api/v1/payrolls', []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors([
                    'employee_id',
                    'payroll_period_id'
                ]);
    }

    /** @test */
    public function it_prevents_duplicate_payroll_for_same_employee_and_period()
    {
        // Create existing payroll
        Payroll::factory()->create([
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $this->payrollPeriod->id
        ]);

        $payrollData = [
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $this->payrollPeriod->id,
            'worked_days' => 30
        ];

        $response = $this->postJson('/api/v1/payrolls', $payrollData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['employee_id']);
    }

    /** @test */
    public function it_can_filter_payrolls_by_period()
    {
        $period2 = PayrollPeriod::factory()->create();
        
        $employee2 = Employee::factory()->create();
        
        Payroll::factory()->create([
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $this->payrollPeriod->id
        ]);
        
        Payroll::factory()->create([
            'employee_id' => $employee2->id,
            'payroll_period_id' => $this->payrollPeriod->id
        ]);
        
        Payroll::factory()->create([
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $period2->id
        ]);

        $response = $this->getJson("/api/v1/payrolls?payroll_period_id={$this->payrollPeriod->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertEquals(2, count($response->json('data.data')));
    }

    /** @test */
    public function it_can_filter_payrolls_by_status()
    {
        $employee2 = Employee::factory()->create();
        
        Payroll::factory()->create([
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $this->payrollPeriod->id,
            'status' => 'approved'
        ]);
        
        Payroll::factory()->create([
             'employee_id' => $employee2->id,
             'payroll_period_id' => $this->payrollPeriod->id,
             'status' => 'draft'
         ]);

        $response = $this->getJson('/api/v1/payrolls?status=approved');

        $response->assertStatus(Response::HTTP_OK);
        $this->assertEquals(1, count($response->json('data.data')));
        $this->assertEquals('approved', $response->json('data.data.0.status'));
    }
}