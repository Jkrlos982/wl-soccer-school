<?php

namespace Database\Factories;

use App\Models\Position;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->randomElement([
                'Gerente General',
                'Jefe de Recursos Humanos',
                'Contador',
                'Asistente Contable',
                'Vendedor',
                'Jefe de Ventas',
                'Desarrollador',
                'Analista de Sistemas',
                'Secretaria',
                'Auxiliar Administrativo',
                'Coordinador de Marketing',
                'Especialista en Finanzas'
            ]),
            'code' => $this->faker->unique()->regexify('[A-Z]{3,5}'),
            'description' => $this->faker->sentence(),
            'department_id' => Department::factory(),
            'min_salary' => $this->faker->randomFloat(2, 1300000, 2000000),
            'max_salary' => $this->faker->randomFloat(2, 2500000, 8000000),
            'requirements' => $this->faker->paragraph(),
            'responsibilities' => $this->faker->paragraph(),
            'level' => $this->faker->randomElement(['entry', 'junior', 'mid', 'senior', 'lead', 'manager', 'director']),
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

    public function forDepartment(Department $department): static
    {
        return $this->state(fn (array $attributes) => [
            'department_id' => $department->id,
        ]);
    }
}