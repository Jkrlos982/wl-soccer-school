<?php

namespace App\Http\Controllers;

use App\Models\MedicalRecord;
use App\Models\MedicalExam;
use App\Models\Injury;
use App\Models\InjuryFollowup;
use App\Models\MedicalCertificate;
use App\Services\MedicalService;
use App\Http\Requests\StoreMedicalRecordRequest;
use App\Http\Requests\UpdateMedicalRecordRequest;
use App\Http\Requests\ScheduleMedicalExamRequest;
use App\Http\Requests\RecordInjuryRequest;
use App\Http\Requests\GenerateCertificateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Exception;

class MedicalController extends Controller
{
    protected MedicalService $medicalService;
    
    public function __construct(MedicalService $medicalService)
    {
        $this->medicalService = $medicalService;
    }
    
    /**
     * Display a listing of medical records.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $schoolId = $request->get('school_id');
            $filters = $request->only(['status', 'medical_clearance', 'exam_due', 'search']);
            $perPage = $request->get('per_page', 15);
            
            if ($schoolId) {
                $records = $this->medicalService->getSchoolMedicalRecords($schoolId, $filters, $perPage);
            } else {
                $records = MedicalRecord::with(['medicalExams', 'injuries', 'medicalCertificates'])
                    ->active()
                    ->paginate($perPage);
            }

            return response()->json([
                'status' => 'success',
                'data' => $records,
                'message' => 'Medical records retrieved successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve medical records',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created medical record.
     */
    public function store(StoreMedicalRecordRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['created_by'] = Auth::id() ?? 1;
            
            $record = $this->medicalService->createMedicalRecord($validated);

            return response()->json([
                'status' => 'success',
                'data' => $record,
                'message' => 'Medical record created successfully'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create medical record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified medical record.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $record = $this->medicalService->getMedicalRecord($id);

            return response()->json([
                'status' => 'success',
                'data' => $record,
                'message' => 'Medical record retrieved successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Medical record not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified medical record.
     */
    public function update(UpdateMedicalRecordRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = Auth::id() ?? 1;
            
            $record = $this->medicalService->updateMedicalRecord($id, $validated);

            return response()->json([
                'status' => 'success',
                'data' => $record,
                'message' => 'Medical record updated successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update medical record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified medical record.
     */
    public function destroy(MedicalRecord $medicalRecord): JsonResponse
    {
        $medicalRecord->update(['is_active' => false]);

        return response()->json([
            'status' => 'success',
            'message' => 'Medical record deactivated successfully'
        ]);
    }

    /**
     * Get medical exams for a specific record.
     */
    public function getExams(MedicalRecord $medicalRecord): JsonResponse
    {
        $exams = $medicalRecord->medicalExams()->with('medicalCertificates')->get();

        return response()->json([
            'status' => 'success',
            'data' => $exams,
            'message' => 'Medical exams retrieved successfully'
        ]);
    }

    /**
     * Get injuries for a specific record.
     */
    public function getInjuries(MedicalRecord $medicalRecord): JsonResponse
    {
        $injuries = $medicalRecord->injuries()->with('followups')->get();

        return response()->json([
            'status' => 'success',
            'data' => $injuries,
            'message' => 'Injuries retrieved successfully'
        ]);
    }

    /**
     * Get medical certificates for a specific record.
     */
    public function getCertificates(MedicalRecord $medicalRecord): JsonResponse
    {
        $certificates = $medicalRecord->medicalCertificates()->valid()->get();

        return response()->json([
            'status' => 'success',
            'data' => $certificates,
            'message' => 'Medical certificates retrieved successfully'
        ]);
    }

    /**
     * Get health status dashboard.
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $schoolId = $request->get('school_id');
            
            if ($schoolId) {
                $stats = $this->medicalService->getSchoolMedicalStats($schoolId);
            } else {
                $stats = [
                    'total_records' => MedicalRecord::active()->count(),
                    'pending_exams' => MedicalExam::scheduled()->count(),
                    'active_injuries' => Injury::active()->count(),
                    'expiring_certificates' => MedicalCertificate::expiringSoon()->count(),
                    'overdue_followups' => InjuryFollowup::scheduled()
                        ->where('followup_date', '<', now())
                        ->count()
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $stats,
                'message' => 'Dashboard data retrieved successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Schedule a medical exam.
     */
    public function scheduleExam(ScheduleMedicalExamRequest $request, int $recordId): JsonResponse
    {
        try {
            $validated = $request->validated();
            $exam = $this->medicalService->scheduleMedicalExam($recordId, $validated);
            
            return response()->json([
                'status' => 'success',
                'data' => $exam,
                'message' => 'Medical exam scheduled successfully'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to schedule medical exam',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Complete a medical exam.
     */
    public function completeExam(Request $request, int $examId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'result' => 'required|in:passed,failed,pending',
                'notes' => 'nullable|string',
                'recommendations' => 'nullable|string',
                'next_exam_date' => 'nullable|date|after:now'
            ]);
            
            $exam = $this->medicalService->completeMedicalExam($examId, $validated);
            
            return response()->json([
                'status' => 'success',
                'data' => $exam,
                'message' => 'Medical exam completed successfully'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to complete medical exam',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Record an injury.
     */
    public function recordInjury(RecordInjuryRequest $request, int $recordId): JsonResponse
    {
        try {
            $validated = $request->validated();
            $injury = $this->medicalService->recordInjury($recordId, $validated);
            
            return response()->json([
                'status' => 'success',
                'data' => $injury,
                'message' => 'Injury recorded successfully'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to record injury',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update injury status.
     */
    public function updateInjuryStatus(Request $request, int $injuryId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:active,recovering,recovered,chronic',
                'notes' => 'nullable|string',
                'recovery_percentage' => 'nullable|integer|min:0|max:100'
            ]);
            
            $injury = $this->medicalService->updateInjuryStatus($injuryId, $validated['status'], $validated);
            
            return response()->json([
                'status' => 'success',
                'data' => $injury,
                'message' => 'Injury status updated successfully'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update injury status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate medical certificate.
     */
    public function generateCertificate(GenerateCertificateRequest $request, int $recordId): JsonResponse
    {
        try {
            $validated = $request->validated();
            $certificate = $this->medicalService->generateMedicalCertificate($recordId, $validated);
            
            return response()->json([
                'status' => 'success',
                'data' => $certificate,
                'message' => 'Medical certificate generated successfully'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate medical certificate',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Validate medical clearance.
     */
    public function validateClearance(int $recordId): JsonResponse
    {
        try {
            $validation = $this->medicalService->validateMedicalClearance($recordId);
            
            return response()->json([
                'status' => 'success',
                'data' => $validation,
                'message' => 'Medical clearance validation completed'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to validate medical clearance',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get records requiring attention.
     */
    public function getAttentionRequired(Request $request): JsonResponse
    {
        try {
            $schoolId = $request->get('school_id');
            
            if (!$schoolId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'School ID is required'
                ], 400);
            }
            
            $records = $this->medicalService->getRecordsRequiringAttention($schoolId);
            
            return response()->json([
                'status' => 'success',
                'data' => $records,
                'message' => 'Records requiring attention retrieved successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve records requiring attention',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get player medical records.
     */
    public function getPlayerRecords(int $playerId): JsonResponse
    {
        try {
            $records = $this->medicalService->getPlayerMedicalRecords($playerId);
            
            return response()->json([
                'status' => 'success',
                'data' => $records,
                'message' => 'Player medical records retrieved successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve player medical records',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}