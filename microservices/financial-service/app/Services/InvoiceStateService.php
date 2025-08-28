<?php

namespace App\Services;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

class InvoiceStateService
{
    /**
     * Valid state transitions.
     */
    private const VALID_TRANSITIONS = [
        Invoice::STATUS_DRAFT => [Invoice::STATUS_PENDING, Invoice::STATUS_CANCELLED],
        Invoice::STATUS_PENDING => [Invoice::STATUS_PAID, Invoice::STATUS_OVERDUE, Invoice::STATUS_CANCELLED],
        Invoice::STATUS_OVERDUE => [Invoice::STATUS_PAID, Invoice::STATUS_CANCELLED],
        Invoice::STATUS_PAID => [], // Final state
        Invoice::STATUS_CANCELLED => [], // Final state
    ];

    /**
     * Transition an invoice to a new state.
     */
    public function transitionTo(Invoice $invoice, string $newStatus, ?string $reason = null): Invoice
    {
        $currentStatus = $invoice->status;
        
        // Validate transition
        if (!$this->isValidTransition($currentStatus, $newStatus)) {
            throw new Exception(
                "Invalid state transition from '{$currentStatus}' to '{$newStatus}'"
            );
        }

        // Perform the transition
        $this->performTransition($invoice, $newStatus, $reason);

        Log::info('Invoice state transition', [
            'invoice_id' => $invoice->id,
            'from_status' => $currentStatus,
            'to_status' => $newStatus,
            'reason' => $reason
        ]);

        return $invoice->fresh();
    }

    /**
     * Check if a state transition is valid.
     */
    public function isValidTransition(string $currentStatus, string $newStatus): bool
    {
        return in_array($newStatus, self::VALID_TRANSITIONS[$currentStatus] ?? []);
    }

    /**
     * Get valid next states for an invoice.
     */
    public function getValidNextStates(Invoice $invoice): array
    {
        return self::VALID_TRANSITIONS[$invoice->status] ?? [];
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(Invoice $invoice, ?string $paymentReference = null): Invoice
    {
        if (!$this->canMarkAsPaid($invoice)) {
            throw new Exception('Invoice cannot be marked as paid in its current state');
        }

        $reason = $paymentReference ? "Payment received. Reference: {$paymentReference}" : 'Payment received';
        
        return $this->transitionTo($invoice, Invoice::STATUS_PAID, $reason);
    }

    /**
     * Mark invoice as overdue.
     */
    public function markAsOverdue(Invoice $invoice): Invoice
    {
        if (!$this->canMarkAsOverdue($invoice)) {
            throw new Exception('Invoice cannot be marked as overdue');
        }

        $daysPastDue = Carbon::now()->diffInDays($invoice->due_date);
        $reason = "Invoice is {$daysPastDue} days past due date";
        
        return $this->transitionTo($invoice, Invoice::STATUS_OVERDUE, $reason);
    }

    /**
     * Cancel an invoice.
     */
    public function cancel(Invoice $invoice, string $reason): Invoice
    {
        if (!$this->canCancel($invoice)) {
            throw new Exception('Invoice cannot be cancelled in its current state');
        }

        return $this->transitionTo($invoice, Invoice::STATUS_CANCELLED, $reason);
    }

    /**
     * Send invoice (transition from draft to pending).
     */
    public function sendInvoice(Invoice $invoice): Invoice
    {
        if ($invoice->status !== Invoice::STATUS_DRAFT) {
            throw new Exception('Only draft invoices can be sent');
        }

        return $this->transitionTo($invoice, Invoice::STATUS_PENDING, 'Invoice sent to student');
    }

    /**
     * Check if invoice can be marked as paid.
     */
    public function canMarkAsPaid(Invoice $invoice): bool
    {
        return in_array($invoice->status, [Invoice::STATUS_PENDING, Invoice::STATUS_OVERDUE]);
    }

    /**
     * Check if invoice can be marked as overdue.
     */
    public function canMarkAsOverdue(Invoice $invoice): bool
    {
        return $invoice->status === Invoice::STATUS_PENDING && 
               Carbon::now()->isAfter($invoice->due_date);
    }

    /**
     * Check if invoice can be cancelled.
     */
    public function canCancel(Invoice $invoice): bool
    {
        return !in_array($invoice->status, [Invoice::STATUS_PAID, Invoice::STATUS_CANCELLED]);
    }

    /**
     * Perform the actual state transition.
     */
    private function performTransition(Invoice $invoice, string $newStatus, ?string $reason): void
    {
        $updates = ['status' => $newStatus];
        
        // Add timestamp for specific transitions
        switch ($newStatus) {
            case Invoice::STATUS_PAID:
                $updates['paid_at'] = Carbon::now();
                break;
            case Invoice::STATUS_CANCELLED:
                $updates['cancelled_at'] = Carbon::now();
                break;
        }

        // Add reason to notes if provided
        if ($reason) {
            $timestamp = Carbon::now()->format('Y-m-d H:i:s');
            $noteEntry = "[{$timestamp}] Status changed to {$newStatus}: {$reason}";
            $updates['notes'] = $invoice->notes ? $invoice->notes . "\n" . $noteEntry : $noteEntry;
        }

        $invoice->update($updates);
    }

    /**
     * Bulk update overdue invoices.
     */
    public function updateOverdueInvoices(int $schoolId): int
    {
        $overdueInvoices = Invoice::where('school_id', $schoolId)
            ->where('status', Invoice::STATUS_PENDING)
            ->where('due_date', '<', Carbon::now())
            ->get();

        $count = 0;
        foreach ($overdueInvoices as $invoice) {
            try {
                $this->markAsOverdue($invoice);
                $count++;
            } catch (Exception $e) {
                Log::warning('Failed to mark invoice as overdue', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Bulk overdue update completed', [
            'school_id' => $schoolId,
            'updated_count' => $count
        ]);

        return $count;
    }

    /**
     * Get invoice state statistics.
     */
    public function getStateStatistics(int $schoolId, ?Carbon $fromDate = null, ?Carbon $toDate = null): array
    {
        $query = Invoice::where('school_id', $schoolId);
        
        if ($fromDate) {
            $query->where('issue_date', '>=', $fromDate);
        }
        
        if ($toDate) {
            $query->where('issue_date', '<=', $toDate);
        }

        $statistics = $query->selectRaw('
            status,
            COUNT(*) as count,
            SUM(total) as total_amount,
            AVG(total) as average_amount
        ')
        ->groupBy('status')
        ->get()
        ->keyBy('status');

        // Add additional metrics
        $result = [
            'by_status' => $statistics,
            'totals' => [
                'total_invoices' => $statistics->sum('count'),
                'total_amount' => $statistics->sum('total_amount'),
                'average_invoice_amount' => $statistics->avg('average_amount')
            ]
        ];

        // Calculate collection rate (paid / (paid + overdue + pending))
        $paidAmount = $statistics->get(Invoice::STATUS_PAID)?->total_amount ?? 0;
        $pendingAmount = $statistics->get(Invoice::STATUS_PENDING)?->total_amount ?? 0;
        $overdueAmount = $statistics->get(Invoice::STATUS_OVERDUE)?->total_amount ?? 0;
        
        $totalOutstanding = $paidAmount + $pendingAmount + $overdueAmount;
        $result['collection_rate'] = $totalOutstanding > 0 ? ($paidAmount / $totalOutstanding) * 100 : 0;

        return $result;
    }

    /**
     * Get invoices that need attention (overdue, approaching due date).
     */
    public function getInvoicesNeedingAttention(int $schoolId, int $daysBeforeDue = 7): array
    {
        $now = Carbon::now();
        $warningDate = $now->copy()->addDays($daysBeforeDue);

        return [
            'overdue' => Invoice::where('school_id', $schoolId)
                ->where('status', Invoice::STATUS_OVERDUE)
                ->orderBy('due_date')
                ->get(),
            
            'approaching_due' => Invoice::where('school_id', $schoolId)
                ->where('status', Invoice::STATUS_PENDING)
                ->whereBetween('due_date', [$now, $warningDate])
                ->orderBy('due_date')
                ->get(),
                
            'long_overdue' => Invoice::where('school_id', $schoolId)
                ->where('status', Invoice::STATUS_OVERDUE)
                ->where('due_date', '<', $now->copy()->subDays(30))
                ->orderBy('due_date')
                ->get()
        ];
    }
}