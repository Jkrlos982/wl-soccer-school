<?php

namespace Database\Factories;

use App\Models\MedicalExam;
use App\Models\MedicalRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MedicalExam>
 */
class MedicalExamFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MedicalExam::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'medical_record_id' => MedicalRecord::factory(),
            'school_id' => 1,
            'player_id' => 1,
            'exam_type' => $this->faker->randomElement(['annual', 'pre_season', 'injury_return', 'fitness_assessment']),
            'exam_code' => 'EX-' . $this->faker->unique()->numerify('########'),
            'exam_date' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
            'exam_time' => $this->faker->optional()->time(),
            'location' => $this->faker->optional()->address(),
            'doctor_name' => 'Dr. ' . $this->faker->name(),
            'doctor_license_number' => 'LIC-' . $this->faker->numerify('######'),
            'doctor_specialty' => $this->faker->optional()->randomElement(['Sports Medicine', 'General Medicine', 'Orthopedics']),
            'medical_center' => $this->faker->optional()->company() . ' Medical Center',
            'status' => $this->faker->randomElement(['scheduled', 'completed', 'cancelled', 'no_show']),
            'result' => $this->faker->optional()->randomElement(['approved', 'conditional', 'rejected', 'pending']),
            'observations' => $this->faker->optional()->paragraph(),
            'vital_signs' => $this->faker->optional()->randomElement([
                json_encode(['blood_pressure' => '120/80', 'heart_rate' => 72, 'temperature' => 36.5]),
                null
            ]),
            'physical_tests' => $this->faker->optional()->randomElement([
                json_encode(['flexibility' => 'good', 'strength' => 'excellent', 'endurance' => 'good']),
                null
            ]),
            'recommendations' => $this->faker->optional()->randomElement([
                json_encode(['rest' => '2 days', 'hydration' => 'increase', 'follow_up' => 'in 1 month']),
                null
            ]),
            'valid_from' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'valid_until' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
            'requires_followup' => $this->faker->boolean(20),
            'followup_date' => $this->faker->optional()->dateTimeBetween('+1 week', '+1 month'),
            'followup_notes' => $this->faker->optional()->sentence(),
            'attachments' => null,
            'certificate_path' => null,
            'cost' => $this->faker->optional()->randomFloat(2, 50, 500),
            'paid' => $this->faker->boolean(60),
            'payment_date' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'invoice_number' => $this->faker->optional()->numerify('INV-######'),
            'scheduled_by' => 1,
            'completed_by' => $this->faker->optional()->numberBetween(1, 5),
        ];
    }

    /**
     * Indicate that the exam is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'result' => 'approved',
            'observations' => $this->faker->paragraph(),
            'completed_by' => 1,
        ]);
    }

    /**
     * Indicate that the exam is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'exam_date' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
            'result' => null,
            'completed_by' => null,
        ]);
    }
}