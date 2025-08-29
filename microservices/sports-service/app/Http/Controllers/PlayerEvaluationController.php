<?php

namespace App\Http\Controllers;

use App\Models\PlayerEvaluation;
use App\Models\Player;
use App\Http\Requests\StorePlayerEvaluationRequest;
use App\Http\Requests\UpdatePlayerEvaluationRequest;
use App\Http\Resources\PlayerEvaluationResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlayerEvaluationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $evaluations = PlayerEvaluation::with(['player', 'evaluator', 'training'])
            ->where('school_id', $request->user()->school_id)
            ->when($request->player_id, fn($q, $player) => $q->byPlayer($player))
            ->when($request->evaluation_type, fn($q, $type) => $q->byType($type))
            ->when($request->evaluator_id, fn($q, $eval) => $q->where('evaluator_id', $eval))
            ->when($request->date_from, fn($q, $date) => $q->where('evaluation_date', '>=', $date))
            ->when($request->date_to, fn($q, $date) => $q->where('evaluation_date', '<=', $date))
            ->orderBy('evaluation_date', 'desc')
            ->paginate(20);
            
        return PlayerEvaluationResource::collection($evaluations);
    }

    public function store(StorePlayerEvaluationRequest $request): PlayerEvaluationResource
    {
        $evaluation = PlayerEvaluation::create([
            'school_id' => $request->user()->school_id,
            'evaluator_id' => $request->user()->id,
            ...$request->validated()
        ]);

        // Calcular rating general automáticamente
        $overallRating = $evaluation->calculateOverallRating();
        if ($overallRating) {
            $evaluation->update(['overall_rating' => $overallRating]);
        }

        return new PlayerEvaluationResource($evaluation->load(['player', 'evaluator']));
    }

    public function show(PlayerEvaluation $evaluation): PlayerEvaluationResource
    {
        $this->authorize('view', $evaluation);

        return new PlayerEvaluationResource($evaluation->load([
            'player', 'evaluator', 'training'
        ]));
    }

    public function update(UpdatePlayerEvaluationRequest $request, PlayerEvaluation $evaluation): PlayerEvaluationResource
    {
        $this->authorize('update', $evaluation);

        $evaluation->update($request->validated());

        // Recalcular rating general
        $overallRating = $evaluation->calculateOverallRating();
        if ($overallRating) {
            $evaluation->update(['overall_rating' => $overallRating]);
        }

        return new PlayerEvaluationResource($evaluation->load(['player', 'evaluator']));
    }

    public function destroy(PlayerEvaluation $evaluation): JsonResponse
    {
        $this->authorize('delete', $evaluation);

        $evaluation->delete();

        return response()->json(['message' => 'Evaluación eliminada exitosamente']);
    }

    public function getPlayerProgress(Player $player, Request $request): JsonResponse
    {
        $this->authorize('view', $player);

        $months = $request->months ?? 6;
        $evaluations = $player->evaluations()
            ->where('evaluation_date', '>=', now()->subMonths($months))
            ->orderBy('evaluation_date')
            ->get();
            
        $progress = [
            'technical_progress' => $this->calculateProgress($evaluations, 'getTechnicalAverage'),
            'physical_progress' => $this->calculateProgress($evaluations, 'getPhysicalAverage'),
            'tactical_progress' => $this->calculateProgress($evaluations, 'getTacticalAverage'),
            'mental_progress' => $this->calculateProgress($evaluations, 'getMentalAverage'),
            'overall_progress' => $this->calculateProgress($evaluations, 'overall_rating'),
            'evaluations_count' => $evaluations->count(),
            'latest_evaluation' => $evaluations->last() ? 
                new PlayerEvaluationResource($evaluations->last()) : null
        ];

        return response()->json($progress);
    }

    private function calculateProgress($evaluations, $method): ?array
    {
        if ($evaluations->count() < 2) {
            return null;
        }

        $values = $evaluations->map(function($eval) use ($method) {
            return $method === 'overall_rating' ? $eval->overall_rating : $eval->$method();
        })->filter()->values();

        if ($values->count() < 2) {
            return null;
        }

        $first = $values->first();
        $last = $values->last();

        return [
            'initial_value' => $first,
            'current_value' => $last,
            'improvement' => round($last - $first, 1),
            'improvement_percentage' => $first > 0 ? round((($last - $first) / $first) * 100, 1) : 0
        ];
    }
}