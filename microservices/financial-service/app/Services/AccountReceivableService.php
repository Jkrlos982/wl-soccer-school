<?php

namespace App\Services;

use App\Models\AccountReceivable;
use App\Models\Payment;
use App\Models\FinancialConcept;
use App\Services\VoucherGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

class AccountReceivableService
{
    protected VoucherGenerator $voucherGenerator;

    public function __construct(VoucherGenerator $voucherGenerator)
    {
        $this->voucherGenerator = $voucherGenerator;
    }
    /**
     * Create a new account receivable
     *
     * @param array $data
     * @return AccountReceivable
     * @throws Exception
     */
    public function createAccountReceivable(array $data): AccountReceivable
    {
        try {
            DB::beginTransaction();

            // Validate concept exists and is active
            $concept = FinancialConcept::where('id', $data['concept_id'])
                ->where('school_id', $data['school_id'])
                ->where('is_active', true)
                ->first();

            if (!$concept) {
                throw new Exception('Financial concept not found or inactive');
            }

            // Validate amount
            if ($data['amount'] <= 0) {
                throw new Exception('Amount must be greater than zero');
            }

            // Validate due date
            $dueDate = Carbon::parse($data['due_date']);
            if ($dueDate->isPast()) {
                throw new Exception('Due date cannot be in the past');
            }

            // Check for duplicate account receivable
            $existingReceivable = AccountReceivable::where('school_id', $data['school_id'])
                ->where('student_id', $data['student_id'])
                ->where('concept_id', $data['concept_id'])
                ->whereIn('status', [AccountReceivable::STATUS_PENDING, AccountReceivable::STATUS_PARTIAL])
                ->first();

            if ($existingReceivable) {
                throw new Exception('Student already has a pending account receivable for this concept');
            }

            // Create account receivable
            $accountReceivable = AccountReceivable::create([
                'school_id' => $data['school_id'],
                'student_id' => $data['student_id'],
                'concept_id' => $data['concept_id'],
                'amount' => $data['amount'],
                'due_date' => $dueDate,
                'status' => AccountReceivable::STATUS_PENDING,
                'description' => $data['description'] ?? null,
                'created_by' => $data['created_by']
            ]);

            DB::commit();

            Log::info('Account receivable created', [
                'account_receivable_id' => $accountReceivable->id,
                'school_id' => $data['school_id'],
                'student_id' => $data['student_id'],
                'amount' => $data['amount']
            ]);

            return $accountReceivable->load(['concept', 'createdBy']);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating account receivable', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Register a payment for an account receivable
     *
     * @param array $data
     * @return Payment
     * @throws Exception
     */
    public function registerPayment(array $data): Payment
    {
        try {
            DB::beginTransaction();

            // Find and validate account receivable
            $accountReceivable = AccountReceivable::where('id', $data['account_receivable_id'])
                ->where('school_id', $data['school_id'])
                ->first();

            if (!$accountReceivable) {
                throw new Exception('Account receivable not found');
            }

            // Validate account receivable status
            if (!in_array($accountReceivable->status, [AccountReceivable::STATUS_PENDING, AccountReceivable::STATUS_PARTIAL])) {
                throw new Exception('Cannot register payment for this account receivable status');
            }

            // Validate payment amount
            if ($data['amount'] <= 0) {
                throw new Exception('Payment amount must be greater than zero');
            }

            $remainingAmount = $accountReceivable->remaining_amount;
            if ($data['amount'] > $remainingAmount) {
                throw new Exception("Payment amount ({$data['amount']}) exceeds remaining amount ({$remainingAmount})");
            }

            // Validate payment date
            $paymentDate = Carbon::parse($data['payment_date']);
            if ($paymentDate->isFuture()) {
                throw new Exception('Payment date cannot be in the future');
            }

            // Create payment record
            $payment = Payment::create([
                'school_id' => $data['school_id'],
                'account_receivable_id' => $accountReceivable->id,
                'amount' => $data['amount'],
                'payment_date' => $paymentDate,
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'voucher_path' => $data['voucher_path'] ?? null,
                'status' => Payment::STATUS_PENDING,
                'created_by' => $data['created_by']
            ]);

            // Update account receivable status if needed
            $this->updateAccountReceivableStatus($accountReceivable);

            DB::commit();

            Log::info('Payment registered', [
                'payment_id' => $payment->id,
                'account_receivable_id' => $accountReceivable->id,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method']
            ]);

            return $payment->load(['accountReceivable', 'createdBy']);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error registering payment', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Confirm a pending payment
     *
     * @param Payment $payment
     * @param int $confirmedBy
     * @return Payment
     * @throws Exception
     */
    public function confirmPayment(Payment $payment, int $confirmedBy): Payment
    {
        try {
            DB::beginTransaction();

            if ($payment->status !== Payment::STATUS_PENDING) {
                throw new Exception('Only pending payments can be confirmed');
            }

            // Update payment status
            $payment->status = Payment::STATUS_CONFIRMED;
            $payment->confirmed_by = $confirmedBy;
            $payment->confirmed_at = Carbon::now();
            $payment->save();

            // Update account receivable status
            $this->updateAccountReceivableStatus($payment->accountReceivable);

            // Generate payment voucher automatically
            try {
                $this->voucherGenerator->generatePaymentVoucher($payment);
                Log::info('Payment voucher generated automatically', [
                    'payment_id' => $payment->id
                ]);
            } catch (Exception $voucherException) {
                Log::warning('Failed to generate payment voucher automatically', [
                    'payment_id' => $payment->id,
                    'error' => $voucherException->getMessage()
                ]);
                // Don't fail the payment confirmation if voucher generation fails
            }

            DB::commit();

            Log::info('Payment confirmed', [
                'payment_id' => $payment->id,
                'confirmed_by' => $confirmedBy,
                'amount' => $payment->amount
            ]);

            return $payment->load(['accountReceivable', 'createdBy']);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error confirming payment', [
                'error' => $e->getMessage(),
                'payment_id' => $payment->id
            ]);
            throw $e;
        }
    }

    /**
     * Reject a pending payment
     *
     * @param Payment $payment
     * @param int $rejectedBy
     * @param string|null $rejectionReason
     * @return Payment
     * @throws Exception
     */
    public function rejectPayment(Payment $payment, int $rejectedBy, ?string $rejectionReason = null): Payment
    {
        try {
            DB::beginTransaction();

            if ($payment->status !== Payment::STATUS_PENDING) {
                throw new Exception('Only pending payments can be rejected');
            }

            // Update payment status
            $payment->status = Payment::STATUS_REJECTED;
            $payment->rejected_by = $rejectedBy;
            $payment->rejected_at = Carbon::now();
            $payment->rejection_reason = $rejectionReason;
            $payment->save();

            // Update account receivable status
            $this->updateAccountReceivableStatus($payment->accountReceivable);

            DB::commit();

            Log::info('Payment rejected', [
                'payment_id' => $payment->id,
                'rejected_by' => $rejectedBy,
                'reason' => $rejectionReason
            ]);

            return $payment->load(['accountReceivable', 'createdBy']);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error rejecting payment', [
                'error' => $e->getMessage(),
                'payment_id' => $payment->id
            ]);
            throw $e;
        }
    }

    /**
     * Cancel a payment
     *
     * @param Payment $payment
     * @param int $cancelledBy
     * @param string|null $cancellationReason
     * @return Payment
     * @throws Exception
     */
    public function cancelPayment(Payment $payment, int $cancelledBy, ?string $cancellationReason = null): Payment
    {
        try {
            DB::beginTransaction();

            if (!in_array($payment->status, [Payment::STATUS_PENDING, Payment::STATUS_CONFIRMED])) {
                throw new Exception('Only pending or confirmed payments can be cancelled');
            }

            // Update payment status
            $payment->status = Payment::STATUS_CANCELLED;
            $payment->cancelled_by = $cancelledBy;
            $payment->cancelled_at = Carbon::now();
            $payment->cancellation_reason = $cancellationReason;
            $payment->save();

            // Update account receivable status
            $this->updateAccountReceivableStatus($payment->accountReceivable);

            DB::commit();

            Log::info('Payment cancelled', [
                'payment_id' => $payment->id,
                'cancelled_by' => $cancelledBy,
                'reason' => $cancellationReason
            ]);

            return $payment->load(['accountReceivable', 'createdBy']);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error cancelling payment', [
                'error' => $e->getMessage(),
                'payment_id' => $payment->id
            ]);
            throw $e;
        }
    }

    /**
     * Update account receivable status based on payments
     *
     * @param AccountReceivable $accountReceivable
     * @return void
     */
    private function updateAccountReceivableStatus(AccountReceivable $accountReceivable): void
    {
        $confirmedPayments = $accountReceivable->payments()->confirmed()->sum('amount');
        $totalAmount = $accountReceivable->amount;

        if ($confirmedPayments >= $totalAmount) {
            $accountReceivable->status = AccountReceivable::STATUS_PAID;
        } elseif ($confirmedPayments > 0) {
            $accountReceivable->status = AccountReceivable::STATUS_PARTIAL;
        } else {
            $accountReceivable->status = AccountReceivable::STATUS_PENDING;
        }

        $accountReceivable->save();
    }

    /**
     * Get accounts receivable summary for a school
     *
     * @param int $schoolId
     * @return array
     */
    public function getSchoolSummary(int $schoolId): array
    {
        $total = AccountReceivable::forSchool($schoolId)->sum('amount');
        $pending = AccountReceivable::forSchool($schoolId)->pending()->sum('amount');
        $partial = AccountReceivable::forSchool($schoolId)->partial()->sum('amount');
        $paid = AccountReceivable::forSchool($schoolId)->paid()->sum('amount');
        $overdue = AccountReceivable::forSchool($schoolId)->overdue()->sum('amount');

        $totalPayments = Payment::forSchool($schoolId)->confirmed()->sum('amount');
        $collectionRate = $total > 0 ? ($totalPayments / $total) * 100 : 0;

        return [
            'total_receivables' => round($total, 2),
            'pending_amount' => round($pending, 2),
            'partial_amount' => round($partial, 2),
            'paid_amount' => round($paid, 2),
            'overdue_amount' => round($overdue, 2),
            'total_payments' => round($totalPayments, 2),
            'collection_rate' => round($collectionRate, 2)
        ];
    }

    /**
     * Get overdue accounts for a school
     *
     * @param int $schoolId
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOverdueAccounts(int $schoolId, int $days = 0)
    {
        $query = AccountReceivable::forSchool($schoolId)
            ->with(['concept', 'createdBy'])
            ->whereIn('status', [AccountReceivable::STATUS_PENDING, AccountReceivable::STATUS_PARTIAL]);

        if ($days > 0) {
            $cutoffDate = Carbon::today()->subDays($days);
            $query->where('due_date', '<=', $cutoffDate);
        } else {
            $query->where('due_date', '<', Carbon::today());
        }

        return $query->orderBy('due_date')->get();
    }

    /**
     * Get accounts due soon for a school
     *
     * @param int $schoolId
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAccountsDueSoon(int $schoolId, int $days = 7)
    {
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays($days);

        return AccountReceivable::forSchool($schoolId)
            ->with(['concept', 'createdBy'])
            ->whereIn('status', [AccountReceivable::STATUS_PENDING, AccountReceivable::STATUS_PARTIAL])
            ->whereBetween('due_date', [$startDate, $endDate])
            ->orderBy('due_date')
            ->get();
    }
}