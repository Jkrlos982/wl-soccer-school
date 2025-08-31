<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payroll>
 */
class PayrollFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Payroll::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $baseSalary = $this->faker->randomFloat(2, 1000000, 5000000); // Colombian pesos
        $totalEarnings = $baseSalary + $this->faker->randomFloat(2, 0, 500000);
        $totalDeductions = $this->faker->randomFloat(2, 50000, 300000);
        $totalTaxes = $this->faker->randomFloat(2, 100000, 500000);
        $grossSalary = $totalEarnings;
        $netSalary = $grossSalary - $totalDeductions - $totalTaxes;

        return [
            'employee_id' => Employee::factory(),
            'payroll_period_id' => PayrollPeriod::factory(),
            'payroll_number' => 'PAY-' . date('Ym') . '-' . str_pad($this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'base_salary' => $baseSalary,
            'gross_salary' => $grossSalary,
            'total_earnings' => $totalEarnings,
            'total_deductions' => $totalDeductions,
            'total_taxes' => $totalTaxes,
            'net_salary' => $netSalary,
            'worked_days' => $this->faker->randomFloat(2, 20, 30),
            'worked_hours' => $this->faker->randomFloat(2, 160, 240),
            'regular_hours' => $this->faker->randomFloat(2, 160, 240),
            'overtime_hours' => $this->faker->randomFloat(2, 0, 20),
            'overtime_amount' => $this->faker->randomFloat(2, 0, 200000),
            'status' => $this->faker->randomElement(['draft', 'calculated', 'approved', 'paid']),
            'notes' => $this->faker->optional()->sentence(),
            'calculated_at' => $this->faker->optional()->dateTimeThisMonth(),
            'approved_at' => null,
            'paid_at' => null,
        ];
    }

    /**
     * Indicate that the payroll is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'calculated_at' => null,
            'approved_at' => null,
            'paid_at' => null,
        ]);
    }

    /**
     * Indicate that the payroll is calculated.
     */
    public function calculated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'calculated',
            'calculated_at' => $this->faker->dateTimeThisMonth(),
            'approved_at' => null,
            'paid_at' => null,
        ]);
    }

    /**
     * Indicate that the payroll is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'calculated_at' => $this->faker->dateTimeThisMonth(),
            'approved_at' => $this->faker->dateTimeThisMonth(),
            'paid_at' => null,
        ]);
    }

    /**
     * Indicate that the payroll is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'calculated_at' => $this->faker->dateTimeThisMonth(),
            'approved_at' => $this->faker->dateTimeThisMonth(),
            'paid_at' => $this->faker->dateTimeThisMonth(),
        ]);
    }
}