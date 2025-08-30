<?php

namespace Database\Factories;

use App\Models\MedicalRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MedicalRecord>
 */
class MedicalRecordFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MedicalRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => $this->faker->numberBetween(1, 10),
            'player_id' => $this->faker->numberBetween(1, 100),
            'record_number' => 'MR-' . now()->year . '-' . str_pad($this->faker->numberBetween(1, 999), 3, '0', STR_PAD_LEFT) . '-' . str_pad($this->faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'blood_type' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'height' => $this->faker->numberBetween(150, 200),
            'weight' => $this->faker->numberBetween(50, 120),
            'allergies' => [
                $this->faker->randomElement(['Ninguna', 'Polen', 'Mariscos', 'Nueces', 'Medicamentos'])
            ],
            'chronic_conditions' => [
                $this->faker->randomElement(['Ninguna', 'Asma', 'Diabetes', 'Hipertensión'])
            ],
            'medications' => [
                $this->faker->randomElement(['Ninguna', 'Vitaminas', 'Suplementos', 'Medicamentos específicos'])
            ],
            'emergency_contacts' => [
                [
                    'name' => $this->faker->name,
                    'relationship' => $this->faker->randomElement(['Padre', 'Madre', 'Tutor', 'Hermano']),
                    'phone' => $this->faker->phoneNumber,
                    'email' => $this->faker->email
                ]
            ],
            'insurance_provider' => $this->faker->company,
            'insurance_policy_number' => $this->faker->numerify('POL-########'),
            'insurance_expiry_date' => $this->faker->dateTimeBetween('now', '+2 years'),
            'primary_doctor_name' => 'Dr. ' . $this->faker->name,
            'primary_doctor_phone' => $this->faker->phoneNumber,
            'primary_doctor_email' => $this->faker->email,
            'is_active' => true,
            'status' => $this->faker->randomElement(['complete', 'incomplete', 'under_review']),
            'notes' => $this->faker->optional()->sentence,
            'last_medical_exam' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'next_medical_exam' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
            'medical_clearance' => $this->faker->boolean(70),
            'clearance_expiry_date' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
            'access_log' => [],
            'consent_given' => true,
            'consent_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'consent_given_by' => $this->faker->name,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    /**
     * Indicate that the medical record is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'status' => 'incomplete',
        ]);
    }

    /**
     * Indicate that the medical record has expired clearance.
     */
    public function expiredClearance(): static
    {
        return $this->state(fn (array $attributes) => [
            'medical_clearance' => false,
            'clearance_expiry_date' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    /**
     * Indicate that the medical record needs exam.
     */
    public function needsExam(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_medical_exam' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }
}