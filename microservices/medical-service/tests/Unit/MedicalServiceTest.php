<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\MedicalService;
use App\Models\MedicalRecord;
use App\Models\MedicalExam;
use App\Models\Injury;
use App\Models\MedicalCertificate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Mockery;

class MedicalServiceTest extends TestCase
{
    use RefreshDatabase;

    private MedicalService $medicalService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'sqlite']);
        $this->medicalService = new MedicalService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_create_medical_record()
    {
        $data = [
            'school_id' => 1,
            'player_id' => 1,
            'blood_type' => 'O+',
            'height' => 175.5,
            'weight' => 70.2,
            'allergies' => ['peanuts', 'shellfish'],
            'medications' => ['aspirin'],
            'emergency_contacts' => [
                ['name' => 'John Doe', 'phone' => '123-456-7890', 'relationship' => 'father']
            ],
            'insurance_provider' => 'Health Insurance Co.',
            'created_by' => 1
        ];

        $record = $this->medicalService->createMedicalRecord($data);

        $this->assertInstanceOf(MedicalRecord::class, $record);
        $this->assertEquals($data['school_id'], $record->school_id);
        $this->assertEquals($data['player_id'], $record->player_id);
        $this->assertEquals($data['blood_type'], $record->blood_type);
        $this->assertEquals($data['height'], $record->height);
        $this->assertEquals($data['weight'], $record->weight);
        $this->assertNotNull($record->record_number);
        $this->assertTrue($record->is_active);
    }

    /** @test */
    public function it_can_update_medical_record()
    {
        $record = MedicalRecord::factory()->create([
            'blood_type' => 'A+',
            'height' => 170.0,
            'weight' => 65.0
        ]);

        $updateData = [
            'blood_type' => 'B+',
            'height' => 175.0,
            'weight' => 70.0,
            'updated_by' => 1
        ];

        $updatedRecord = $this->medicalService->updateMedicalRecord($record->id, $updateData);

        $this->assertEquals('B+', $updatedRecord->blood_type);
        $this->assertEquals(175.0, $updatedRecord->height);
        $this->assertEquals(70.0, $updatedRecord->weight);
        $this->assertEquals(1, $updatedRecord->updated_by);
    }

    /** @test */
    public function it_can_get_medical_record_by_id()
    {
        $record = MedicalRecord::factory()->create();

        $foundRecord = $this->medicalService->getMedicalRecord($record->id);

        $this->assertInstanceOf(MedicalRecord::class, $foundRecord);
        $this->assertEquals($record->id, $foundRecord->id);
    }

    /** @test */
    public function it_can_get_medical_records_by_school()
    {
        $schoolId = 1;
        MedicalRecord::factory()->count(3)->create(['school_id' => $schoolId]);
        MedicalRecord::factory()->count(2)->create(['school_id' => 2]);

        $records = MedicalRecord::where('school_id', $schoolId)->get();

        $this->assertInstanceOf(Collection::class, $records);
        $this->assertCount(3, $records);
        $records->each(function ($record) use ($schoolId) {
            $this->assertEquals($schoolId, $record->school_id);
        });
    }

    /** @test */
    public function it_can_schedule_medical_exam()
    {
        $record = MedicalRecord::factory()->create();
        $examData = [
            'exam_type' => 'annual_physical',
            'exam_date' => now()->addDays(7)->format('Y-m-d'),
            'doctor_name' => 'Dr. Smith',
            'notes' => 'Annual physical examination'
        ];

        $exam = $this->medicalService->scheduleMedicalExam($record->id, $examData);

        $this->assertInstanceOf(MedicalExam::class, $exam);
        $this->assertEquals($record->id, $exam->medical_record_id);
        $this->assertEquals($examData['exam_type'], $exam->exam_type);
        $this->assertEquals($examData['doctor_name'], $exam->doctor_name);
        $this->assertEquals('scheduled', $exam->status);
    }

    /** @test */
    public function it_can_complete_medical_exam()
    {
        $exam = MedicalExam::factory()->create(['status' => 'scheduled']);
        $completionData = [
            'results' => 'approved',
            'recommendations' => 'Continue regular exercise',
            'next_exam_date' => now()->addYear()->format('Y-m-d')
        ];

        $completedExam = $this->medicalService->completeMedicalExam($exam->id, $completionData);

        $this->assertEquals('completed', $completedExam->status);
        $this->assertEquals($completionData['results'], $completedExam->result);
        $this->assertEquals($completionData['recommendations'], $completedExam->recommendations);
        $this->assertNotNull($completedExam->completed_by);
    }

    /** @test */
    public function it_can_record_injury()
    {
        $record = MedicalRecord::factory()->create();
        $injuryData = [
            'injury_type' => 'sprain',
            'body_part' => 'ankle',
            'severity' => 'moderate',
            'injury_datetime' => now(),
            'description' => 'Twisted ankle during practice',
            'estimated_recovery_days' => 14
        ];

        $injury = $this->medicalService->recordInjury($record->id, $injuryData);

        $this->assertInstanceOf(Injury::class, $injury);
        $this->assertEquals($record->id, $injury->medical_record_id);
        $this->assertEquals($injuryData['injury_type'], $injury->injury_type);
        $this->assertEquals($injuryData['body_part'], $injury->body_part);
        $this->assertEquals($injuryData['severity'], $injury->severity);
        $this->assertEquals('active', $injury->status);
    }

    /** @test */
    public function it_can_update_injury_status()
    {
        $injury = Injury::factory()->create(['status' => 'active']);
        $statusData = [
            'status' => 'recovering',
            'recovery_notes' => 'Patient responding well to treatment'
        ];

        $updatedInjury = $this->medicalService->updateInjuryStatus($injury->id, $statusData['status']);

        $this->assertEquals('recovering', $updatedInjury->status);
    }

    /** @test */
    public function it_can_generate_medical_certificate()
    {
        $record = MedicalRecord::factory()->create();
        $certificateData = [
            'certificate_type' => 'fitness_to_play',
            'title' => 'Fitness to Play Certificate',
            'description' => 'Annual physical examination certificate',
            'valid_from' => now()->format('Y-m-d'),
            'valid_until' => now()->addMonths(6)->format('Y-m-d'),
            'issued_by' => 'Dr. Johnson',
            'doctor_license' => 'MD12345',
            'clearance_status' => 'cleared'
        ];

        $certificate = $this->medicalService->generateMedicalCertificate($record->id, $certificateData);

        $this->assertInstanceOf(MedicalCertificate::class, $certificate);
        $this->assertEquals($record->id, $certificate->medical_record_id);
        $this->assertEquals($certificateData['certificate_type'], $certificate->certificate_type);
        $this->assertEquals($certificateData['title'], $certificate->title);
        $this->assertEquals($certificateData['clearance_status'], $certificate->clearance_status);
        $this->assertNotNull($certificate->certificate_number);
    }

    /** @test */
    public function it_can_validate_medical_clearance()
    {
        $record = MedicalRecord::factory()->create([
            'medical_clearance' => true,
            'consent_given' => true,
            'clearance_expiry_date' => now()->addMonths(6),
            'next_medical_exam' => now()->addMonths(12)
        ]);
        
        // Create a valid certificate
        MedicalCertificate::factory()->create([
            'medical_record_id' => $record->id,
            'school_id' => $record->school_id,
            'player_id' => $record->player_id,
            'certificate_type' => 'fitness_to_play',
            'title' => 'Fitness to Play Certificate',
            'description' => 'Medical clearance for sports participation',
            'issue_date' => now()->format('Y-m-d'),
            'issued_by' => 'Dr. Smith',
            'clearance_status' => 'cleared',
            'status' => 'issued',
            'valid_until' => now()->addMonths(3),
            'created_by' => 1
        ]);

        $clearance = $this->medicalService->validateMedicalClearance($record->id);

        $this->assertTrue($clearance['is_valid']);
        $this->assertEquals('cleared', $clearance['status']);
        $this->assertNotNull($clearance['certificate']);
    }

    /** @test */
    public function it_returns_invalid_clearance_when_no_certificate_exists()
    {
        $record = MedicalRecord::factory()->create();

        $clearance = $this->medicalService->validateMedicalClearance($record->id);

        $this->assertFalse($clearance['is_valid']);
        $this->assertEquals('no_certificate', $clearance['status']);
        $this->assertNull($clearance['certificate']);
    }

    /** @test */
    public function it_can_get_health_statistics()
    {
        // Clear any existing data
        MedicalRecord::query()->delete();
        MedicalExam::query()->delete();
        Injury::query()->delete();
        MedicalCertificate::query()->delete();
        
        // Create test data
        $records = MedicalRecord::factory()->count(5)->create(['school_id' => 999]);
        MedicalExam::factory()->count(3)->create([
            'status' => 'scheduled',
            'medical_record_id' => $records->first()->id
        ]);
        Injury::factory()->count(2)->create([
            'status' => 'active',
            'medical_record_id' => $records->first()->id
        ]);
        MedicalCertificate::factory()->count(4)->create([
            'valid_until' => now()->addDays(30),
            'medical_record_id' => $records->first()->id
        ]);

        $stats = $this->medicalService->getSchoolMedicalStats(999);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_records', $stats);
        $this->assertArrayHasKey('pending_exams', $stats);
        $this->assertArrayHasKey('active_injuries', $stats);
        $this->assertArrayHasKey('expiring_certificates', $stats);
        $this->assertEquals(5, $stats['total_records']);
    }

    /** @test */
    public function it_can_get_records_requiring_attention()
    {
        $schoolId = 1;
        
        // Clear any existing data
        MedicalRecord::query()->delete();
        Injury::query()->delete();
        MedicalCertificate::query()->delete();
        
        // Create records that need attention
        $recordWithoutClearance = MedicalRecord::factory()->create([
            'school_id' => $schoolId,
            'medical_clearance' => false
        ]);

        $recordWithActiveInjury = MedicalRecord::factory()->create(['school_id' => $schoolId]);
        Injury::factory()->create([
            'medical_record_id' => $recordWithActiveInjury->id,
            'status' => 'active'
        ]);

        $records = $this->medicalService->getRecordsRequiringAttention($schoolId);

        $this->assertInstanceOf(Collection::class, $records);
        $this->assertGreaterThanOrEqual(2, $records->count());
    }

    /** @test */
    public function it_can_get_player_medical_records()
    {
        $playerId = 1;
        $records = MedicalRecord::factory()->count(2)->create(['player_id' => $playerId]);
        MedicalRecord::factory()->create(['player_id' => 2]); // Different player

        $playerRecords = $this->medicalService->getPlayerMedicalRecords($playerId);

        $this->assertInstanceOf(Collection::class, $playerRecords);
        $this->assertCount(2, $playerRecords);
        $playerRecords->each(function ($record) use ($playerId) {
            $this->assertEquals($playerId, $record->player_id);
        });
    }

    /** @test */
    public function it_can_bulk_update_medical_records()
    {
        $records = MedicalRecord::factory()->count(3)->create(['school_id' => 1]);
        $recordIds = $records->pluck('id')->toArray();
        
        $updateData = [
            'insurance_provider' => 'New Insurance Co.',
            'updated_by' => 1
        ];

        $result = $this->medicalService->bulkUpdateClearances($recordIds, true);

        $this->assertEquals(3, $result);
    }

    /** @test */
    public function it_can_export_medical_data()
    {
        $schoolId = 1;
        MedicalRecord::factory()->count(3)->create(['school_id' => $schoolId]);

        $exportData = $this->medicalService->exportMedicalRecords($schoolId);

        $this->assertInstanceOf(Collection::class, $exportData);
        $this->assertCount(3, $exportData);
    }

    /** @test */
    public function it_throws_exception_for_invalid_medical_record_id()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->medicalService->getMedicalRecord(999);
    }

    /** @test */
    public function it_throws_exception_for_invalid_exam_id()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->medicalService->completeMedicalExam(999, [
            'result' => 'approved',
            'observations' => 'Test observations',
            'vital_signs' => ['heart_rate' => 70],
            'physical_tests' => ['flexibility' => 'good'],
            'recommendations' => 'Continue training',
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addYear()->toDateString()
        ], 1);
    }

    /** @test */
    public function it_throws_exception_for_invalid_injury_id()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->medicalService->updateInjuryStatus(999, 'recovered');
    }
}