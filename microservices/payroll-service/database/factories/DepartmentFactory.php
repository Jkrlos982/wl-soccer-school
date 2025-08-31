<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Recursos Humanos',
                'Contabilidad',
                'Ventas',
                'Marketing',
                'Tecnología',
                'Operaciones',
                'Administración',
                'Finanzas'
            ]),
            'code' => $this->faker->unique()->regexify('[A-Z]{2,4}'),
            'description' => $this->faker->sentence(),
            'manager_id' => null,
            'budget' => $this->faker->randomFloat(2, 1000000, 50000000),
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}