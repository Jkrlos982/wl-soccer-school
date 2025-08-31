<?php

namespace Database\Factories;

use App\Models\PayrollConcept;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PayrollConcept>
 */
class PayrollConceptFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PayrollConcept::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['earning', 'deduction', 'tax', 'benefit']),
            'calculation_type' => $this->faker->randomElement(['fixed', 'percentage', 'formula']),
            'default_value' => $this->faker->randomFloat(4, 0, 5000),
            'formula' => null,
            'is_taxable' => $this->faker->boolean(),
            'affects_social_security' => $this->faker->boolean(),
            'is_mandatory' => $this->faker->boolean(),
            'display_order' => $this->faker->numberBetween(1, 100),
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }

    /**
     * Indicate that the payroll concept is an earning.
     */
    public function earning(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'earning',
            'category' => 'salary',
        ]);
    }

    /**
     * Indicate that the payroll concept is a deduction.
     */
    public function deduction(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'deduction',
            'category' => 'tax',
        ]);
    }

    /**
     * Indicate that the payroll concept is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}