<?php

namespace App\Services;

use App\Models\MedicalRecord;
use App\Models\MedicalExam;
use App\Models\Injury;
use App\Models\MedicalCertificate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;

class MedicalService
{
    /**
     * Create a new medical record.
     */
    public function createMedicalRecord(array $data): MedicalRecord
    {
        try {
            DB::beginTransaction();
            
            // Generate unique record number before creating
            $year = now()->year;
            $schoolCode = str_pad($data['school_id'], 3, '0', STR_PAD_LEFT);
            $sequence = str_pad(
                MedicalRecord::where('school_id', $data['school_id'])
                    ->whereYear('created_at', $year)
                    ->count() + 1,
                4, '0', STR_PAD_LEFT
            );
            $data['record_number'] = "MR-{$year}-{$schoolCode}-{$sequence}";
            $data['is_active'] = true;
            
            $record = MedicalRecord::create($data);
            
            // Log the creation
            Log::info('Medical record created', [
                'record_id' => $record->id,
                'player_id' => $record->player_id,
                'school_id' => $record->school_id
            ]);
            
            DB::commit();
            return $record;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create medical record', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }
    
    /**
     * Update a medical record.
     */
    public function updateMedicalRecord(int $recordId, array $data): MedicalRecord
    {
        try {
            $record = MedicalRecord::findOrFail($recordId);
            
            // Log access before update
            $userId = Auth::check() ? Auth::id() : 0;
            $record->logAccess($userId, 'update');
            
            $record->update($data);
            
            Log::info('Medical record updated', [
                'record_id' => $record->id,
                'updated_fields' => array_keys($data)
            ]);
            
            return $record->fresh();
            
        } catch (Exception $e) {
            Log::error('Failed to update medical record', [
                'record_id' => $recordId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get medical record by ID.
     */
    public function getMedicalRecord(int $recordId): MedicalRecord
    {
        $record = MedicalRecord::with([
            'medicalExams',
            'injuries',
            'medicalCertificates',
            'creator',
            'updater'
        ])->findOrFail($recordId);
        
        // Log access
        $userId = Auth::check() ? Auth::id() : 0;
        $record->logAccess($userId, 'view');
        
        return $record;
    }
    
    /**
     * Get medical records for a school with pagination.
     */
    public function getSchoolMedicalRecords(int $schoolId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = MedicalRecord::forSchool($schoolId)
            ->with(['medicalExams', 'injuries', 'certificates']);
        
        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['medical_clearance'])) {
            $query->where('medical_clearance', $filters['medical_clearance']);
        }
        
        if (isset($filters['exam_due'])) {
            $query->examDue();
        }
        
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('record_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }
        
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
    
    /**
     * Get medical records by player ID.
     */
    public function getPlayerMedicalRecords(int $playerId): Collection
    {
        return MedicalRecord::where('player_id', $playerId)
            ->with(['medicalExams', 'injuries', 'certificates'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    /**
     * Schedule medical exam.
     */
    public function scheduleMedicalExam(int $recordId, array $examData): MedicalExam
    {
        try {
            DB::beginTransaction();
            
            $record = MedicalRecord::findOrFail($recordId);
            
            $exam = MedicalExam::create(array_merge($examData, [
                'medical_record_id' => $recordId,
                'school_id' => $record->school_id,
                'player_id' => $record->player_id,
                'exam_code' => $this->generateExamCode(),
                'scheduled_by' => 1, // Default admin user
                'status' => 'scheduled'
            ]));
            
            // Update next exam date in medical record
            $record->update([
                'next_medical_exam' => $exam->exam_date
            ]);
            
            Log::info('Medical exam scheduled', [
                'exam_id' => $exam->id,
                'record_id' => $recordId,
                'exam_date' => $exam->exam_date
            ]);
            
            DB::commit();
            return $exam;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to schedule medical exam', [
                'record_id' => $recordId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Complete medical exam.
     */
    public function completeMedicalExam(int $examId, array $results, int $userId = 1): MedicalExam
    {
        try {
            DB::beginTransaction();
            
            $exam = MedicalExam::findOrFail($examId);
            
            $exam->update([
                 'status' => 'completed',
                 'result' => $results['results'] ?? $results['result'] ?? null,
                 'observations' => $results['observations'] ?? null,
                 'completed_by' => $userId,
                 'vital_signs' => $results['vital_signs'] ?? null,
                 'physical_tests' => $results['physical_tests'] ?? null,
                 'recommendations' => $results['recommendations'] ?? null,
                 'valid_from' => $results['valid_from'] ?? null,
                 'valid_until' => $results['valid_until'] ?? null,
                 'requires_followup' => $results['requires_followup'] ?? false,
                 'followup_date' => $results['followup_date'] ?? null,
                 'completed_by' => $userId
             ]);
            
            // Update medical record clearance if exam passed
            if (isset($results['result']) && $results['result'] === 'passed') {
                $exam->medicalRecord->update([
                    'medical_clearance' => true,
                    'clearance_expiry_date' => now()->addYear(),
                    'last_medical_exam' => $exam->exam_date
                ]);
            }
            
            Log::info('Medical exam completed', [
                'exam_id' => $examId,
                'result' => $results['result'] ?? 'unknown'
            ]);
            
            DB::commit();
            return $exam->fresh();
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete medical exam', [
                'exam_id' => $examId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Record injury.
     */
    public function recordInjury(int $recordId, array $injuryData): Injury
    {
        try {
            $medicalRecord = MedicalRecord::findOrFail($recordId);
            
            // Ensure required fields are set
            if (!isset($injuryData['description']) || empty($injuryData['description'])) {
                $injuryData['description'] = 'Injury reported during medical examination';
            }
            if (!isset($injuryData['injury_context']) || empty($injuryData['injury_context'])) {
                $injuryData['injury_context'] = 'other';
            }
            
            $injury = Injury::create(array_merge($injuryData, [
                'medical_record_id' => $recordId,
                'school_id' => $medicalRecord->school_id,
                'player_id' => $medicalRecord->player_id,
                'injury_code' => $this->generateInjuryCode(),
                'reported_by' => 1,
                'status' => 'active'
            ]));
            
            // Update medical clearance if injury is severe
            if (in_array($injuryData['severity'] ?? '', ['severe', 'critical'])) {
                MedicalRecord::findOrFail($recordId)->update([
                    'medical_clearance' => false
                ]);
            }
            
            Log::info('Injury recorded', [
                'injury_id' => $injury->id,
                'record_id' => $recordId,
                'severity' => $injuryData['severity'] ?? 'unknown'
            ]);
            
            return $injury;
            
        } catch (Exception $e) {
            Log::error('Failed to record injury', [
                'record_id' => $recordId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Update injury status.
     */
    public function updateInjuryStatus(int $injuryId, string $status, array $additionalData = []): Injury
    {
        try {
            $injury = Injury::findOrFail($injuryId);
            
            $updateData = array_merge($additionalData, ['status' => $status]);
            
            if ($status === 'recovered') {
                $updateData['recovery_date'] = now();
            }
            
            $injury->update($updateData);
            
            Log::info('Injury status updated', [
                'injury_id' => $injuryId,
                'new_status' => $status
            ]);
            
            return $injury->fresh();
            
        } catch (Exception $e) {
            Log::error('Failed to update injury status', [
                'injury_id' => $injuryId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Generate medical certificate.
     */
    public function generateMedicalCertificate(int $recordId, array $certificateData): MedicalCertificate
    {
        try {
            // Get the medical record to extract school_id and player_id
            $medicalRecord = MedicalRecord::findOrFail($recordId);
            
            $certificate = MedicalCertificate::create(array_merge($certificateData, [
                'medical_record_id' => $recordId,
                'school_id' => $medicalRecord->school_id,
                'player_id' => $medicalRecord->player_id,
                'certificate_number' => $this->generateCertificateNumber(),
                'issue_date' => now()->toDateString(),
                'status' => 'issued',
                'created_by' => $medicalRecord->created_by ?? 1
            ]));
            
            Log::info('Medical certificate generated', [
                'certificate_id' => $certificate->id,
                'record_id' => $recordId,
                'certificate_number' => $certificate->certificate_number
            ]);
            
            return $certificate;
            
        } catch (Exception $e) {
            Log::error('Failed to generate medical certificate', [
                'record_id' => $recordId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get medical statistics for a school.
     */
    public function getSchoolMedicalStats(int $schoolId): array
    {
        $totalRecords = MedicalRecord::forSchool($schoolId)->count();
        $activeRecords = MedicalRecord::forSchool($schoolId)->active()->count();
        $withClearance = MedicalRecord::forSchool($schoolId)->withValidClearance()->count();
        $examsDue = MedicalRecord::forSchool($schoolId)->examDue()->count();
        $activeInjuries = Injury::whereHas('medicalRecord', function($q) use ($schoolId) {
            $q->forSchool($schoolId);
        })->whereIn('status', ['active', 'recovering'])->count();
        
        // Get pending exams for the school
        $pendingExams = MedicalExam::whereHas('medicalRecord', function($q) use ($schoolId) {
            $q->forSchool($schoolId);
        })->where('status', 'scheduled')->count();
        
        // Get expiring certificates for the school
        $expiringCertificates = MedicalCertificate::whereHas('medicalRecord', function($q) use ($schoolId) {
            $q->forSchool($schoolId);
        })->where('valid_until', '<=', now()->addDays(30))
          ->where('valid_until', '>', now())
          ->count();
        
        return [
            'total_records' => $totalRecords,
            'active_records' => $activeRecords,
            'with_clearance' => $withClearance,
            'clearance_percentage' => $totalRecords > 0 ? round(($withClearance / $totalRecords) * 100, 2) : 0,
            'exams_due' => $examsDue,
            'pending_exams' => $pendingExams,
            'active_injuries' => $activeInjuries,
            'expiring_certificates' => $expiringCertificates,
            'last_updated' => now()->toISOString()
        ];
    }
    
    /**
     * Get records requiring attention.
     */
    public function getRecordsRequiringAttention(int $schoolId): Collection
    {
        return MedicalRecord::forSchool($schoolId)
            ->where(function($query) {
                $query->examDue()
                      ->orWhere('medical_clearance', false)
                      ->orWhereHas('injuries', function($q) {
                          $q->whereIn('status', ['active', 'recovering']);
                      });
            })
            ->with(['medicalExams', 'injuries'])
            ->orderBy('updated_at', 'desc')
            ->get();
    }
    
    /**
     * Validate medical clearance.
     */
    public function validateMedicalClearance(int $recordId): array
    {
        $record = MedicalRecord::findOrFail($recordId);
        
        // Get the most recent valid certificate
        $certificate = $record->medicalCertificates()
            ->where('status', 'issued')
            ->where('valid_until', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();
        
        $validations = [
            'has_clearance' => $record->medical_clearance,
            'clearance_expired' => $record->isClearanceExpired(),
            'exam_due' => $record->isMedicalExamDue(),
            'has_active_injuries' => $record->hasActiveInjuries(),
            'consent_given' => $record->consent_given,
            'certificate' => $certificate,
            'status' => $certificate ? 'cleared' : 'no_certificate'
        ];
        
        $validations['is_valid'] = $validations['has_clearance'] && 
                                  !$validations['clearance_expired'] && 
                                  !$validations['exam_due'] && 
                                  !$validations['has_active_injuries'] && 
                                  $validations['consent_given'] &&
                                  $certificate !== null;
        
        return $validations;
    }
    
    /**
     * Generate certificate number.
     */
    private function generateCertificateNumber(): string
    {
        $year = now()->year;
        $sequence = str_pad(
            MedicalCertificate::whereYear('created_at', $year)->count() + 1,
            6, '0', STR_PAD_LEFT
        );
        
        return "MC-{$year}-{$sequence}";
    }
    
    /**
     * Generate unique injury code.
     */
    private function generateInjuryCode(): string
    {
        $year = now()->year;
        $month = now()->format('m');
        $sequence = str_pad(Injury::whereYear('created_at', $year)->count() + 1, 4, '0', STR_PAD_LEFT);
        
        return "INJ-{$year}{$month}-{$sequence}";
    }
    
    private function generateExamCode(): string
    {
        $year = now()->year;
        $month = now()->format('m');
        $sequence = str_pad(MedicalExam::whereYear('created_at', $year)->count() + 1, 4, '0', STR_PAD_LEFT);
        
        return "EXM-{$year}{$month}-{$sequence}";
    }
    
    /**
     * Bulk update medical clearances.
     */
    public function bulkUpdateClearances(array $recordIds, bool $clearance, ?Carbon $expiryDate = null): int
    {
        try {
            $updateData = ['medical_clearance' => $clearance];
            
            if ($expiryDate) {
                $updateData['clearance_expiry_date'] = $expiryDate;
            }
            
            $updated = MedicalRecord::whereIn('id', $recordIds)->update($updateData);
            
            Log::info('Bulk clearance update completed', [
                'records_updated' => $updated,
                'clearance' => $clearance
            ]);
            
            return $updated;
            
        } catch (Exception $e) {
            Log::error('Failed to bulk update clearances', [
                'error' => $e->getMessage(),
                'record_ids' => $recordIds
            ]);
            throw $e;
        }
    }
    
    /**
     * Export medical records data.
     */
    public function exportMedicalRecords(int $schoolId, array $filters = []): Collection
    {
        $query = MedicalRecord::forSchool($schoolId)
            ->with(['medicalExams', 'injuries', 'certificates']);
        
        // Apply date range filter
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        // Apply status filter
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }
}