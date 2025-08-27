<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Http\Requests\CreateTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Display a listing of transactions with advanced filtering
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

            $query = Transaction::with(['financialConcept', 'accounts'])
                ->bySchool($schoolId);

            // Apply filters
            if ($request->has('status')) {
                $query->byStatus($request->status);
            }

            if ($request->has('financial_concept_id')) {
                $query->where('financial_concept_id', $request->financial_concept_id);
            }

            if ($request->has('date_from')) {
                $query->byDateRange($request->date_from, $request->date_to);
            }

            if ($request->has('amount_min')) {
                $query->where('amount', '>=', $request->amount_min);
            }

            if ($request->has('amount_max')) {
                $query->where('amount', '<=', $request->amount_max);
            }

            if ($request->has('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            if ($request->has('reference_number')) {
                $query->where('reference_number', 'like', '%' . $request->reference_number . '%');
            }

            // Search in description
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                      ->orWhere('reference_number', 'like', "%{$search}%")
                      ->orWhereHas('financialConcept', function($subQ) use ($search) {
                          $subQ->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'transaction_date');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $transactions = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $transactions
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created transaction
     */
    public function store(CreateTransactionRequest $request): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $data = $request->validated();
            $data['school_id'] = $schoolId;
            $data['created_by'] = auth()->id();

            $transaction = $this->transactionService->createTransaction($data);

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully',
                'data' => $transaction
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating transaction',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display the specified transaction
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

            $transaction = Transaction::with(['financialConcept', 'accounts', 'transactionAccounts'])
                ->bySchool($schoolId)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $transaction
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified transaction
     */
    public function update(UpdateTransactionRequest $request, int $id): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $transaction = Transaction::bySchool($schoolId)->findOrFail($id);

            // Only allow updates if transaction is pending
            if ($transaction->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending transactions can be updated'
                ], 400);
            }

            $data = $request->validated();
            $transaction->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Transaction updated successfully',
                'data' => $transaction->load(['financialConcept', 'accounts'])
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating transaction',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove the specified transaction
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

            $transaction = Transaction::bySchool($schoolId)->findOrFail($id);

            // Only allow deletion if transaction is pending
            if ($transaction->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending transactions can be deleted'
                ], 400);
            }

            $transaction->delete();

            return response()->json([
                'success' => true,
                'message' => 'Transaction deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting transaction',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update transaction status (approval workflow)
     */
    public function updateStatus(Request $request, int $id): JsonResponse
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
                'status' => 'required|in:pending,approved,rejected,cancelled',
                'reason' => 'nullable|string|max:500'
            ]);

            $transaction = Transaction::bySchool($schoolId)->findOrFail($id);

            $updatedTransaction = $this->transactionService->updateTransactionStatus(
                $transaction,
                $request->status,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Transaction status updated successfully',
                'data' => $updatedTransaction
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating transaction status',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get transaction statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $query = Transaction::bySchool($schoolId);

            // Apply date filter if provided
            if ($request->has('date_from')) {
                $query->byDateRange($request->date_from, $request->date_to);
            }

            $statistics = [
                'total_transactions' => $query->count(),
                'total_amount' => $query->sum('amount'),
                'by_status' => $query->selectRaw('status, COUNT(*) as count, SUM(amount) as total_amount')
                    ->groupBy('status')
                    ->get(),
                'by_payment_method' => $query->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total_amount')
                    ->groupBy('payment_method')
                    ->get(),
                'by_concept_type' => $query->join('financial_concepts', 'transactions.financial_concept_id', '=', 'financial_concepts.id')
                    ->selectRaw('financial_concepts.type, COUNT(*) as count, SUM(transactions.amount) as total_amount')
                    ->groupBy('financial_concepts.type')
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate reference number for preview
     */
    public function generateReferenceNumber(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'financial_concept_id' => 'required|exists:financial_concepts,id',
                'transaction_date' => 'nullable|date'
            ]);

            $referenceNumber = $this->transactionService->generateReferenceNumber(
                $request->financial_concept_id,
                $request->transaction_date
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'reference_number' => $referenceNumber
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating reference number',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
