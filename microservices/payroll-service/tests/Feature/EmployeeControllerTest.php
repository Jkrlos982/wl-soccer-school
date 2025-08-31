<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\Response;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Department $department;
    protected Position $position;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test department and position
        $this->department = Department::factory()->create();
        $this->position = Position::factory()->create(['department_id' => $this->department->id]);
    }

    /** @test */
    public function it_can_list_employees()
    {
        $employees = Employee::factory()->count(3)->create();
        
        // Attach positions to employees through pivot table
        foreach ($employees as $employee) {
            $employee->positions()->attach($this->position->id, [
                'start_date' => now(),
                'salary' => 50000,
                'status' => 'active'
            ]);
        }

        $response = $this->getJson('/api/v1/employees');

        $response->assertStatus(Response::HTTP_OK)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data' => [
                            '*' => [
                                 'id',
                                 'employee_number',
                                 'first_name',
                                 'last_name',
                                 'email',
                                 'positions'
                             ]
                        ],
                        'current_page',
                        'per_page',
                        'total'
                    ],
                    'message'
                ]);
    }

    /** @test */
    public function it_can_create_an_employee()
    {
        $employeeData = [
            'employee_number' => 'EMP001',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'email' => 'juan.perez@example.com',
            'phone' => '+57 300 123 4567',
            'identification_type' => 'DNI',
            'identification_number' => '12345678',
            'birth_date' => '1990-05-15',
            'hire_date' => '2023-01-15',
            'base_salary' => 2500000,
            'employment_type' => 'full_time',
            'employment_status' => 'active',
            'gender' => 'M',
            'marital_status' => 'soltero',
            'address' => 'Calle 123 #45-67',
            'city' => 'Bogotá',
            'emergency_contact_name' => 'María Pérez',
            'emergency_contact_phone' => '+57 301 987 6543',
            'bank_name' => 'Bancolombia',
            'bank_account_type' => 'ahorros',
            'bank_account_number' => '12345678901',
            'position_id' => $this->position->id,
            'salary' => 2500000,
            'start_date' => '2023-01-15'
        ];

        $response = $this->postJson('/api/v1/employees', $employeeData);

        $response->assertStatus(Response::HTTP_CREATED)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'employee_number',
                        'first_name',
                        'last_name',
                        'email'
                    ],
                    'message'
                ]);

        $this->assertDatabaseHas('employees', [
            'employee_number' => 'EMP001',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'email' => 'juan.perez@example.com'
        ]);
    }

    /** @test */
    public function it_can_show_an_employee()
    {
        $employee = Employee::factory()->create();
        
        // Attach position to employee through pivot table
        $employee->positions()->attach($this->position->id, [
            'start_date' => now(),
            'salary' => 50000,
            'status' => 'active'
        ]);

        $response = $this->getJson("/api/v1/employees/{$employee->id}");

        $response->assertStatus(Response::HTTP_OK)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'employee_number',
                        'first_name',
                        'last_name',
                        'email',
                        'positions'
                    ]
                ]);
    }

    /** @test */
    public function it_can_update_an_employee()
    {
        $employee = Employee::factory()->create();
        
        // Attach position to employee through pivot table
        $employee->positions()->attach($this->position->id, [
            'start_date' => now(),
            'salary' => 50000,
            'status' => 'active'
        ]);

        $updateData = [
            'first_name' => 'Carlos',
            'last_name' => 'González',
            'base_salary' => 3000000
        ];

        $response = $this->putJson("/api/v1/employees/{$employee->id}", $updateData);

        $response->assertStatus(Response::HTTP_OK)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'first_name',
                        'last_name'
                    ],
                    'message'
                ]);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'first_name' => 'Carlos',
            'last_name' => 'González',
            'base_salary' => 3000000
        ]);
    }

    /** @test */
    public function it_can_delete_an_employee()
    {
        $employee = Employee::factory()->create();
        
        // Attach position to employee through pivot table
         $employee->positions()->attach($this->position->id, [
             'start_date' => now(),
             'salary' => 50000,
             'status' => 'active'
         ]);

        $response = $this->deleteJson("/api/v1/employees/{$employee->id}");

        $response->assertStatus(Response::HTTP_OK)
                ->assertJson([
                    'message' => 'Employee deleted successfully'
                ]);

        $this->assertSoftDeleted('employees', [
            'id' => $employee->id
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_employee()
    {
        $response = $this->postJson('/api/v1/employees', []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors([
                    'employee_number',
                    'first_name',
                    'last_name',
                    'email',
                    'identification_type',
                    'identification_number'
                ]);
    }

    /** @test */
    public function it_validates_unique_employee_number()
    {
        $existingEmployee = Employee::factory()->create([
            'employee_number' => 'EMP001'
        ]);
        
        // Attach position to existing employee through pivot table
        $existingEmployee->positions()->attach($this->position->id, [
            'start_date' => now(),
            'salary' => 50000,
            'status' => 'active'
        ]);

        $employeeData = [
            'employee_number' => 'EMP001', // Duplicate code
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@company.com',
            'identification_type' => 'CC',
            'identification_number' => '87654321'
        ];

        $response = $this->postJson('/api/v1/employees', $employeeData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['employee_number']);
    }

    /** @test */
    public function it_can_filter_employees_by_department()
    {
        $department2 = Department::factory()->create();
        
        $employees1 = Employee::factory()->count(2)->create();
        
        // Attach positions to employees in department 1
        foreach ($employees1 as $employee) {
            $employee->positions()->attach($this->position->id, [
                'start_date' => now(),
                'salary' => 50000,
                'status' => 'active'
            ]);
        }
        
        // Create position for department 2
        $position2 = Position::factory()->create(['department_id' => $department2->id]);
        
        $employees2 = Employee::factory()->count(3)->create();
        
        // Attach positions to employees in department 2
        foreach ($employees2 as $employee) {
            $employee->positions()->attach($position2->id, [
                'start_date' => now(),
                'salary' => 50000,
                'status' => 'active'
            ]);
        }

        $response = $this->getJson("/api/v1/employees?department_id={$this->department->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertEquals(2, count($response->json('data.data')));
    }

    /** @test */
    public function it_can_search_employees_by_name()
    {
        $employee1 = Employee::factory()->create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
        ]);
        
        $employee2 = Employee::factory()->create([
            'first_name' => 'María',
            'last_name' => 'González',
        ]);
        
        // Attach positions to employees
        $employee1->positions()->attach($this->position->id, [
            'start_date' => now(),
            'salary' => 50000,
            'status' => 'active'
        ]);
        
        $employee2->positions()->attach($this->position->id, [
            'start_date' => now(),
            'salary' => 50000,
            'status' => 'active'
        ]);

        $response = $this->getJson('/api/v1/employees?search=Juan');

        $response->assertStatus(Response::HTTP_OK);
        $this->assertEquals(1, count($response->json('data.data')));
        $this->assertEquals('Juan', $response->json('data.data.0.first_name'));
    }
}