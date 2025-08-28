<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialConcept extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'description',
        'code',
        'type',
        'category',
        'template_id',
        'is_active',
        'is_default',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'school_id' => 'integer',
        'template_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer'
    ];

    /**
     * Get the transactions for the financial concept.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the template that this concept is based on.
     */
    public function template()
    {
        return $this->belongsTo(ConceptTemplate::class, 'template_id');
    }

    /**
     * Get the user who created this concept.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this concept.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope a query to only include active financial concepts.
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
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to only include default concepts.
     */
    public function scopeDefaults($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to only include custom concepts.
     */
    public function scopeCustom($query)
    {
        return $query->where('is_default', false);
    }

    // Accessors
    public function getIsIncomeAttribute()
    {
        return $this->type === 'income';
    }

    public function getIsExpenseAttribute()
    {
        return $this->type === 'expense';
    }

    // Constants
    const TYPE_INCOME = 'income';
    const TYPE_EXPENSE = 'expense';

    const CATEGORY_EDUCATION = 'educacion';
    const CATEGORY_SALES = 'ventas';
    const CATEGORY_EVENTS = 'eventos';
    const CATEGORY_SPONSORSHIP = 'patrocinios';
    const CATEGORY_PERSONNEL = 'personal';
    const CATEGORY_OPERATIONAL = 'operativos';
    const CATEGORY_EQUIPMENT = 'equipamiento';
    const CATEGORY_MARKETING = 'marketing';
    const CATEGORY_OTHER = 'otros';

    public static function getTypes()
    {
        return [
            self::TYPE_INCOME => 'Ingreso',
            self::TYPE_EXPENSE => 'Gasto'
        ];
    }

    public static function getCategories()
    {
        return [
            self::CATEGORY_EDUCATION => 'Educación',
            self::CATEGORY_SALES => 'Ventas',
            self::CATEGORY_EVENTS => 'Eventos',
            self::CATEGORY_SPONSORSHIP => 'Patrocinios',
            self::CATEGORY_PERSONNEL => 'Personal',
            self::CATEGORY_OPERATIONAL => 'Operativos',
            self::CATEGORY_EQUIPMENT => 'Equipamiento',
            self::CATEGORY_MARKETING => 'Marketing',
            self::CATEGORY_OTHER => 'Otros'
        ];
    }
}
