<?php

namespace Database\Factories;

use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class PayrollPeriodFactory extends Factory
{
    protected $model = PayrollPeriod::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', '+3 months');
        $startDateCarbon = Carbon::parse($startDate);
        $endDate = $startDateCarbon->copy()->endOfMonth();
        $payDate = $endDate->copy()->addDays(5); // Pay 5 days after period ends
        
        return [
            'name' => $startDateCarbon->format('F Y'),
            'start_date' => $startDateCarbon->startOfMonth(),
            'end_date' => $endDate,
            'pay_date' => $payDate,
            'period_type' => $this->faker->randomElement(['weekly', 'biweekly', 'monthly', 'quarterly']),
            'status' => 'draft',
            'year' => $startDateCarbon->year,
            'month' => $startDateCarbon->month,
            'period_number' => $this->faker->numberBetween(1, 12),
            'total_gross' => $this->faker->randomFloat(2, 1000000, 50000000), // 1M to 50M COP
            'total_deductions' => $this->faker->randomFloat(2, 100000, 5000000), // 100K to 5M COP
            'total_net' => $this->faker->randomFloat(2, 900000, 45000000), // 900K to 45M COP
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
        ]);
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    public function current(): static
    {
        $now = Carbon::now();
        return $this->state(fn (array $attributes) => [
            'name' => $now->format('F Y'),
            'start_date' => $now->startOfMonth()->copy(),
            'end_date' => $now->endOfMonth()->copy(),
            'status' => 'open',
        ]);
    }

    public function forMonth(int $year, int $month): static
    {
        $date = Carbon::create($year, $month, 1);
        return $this->state(fn (array $attributes) => [
            'name' => $date->format('F Y'),
            'start_date' => $date->startOfMonth()->copy(),
            'end_date' => $date->endOfMonth()->copy(),
        ]);
    }
}