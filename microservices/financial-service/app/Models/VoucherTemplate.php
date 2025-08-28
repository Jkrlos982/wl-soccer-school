<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'type',
        'template_html',
        'variables',
        'is_default'
    ];

    protected $casts = [
        'school_id' => 'integer',
        'variables' => 'array',
        'is_default' => 'boolean'
    ];

    /**
     * Get the user who created this template.
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
     * Scope a query to filter by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include default templates.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the default template for a specific type and school.
     */
    public static function getDefaultTemplate($type, $schoolId = null)
    {
        $query = static::where('type', $type)->where('is_default', true);
        
        if ($schoolId) {
            $query->where(function($q) use ($schoolId) {
                $q->where('school_id', $schoolId)
                  ->orWhereNull('school_id');
            })->orderBy('school_id', 'desc');
        } else {
            $query->whereNull('school_id');
        }
        
        return $query->first();
    }

    // Constants
    const TYPE_PAYMENT_VOUCHER = 'payment_voucher';
    const TYPE_RECEIPT = 'receipt';
    const TYPE_INVOICE = 'invoice';
    const TYPE_PAYMENT_PLAN = 'payment_plan';

    public static function getTypes()
    {
        return [
            self::TYPE_PAYMENT_VOUCHER => 'Comprobante de Pago',
            self::TYPE_RECEIPT => 'Recibo',
            self::TYPE_INVOICE => 'Factura',
            self::TYPE_PAYMENT_PLAN => 'Plan de Pagos'
        ];
    }

    /**
     * Get the available variables for template rendering.
     */
    public function getAvailableVariables()
    {
        return $this->variables ?? [];
    }

    /**
     * Render the template with provided data.
     */
    public function render(array $data = [])
    {
        $html = $this->template_html;
        
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                // Handle nested objects/arrays
                $this->renderNestedData($html, $key, $value);
            } else {
                $html = str_replace('{{ ' . $key . ' }}', $value, $html);
            }
        }
        
        return $html;
    }

    /**
     * Handle nested data rendering (e.g., {{ student.name }}).
     */
    private function renderNestedData(&$html, $prefix, $data)
    {
        if (is_object($data)) {
            $data = $data->toArray();
        }
        
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_scalar($value)) {
                    $placeholder = '{{ ' . $prefix . '.' . $key . ' }}';
                    $html = str_replace($placeholder, $value, $html);
                }
            }
        }
    }
}