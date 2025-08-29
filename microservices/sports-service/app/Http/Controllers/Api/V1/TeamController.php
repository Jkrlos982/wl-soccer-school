<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * TODO: Filter teams by school_id from authenticated user
     */
    public function index()
    {
        // TODO: Get school_id from authenticated user
        $schoolId = 1; // Hardcoded for now
        
        $teams = Team::where('school_id', $schoolId)
            ->with(['category', 'players', 'coach'])
            ->active()
            ->get();
            
        return TeamResource::collection($teams);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeamRequest $request)
    {
        $validated = $request->validated();
        
        // TODO: Get school_id from authenticated user
        $validated['school_id'] = 1; // Hardcoded for now
        
        $team = Team::create($validated);
        $team->load(['category', 'players', 'coach']);
        
        return new TeamResource($team);
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        // TODO: Verify team belongs to authenticated user's school
        $team->load(['category', 'players', 'coach']);
        
        return new TeamResource($team);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTeamRequest $request, Team $team)
    {
        // TODO: Verify team belongs to authenticated user's school
        $validated = $request->validated();
        
        $team->update($validated);
        $team->load(['category', 'players', 'coach']);
        
        return new TeamResource($team);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        // TODO: Verify team belongs to authenticated user's school
        
        // Soft delete the team
        $team->delete();
        
        return response()->json([
            'message' => 'Team deleted successfully'
        ]);
    }
}
