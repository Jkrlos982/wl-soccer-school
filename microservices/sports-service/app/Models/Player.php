<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'category_id',
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'document_type',
        'document_number',
        'address',
        'phone',
        'email',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'medical_conditions',
        'allergies',
        'medications',
        'position',
        'jersey_number',
        'photo_path',
        'is_active',
        'enrollment_date',
        'notes'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'enrollment_date' => 'date',
        'is_active' => 'boolean'
    ];

    protected $appends = [
        'full_name',
        'age',
        'display_name'
    ];

    // Relationships
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function trainings()
    {
        return $this->belongsToMany(Training::class, 'attendances')
                    ->withPivot(['status', 'arrival_time', 'notes', 'recorded_at'])
                    ->withTimestamps();
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function evaluations()
    {
        return $this->hasMany(PlayerEvaluation::class);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getAgeAttribute()
    {
        return Carbon::parse($this->birth_date)->age;
    }

    public function getDisplayNameAttribute()
    {
        return $this->full_name . ($this->jersey_number ? ' (#' . $this->jersey_number . ')' : '');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByGender($query, $gender)
    {
        return $query->where('gender', $gender);
    }

    public function scopeByPosition($query, $position)
    {
        return $query->where('position', $position);
    }

    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    // Helper methods
    public function isEligibleForCategory(Category $category)
    {
        $age = $this->age;
        $genderMatch = $category->gender === 'mixed' || $category->gender === $this->gender;
        $ageMatch = $age >= $category->min_age && $age <= $category->max_age;
        
        return $genderMatch && $ageMatch;
    }

    public function hasEmergencyContact()
    {
        return !empty($this->emergency_contact_name) && !empty($this->emergency_contact_phone);
    }

    public function hasMedicalConditions()
    {
        return !empty($this->medical_conditions) || !empty($this->allergies) || !empty($this->medications);
    }

    public function getPhotoUrl()
    {
        if ($this->photo_path) {
            return asset('storage/' . $this->photo_path);
        }
        
        // Return default avatar based on gender
        return asset('images/avatars/default-' . $this->gender . '.png');
    }

    // TODO: Implement when Attendance model is created
    // public function getAttendanceRate($startDate = null, $endDate = null)
    // {
    //     $query = $this->attendances();
    //     
    //     if ($startDate) {
    //         $query->where('date', '>=', $startDate);
    //     }
    //     
    //     if ($endDate) {
    //         $query->where('date', '<=', $endDate);
    //     }
    //     
    //     $total = $query->count();
    //     $present = $query->whereIn('status', ['present', 'late'])->count();
    //     
    //     return $total > 0 ? round(($present / $total) * 100, 2) : 0;
    // }

    // Validation rules
    public static function validationRules($playerId = null)
    {
        $documentRule = 'required|string|max:20';
        if ($playerId) {
            $documentRule .= '|unique:players,document_number,' . $playerId;
        } else {
            $documentRule .= '|unique:players,document_number';
        }

        return [
            'school_id' => 'required|exists:schools,id',
            'category_id' => 'required|exists:categories,id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'birth_date' => 'required|date|before:today',
            'gender' => 'required|in:male,female',
            'document_type' => 'required|in:CC,TI,CE,PP',
            'document_number' => $documentRule,
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'emergency_contact_name' => 'required|string|max:100',
            'emergency_contact_phone' => 'required|string|max:20',
            'emergency_contact_relationship' => 'required|string|max:50',
            'medical_conditions' => 'nullable|string|max:500',
            'allergies' => 'nullable|string|max:500',
            'medications' => 'nullable|string|max:500',
            'position' => 'nullable|in:goalkeeper,defender,midfielder,forward',
            'jersey_number' => 'nullable|integer|min:1|max:99',
            'photo_path' => 'nullable|string|max:255',
            'enrollment_date' => 'required|date',
            'notes' => 'nullable|string|max:1000'
        ];
    }

    // Custom validation messages
    public static function validationMessages()
    {
        return [
            'school_id.required' => 'La escuela es obligatoria.',
            'school_id.exists' => 'La escuela seleccionada no existe.',
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'first_name.required' => 'El nombre es obligatorio.',
            'last_name.required' => 'El apellido es obligatorio.',
            'birth_date.required' => 'La fecha de nacimiento es obligatoria.',
            'birth_date.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'gender.required' => 'El género es obligatorio.',
            'gender.in' => 'El género debe ser masculino o femenino.',
            'document_type.required' => 'El tipo de documento es obligatorio.',
            'document_number.required' => 'El número de documento es obligatorio.',
            'document_number.unique' => 'Ya existe un jugador con este número de documento.',
            'emergency_contact_name.required' => 'El nombre del contacto de emergencia es obligatorio.',
            'emergency_contact_phone.required' => 'El teléfono del contacto de emergencia es obligatorio.',
            'emergency_contact_relationship.required' => 'La relación del contacto de emergencia es obligatoria.',
            'position.in' => 'La posición debe ser: portero, defensa, mediocampo o delantero.',
            'jersey_number.min' => 'El número de camiseta debe ser mayor a 0.',
            'jersey_number.max' => 'El número de camiseta debe ser menor a 100.',
            'enrollment_date.required' => 'La fecha de inscripción es obligatoria.'
        ];
    }
}