<?php

namespace App\Http\Controllers;

use App\Models\MedicalRecord;
use App\Models\MedicalExam;
use App\Models\Injury;
use App\Models\InjuryFollowup;
use App\Models\MedicalCertificate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MedicalController extends Controller
{
    /**
     * Display a listing of medical records.
     */
    public function index(): JsonResponse
    {
        $records = MedicalRecord::with(['medicalExams', 'injuries', 'medicalCertificates'])
            ->active()
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $records,
            'message' => 'Medical records retrieved successfully'
        ]);
    }

    /**
     * Store a newly created medical record.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_id' => 'required|integer',
            'player_id' => 'required|integer',
            'blood_type' => 'nullable|string|max:10',
            'height' => 'nullable|numeric',
            'weight' => 'nullable|numeric',
            'allergies' => 'nullable|array',
            'chronic_conditions' => 'nullable|array',
            'medications' => 'nullable|array',
            'emergency_contacts' => 'nullable|array',
            'insurance_provider' => 'nullable|string|max:255',
            'insurance_policy_number' => 'nullable|string|max:255',
            'primary_doctor_name' => 'nullable|string|max:255',
            'primary_doctor_phone' => 'nullable|string|max:20',
            'primary_doctor_email' => 'nullable|email|max:255'
        ]);

        $validated['created_by'] = auth()->id() ?? 1; // Default to 1 if no auth
        $validated['record_number'] = 'MR-' . time() . '-' . $validated['player_id'];

        $record = MedicalRecord::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $record,
            'message' => 'Medical record created successfully'
        ], 201);
    }

    /**
     * Display the specified medical record.
     */
    public function show(MedicalRecord $medicalRecord): JsonResponse
    {
        $record = $medicalRecord->load([
            'medicalExams',
            'injuries.followups',
            'medicalCertificates'
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $record,
            'message' => 'Medical record retrieved successfully'
        ]);
    }

    /**
     * Update the specified medical record.
     */
    public function update(Request $request, MedicalRecord $medicalRecord): JsonResponse
    {
        $validated = $request->validate([
            'blood_type' => 'nullable|string|max:10',
            'height' => 'nullable|numeric',
            'weight' => 'nullable|numeric',
            'allergies' => 'nullable|array',
            'chronic_conditions' => 'nullable|array',
            'medications' => 'nullable|array',
            'emergency_contacts' => 'nullable|array',
            'insurance_provider' => 'nullable|string|max:255',
            'insurance_policy_number' => 'nullable|string|max:255',
            'primary_doctor_name' => 'nullable|string|max:255',
            'primary_doctor_phone' => 'nullable|string|max:20',
            'primary_doctor_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string'
        ]);

        $validated['updated_by'] = auth()->id() ?? 1;

        $medicalRecord->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $medicalRecord,
            'message' => 'Medical record updated successfully'
        ]);
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
    public function dashboard(): JsonResponse
    {
        $stats = [
            'total_records' => MedicalRecord::active()->count(),
            'pending_exams' => MedicalExam::scheduled()->count(),
            'active_injuries' => Injury::active()->count(),
            'expiring_certificates' => MedicalCertificate::expiringSoon()->count(),
            'overdue_followups' => InjuryFollowup::scheduled()
                ->where('followup_date', '<', now())
                ->count()
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats,
            'message' => 'Dashboard data retrieved successfully'
        ]);
    }
}