<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Requests\StorePlayerRequest;
use App\Http\Requests\UpdatePlayerRequest;
use App\Models\Player;
use App\Transformers\PlayerTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

class PlayerController extends BaseController
{
    /**
     * Display a listing of players.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Player::with(['category', 'school'])
            ->where('school_id', $request->user()->school_id ?? 1); // TODO: Get from authenticated user
        
        // Apply filters
        if ($request->has('category_id')) {
            $query->byCategory($request->category_id);
        }
        
        if ($request->has('gender')) {
            $query->byGender($request->gender);
        }
        
        if ($request->has('position')) {
            $query->byPosition($request->position);
        }
        
        if ($request->has('active')) {
            if ($request->boolean('active')) {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%");
            });
        }
        
        $players = $query->paginate($request->get('per_page', 15));
        
        $resource = new Collection($players->items(), new PlayerTransformer());
        return $this->respondWithPaginatedCollection($resource, $players);
    }

    /**
     * Store a newly created player.
     */
    public function store(StorePlayerRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['school_id'] = $request->user()->school_id ?? 1; // TODO: Get from authenticated user
        
        $player = Player::create($data);
        $player->load(['category', 'school']);
        
        $resource = new Item($player, new PlayerTransformer());
        return $this->respondWithItem($resource, 'Jugador creado exitosamente', 201);
    }

    /**
     * Display the specified player.
     */
    public function show(Player $player): JsonResponse
    {
        // TODO: Add policy authorization
        // $this->authorize('view', $player);
        
        $player->load(['category', 'school']);
        
        $resource = new Item($player, new PlayerTransformer());
        return $this->respondWithItem($resource);
    }

    /**
     * Update the specified player.
     */
    public function update(UpdatePlayerRequest $request, Player $player): JsonResponse
    {
        // TODO: Add policy authorization
        // $this->authorize('update', $player);
        
        $player->update($request->validated());
        $player->load(['category', 'school']);
        
        $resource = new Item($player, new PlayerTransformer());
        return $this->respondWithItem($resource, 'Jugador actualizado exitosamente');
    }

    /**
     * Remove the specified player.
     */
    public function destroy(Player $player): JsonResponse
    {
        // TODO: Add policy authorization
        // $this->authorize('delete', $player);
        
        // TODO: Check if player has attendances or other related data
        // if ($player->attendances()->exists()) {
        //     return $this->respondWithError('No se puede eliminar un jugador con registros de asistencia', 422);
        // }
        
        $player->delete();
        
        return $this->respondWithSuccess('Jugador eliminado exitosamente');
    }

    /**
     * Get players by category.
     */
    public function byCategory(Request $request, $categoryId): JsonResponse
    {
        $players = Player::with(['category', 'school'])
            ->byCategory($categoryId)
            ->where('school_id', $request->user()->school_id ?? 1) // TODO: Get from authenticated user
            ->active()
            ->get();
        
        $resource = new Collection($players, new PlayerTransformer());
        return $this->respondWithCollection($resource);
    }

    /**
     * Get player statistics.
     */
    public function statistics(Player $player): JsonResponse
    {
        // TODO: Add policy authorization
        // $this->authorize('view', $player);
        
        $stats = [
            'basic_info' => [
                'age' => $player->age,
                'position' => $player->position,
                'jersey_number' => $player->jersey_number,
                'enrollment_date' => $player->enrollment_date->format('Y-m-d'),
            ],
            'category_info' => [
                'category_name' => $player->category->name ?? null,
                'is_eligible' => $player->category ? $player->isEligibleForCategory($player->category) : false,
            ],
            'medical_info' => [
                'has_medical_conditions' => $player->hasMedicalConditions(),
                'has_emergency_contact' => $player->hasEmergencyContact(),
            ],
            // TODO: Add attendance and performance statistics when models are available
            // 'attendance' => [
            //     'total_trainings' => $player->attendances()->count(),
            //     'attendance_rate' => $player->getAttendanceRate(),
            // ],
        ];
        
        return $this->respondWithSuccess('Estadísticas del jugador obtenidas exitosamente', $stats);
    }

    /**
     * Upload player photo.
     */
    public function uploadPhoto(Request $request, Player $player): JsonResponse
    {
        // TODO: Add policy authorization
        // $this->authorize('update', $player);
        
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);
        
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($player->photo_path) {
                \Storage::disk('public')->delete($player->photo_path);
            }
            
            $path = $request->file('photo')->store('players/photos', 'public');
            $player->update(['photo_path' => $path]);
            
            return $this->respondWithSuccess('Foto subida exitosamente', [
                'photo_url' => $player->getPhotoUrl()
            ]);
        }
        
        return $this->respondWithError('No se pudo subir la foto', 400);
    }
}
