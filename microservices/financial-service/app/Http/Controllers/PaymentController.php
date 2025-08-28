<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\AccountReceivable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments with advanced filtering
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

            $query = Payment::with(['accountReceivable.financialConcept', 'createdBy'])
                ->forSchool($schoolId);

            // Apply filters
            if ($request->has('status')) {
                $query->byStatus($request->status);
            }

            if ($request->has('account_receivable_id')) {
                $query->where('account_receivable_id', $request->account_receivable_id);
            }

            if ($request->has('payment_method')) {
                $query->byMethod($request->payment_method);
            }

            if ($request->has('payment_date_from') && $request->has('payment_date_to')) {
                $query->paidBetween($request->payment_date_from, $request->payment_date_to);
            }

            if ($request->has('amount_min')) {
                $query->where('amount', '>=', $request->amount_min);
            }

            if ($request->has('amount_max')) {
                $query->where('amount', '<=', $request->amount_max);
            }

            if ($request->has('reference_number')) {
                $query->where('reference_number', 'like', '%' . $request->reference_number . '%');
            }

            if ($request->has('confirmed') && $request->confirmed) {
                $query->confirmed();
            }

            if ($request->has('pending') && $request->pending) {
                $query->pending();
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('reference_number', 'like', "%{$search}%")
                      ->orWhereHas('accountReceivable.financialConcept', function($subQ) use ($search) {
                          $subQ->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'payment_date');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $payments = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $payments
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created payment
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
                'account_receivable_id' => 'required|integer|exists:accounts_receivable,id',
                'amount' => 'required|numeric|min:0.01',
                'payment_date' => 'required|date|before_or_equal:today',
                'payment_method' => 'required|string|in:' . implode(',', array_keys(Payment::getPaymentMethods())),
                'reference_number' => 'nullable|string|max:100',
                'voucher' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120' // 5MB max
            ]);

            // Verify account receivable belongs to the school
            $accountReceivable = AccountReceivable::forSchool($schoolId)
                ->findOrFail($request->account_receivable_id);

            // Check if account receivable can receive payments
            if ($accountReceivable->status === AccountReceivable::STATUS_PAID) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account receivable is already fully paid'
                ], 400);
            }

            // Check if payment amount doesn't exceed remaining amount
            if ($request->amount > $accountReceivable->remaining_amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount exceeds remaining amount to pay'
                ], 400);
            }

            $data = $request->only([
                'account_receivable_id', 'amount', 'payment_date', 
                'payment_method', 'reference_number'
            ]);
            $data['school_id'] = $schoolId;
            $data['created_by'] = auth()->id();
            $data['status'] = Payment::STATUS_PENDING;

            // Handle voucher upload
            if ($request->hasFile('voucher')) {
                $voucher = $request->file('voucher');
                $path = $voucher->store('vouchers', 'public');
                $data['voucher_path'] = $path;
            }

            $payment = Payment::create($data);
            $payment->load(['accountReceivable.financialConcept', 'createdBy']);

            return response()->json([
                'success' => true,
                'message' => 'Payment registered successfully',
                'data' => $payment
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error registering payment',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display the specified payment
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

            $payment = Payment::with(['accountReceivable.financialConcept', 'createdBy'])
                ->forSchool($schoolId)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $payment
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified payment
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

            $payment = Payment::forSchool($schoolId)->findOrFail($id);

            // Only allow updates if payment is pending
            if ($payment->status !== Payment::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending payments can be updated'
                ], 400);
            }

            $request->validate([
                'amount' => 'sometimes|numeric|min:0.01',
                'payment_date' => 'sometimes|date|before_or_equal:today',
                'payment_method' => 'sometimes|string|in:' . implode(',', array_keys(Payment::getPaymentMethods())),
                'reference_number' => 'nullable|string|max:100',
                'voucher' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120'
            ]);

            $data = $request->only([
                'amount', 'payment_date', 'payment_method', 'reference_number'
            ]);

            // Handle voucher upload
            if ($request->hasFile('voucher')) {
                // Delete old voucher if exists
                if ($payment->voucher_path) {
                    Storage::disk('public')->delete($payment->voucher_path);
                }
                
                $voucher = $request->file('voucher');
                $path = $voucher->store('vouchers', 'public');
                $data['voucher_path'] = $path;
            }

            $payment->update($data);
            $payment->load(['accountReceivable.financialConcept', 'createdBy']);

            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully',
                'data' => $payment
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating payment',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Confirm a pending payment
     */
    public function confirm(Request $request, int $id): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $payment = Payment::forSchool($schoolId)->findOrFail($id);

            if ($payment->status !== Payment::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending payments can be confirmed'
                ], 400);
            }

            $payment->status = Payment::STATUS_CONFIRMED;
            $payment->save();

            // Update account receivable status
            $payment->accountReceivable->updateStatus();
            $payment->load(['accountReceivable.financialConcept', 'createdBy']);

            return response()->json([
                'success' => true,
                'message' => 'Payment confirmed successfully',
                'data' => $payment
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error confirming payment',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Reject a pending payment
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $payment = Payment::forSchool($schoolId)->findOrFail($id);

            if ($payment->status !== Payment::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending payments can be rejected'
                ], 400);
            }

            $payment->status = Payment::STATUS_REJECTED;
            $payment->save();
            $payment->load(['accountReceivable.financialConcept', 'createdBy']);

            return response()->json([
                'success' => true,
                'message' => 'Payment rejected successfully',
                'data' => $payment
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error rejecting payment',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancel a payment
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

            $payment = Payment::forSchool($schoolId)->findOrFail($id);

            if ($payment->status === Payment::STATUS_CANCELLED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment is already cancelled'
                ], 400);
            }

            $payment->status = Payment::STATUS_CANCELLED;
            $payment->save();

            // Update account receivable status
            $payment->accountReceivable->updateStatus();
            $payment->load(['accountReceivable.financialConcept', 'createdBy']);

            return response()->json([
                'success' => true,
                'message' => 'Payment cancelled successfully',
                'data' => $payment
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling payment',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Download payment voucher
     */
    public function downloadVoucher(Request $request, int $id): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $payment = Payment::forSchool($schoolId)->findOrFail($id);

            if (!$payment->voucher_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment has no voucher'
                ], 404);
            }

            if (!Storage::disk('public')->exists($payment->voucher_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Voucher file not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'voucher_url' => $payment->voucher_url
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error downloading voucher',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}