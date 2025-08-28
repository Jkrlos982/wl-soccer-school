<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanInstallment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Carbon\Carbon;

class PaymentPlanController extends Controller
{
    /**
     * Display a listing of payment plans with advanced filtering
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

            $query = PaymentPlan::with(['createdBy', 'installments'])
                ->forSchool($schoolId);

            // Apply filters
            if ($request->has('status')) {
                $query->byStatus($request->status);
            }

            if ($request->has('student_id')) {
                $query->forStudent($request->student_id);
            }

            if ($request->has('frequency')) {
                $query->byFrequency($request->frequency);
            }

            if ($request->has('start_date_from') && $request->has('start_date_to')) {
                $query->whereBetween('start_date', [$request->start_date_from, $request->start_date_to]);
            }

            if ($request->has('total_amount_min')) {
                $query->where('total_amount', '>=', $request->total_amount_min);
            }

            if ($request->has('total_amount_max')) {
                $query->where('total_amount', '<=', $request->total_amount_max);
            }

            if ($request->has('active') && $request->active) {
                $query->active();
            }

            if ($request->has('completed') && $request->completed) {
                $query->completed();
            }

            // Search in description
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('description', 'like', "%{$search}%");
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'start_date');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $paymentPlans = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $paymentPlans
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payment plans',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created payment plan
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
                'total_amount' => 'required|numeric|min:0.01',
                'installments' => 'required|integer|min:2|max:60',
                'frequency' => 'required|string|in:' . implode(',', array_keys(PaymentPlan::getFrequencies())),
                'start_date' => 'required|date|after_or_equal:today',
                'description' => 'nullable|string|max:500'
            ]);

            $data = $request->only([
                'student_id', 'total_amount', 'installments', 
                'frequency', 'start_date', 'description'
            ]);
            $data['school_id'] = $schoolId;
            $data['created_by'] = auth()->id();
            $data['status'] = PaymentPlan::STATUS_ACTIVE;

            $paymentPlan = PaymentPlan::create($data);
            
            // Generate installments
            $paymentPlan->generateInstallments();
            
            $paymentPlan->load(['createdBy', 'installments']);

            return response()->json([
                'success' => true,
                'message' => 'Payment plan created successfully',
                'data' => $paymentPlan
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating payment plan',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display the specified payment plan
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

            $paymentPlan = PaymentPlan::with([
                'createdBy', 
                'installments' => function($query) {
                    $query->orderBy('installment_number');
                },
                'installments.payment'
            ])
                ->forSchool($schoolId)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $paymentPlan
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment plan not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified payment plan
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

            $paymentPlan = PaymentPlan::forSchool($schoolId)->findOrFail($id);

            // Only allow updates if payment plan is active and no payments have been made
            if ($paymentPlan->status !== PaymentPlan::STATUS_ACTIVE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only active payment plans can be updated'
                ], 400);
            }

            if ($paymentPlan->paidInstallments()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update payment plan with existing payments'
                ], 400);
            }

            $request->validate([
                'total_amount' => 'sometimes|numeric|min:0.01',
                'installments' => 'sometimes|integer|min:2|max:60',
                'frequency' => 'sometimes|string|in:' . implode(',', array_keys(PaymentPlan::getFrequencies())),
                'start_date' => 'sometimes|date|after_or_equal:today',
                'description' => 'nullable|string|max:500'
            ]);

            $data = $request->only([
                'total_amount', 'installments', 'frequency', 'start_date', 'description'
            ]);

            $paymentPlan->update($data);
            
            // Regenerate installments if structure changed
            if ($request->has(['total_amount', 'installments', 'frequency', 'start_date'])) {
                $paymentPlan->generateInstallments();
            }
            
            $paymentPlan->load(['createdBy', 'installments']);

            return response()->json([
                'success' => true,
                'message' => 'Payment plan updated successfully',
                'data' => $paymentPlan
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating payment plan',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Suspend the specified payment plan
     */
    public function suspend(Request $request, int $id): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $paymentPlan = PaymentPlan::forSchool($schoolId)->findOrFail($id);

            if ($paymentPlan->status !== PaymentPlan::STATUS_ACTIVE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only active payment plans can be suspended'
                ], 400);
            }

            $paymentPlan->status = PaymentPlan::STATUS_SUSPENDED;
            $paymentPlan->save();
            $paymentPlan->load(['createdBy', 'installments']);

            return response()->json([
                'success' => true,
                'message' => 'Payment plan suspended successfully',
                'data' => $paymentPlan
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error suspending payment plan',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Reactivate the specified payment plan
     */
    public function reactivate(Request $request, int $id): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $paymentPlan = PaymentPlan::forSchool($schoolId)->findOrFail($id);

            if ($paymentPlan->status !== PaymentPlan::STATUS_SUSPENDED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only suspended payment plans can be reactivated'
                ], 400);
            }

            $paymentPlan->status = PaymentPlan::STATUS_ACTIVE;
            $paymentPlan->save();
            $paymentPlan->load(['createdBy', 'installments']);

            return response()->json([
                'success' => true,
                'message' => 'Payment plan reactivated successfully',
                'data' => $paymentPlan
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error reactivating payment plan',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancel the specified payment plan
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $paymentPlan = PaymentPlan::forSchool($schoolId)->findOrFail($id);

            if ($paymentPlan->status === PaymentPlan::STATUS_CANCELLED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment plan is already cancelled'
                ], 400);
            }

            if ($paymentPlan->status === PaymentPlan::STATUS_COMPLETED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel completed payment plan'
                ], 400);
            }

            $paymentPlan->status = PaymentPlan::STATUS_CANCELLED;
            $paymentPlan->save();

            // Cancel pending installments
            $paymentPlan->pendingInstallments()->update([
                'status' => PaymentPlanInstallment::STATUS_CANCELLED
            ]);

            $paymentPlan->load(['createdBy', 'installments']);

            return response()->json([
                'success' => true,
                'message' => 'Payment plan cancelled successfully',
                'data' => $paymentPlan
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling payment plan',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get installments for a payment plan
     */
    public function installments(Request $request, int $id): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $paymentPlan = PaymentPlan::forSchool($schoolId)->findOrFail($id);

            $query = $paymentPlan->installments()->with('payment');

            // Apply filters
            if ($request->has('status')) {
                $query->byStatus($request->status);
            }

            if ($request->has('due_date_from') && $request->has('due_date_to')) {
                $query->dueBetween($request->due_date_from, $request->due_date_to);
            }

            $installments = $query->orderBy('installment_number')->get();

            return response()->json([
                'success' => true,
                'data' => $installments
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving installments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment plans due soon
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

            $paymentPlans = PaymentPlan::with(['createdBy', 'installments' => function($query) use ($endDate) {
                $query->pending()
                      ->dueBetween(Carbon::today(), $endDate)
                      ->orderBy('due_date');
            }])
                ->forSchool($schoolId)
                ->active()
                ->whereHas('installments', function($query) use ($endDate) {
                    $query->pending()
                          ->dueBetween(Carbon::today(), $endDate);
                })
                ->get();

            return response()->json([
                'success' => true,
                'data' => $paymentPlans
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payment plans due soon',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}