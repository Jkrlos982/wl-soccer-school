<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\FinancialConcept;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class InvoiceService
{
    /**
     * Generate monthly invoices for all active students.
     */
    public function generateMonthlyInvoices(int $schoolId, ?Carbon $month = null): array
    {
        $month = $month ?? Carbon::now();
        $results = [
            'success' => 0,
            'errors' => 0,
            'invoices' => [],
            'error_messages' => []
        ];

        try {
            // Get active students for the school (this would typically come from another microservice)
            // For now, we'll simulate this with a method that gets student IDs
            $studentIds = $this->getActiveStudentIds($schoolId);

            DB::beginTransaction();

            foreach ($studentIds as $studentId) {
                try {
                    $invoice = $this->generateStudentInvoice($schoolId, $studentId, $month);
                    $results['invoices'][] = $invoice;
                    $results['success']++;
                } catch (Exception $e) {
                    $results['errors']++;
                    $results['error_messages'][] = "Student {$studentId}: " . $e->getMessage();
                    Log::error('Error generating invoice for student', [
                        'student_id' => $studentId,
                        'school_id' => $schoolId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            Log::info('Monthly invoices generation completed', [
                'school_id' => $schoolId,
                'month' => $month->format('Y-m'),
                'success' => $results['success'],
                'errors' => $results['errors']
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error in monthly invoice generation', [
                'school_id' => $schoolId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        return $results;
    }

    /**
     * Generate an invoice for a specific student.
     */
    public function generateStudentInvoice(int $schoolId, int $studentId, ?Carbon $month = null): Invoice
    {
        $month = $month ?? Carbon::now();
        
        // Check if invoice already exists for this student and month
        $existingInvoice = Invoice::where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->whereYear('issue_date', $month->year)
            ->whereMonth('issue_date', $month->month)
            ->first();

        if ($existingInvoice) {
            throw new Exception("Invoice already exists for student {$studentId} in {$month->format('Y-m')}");
        }

        DB::beginTransaction();

        try {
            // Create the invoice
            $invoice = Invoice::create([
                'school_id' => $schoolId,
                'student_id' => $studentId,
                'invoice_number' => $this->generateInvoiceNumber($schoolId, $month),
                'issue_date' => $month->startOfMonth(),
                'due_date' => $month->copy()->addDays(30),
                'status' => Invoice::STATUS_PENDING,
                'subtotal' => 0,
                'tax_amount' => 0,
                'total' => 0,
            ]);

            // Add invoice items (monthly tuition and other recurring concepts)
            $this->addInvoiceItems($invoice, $studentId);

            // Calculate totals
            $invoice->calculateTotals();

            DB::commit();

            Log::info('Invoice generated successfully', [
                'invoice_id' => $invoice->id,
                'student_id' => $studentId,
                'school_id' => $schoolId,
                'total' => $invoice->total
            ]);

            return $invoice->fresh(['items', 'items.concept']);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error generating student invoice', [
                'student_id' => $studentId,
                'school_id' => $schoolId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate a unique invoice number.
     */
    private function generateInvoiceNumber(int $schoolId, Carbon $date): string
    {
        $prefix = 'INV';
        $year = $date->format('Y');
        $month = $date->format('m');
        
        // Get the next sequence number for this school, year, and month
        $lastInvoice = Invoice::where('school_id', $schoolId)
            ->where('invoice_number', 'like', "{$prefix}-{$year}-{$month}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        $sequence = 1;
        if ($lastInvoice) {
            $lastNumber = explode('-', $lastInvoice->invoice_number);
            $sequence = intval(end($lastNumber)) + 1;
        }

        return sprintf('%s-%s-%s-%04d', $prefix, $year, $month, $sequence);
    }

    /**
     * Add invoice items based on student's active enrollments and recurring concepts.
     */
    private function addInvoiceItems(Invoice $invoice, int $studentId): void
    {
        // Get recurring financial concepts for this school (monthly tuition, etc.)
        $recurringConcepts = FinancialConcept::where('school_id', $invoice->school_id)
            ->where('type', FinancialConcept::TYPE_INCOME)
            ->where('category', FinancialConcept::CATEGORY_EDUCATION)
            ->where('is_active', true)
            ->get();

        foreach ($recurringConcepts as $concept) {
            // For now, we'll add a standard monthly tuition
            // In a real implementation, this would be based on student's enrollment details
            $unitPrice = $this->getConceptPriceForStudent($concept, $studentId);
            
            if ($unitPrice > 0) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'concept_id' => $concept->id,
                    'description' => $concept->description ?: $concept->name,
                    'quantity' => 1,
                    'unit_price' => $unitPrice,
                    'total' => $unitPrice,
                ]);
            }
        }
    }

    /**
     * Get the price for a concept for a specific student.
     * This would typically integrate with enrollment/student service.
     */
    private function getConceptPriceForStudent(FinancialConcept $concept, int $studentId): float
    {
        // This is a simplified implementation
        // In reality, this would check student's grade level, enrollment type, etc.
        
        // Default prices based on concept category
        $defaultPrices = [
            'mensualidad' => 500000, // $500,000 COP
            'matricula' => 200000,   // $200,000 COP
            'pension' => 450000,     // $450,000 COP
        ];

        $conceptName = strtolower($concept->name);
        
        foreach ($defaultPrices as $key => $price) {
            if (str_contains($conceptName, $key)) {
                return $price;
            }
        }

        return 0;
    }

    /**
     * Get active student IDs for a school.
     * This would typically call another microservice.
     */
    private function getActiveStudentIds(int $schoolId): array
    {
        // This is a mock implementation
        // In reality, this would call the student/enrollment microservice
        
        // For testing purposes, return some mock student IDs
        return range(1, 10); // Students with IDs 1-10
    }

    /**
     * Update invoice status based on payments and due dates.
     */
    public function updateInvoiceStatuses(int $schoolId): array
    {
        $updated = [
            'overdue' => 0,
            'paid' => 0
        ];

        // Mark overdue invoices
        $overdueCount = Invoice::where('school_id', $schoolId)
            ->where('status', Invoice::STATUS_PENDING)
            ->where('due_date', '<', Carbon::now())
            ->update(['status' => Invoice::STATUS_OVERDUE]);

        $updated['overdue'] = $overdueCount;

        Log::info('Invoice statuses updated', [
            'school_id' => $schoolId,
            'overdue_count' => $overdueCount
        ]);

        return $updated;
    }

    /**
     * Get invoice statistics for a school.
     */
    public function getInvoiceStatistics(int $schoolId, ?Carbon $month = null): array
    {
        $query = Invoice::where('school_id', $schoolId);

        if ($month) {
            $query->whereYear('issue_date', $month->year)
                  ->whereMonth('issue_date', $month->month);
        }

        return [
            'total_invoices' => $query->count(),
            'total_amount' => $query->sum('total'),
            'by_status' => $query->selectRaw('status, COUNT(*) as count, SUM(total) as amount')
                                ->groupBy('status')
                                ->get()
                                ->keyBy('status'),
            'overdue_count' => $query->where('status', Invoice::STATUS_OVERDUE)->count(),
            'pending_amount' => $query->whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_OVERDUE])
                                    ->sum('total'),
        ];
    }

    /**
     * Cancel an invoice.
     */
    public function cancelInvoice(int $invoiceId, string $reason = null): Invoice
    {
        $invoice = Invoice::findOrFail($invoiceId);
        
        if ($invoice->status === Invoice::STATUS_PAID) {
            throw new Exception('Cannot cancel a paid invoice');
        }

        $invoice->update([
            'status' => Invoice::STATUS_CANCELLED,
            'notes' => $invoice->notes . "\n\nCancelled: " . ($reason ?: 'No reason provided')
        ]);

        Log::info('Invoice cancelled', [
            'invoice_id' => $invoiceId,
            'reason' => $reason
        ]);

        return $invoice;
    }
}