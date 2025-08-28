<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AccountReceivable;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Carbon\Carbon;

class AccountReceivableController extends Controller
{
    /**
     * Display a listing of accounts receivable with advanced filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $query = AccountReceivable::with(['financialConcept', 'createdBy', 'payments'])
                ->forSchool($schoolId);

            // Apply filters
            if ($request->has('status')) {
                $query->byStatus($request->status);
            }

            if ($request->has('student_id')) {
                $query->forStudent($request->student_id);
            }

            if ($request->has('concept_id')) {
                $query->where('concept_id', $request->concept_id);
            }

            if ($request->has('due_date_from') && $request->has('due_date_to')) {
                $query->dueBetween($request->due_date_from, $request->due_date_to);
            }

            if ($request->has('amount_min')) {
                $query->where('amount', '>=', $request->amount_min);
            }

            if ($request->has('amount_max')) {
                $query->where('amount', '<=', $request->amount_max);
            }

            if ($request->has('overdue') && $request->overdue) {
                $query->overdue();
            }

            if ($request->has('pending') && $request->pending) {
                $query->pending();
            }

            if ($request->has('paid') && $request->paid) {
                $query->paid();
            }

            // Search in description
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                      ->orWhereHas('financialConcept', function($subQ) use ($search) {
                          $subQ->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'due_date');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $accountsReceivable = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $accountsReceivable
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving accounts receivable',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created account receivable
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $request->validate([
                'student_id' => 'required|integer',
                'concept_id' => 'required|integer|exists:financial_concepts,id',
                'amount' => 'required|numeric|min:0.01',
                'due_date' => 'required|date|after_or_equal:today',
                'description' => 'nullable|string|max:500'
            ]);

            $data = $request->only([
                'student_id', 'concept_id', 'amount', 'due_date', 'description'
            ]);
            $data['school_id'] = $schoolId;
            $data['created_by'] = auth()->id();
            $data['status'] = AccountReceivable::STATUS_PENDING;

            $accountReceivable = AccountReceivable::create($data);
            $accountReceivable->load(['financialConcept', 'createdBy']);

            return response()->json([
                'success' => true,
                'message' => 'Account receivable created successfully',
                'data' => $accountReceivable
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating account receivable',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display the specified account receivable
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $accountReceivable = AccountReceivable::with([
                'financialConcept', 
                'createdBy', 
                'payments' => function($query) {
                    $query->orderBy('payment_date', 'desc');
                }
            ])
                ->forSchool($schoolId)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $accountReceivable
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Account receivable not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified account receivable
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $accountReceivable = AccountReceivable::forSchool($schoolId)->findOrFail($id);

            // Only allow updates if account receivable is pending or partial
            if (!in_array($accountReceivable->status, [AccountReceivable::STATUS_PENDING, AccountReceivable::STATUS_PARTIAL])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending or partially paid accounts receivable can be updated'
                ], 400);
            }

            $request->validate([
                'amount' => 'sometimes|numeric|min:0.01',
                'due_date' => 'sometimes|date',
                'description' => 'nullable|string|max:500'
            ]);

            $data = $request->only(['amount', 'due_date', 'description']);
            $accountReceivable->update($data);
            $accountReceivable->updateStatus();
            $accountReceivable->load(['financialConcept', 'createdBy', 'payments']);

            return response()->json([
                'success' => true,
                'message' => 'Account receivable updated successfully',
                'data' => $accountReceivable
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating account receivable',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove the specified account receivable
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $accountReceivable = AccountReceivable::forSchool($schoolId)->findOrFail($id);

            // Only allow deletion if no payments have been made
            if ($accountReceivable->payments()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete account receivable with existing payments'
                ], 400);
            }

            $accountReceivable->delete();

            return response()->json([
                'success' => true,
                'message' => 'Account receivable deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting account receivable',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get summary statistics for accounts receivable
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $query = AccountReceivable::forSchool($schoolId);

            // Apply student filter if provided
            if ($request->has('student_id')) {
                $query->forStudent($request->student_id);
            }

            $summary = [
                'total_amount' => $query->sum('amount'),
                'total_count' => $query->count(),
                'pending_amount' => $query->clone()->pending()->sum('amount'),
                'pending_count' => $query->clone()->pending()->count(),
                'partial_amount' => $query->clone()->partial()->sum('amount'),
                'partial_count' => $query->clone()->partial()->count(),
                'paid_amount' => $query->clone()->paid()->sum('amount'),
                'paid_count' => $query->clone()->paid()->count(),
                'overdue_amount' => $query->clone()->overdue()->sum('amount'),
                'overdue_count' => $query->clone()->overdue()->count(),
            ];

            $summary['remaining_amount'] = $summary['pending_amount'] + $summary['partial_amount'];
            $summary['collection_rate'] = $summary['total_amount'] > 0 
                ? round(($summary['paid_amount'] / $summary['total_amount']) * 100, 2)
                : 0;

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get accounts receivable due soon (within specified days)
     */
    public function dueSoon(Request $request): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $days = $request->get('days', 7); // Default to 7 days
            $endDate = Carbon::today()->addDays($days);

            $accountsReceivable = AccountReceivable::with(['financialConcept', 'createdBy'])
                ->forSchool($schoolId)
                ->whereIn('status', [AccountReceivable::STATUS_PENDING, AccountReceivable::STATUS_PARTIAL])
                ->dueBetween(Carbon::today(), $endDate)
                ->orderBy('due_date')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $accountsReceivable
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving accounts receivable due soon',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}