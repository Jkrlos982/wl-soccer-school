<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class PayrollConcept extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'calculation_type',
        'default_value',
        'formula',
        'is_taxable',
        'affects_social_security',
        'is_mandatory',
        'display_order',
        'status',
    ];

    protected $casts = [
        'default_value' => 'decimal:4',
        'is_taxable' => 'boolean',
        'affects_social_security' => 'boolean',
        'is_mandatory' => 'boolean',
        'formula' => 'array',
    ];

    protected $dates = [
        'deleted_at',
    ];

    /**
     * Get payroll details for this concept.
     */
    public function payrollDetails(): HasMany
    {
        return $this->hasMany(PayrollDetail::class);
    }

    /**
     * Get employee benefits for this concept.
     */
    public function employeeBenefits(): HasMany
    {
        return $this->hasMany(EmployeeBenefit::class);
    }

    /**
     * Check if concept is active.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if concept is an earning.
     */
    public function isEarning(): bool
    {
        return $this->type === 'earning';
    }

    /**
     * Check if concept is a deduction.
     */
    public function isDeduction(): bool
    {
        return $this->type === 'deduction';
    }

    /**
     * Check if concept is a tax.
     */
    public function isTax(): bool
    {
        return $this->type === 'tax';
    }

    /**
     * Check if concept is taxable.
     */
    public function isTaxable(): bool
    {
        return $this->is_taxable;
    }

    /**
     * Check if concept is mandatory.
     */
    public function isMandatory(): bool
    {
        return $this->is_mandatory;
    }

    /**
     * Calculate amount based on base amount and rate.
     */
    public function calculateAmount(float $baseAmount, float $rate = null, float $quantity = 1): float
    {
        $rate = $rate ?? $this->default_rate ?? 0;
        $amount = 0;

        switch ($this->calculation_type) {
            case 'fixed':
                $amount = $this->default_amount * $quantity;
                break;
            case 'percentage':
                $amount = ($baseAmount * $rate / 100) * $quantity;
                break;
            case 'rate':
                $amount = $rate * $quantity;
                break;
            case 'formula':
                $amount = $this->calculateByFormula($baseAmount, $rate, $quantity);
                break;
            default:
                $amount = $this->default_amount * $quantity;
        }

        // Apply minimum and maximum limits
        if ($this->minimum_amount && $amount < $this->minimum_amount) {
            $amount = $this->minimum_amount;
        }

        if ($this->maximum_amount && $amount > $this->maximum_amount) {
            $amount = $this->maximum_amount;
        }

        return round($amount, 2);
    }

    /**
     * Calculate amount using formula.
     */
    protected function calculateByFormula(float $baseAmount, float $rate, float $quantity): float
    {
        if (!$this->formula || !is_array($this->formula)) {
            return 0;
        }

        // Simple formula evaluation
        // This is a basic implementation - in production, you might want to use a proper expression evaluator
        $variables = [
            'base_amount' => $baseAmount,
            'rate' => $rate,
            'quantity' => $quantity,
            'default_amount' => $this->default_amount,
            'default_rate' => $this->default_rate,
        ];

        $expression = $this->formula['expression'] ?? '';
        
        // Replace variables in expression
        foreach ($variables as $var => $value) {
            $expression = str_replace('{' . $var . '}', $value, $expression);
        }

        // Basic safety check - only allow numbers, operators, and parentheses
        if (preg_match('/^[0-9+\\-*\\/.() ]+$/', $expression)) {
            try {
                return eval("return $expression;");
            } catch (Exception $e) {
                return 0;
            }
        }

        return 0;
    }

    /**
     * Get formatted type name.
     */
    public function getFormattedTypeAttribute(): string
    {
        return ucfirst($this->type);
    }

    /**
     * Get formatted category name.
     */
    public function getFormattedCategoryAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->category));
    }

    /**
     * Scope for active concepts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for earnings.
     */
    public function scopeEarnings($query)
    {
        return $query->where('type', 'earning');
    }

    /**
     * Scope for deductions.
     */
    public function scopeDeductions($query)
    {
        return $query->where('type', 'deduction');
    }

    /**
     * Scope for taxes.
     */
    public function scopeTaxes($query)
    {
        return $query->where('type', 'tax');
    }

    /**
     * Scope for mandatory concepts.
     */
    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    /**
     * Scope for taxable concepts.
     */
    public function scopeTaxable($query)
    {
        return $query->where('is_taxable', true);
    }

    /**
     * Scope ordered by order field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }
}
