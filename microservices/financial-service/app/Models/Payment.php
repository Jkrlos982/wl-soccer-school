<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'account_receivable_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'voucher_path',
        'status',
        'created_by'
    ];

    protected $casts = [
        'school_id' => 'integer',
        'account_receivable_id' => 'integer',
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'created_by' => 'integer'
    ];

    /**
     * Get the account receivable that this payment belongs to.
     */
    public function accountReceivable(): BelongsTo
    {
        return $this->belongsTo(AccountReceivable::class);
    }

    /**
     * Get the user who created this payment.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include confirmed payments.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope a query to only include pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to filter by payment method.
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope a query to filter by payment date range.
     */
    public function scopePaidBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    // Constants
    const METHOD_CASH = 'cash';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_CREDIT_CARD = 'credit_card';
    const METHOD_DEBIT_CARD = 'debit_card';
    const METHOD_CHECK = 'check';
    const METHOD_OTHER = 'other';

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    public static function getPaymentMethods()
    {
        return [
            self::METHOD_CASH => 'Efectivo',
            self::METHOD_BANK_TRANSFER => 'Transferencia Bancaria',
            self::METHOD_CREDIT_CARD => 'Tarjeta de Crédito',
            self::METHOD_DEBIT_CARD => 'Tarjeta de Débito',
            self::METHOD_CHECK => 'Cheque',
            self::METHOD_OTHER => 'Otro'
        ];
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_CONFIRMED => 'Confirmado',
            self::STATUS_REJECTED => 'Rechazado',
            self::STATUS_CANCELLED => 'Cancelado'
        ];
    }

    /**
     * Check if the payment has a voucher.
     */
    public function getHasVoucherAttribute()
    {
        return !empty($this->voucher_path);
    }

    /**
     * Get the voucher URL.
     */
    public function getVoucherUrlAttribute()
    {
        if ($this->voucher_path) {
            return asset('storage/' . $this->voucher_path);
        }
        return null;
    }
}