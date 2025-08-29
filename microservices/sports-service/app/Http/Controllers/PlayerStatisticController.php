<?php

namespace App\Http\Controllers;

use App\Models\PlayerStatistic;
use App\Http\Resources\PlayerStatisticResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PlayerStatisticController extends Controller
{
    /**
     * Display a listing of player statistics.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PlayerStatistic::with(['player', 'training', 'recordedBy']);
        
        // Filter by player
        if ($request->has('player_id')) {
            $query->where('player_id', $request->player_id);
        }
        
        // Filter by context
        if ($request->has('context')) {
            $query->where('context', $request->context);
        }
        
        // Filter by date range
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('date', [$request->date_from, $request->date_to]);
        }
        
        // Filter by school
        if ($request->has('school_id')) {
            $query->where('school_id', $request->school_id);
        }
        
        $statistics = $query->orderBy('date', 'desc')->paginate(15);
        
        return response()->json([
            'success' => true,
            'message' => 'Player statistics retrieved successfully',
            'data' => PlayerStatisticResource::collection($statistics->items()),
            'pagination' => [
                'current_page' => $statistics->currentPage(),
                'last_page' => $statistics->lastPage(),
                'per_page' => $statistics->perPage(),
                'total' => $statistics->total()
            ]
        ]);
    }

    /**
     * Store a newly created player statistic.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'school_id' => 'required|exists:schools,id',
            'player_id' => 'required|exists:players,id',
            'training_id' => 'nullable|exists:trainings,id',
            'match_id' => 'nullable|integer',
            'date' => 'required|date',
            'context' => 'required|in:training,match,friendly',
            'minutes_played' => 'nullable|integer|min:0|max:120',
            'goals_scored' => 'nullable|integer|min:0',
            'assists' => 'nullable|integer|min:0',
            'shots_on_target' => 'nullable|integer|min:0',
            'shots_off_target' => 'nullable|integer|min:0',
            'passes_completed' => 'nullable|integer|min:0',
            'passes_attempted' => 'nullable|integer|min:0',
            'tackles_won' => 'nullable|integer|min:0',
            'tackles_lost' => 'nullable|integer|min:0',
            'interceptions' => 'nullable|integer|min:0',
            'fouls_committed' => 'nullable|integer|min:0',
            'fouls_received' => 'nullable|integer|min:0',
            'yellow_cards' => 'nullable|integer|min:0',
            'red_cards' => 'nullable|integer|min:0',
            'saves' => 'nullable|integer|min:0',
            'goals_conceded' => 'nullable|integer|min:0',
            'clean_sheets' => 'nullable|boolean',
            'crosses_completed' => 'nullable|integer|min:0',
            'dribbles_successful' => 'nullable|integer|min:0',
            'aerial_duels_won' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['recorded_by'] = $request->auth_user['id'];

        $statistic = PlayerStatistic::create($data);
        $statistic->load(['player', 'training', 'recordedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Player statistic created successfully',
            'data' => new PlayerStatisticResource($statistic)
        ], 201);
    }

    /**
     * Display the specified player statistic.
     */
    public function show(PlayerStatistic $playerStatistic): JsonResponse
    {
        $playerStatistic->load(['player', 'training', 'recordedBy']);
        
        return response()->json([
            'success' => true,
            'message' => 'Player statistic retrieved successfully',
            'data' => new PlayerStatisticResource($playerStatistic)
        ]);
    }

    /**
     * Update the specified player statistic.
     */
    public function update(Request $request, PlayerStatistic $playerStatistic): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'training_id' => 'nullable|exists:trainings,id',
            'match_id' => 'nullable|integer',
            'date' => 'sometimes|required|date',
            'context' => 'sometimes|required|in:training,match,friendly',
            'minutes_played' => 'nullable|integer|min:0|max:120',
            'goals_scored' => 'nullable|integer|min:0',
            'assists' => 'nullable|integer|min:0',
            'shots_on_target' => 'nullable|integer|min:0',
            'shots_off_target' => 'nullable|integer|min:0',
            'passes_completed' => 'nullable|integer|min:0',
            'passes_attempted' => 'nullable|integer|min:0',
            'tackles_won' => 'nullable|integer|min:0',
            'tackles_lost' => 'nullable|integer|min:0',
            'interceptions' => 'nullable|integer|min:0',
            'fouls_committed' => 'nullable|integer|min:0',
            'fouls_received' => 'nullable|integer|min:0',
            'yellow_cards' => 'nullable|integer|min:0',
            'red_cards' => 'nullable|integer|min:0',
            'saves' => 'nullable|integer|min:0',
            'goals_conceded' => 'nullable|integer|min:0',
            'clean_sheets' => 'nullable|boolean',
            'crosses_completed' => 'nullable|integer|min:0',
            'dribbles_successful' => 'nullable|integer|min:0',
            'aerial_duels_won' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $playerStatistic->update($validator->validated());
        $playerStatistic->load(['player', 'training', 'recordedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Player statistic updated successfully',
            'data' => new PlayerStatisticResource($playerStatistic)
        ]);
    }

    /**
     * Remove the specified player statistic.
     */
    public function destroy(PlayerStatistic $playerStatistic): JsonResponse
    {
        $playerStatistic->delete();

        return response()->json([
            'success' => true,
            'message' => 'Player statistic deleted successfully'
        ]);
    }
}