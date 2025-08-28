<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\InvoiceStateService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Exception;

class InvoiceController extends Controller
{
    protected InvoiceService $invoiceService;
    protected InvoiceStateService $invoiceStateService;

    public function __construct(
        InvoiceService $invoiceService,
        InvoiceStateService $invoiceStateService
    ) {
        $this->invoiceService = $invoiceService;
        $this->invoiceStateService = $invoiceStateService;
    }

    /**
     * Get invoices for a school with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'school_id' => 'required|integer',
            'status' => 'sometimes|in:draft,pending,paid,overdue,cancelled',
            'student_id' => 'sometimes|integer',
            'from_date' => 'sometimes|date',
            'to_date' => 'sometimes|date|after_or_equal:from_date',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = Invoice::with(['items', 'items.concept'])
                ->where('school_id', $request->school_id);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('student_id')) {
                $query->where('student_id', $request->student_id);
            }

            if ($request->has('from_date')) {
                $query->whereDate('issue_date', '>=', $request->from_date);
            }

            if ($request->has('to_date')) {
                $query->whereDate('issue_date', '<=', $request->to_date);
            }

            // Order by issue date (newest first)
            $query->orderBy('issue_date', 'desc');

            $perPage = $request->get('per_page', 15);
            $invoices = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $invoices
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving invoices',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific invoice by ID.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $invoice = Invoice::with(['items', 'items.concept'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $invoice
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Generate a new invoice for a student.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'school_id' => 'required|integer',
            'student_id' => 'required|integer',
            'month' => 'sometimes|date_format:Y-m'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $month = $request->has('month') 
                ? Carbon::createFromFormat('Y-m', $request->month)
                : Carbon::now();

            $invoice = $this->invoiceService->generateStudentInvoice(
                $request->school_id,
                $request->student_id,
                $month
            );

            return response()->json([
                'success' => true,
                'message' => 'Invoice generated successfully',
                'data' => $invoice
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating invoice',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update an invoice.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'due_date' => 'sometimes|date|after:today',
            'notes' => 'sometimes|string|max:1000',
            'status' => 'sometimes|in:draft,pending,paid,overdue,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $invoice = Invoice::findOrFail($id);

            // Handle status change separately through state service
            if ($request->has('status') && $request->status !== $invoice->status) {
                $invoice = $this->invoiceStateService->transitionTo(
                    $invoice,
                    $request->status,
                    'Status updated via API'
                );
            }

            // Update other fields
            $updateData = $request->only(['due_date', 'notes']);
            if (!empty($updateData)) {
                $invoice->update($updateData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Invoice updated successfully',
                'data' => $invoice->fresh(['items', 'items.concept'])
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating invoice',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete an invoice (only if not paid).
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $invoice = Invoice::findOrFail($id);

            if ($invoice->status === Invoice::STATUS_PAID) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a paid invoice'
                ], 400);
            }

            $invoice->delete();

            return response()->json([
                'success' => true,
                'message' => 'Invoice deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting invoice',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Generate monthly invoices for a school.
     */
    public function generateMonthly(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'school_id' => 'required|integer',
            'month' => 'sometimes|date_format:Y-m'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $month = $request->has('month') 
                ? Carbon::createFromFormat('Y-m', $request->month)
                : Carbon::now();

            $results = $this->invoiceService->generateMonthlyInvoices(
                $request->school_id,
                $month
            );

            return response()->json([
                'success' => true,
                'message' => 'Monthly invoices generated successfully',
                'data' => $results
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating monthly invoices',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Mark an invoice as paid.
     */
    public function markAsPaid(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_reference' => 'sometimes|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $invoice = Invoice::findOrFail($id);
            
            $updatedInvoice = $this->invoiceStateService->markAsPaid(
                $invoice,
                $request->payment_reference
            );

            return response()->json([
                'success' => true,
                'message' => 'Invoice marked as paid successfully',
                'data' => $updatedInvoice
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error marking invoice as paid',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancel an invoice.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $invoice = Invoice::findOrFail($id);
            
            $updatedInvoice = $this->invoiceStateService->cancel(
                $invoice,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Invoice cancelled successfully',
                'data' => $updatedInvoice
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling invoice',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get invoice statistics for a school.
     */
    public function statistics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'school_id' => 'required|integer',
            'from_date' => 'sometimes|date',
            'to_date' => 'sometimes|date|after_or_equal:from_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $fromDate = $request->has('from_date') ? Carbon::parse($request->from_date) : null;
            $toDate = $request->has('to_date') ? Carbon::parse($request->to_date) : null;

            $statistics = $this->invoiceStateService->getStateStatistics(
                $request->school_id,
                $fromDate,
                $toDate
            );

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
     * Get invoices that need attention.
     */
    public function needingAttention(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'school_id' => 'required|integer',
            'days_before_due' => 'sometimes|integer|min:1|max:30'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $daysBeforeDue = $request->get('days_before_due', 7);
            
            $invoices = $this->invoiceStateService->getInvoicesNeedingAttention(
                $request->school_id,
                $daysBeforeDue
            );

            return response()->json([
                'success' => true,
                'data' => $invoices
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving invoices needing attention',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update overdue invoices for a school.
     */
    public function updateOverdue(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'school_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updatedCount = $this->invoiceStateService->updateOverdueInvoices(
                $request->school_id
            );

            return response()->json([
                'success' => true,
                'message' => "Updated {$updatedCount} invoices to overdue status",
                'data' => ['updated_count' => $updatedCount]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating overdue invoices',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}