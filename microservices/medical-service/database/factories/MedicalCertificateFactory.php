<?php

namespace Database\Factories;

use App\Models\MedicalCertificate;
use App\Models\MedicalRecord;
use App\Models\MedicalExam;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicalCertificateFactory extends Factory
{
    protected $model = MedicalCertificate::class;

    public function definition()
    {
        return [
            'school_id' => 1,
            'player_id' => 1,
            'medical_record_id' => MedicalRecord::factory(),
            'medical_exam_id' => MedicalExam::factory(),
            'certificate_number' => 'CERT-' . $this->faker->unique()->numerify('########'),
            'certificate_type' => $this->faker->randomElement(['fitness_to_play', 'medical_clearance', 'return_to_play', 'injury_report']),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'issue_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'valid_from' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'valid_until' => $this->faker->dateTimeBetween('now', '+1 year'),
            'is_permanent' => $this->faker->boolean(10),
            'issued_by' => $this->faker->name(),
            'medical_center' => $this->faker->company() . ' Medical Center',
            'medical_findings' => $this->faker->optional()->paragraph(),
            'recommendations' => $this->faker->optional()->paragraph(),
            'restrictions' => $this->faker->optional()->sentence(),
            'status' => $this->faker->randomElement(['draft', 'issued', 'expired', 'revoked']),
            'clearance_status' => $this->faker->randomElement(['cleared', 'cleared_with_restrictions', 'not_cleared', 'pending_further_evaluation']),
            'revocation_reason' => null,
            'revocation_date' => null,
            'revoked_by' => null,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function issued()
    {
        return $this->state([
            'status' => 'issued',
            'valid_until' => $this->faker->dateTimeBetween('+1 month', '+1 year'),
        ]);
    }

    public function expired()
    {
        return $this->state([
            'status' => 'expired',
            'valid_until' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    public function fitness()
    {
        return $this->state([
            'title' => 'Certificado de Aptitud Física',
            'status' => 'issued',
        ]);
    }

    public function revoked()
    {
        return $this->state([
            'status' => 'revoked',
            'revocation_reason' => $this->faker->sentence(),
            'revocation_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'revoked_by' => 1,
        ]);
    }
}