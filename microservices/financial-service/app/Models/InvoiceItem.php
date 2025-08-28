<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'concept_id',
        'description',
        'quantity',
        'unit_price',
        'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'invoice_id' => 'integer',
        'concept_id' => 'integer',
    ];

    /**
     * Get the invoice that owns the invoice item.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the financial concept for the invoice item.
     */
    public function concept(): BelongsTo
    {
        return $this->belongsTo(FinancialConcept::class, 'concept_id');
    }

    /**
     * Calculate the total for this item.
     */
    public function calculateTotal(): void
    {
        $this->total = $this->quantity * $this->unit_price;
        $this->save();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically calculate total when creating or updating
        static::saving(function ($item) {
            $item->total = $item->quantity * $item->unit_price;
        });
    }
}