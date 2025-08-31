<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Employee::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_number' => 'EMP' . $this->faker->unique()->numberBetween(1000, 9999),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'birth_date' => $this->faker->date('Y-m-d', '-25 years'),
            'gender' => $this->faker->randomElement(['M', 'F', 'Other']),
            'identification_type' => $this->faker->randomElement(['DNI', 'Passport', 'License']),
            'identification_number' => $this->faker->unique()->numerify('########'),
            'hire_date' => $this->faker->date('Y-m-d', '-2 years'),
            'employment_status' => $this->faker->randomElement(['active', 'inactive', 'terminated']),
            'employment_type' => $this->faker->randomElement(['full_time', 'part_time', 'contract', 'intern']),
            'base_salary' => $this->faker->numberBetween(30000, 100000),
            'bank_name' => $this->faker->company(),
            'bank_account_type' => $this->faker->randomElement(['checking', 'savings']),
            'bank_account_number' => $this->faker->bankAccountNumber(),
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => $this->faker->phoneNumber(),
            'emergency_contact_relationship' => $this->faker->randomElement(['spouse', 'parent', 'sibling', 'friend']),
            'tax_information' => json_encode([
                'tax_id' => $this->faker->numerify('###-##-####'),
                'withholding_allowances' => $this->faker->numberBetween(0, 5),
            ]),
        ];
    }

    /**
     * Indicate that the employee is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => 'active',
        ]);
    }

    /**
     * Indicate that the employee is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the employee is terminated.
     */
    public function terminated(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => 'terminated',
        ]);
    }

    /**
     * Indicate that the employee has a high salary.
     */
    public function highSalary(): static
    {
        return $this->state(fn (array $attributes) => [
            'base_salary' => $this->faker->numberBetween(80000, 150000),
        ]);
    }

    /**
     * Indicate that the employee is full time.
     */
    public function fullTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_type' => 'full_time',
        ]);
    }

    /**
     * Indicate that the employee is part time.
     */
    public function partTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_type' => 'part_time',
        ]);
    }

    /**
     * Indicate that the employee is recently hired.
     */
    public function recentlyHired(): static
    {
        return $this->state(fn (array $attributes) => [
            'hire_date' => $this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
        ]);
    }
}