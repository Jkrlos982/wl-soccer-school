<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\PayrollPeriod;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Carbon\Carbon;

class PayrollPeriodControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $department = Department::factory()->create();
        $position = Position::factory()->create();
        
        $employee = Employee::factory()->create();
        
        // Attach position to employee through pivot table
        $employee->positions()->attach($position->id, [
            'start_date' => now(),
            'salary' => 50000,
            'status' => 'active'
        ]);
    }

    /** @test */
    public function it_can_list_payroll_periods()
    {
        // Clear any existing periods
        PayrollPeriod::query()->delete();
        
        PayrollPeriod::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/payroll-periods');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'data' => [
                             '*' => [
                                 'id',
                                 'name',
                                 'start_date',
                                 'end_date',
                                 'status',
                                 'created_at',
                                 'updated_at'
                             ]
                         ],
                         'current_page',
                         'total'
                     ],
                     'message'
                 ]);
    }

    /** @test */
    public function it_can_create_a_payroll_period()
    {
        $periodData = [
            'name' => 'Enero 2024',
            'type' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'pay_date' => '2024-02-05',
            'description' => 'Período de enero 2024'
        ];

        $response = $this->postJson('/api/v1/payroll-periods', $periodData);

        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'name' => 'Enero 2024',
                     'status' => 'draft'
                 ]);

        $this->assertDatabaseHas('payroll_periods', [
            'name' => 'Enero 2024',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31'
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_period()
    {
        $response = $this->postJson('/api/v1/payroll-periods', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'start_date', 'end_date']);
    }

    /** @test */
    public function it_can_show_a_payroll_period()
    {
        $period = PayrollPeriod::factory()->create();

        $response = $this->getJson("/api/v1/payroll-periods/{$period->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'id' => $period->id,
                     'name' => $period->name
                 ]);
    }

    /** @test */
    public function it_can_update_a_payroll_period()
    {
        $period = PayrollPeriod::factory()->create();
        
        $updateData = [
            'name' => 'Febrero 2024 Actualizado',
            'start_date' => $period->start_date,
            'end_date' => $period->end_date
        ];

        $response = $this->putJson("/api/v1/payroll-periods/{$period->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'name' => 'Febrero 2024 Actualizado'
                 ]);

        $this->assertDatabaseHas('payroll_periods', [
            'id' => $period->id,
            'name' => 'Febrero 2024 Actualizado',
            'status' => 'draft'
        ]);
    }

    /** @test */
    public function it_can_delete_a_payroll_period()
    {
        $period = PayrollPeriod::factory()->create();

        $response = $this->deleteJson("/api/v1/payroll-periods/{$period->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('payroll_periods', ['id' => $period->id]);
    }

    /** @test */
    public function it_can_open_a_payroll_period()
    {
        $period = PayrollPeriod::factory()->create(['status' => 'draft']);

        $response = $this->postJson("/api/v1/payroll-periods/{$period->id}/open");

        $response->assertStatus(200)
                 ->assertJsonFragment(['status' => 'processing']);

        $this->assertDatabaseHas('payroll_periods', [
            'id' => $period->id,
            'status' => 'processing'
        ]);
    }

    /** @test */
    public function it_can_close_a_payroll_period()
    {
        $period = PayrollPeriod::factory()->create(['status' => 'processing']);

        $response = $this->postJson("/api/v1/payroll-periods/{$period->id}/close");

        $response->assertStatus(200)
                 ->assertJsonFragment(['status' => 'closed']);

        $this->assertDatabaseHas('payroll_periods', [
            'id' => $period->id,
            'status' => 'closed'
        ]);
    }

    /** @test */
    public function it_can_get_current_payroll_period()
    {
        // Clear any existing periods
        PayrollPeriod::query()->delete();
        
        // Create a period that definitely covers today
        $today = Carbon::today();
        $currentPeriod = PayrollPeriod::create([
            'name' => 'Test Period ' . $today->format('Y-m'),
            'start_date' => $today->copy()->subDays(5),
            'end_date' => $today->copy()->addDays(5),
            'pay_date' => $today->copy()->addDays(10),
            'period_type' => 'monthly',
            'status' => 'processing',
            'year' => $today->year,
            'month' => $today->month,
            'period_number' => 1,
            'total_gross' => 0,
            'total_deductions' => 0,
            'total_net' => 0
        ]);

        // Verify the period was created
        $this->assertDatabaseHas('payroll_periods', [
            'id' => $currentPeriod->id,
            'status' => 'processing'
        ]);

        // Test the query directly
        $directQuery = PayrollPeriod::where('status', 'processing')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();
        
        $this->assertNotNull($directQuery, 'Direct query should find the period');
        $this->assertEquals($currentPeriod->id, $directQuery->id);

        $response = $this->getJson('/api/v1/payroll-periods/current');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'name',
                         'start_date',
                         'end_date',
                         'status',
                         'created_at',
                         'updated_at'
                     ],
                     'message'
                 ])
                 ->assertJsonFragment([
                     'id' => $currentPeriod->id,
                     'status' => 'processing'
                 ]);
    }

    /** @test */
    public function it_returns_404_when_payroll_period_not_found()
    {
        $response = $this->getJson('/api/v1/payroll-periods/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_filter_periods_by_status()
    {
        // Clear any existing periods
        PayrollPeriod::query()->delete();
        
        PayrollPeriod::factory()->create(['status' => 'processing']);
        PayrollPeriod::factory()->create(['status' => 'closed']);
        PayrollPeriod::factory()->create(['status' => 'processing']);

        $response = $this->getJson('/api/v1/payroll-periods?status=processing');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        
        $this->assertCount(2, $data);
        foreach ($data as $period) {
            $this->assertEquals('processing', $period['status']);
        }
    }

    /** @test */
    public function it_can_get_period_summary()
    {
        $period = PayrollPeriod::factory()->create();

        $response = $this->getJson("/api/v1/payroll-periods/{$period->id}/summary");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'period_id',
                         'total_employees',
                         'total_payrolls',
                         'total_gross_salary',
                         'total_deductions',
                         'total_net_salary'
                     ],
                     'message'
                 ]);
    }
}