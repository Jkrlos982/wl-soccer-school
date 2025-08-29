<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Requests\StorePlayerEvaluationRequest;
use App\Http\Requests\UpdatePlayerEvaluationRequest;
use App\Models\PlayerEvaluation;
use App\Models\Player;
use App\Transformers\PlayerEvaluationTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use Carbon\Carbon;

class PlayerEvaluationController extends BaseController
{
    /**
     * Display a listing of player evaluations.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PlayerEvaluation::with(['player', 'evaluator', 'training', 'school'])
            ->where('school_id', $request->user()->school_id ?? 1); // TODO: Get from authenticated user
        
        // Apply filters
        if ($request->has('player_id')) {
            $query->byPlayer($request->player_id);
        }
        
        if ($request->has('evaluator_id')) {
            $query->byEvaluator($request->evaluator_id);
        }
        
        if ($request->has('training_id')) {
            $query->byTraining($request->training_id);
        }
        
        if ($request->has('evaluation_type')) {
            $query->byType($request->evaluation_type);
        }
        
        if ($request->has('date_from')) {
            $query->fromDate($request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->toDate($request->date_to);
        }
        
        if ($request->has('min_rating')) {
            $query->withMinRating($request->min_rating);
        }
        
        if ($request->has('max_rating')) {
            $query->withMaxRating($request->max_rating);
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'evaluation_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $evaluations = $query->paginate($request->get('per_page', 15));
        
        return $this->respondWithPaginatedCollection($evaluations, new PlayerEvaluationTransformer());
    }

    /**
     * Store a newly created player evaluation.
     */
    public function store(StorePlayerEvaluationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['school_id'] = $request->user()->school_id ?? 1; // TODO: Get from authenticated user
        $data['evaluator_id'] = $request->user()->id ?? 1; // TODO: Get from authenticated user
        
        // Set evaluation_date to today if not provided
        if (!isset($data['evaluation_date'])) {
            $data['evaluation_date'] = Carbon::today();
        }
        
        $evaluation = PlayerEvaluation::create($data);
        $evaluation->load(['player', 'evaluator', 'training', 'school']);
        
        return $this->respondWithItem($evaluation, new PlayerEvaluationTransformer(), null, 'Evaluación creada exitosamente', 201);
    }

    /**
     * Display the specified player evaluation.
     */
    public function show(PlayerEvaluation $evaluation): JsonResponse
    {
        // TODO: Add policy authorization
        // $this->authorize('view', $evaluation);
        
        $evaluation->load(['player', 'evaluator', 'training', 'school']);
        
        return $this->respondWithItem($evaluation, new PlayerEvaluationTransformer());
    }

    /**
     * Update the specified player evaluation.
     */
    public function update(UpdatePlayerEvaluationRequest $request, PlayerEvaluation $evaluation): JsonResponse
    {
        // TODO: Add policy authorization
        // $this->authorize('update', $evaluation);
        
        $evaluation->update($request->validated());
        $evaluation->load(['player', 'evaluator', 'training', 'school']);
        
        return $this->respondWithItem($evaluation, new PlayerEvaluationTransformer(), null, 'Evaluación actualizada exitosamente');
    }

    /**
     * Remove the specified player evaluation.
     */
    public function destroy(PlayerEvaluation $evaluation): JsonResponse
    {
        // TODO: Add policy authorization
        // $this->authorize('delete', $evaluation);
        
        $evaluation->delete();
        
        return $this->respondWithSuccess(null, 'Evaluación eliminada exitosamente');
    }

    /**
     * Get evaluations for a specific player.
     */
    public function getPlayerEvaluations(Request $request, Player $player): JsonResponse
    {
        // TODO: Add policy authorization
        // $this->authorize('view', $player);
        
        $query = $player->evaluations()
            ->with(['evaluator', 'training'])
            ->where('school_id', $request->user()->school_id ?? 1);
        
        // Apply filters
        if ($request->has('evaluation_type')) {
            $query->byType($request->evaluation_type);
        }
        
        if ($request->has('date_from')) {
            $query->fromDate($request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->toDate($request->date_to);
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'evaluation_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $evaluations = $query->paginate($request->get('per_page', 15));
        
        return $this->respondWithPaginatedCollection($evaluations, new PlayerEvaluationTransformer());
    }

    /**
     * Get evaluation statistics for a player.
     */
    public function getPlayerEvaluationStats(Request $request, Player $player): JsonResponse
    {
        // TODO: Add policy authorization
        // $this->authorize('view', $player);
        
        $query = $player->evaluations()
            ->where('school_id', $request->user()->school_id ?? 1);
        
        // Apply date filters if provided
        if ($request->has('date_from')) {
            $query->fromDate($request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->toDate($request->date_to);
        }
        
        $evaluations = $query->get();
        
        if ($evaluations->isEmpty()) {
            return $this->respondWithSuccess([
                'total_evaluations' => 0,
                'averages' => null,
                'latest_evaluation' => null,
                'progress' => null
            ]);
        }
        
        // Calculate averages
        $stats = [
            'total_evaluations' => $evaluations->count(),
            'averages' => [
                'technical' => round($evaluations->avg(function ($eval) {
                    return $eval->getTechnicalAverage();
                }), 2),
                'physical' => round($evaluations->avg(function ($eval) {
                    return $eval->getPhysicalAverage();
                }), 2),
                'tactical' => round($evaluations->avg(function ($eval) {
                    return $eval->getTacticalAverage();
                }), 2),
                'mental' => round($evaluations->avg(function ($eval) {
                    return $eval->getMentalAverage();
                }), 2),
                'overall' => round($evaluations->avg(function ($eval) {
                    return $eval->getOverallAverage();
                }), 2),
            ],
            'latest_evaluation' => $evaluations->sortByDesc('evaluation_date')->first()?->evaluation_date?->format('Y-m-d'),
            'evaluation_types' => $evaluations->groupBy('evaluation_type')->map->count(),
        ];
        
        // Calculate progress (compare first and last evaluation)
        if ($evaluations->count() >= 2) {
            $firstEval = $evaluations->sortBy('evaluation_date')->first();
            $lastEval = $evaluations->sortByDesc('evaluation_date')->first();
            
            $stats['progress'] = [
                'technical' => round($lastEval->getTechnicalAverage() - $firstEval->getTechnicalAverage(), 2),
                'physical' => round($lastEval->getPhysicalAverage() - $firstEval->getPhysicalAverage(), 2),
                'tactical' => round($lastEval->getTacticalAverage() - $firstEval->getTacticalAverage(), 2),
                'mental' => round($lastEval->getMentalAverage() - $firstEval->getMentalAverage(), 2),
                'overall' => round($lastEval->getOverallAverage() - $firstEval->getOverallAverage(), 2),
            ];
        }
        
        return $this->respondWithSuccess($stats);
    }

    /**
     * Get evaluation comparison between players.
     */
    public function comparePlayerEvaluations(Request $request): JsonResponse
    {
        $request->validate([
            'player_ids' => 'required|array|min:2|max:5',
            'player_ids.*' => 'exists:players,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'evaluation_type' => 'nullable|in:training,match,test,periodic'
        ]);
        
        $playerIds = $request->player_ids;
        $schoolId = $request->user()->school_id ?? 1;
        
        $comparisons = [];
        
        foreach ($playerIds as $playerId) {
            $player = Player::find($playerId);
            if (!$player || $player->school_id !== $schoolId) {
                continue;
            }
            
            $query = $player->evaluations()->where('school_id', $schoolId);
            
            if ($request->has('date_from')) {
                $query->fromDate($request->date_from);
            }
            
            if ($request->has('date_to')) {
                $query->toDate($request->date_to);
            }
            
            if ($request->has('evaluation_type')) {
                $query->byType($request->evaluation_type);
            }
            
            $evaluations = $query->get();
            
            if ($evaluations->isNotEmpty()) {
                $comparisons[] = [
                    'player' => [
                        'id' => $player->id,
                        'name' => $player->full_name,
                        'position' => $player->position,
                    ],
                    'total_evaluations' => $evaluations->count(),
                    'averages' => [
                        'technical' => round($evaluations->avg(function ($eval) {
                            return $eval->getTechnicalAverage();
                        }), 2),
                        'physical' => round($evaluations->avg(function ($eval) {
                            return $eval->getPhysicalAverage();
                        }), 2),
                        'tactical' => round($evaluations->avg(function ($eval) {
                            return $eval->getTacticalAverage();
                        }), 2),
                        'mental' => round($evaluations->avg(function ($eval) {
                            return $eval->getMentalAverage();
                        }), 2),
                        'overall' => round($evaluations->avg(function ($eval) {
                            return $eval->getOverallAverage();
                        }), 2),
                    ]
                ];
            }
        }
        
        return $this->respondWithSuccess([
            'comparisons' => $comparisons,
            'filters_applied' => [
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'evaluation_type' => $request->evaluation_type,
            ]
        ]);
    }
}