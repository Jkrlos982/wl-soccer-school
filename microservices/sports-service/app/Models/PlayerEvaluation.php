<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'player_id', 'evaluator_id', 'training_id', 'evaluation_date',
        'evaluation_type', 'technical_skills', 'ball_control', 'passing', 'shooting',
        'dribbling', 'speed', 'endurance', 'strength', 'agility', 'positioning',
        'decision_making', 'teamwork', 'game_understanding', 'attitude', 'discipline',
        'leadership', 'commitment', 'overall_rating', 'strengths', 'areas_for_improvement',
        'goals_next_period', 'coach_comments', 'custom_metrics'
    ];

    protected $casts = [
        'evaluation_date' => 'date',
        'overall_rating' => 'decimal:1',
        'custom_metrics' => 'array'
    ];

    // Relaciones
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    // Métodos auxiliares
    public function calculateOverallRating(): ?float
    {
        $technicalAvg = $this->getTechnicalAverage();
        $physicalAvg = $this->getPhysicalAverage();
        $tacticalAvg = $this->getTacticalAverage();
        $mentalAvg = $this->getMentalAverage();

        $averages = array_filter([$technicalAvg, $physicalAvg, $tacticalAvg, $mentalAvg]);

        return count($averages) > 0 ? round(array_sum($averages) / count($averages), 1) : null;
    }

    public function getTechnicalAverage(): ?float
    {
        $skills = array_filter([
            $this->technical_skills, $this->ball_control,
            $this->passing, $this->shooting, $this->dribbling
        ]);
        return count($skills) > 0 ? array_sum($skills) / count($skills) : null;
    }

    public function getPhysicalAverage(): ?float
    {
        $skills = array_filter([
            $this->speed, $this->endurance, $this->strength, $this->agility
        ]);
        return count($skills) > 0 ? array_sum($skills) / count($skills) : null;
    }

    public function getTacticalAverage(): ?float
    {
        $skills = array_filter([
            $this->positioning, $this->decision_making,
            $this->teamwork, $this->game_understanding
        ]);
        return count($skills) > 0 ? array_sum($skills) / count($skills) : null;
    }

    public function getMentalAverage(): ?float
    {
        $skills = array_filter([
            $this->attitude, $this->discipline, $this->leadership, $this->commitment
        ]);
        return count($skills) > 0 ? array_sum($skills) / count($skills) : null;
    }

    // Scopes
    public function scopeByPlayer($query, $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('evaluation_type', $type);
    }

    public function scopeRecent($query, $months = 3)
    {
        return $query->where('evaluation_date', '>=', now()->subMonths($months));
    }

    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeByEvaluator($query, $evaluatorId)
    {
        return $query->where('evaluator_id', $evaluatorId);
    }

    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        $query->where('evaluation_date', '>=', $startDate);
        
        if ($endDate) {
            $query->where('evaluation_date', '<=', $endDate);
        }
        
        return $query;
    }

    // Validation rules
    public static function validationRules(): array
    {
        return [
            'player_id' => 'required|exists:players,id',
            'training_id' => 'nullable|exists:trainings,id',
            'evaluation_date' => 'required|date',
            'evaluation_type' => 'required|in:training,match,monthly,semester',
            'technical_skills' => 'nullable|integer|min:1|max:10',
            'ball_control' => 'nullable|integer|min:1|max:10',
            'passing' => 'nullable|integer|min:1|max:10',
            'shooting' => 'nullable|integer|min:1|max:10',
            'dribbling' => 'nullable|integer|min:1|max:10',
            'speed' => 'nullable|integer|min:1|max:10',
            'endurance' => 'nullable|integer|min:1|max:10',
            'strength' => 'nullable|integer|min:1|max:10',
            'agility' => 'nullable|integer|min:1|max:10',
            'positioning' => 'nullable|integer|min:1|max:10',
            'decision_making' => 'nullable|integer|min:1|max:10',
            'teamwork' => 'nullable|integer|min:1|max:10',
            'game_understanding' => 'nullable|integer|min:1|max:10',
            'attitude' => 'nullable|integer|min:1|max:10',
            'discipline' => 'nullable|integer|min:1|max:10',
            'leadership' => 'nullable|integer|min:1|max:10',
            'commitment' => 'nullable|integer|min:1|max:10',
            'overall_rating' => 'nullable|numeric|min:1|max:10',
            'strengths' => 'nullable|string|max:1000',
            'areas_for_improvement' => 'nullable|string|max:1000',
            'goals_next_period' => 'nullable|string|max:1000',
            'coach_comments' => 'nullable|string|max:2000',
            'custom_metrics' => 'nullable|array'
        ];
    }

    // Custom validation messages
    public static function validationMessages(): array
    {
        return [
            'player_id.required' => 'El jugador es obligatorio.',
            'player_id.exists' => 'El jugador seleccionado no existe.',
            'training_id.exists' => 'El entrenamiento seleccionado no existe.',
            'evaluation_date.required' => 'La fecha de evaluación es obligatoria.',
            'evaluation_date.date' => 'La fecha de evaluación debe ser una fecha válida.',
            'evaluation_type.required' => 'El tipo de evaluación es obligatorio.',
            'evaluation_type.in' => 'El tipo de evaluación debe ser: entrenamiento, partido, mensual o semestral.',
            '*.min' => 'La calificación debe ser mínimo 1.',
            '*.max' => 'La calificación debe ser máximo 10.',
            'strengths.max' => 'Las fortalezas no pueden exceder 1000 caracteres.',
            'areas_for_improvement.max' => 'Las áreas de mejora no pueden exceder 1000 caracteres.',
            'goals_next_period.max' => 'Los objetivos no pueden exceder 1000 caracteres.',
            'coach_comments.max' => 'Los comentarios no pueden exceder 2000 caracteres.'
        ];
    }
}