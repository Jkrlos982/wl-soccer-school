<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\PayrollConcept;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PayrollConceptControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_list_payroll_concepts()
    {
        PayrollConcept::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/payroll-concepts');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'current_page',
                         'data' => [
                             '*' => [
                                 'id',
                                 'name',
                                 'code',
                                 'type',
                                 'default_value',
                                 'status',
                                 'created_at',
                                 'updated_at'
                             ]
                         ],
                         'first_page_url',
                         'from',
                         'last_page',
                         'last_page_url',
                         'links',
                         'next_page_url',
                         'path',
                         'per_page',
                         'prev_page_url',
                         'to',
                         'total'
                     ],
                     'message'
                 ]);
    }

    /** @test */
    public function it_can_create_a_payroll_concept()
    {
        $conceptData = [
            'name' => 'Auxilio de Transporte',
            'code' => 'AUX_TRANS',
            'type' => 'earning',
            'calculation_type' => 'fixed',
            'default_value' => 100000,
            'status' => 'active',
            'description' => 'Auxilio de transporte mensual',
            'is_taxable' => true,
            'affects_social_security' => true,
            'is_mandatory' => false
        ];

        $response = $this->postJson('/api/v1/payroll-concepts', $conceptData);

        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'name' => 'Auxilio de Transporte',
                     'code' => 'AUX_TRANS',
                     'type' => 'earning'
                 ]);

        $this->assertDatabaseHas('payroll_concepts', [
            'name' => 'Auxilio de Transporte',
            'code' => 'AUX_TRANS',
            'type' => 'earning',
            'default_value' => 100000
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_concept()
    {
        $response = $this->postJson('/api/v1/payroll-concepts', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'code', 'type']);
    }

    /** @test */
    public function it_validates_unique_code_when_creating_concept()
    {
        PayrollConcept::factory()->create(['code' => 'SALARY_BASE']);

        $conceptData = [
            'name' => 'Salario Base Duplicado',
            'code' => 'SALARY_BASE',
            'type' => 'earning',
            'calculation_type' => 'fixed',
            'default_value' => 1000.00,
            'status' => 'active',
            'description' => 'Test concept description',
            'is_taxable' => true,
            'affects_social_security' => true,
            'is_mandatory' => false
        ];

        $response = $this->postJson('/api/v1/payroll-concepts', $conceptData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['code']);
    }

    /** @test */
    public function it_can_show_a_payroll_concept()
    {
        $concept = PayrollConcept::factory()->create();

        $response = $this->getJson("/api/v1/payroll-concepts/{$concept->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'id' => $concept->id,
                     'name' => $concept->name,
                     'code' => $concept->code
                 ]);
    }

    /** @test */
    public function it_can_update_a_payroll_concept()
    {
        $concept = PayrollConcept::factory()->create();
        
        $updateData = [
            'name' => 'Concepto Actualizado',
            'code' => $concept->code,
            'type' => $concept->type,
            'calculation_type' => 'fixed',
            'default_value' => 200000,
            'status' => 'inactive',
            'is_taxable' => true,
            'affects_social_security' => true,
            'is_mandatory' => false
        ];

        $response = $this->putJson("/api/v1/payroll-concepts/{$concept->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'name' => 'Concepto Actualizado',
                     'status' => 'inactive'
                 ]);

        $this->assertDatabaseHas('payroll_concepts', [
            'id' => $concept->id,
            'name' => 'Concepto Actualizado',
            'status' => 'inactive'
        ]);
        
        $concept->refresh();
        $this->assertEquals(200000, $concept->default_value);
    }

    /** @test */
    public function it_can_delete_a_payroll_concept()
    {
        $concept = PayrollConcept::factory()->create();

        $response = $this->deleteJson("/api/v1/payroll-concepts/{$concept->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['success' => true]);
        $this->assertSoftDeleted('payroll_concepts', ['id' => $concept->id]);
    }

    /** @test */
    public function it_can_activate_a_payroll_concept()
    {
        $concept = PayrollConcept::factory()->create(['status' => 'inactive']);

        $response = $this->postJson("/api/v1/payroll-concepts/{$concept->id}/activate");

        $response->assertStatus(200)
                  ->assertJsonPath('data.status', 'active');

         $this->assertDatabaseHas('payroll_concepts', [
             'id' => $concept->id,
             'status' => 'active'
         ]);
    }

    /** @test */
    public function it_can_deactivate_a_payroll_concept()
    {
        $concept = PayrollConcept::factory()->create([
            'status' => 'active',
            'is_mandatory' => false
        ]);

        $response = $this->postJson("/api/v1/payroll-concepts/{$concept->id}/deactivate");

        $response->assertStatus(200)
                  ->assertJsonPath('data.status', 'inactive');

         $this->assertDatabaseHas('payroll_concepts', [
             'id' => $concept->id,
             'status' => 'inactive'
         ]);
    }

    /** @test */
    public function it_can_filter_concepts_by_type()
    {
        // Create test data with different types
        PayrollConcept::factory()->create(['type' => 'earning']);
        PayrollConcept::factory()->create(['type' => 'deduction']);
        PayrollConcept::factory()->create(['type' => 'earning']);

        $response = $this->getJson('/api/v1/payroll-concepts?type=earning');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        
        $this->assertGreaterThan(0, count($data));
        foreach ($data as $concept) {
            $this->assertEquals('earning', $concept['type']);
        }
    }

    /** @test */
    public function it_can_filter_concepts_by_status()
    {
        // Create test data with different statuses
        PayrollConcept::factory()->create(['status' => 'active']);
        PayrollConcept::factory()->create(['status' => 'inactive']);
        PayrollConcept::factory()->create(['status' => 'active']);

        $response = $this->getJson('/api/v1/payroll-concepts?status=active');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        
        $this->assertGreaterThan(0, count($data));
        foreach ($data as $concept) {
            $this->assertEquals('active', $concept['status']);
        }
    }

    /** @test */
    public function it_returns_404_when_concept_not_found()
    {
        $response = $this->getJson('/api/v1/payroll-concepts/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_validate_concept_formula()
    {
        $concept = PayrollConcept::factory()->create([
            'type' => 'earning',
            'calculation_type' => 'formula',
            'formula' => 'salary * 0.04'
        ]);

        $response = $this->postJson('/api/v1/payroll-concepts/validate-formula', [
            'formula' => $concept->formula,
            'base_concept_codes' => []
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'variables_found',
                         'base_concepts_used'
                     ]
                 ]);
    }

    /** @test */
    public function it_validates_concept_type_values()
    {
        $conceptData = [
            'name' => 'Concepto Inválido',
            'code' => 'INVALID',
            'type' => 'invalid_type',
            'calculation_type' => 'fixed',
            'default_value' => 1000.00,
            'status' => 'active',
            'description' => 'Test concept description',
            'is_taxable' => true,
            'affects_social_security' => true,
            'is_mandatory' => false
        ];

        $response = $this->postJson('/api/v1/payroll-concepts', $conceptData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['type']);
    }
}