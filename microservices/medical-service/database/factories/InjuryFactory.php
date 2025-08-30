<?php

namespace Database\Factories;

use App\Models\Injury;
use App\Models\MedicalRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Injury>
 */
class InjuryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Injury::class;

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
            'injury_code' => 'INJ-' . $this->faker->unique()->numerify('########'),
            'injury_type' => $this->faker->randomElement(['muscle', 'bone', 'ligament', 'tendon', 'joint']),
            'body_part' => $this->faker->randomElement(['knee', 'ankle', 'shoulder', 'wrist', 'back', 'neck']),
            'severity' => $this->faker->randomElement(['mild', 'moderate', 'severe']),
            'description' => $this->faker->paragraph(),
            'injury_datetime' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'injury_location' => $this->faker->optional()->address(),
            'injury_context' => $this->faker->randomElement(['training', 'match', 'other']),
            'injury_mechanism' => $this->faker->optional()->paragraph(),
            'witnessed' => $this->faker->boolean(),
            'witnesses' => $this->faker->optional()->randomElement([null, json_encode([$this->faker->name(), $this->faker->name()])]),
            'diagnosis' => $this->faker->optional()->sentence(),
            'diagnosed_by' => $this->faker->optional()->name(),
            'diagnosis_date' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'treatment_plan' => $this->faker->optional()->randomElement([null, json_encode(['rest', 'physiotherapy', 'medication'])]),
            'medications_prescribed' => $this->faker->optional()->randomElement([null, json_encode(['ibuprofen', 'paracetamol'])]),
            'requires_surgery' => $this->faker->boolean(20),
            'surgery_date' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'status' => $this->faker->randomElement(['active', 'recovering', 'recovered', 'chronic']),
            'estimated_recovery_days' => $this->faker->optional()->numberBetween(7, 180),
            'expected_return_date' => $this->faker->optional()->dateTimeBetween('now', '+3 months'),
            'actual_return_date' => $this->faker->optional()->dateTimeBetween('now', '+6 months'),
            'cleared_to_play' => $this->faker->boolean(30),
            'clearance_date' => $this->faker->optional()->dateTimeBetween('now', '+2 months'),
            'cleared_by' => $this->faker->optional()->name(),
            'prevention_measures' => $this->faker->optional()->randomElement([null, json_encode(['warm-up', 'stretching', 'proper equipment'])]),
            'return_to_play_protocol' => $this->faker->optional()->paragraph(),
            'requires_monitoring' => $this->faker->boolean(80),
            'monitoring_schedule' => $this->faker->optional()->randomElement([null, json_encode(['weekly', 'bi-weekly'])]),
            'training_days_missed' => $this->faker->numberBetween(0, 30),
            'matches_missed' => $this->faker->numberBetween(0, 10),
            'performance_impact_percentage' => $this->faker->optional()->randomFloat(2, 0, 100),
            'medical_reports' => $this->faker->optional()->randomElement([null, json_encode(['report1.pdf', 'report2.pdf'])]),
            'imaging_studies' => $this->faker->optional()->randomElement([null, json_encode(['xray.jpg', 'mri.jpg'])]),
            'progress_photos' => $this->faker->optional()->randomElement([null, json_encode(['photo1.jpg', 'photo2.jpg'])]),
            'reported_by' => 1,
            'updated_by' => 1,
        ];
    }

    /**
     * Indicate that the injury is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'expected_recovery_date' => $this->faker->dateTimeBetween('+1 week', '+2 months'),
        ]);
    }

    /**
     * Indicate that the injury is recovered.
     */
    public function recovered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'recovered',
            'return_to_play_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the injury is severe.
     */
    public function severe(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'severe',
            'expected_recovery_date' => $this->faker->dateTimeBetween('+1 month', '+6 months'),
        ]);
    }
}