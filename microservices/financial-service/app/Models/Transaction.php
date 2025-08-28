<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'financial_concept_id',
        'reference_number',
        'description',
        'amount',
        'transaction_date',
        'status',
        'payment_method',
        'metadata',
        'created_by',
        'approved_by',
        'approved_at',
        'approval_notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'approved_at' => 'datetime',
        'metadata' => 'array',
        'school_id' => 'integer',
        'financial_concept_id' => 'integer',
        'created_by' => 'integer',
        'approved_by' => 'integer'
    ];

    /**
     * Get the financial concept that owns the transaction.
     */
    public function financialConcept(): BelongsTo
    {
        return $this->belongsTo(FinancialConcept::class);
    }

    /**
     * Get the accounts associated with the transaction.
     */
    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'transaction_accounts')
                    ->withPivot(['type', 'amount'])
                    ->withTimestamps();
    }

    /**
     * Get the transaction accounts for the transaction.
     */
    public function transactionAccounts(): HasMany
    {
        return $this->hasMany(TransactionAccount::class);
    }

    /**
     * Scope a query to filter by school.
     */
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to filter approved transactions.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to filter by financial concept type.
     */
    public function scopeByConceptType($query, $type)
    {
        return $query->whereHas('financialConcept', function ($q) use ($type) {
            $q->where('type', $type);
        });
    }

    /**
     * Check if transaction is pending approval.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if transaction can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction can be rejected.
     */
    public function canBeRejected(): bool
    {
        return in_array($this->status, ['pending', 'approved']);
    }
}
