<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'account_number',
        'type',
        'balance',
        'description',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
        'school_id' => 'integer',
        'created_by' => 'integer'
    ];

    /**
     * Get the transactions associated with the account.
     */
    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(Transaction::class, 'transaction_accounts')
                    ->withPivot(['type', 'amount'])
                    ->withTimestamps();
    }

    /**
     * Get the transaction accounts for the account.
     */
    public function transactionAccounts(): HasMany
    {
        return $this->hasMany(TransactionAccount::class);
    }

    /**
     * Scope a query to only include active accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by school.
     */
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Update account balance.
     */
    public function updateBalance($amount, $type = 'debit')
    {
        if ($type === 'debit') {
            $this->balance += $amount;
        } else {
            $this->balance -= $amount;
        }
        $this->save();
    }
}
