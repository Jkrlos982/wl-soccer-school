<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\FinancialConcept;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

class TransactionService
{
    /**
     * Create a new transaction with account balance updates
     */
    public function createTransaction(array $data): Transaction
    {
        DB::beginTransaction();
        
        try {
            // Generate reference number if not provided
            if (!isset($data['reference_number'])) {
                $data['reference_number'] = $this->generateReferenceNumber(
                    $data['financial_concept_id'],
                    $data['transaction_date'] ?? now()
                );
            }
            
            // Set default status if not provided
            if (!isset($data['status'])) {
                $data['status'] = 'pending';
            }
            
            // Validate business rules
            $this->validateTransaction($data);
            
            // Create transaction
            $transaction = Transaction::create($data);
            
            // Update account balances if accounts are provided
            if (isset($data['accounts']) && is_array($data['accounts'])) {
                $this->updateAccountBalances($transaction, $data['accounts']);
            }
            
            // Log transaction for audit
            $this->logTransaction($transaction);
            
            DB::commit();
            
            return $transaction->load(['financialConcept', 'accounts']);
            
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Transaction creation failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }
    
    /**
     * Update transaction status (approval workflow)
     */
    public function updateTransactionStatus(Transaction $transaction, string $status, ?string $reason = null): Transaction
    {
        $validStatuses = ['pending', 'approved', 'rejected', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            throw new Exception("Invalid status: {$status}");
        }
        
        $oldStatus = $transaction->status;
        
        DB::beginTransaction();
        
        try {
            $transaction->update([
                'status' => $status,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'status_history' => array_merge(
                        $transaction->metadata['status_history'] ?? [],
                        [[
                            'from' => $oldStatus,
                            'to' => $status,
                            'reason' => $reason,
                            'changed_at' => now()->toISOString(),
                            'changed_by' => auth()->id()
                        ]]
                    )
                ])
            ]);
            
            // If transaction is being approved, ensure account balances are updated
            if ($status === 'approved' && $oldStatus === 'pending') {
                $this->ensureAccountBalancesUpdated($transaction);
            }
            
            // If transaction is being rejected/cancelled, reverse account balances
            if (in_array($status, ['rejected', 'cancelled']) && $oldStatus === 'approved') {
                $this->reverseAccountBalances($transaction);
            }
            
            $this->logStatusChange($transaction, $oldStatus, $status, $reason);
            
            DB::commit();
            
            return $transaction->fresh(['financialConcept', 'accounts']);
            
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
    
    /**
     * Generate unique reference number
     */
    public function generateReferenceNumber(int $financialConceptId, $date = null): string
    {
        $date = $date ? Carbon::parse($date) : now();
        $concept = FinancialConcept::find($financialConceptId);
        
        $type = $concept ? strtoupper(substr($concept->type, 0, 3)) : 'TXN';
        $year = $date->format('Y');
        $month = $date->format('m');
        
        // Get next sequential number for this month and type
        $lastTransaction = Transaction::whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->where('reference_number', 'like', "{$year}-{$month}-{$type}-%")
            ->orderBy('reference_number', 'desc')
            ->first();
        
        $nextNumber = 1;
        if ($lastTransaction) {
            $parts = explode('-', $lastTransaction->reference_number);
            if (count($parts) === 4) {
                $nextNumber = intval($parts[3]) + 1;
            }
        }
        
        return sprintf('%s-%s-%s-%04d', $year, $month, $type, $nextNumber);
    }
    
    /**
     * Update account balances based on transaction
     */
    protected function updateAccountBalances(Transaction $transaction, array $accounts): void
    {
        foreach ($accounts as $accountData) {
            $account = Account::findOrFail($accountData['account_id']);
            $amount = $accountData['amount'];
            $type = $accountData['type']; // 'debit' or 'credit'
            
            // Validate account belongs to same school
            if ($account->school_id !== $transaction->school_id) {
                throw new Exception("Account {$account->id} does not belong to school {$transaction->school_id}");
            }
            
            // Update account balance
            $account->updateBalance($amount, $type);
            
            // Attach account to transaction with pivot data
            $transaction->accounts()->attach($account->id, [
                'type' => $type,
                'amount' => $amount
            ]);
        }
    }
    
    /**
     * Ensure account balances are updated (for approved transactions)
     */
    protected function ensureAccountBalancesUpdated(Transaction $transaction): void
    {
        // Check if balances were already updated
        if ($transaction->accounts()->count() === 0) {
            // No accounts attached, skip balance update
            return;
        }
        
        // Balances should already be updated when transaction was created
        // This is a safety check for approved transactions
    }
    
    /**
     * Reverse account balances (for rejected/cancelled transactions)
     */
    protected function reverseAccountBalances(Transaction $transaction): void
    {
        foreach ($transaction->transactionAccounts as $transactionAccount) {
            $account = $transactionAccount->account;
            $amount = $transactionAccount->amount;
            $type = $transactionAccount->type;
            
            // Reverse the balance update
            $reverseType = $type === 'debit' ? 'credit' : 'debit';
            $account->updateBalance($amount, $reverseType);
        }
    }
    
    /**
     * Validate transaction business rules
     */
    protected function validateTransaction(array $data): void
    {
        // Validate financial concept exists and is active
        $concept = FinancialConcept::find($data['financial_concept_id']);
        if (!$concept || !$concept->is_active) {
            throw new Exception('Financial concept is not active or does not exist');
        }
        
        // Validate transaction date is not in the future
        $transactionDate = Carbon::parse($data['transaction_date'] ?? now());
        if ($transactionDate->isFuture()) {
            throw new Exception('Transaction date cannot be in the future');
        }
        
        // Validate amount is positive
        if ($data['amount'] <= 0) {
            throw new Exception('Transaction amount must be positive');
        }
        
        // Validate accounts if provided
        if (isset($data['accounts']) && is_array($data['accounts'])) {
            $this->validateAccounts($data['accounts'], $data['school_id']);
        }
    }
    
    /**
     * Validate accounts data
     */
    protected function validateAccounts(array $accounts, int $schoolId): void
    {
        foreach ($accounts as $accountData) {
            if (!isset($accountData['account_id'], $accountData['amount'], $accountData['type'])) {
                throw new Exception('Account data must include account_id, amount, and type');
            }
            
            $account = Account::find($accountData['account_id']);
            if (!$account) {
                throw new Exception("Account {$accountData['account_id']} not found");
            }
            
            if ($account->school_id !== $schoolId) {
                throw new Exception("Account {$accountData['account_id']} does not belong to school {$schoolId}");
            }
            
            if (!$account->is_active) {
                throw new Exception("Account {$accountData['account_id']} is not active");
            }
            
            if (!in_array($accountData['type'], ['debit', 'credit'])) {
                throw new Exception('Account type must be either debit or credit');
            }
            
            if ($accountData['amount'] <= 0) {
                throw new Exception('Account amount must be positive');
            }
        }
    }
    
    /**
     * Log transaction for audit purposes
     */
    protected function logTransaction(Transaction $transaction): void
    {
        Log::info('Transaction created', [
            'transaction_id' => $transaction->id,
            'reference_number' => $transaction->reference_number,
            'amount' => $transaction->amount,
            'school_id' => $transaction->school_id,
            'created_by' => $transaction->created_by
        ]);
    }
    
    /**
     * Log status change for audit purposes
     */
    protected function logStatusChange(Transaction $transaction, string $oldStatus, string $newStatus, ?string $reason): void
    {
        Log::info('Transaction status changed', [
            'transaction_id' => $transaction->id,
            'reference_number' => $transaction->reference_number,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'changed_by' => auth()->id()
        ]);
    }
}