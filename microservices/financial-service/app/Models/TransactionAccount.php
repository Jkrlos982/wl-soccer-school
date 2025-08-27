<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'account_id',
        'type',
        'amount'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_id' => 'integer',
        'account_id' => 'integer'
    ];

    /**
     * Get the transaction that owns the transaction account.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the account that owns the transaction account.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter debits.
     */
    public function scopeDebits($query)
    {
        return $query->where('type', 'debit');
    }

    /**
     * Scope a query to filter credits.
     */
    public function scopeCredits($query)
    {
        return $query->where('type', 'credit');
    }
}
