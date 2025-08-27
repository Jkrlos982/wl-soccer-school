<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConceptTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'code',
        'type',
        'category',
        'default_amount',
        'is_active',
        'is_system',
        'metadata',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'default_amount' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $attributes = [
        'is_active' => true,
        'is_system' => false,
        'default_amount' => 0.00
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    // Relationships
    public function financialConcepts()
    {
        return $this->hasMany(FinancialConcept::class, 'template_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
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

    // Methods
    public function createFinancialConcept(array $data = [])
    {
        $conceptData = array_merge([
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code,
            'type' => $this->type,
            'category' => $this->category,
            'template_id' => $this->id,
            'is_active' => true
        ], $data);

        return FinancialConcept::create($conceptData);
    }

    public function duplicate(array $overrides = [])
    {
        $data = array_merge(
            $this->only([
                'name', 'description', 'code', 'type', 'category',
                'default_amount', 'metadata'
            ]),
            $overrides,
            [
                'is_system' => false,
                'created_by' => auth()->id()
            ]
        );

        return static::create($data);
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