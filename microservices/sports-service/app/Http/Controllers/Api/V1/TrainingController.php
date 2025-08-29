<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrainingRequest;
use App\Http\Requests\UpdateTrainingRequest;
use App\Http\Requests\CompleteTrainingRequest;
use App\Http\Resources\TrainingResource;
use App\Models\Training;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TrainingController extends BaseController
{
    /**
     * Display a listing of trainings.
     */
    public function index(Request $request)
    {
        $trainings = Training::with(['category', 'coach', 'attendances'])
            ->where('school_id', $this->getSchoolId($request))
            ->when($request->category_id, fn($q, $cat) => $q->byCategory($cat))
            ->when($request->coach_id, fn($q, $coach) => $q->byCoach($coach))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->date_from, fn($q, $date) => $q->where('date', '>=', $date))
            ->when($request->date_to, fn($q, $date) => $q->where('date', '<=', $date))
            ->when($request->search, function($q, $search) {
                $q->where(function($query) use ($search) {
                    $query->where('location', 'like', "%{$search}%")
                          ->orWhere('objectives', 'like', "%{$search}%")
                          ->orWhere('activities', 'like', "%{$search}%")
                          ->orWhereHas('category', function($q) use ($search) {
                              $q->where('name', 'like', "%{$search}%");
                          });
                });
            })
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(20);
            
        return TrainingResource::collection($trainings);
    }

    /**
     * Store a newly created training.
     */
    public function store(StoreTrainingRequest $request)
    {
        $training = Training::create([
            'school_id' => $this->getSchoolId($request),
            ...$request->validated()
        ]);

        // Crear asistencias automáticamente para todos los jugadores de la categoría
        $this->createAttendanceRecords($training);

        return new TrainingResource($training->load(['category', 'coach', 'attendances']));
    }

    /**
     * Display the specified training.
     */
    public function show(Training $training)
    {
        // $this->authorize('view', $training);

        return new TrainingResource($training->load([
            'category', 'coach', 'attendances.player'
        ]));
    }

    /**
     * Update the specified training.
     */
    public function update(UpdateTrainingRequest $request, Training $training)
    {
        // $this->authorize('update', $training);

        $training->update($request->validated());

        return new TrainingResource($training->load(['category', 'coach', 'attendances']));
    }

    /**
     * Remove the specified training.
     */
    public function destroy(Training $training): JsonResponse
    {
        // $this->authorize('delete', $training);

        if ($training->status === 'completed') {
            return response()->json([
                'message' => 'No se puede eliminar un entrenamiento completado'
            ], 422);
        }

        $training->delete();

        return response()->json(['message' => 'Entrenamiento eliminado exitosamente']);
    }

    /**
     * Start a training session.
     */
    public function startTraining(Training $training)
    {
        // $this->authorize('update', $training);

        if ($training->status !== 'scheduled') {
            return response()->json([
                'message' => 'Solo se pueden iniciar entrenamientos programados'
            ], 422);
        }

        $training->update(['status' => 'in_progress']);

        return new TrainingResource($training);
    }

    /**
     * Complete a training session.
     */
    public function completeTraining(CompleteTrainingRequest $request, Training $training)
    {
        // $this->authorize('update', $training);

        if ($training->status !== 'in_progress') {
            return response()->json([
                'message' => 'Solo se pueden completar entrenamientos en progreso'
            ], 422);
        }

        $training->update([
            'status' => 'completed',
            'observations' => $request->observations,
            'duration_minutes' => $request->duration_minutes
        ]);

        return new TrainingResource($training);
    }

    /**
     * Get upcoming trainings.
     */
    public function getUpcoming(Request $request)
    {
        $trainings = Training::with(['category', 'coach'])
            ->where('school_id', $this->getSchoolId($request))
            ->upcoming()
            ->when($request->category_id, fn($q, $cat) => $q->byCategory($cat))
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(10)
            ->get();
            
        return TrainingResource::collection($trainings);
    }

    /**
     * Get trainings by category.
     */
    public function byCategory(Request $request, $categoryId)
    {
        $trainings = Training::with(['category', 'coach', 'attendances'])
            ->where('school_id', $this->getSchoolId($request))
            ->byCategory($categoryId)
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(15);

        return TrainingResource::collection($trainings);
    }

    /**
     * Get training statistics.
     */
    public function statistics(Request $request)
    {
        $schoolId = $this->getSchoolId($request);
        
        $stats = [
            'total_trainings' => Training::where('school_id', $schoolId)->count(),
            'completed_trainings' => Training::where('school_id', $schoolId)->completed()->count(),
            'upcoming_trainings' => Training::where('school_id', $schoolId)->upcoming()->count(),
            'trainings_by_type' => Training::where('school_id', $schoolId)
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'trainings_by_status' => Training::where('school_id', $schoolId)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'recent_trainings' => TrainingResource::collection(
                Training::with(['category', 'coach'])
                    ->where('school_id', $schoolId)
                    ->recent(5)
                    ->get()
            )
        ];

        return response()->json($stats);
    }

    /**
     * Cancel a training.
     */
    public function cancelTraining(Training $training)
    {
        // $this->authorize('update', $training);

        if (in_array($training->status, ['completed', 'cancelled'])) {
            return response()->json([
                'message' => 'No se puede cancelar un entrenamiento completado o ya cancelado'
            ], 422);
        }

        $training->update(['status' => 'cancelled']);

        return new TrainingResource($training);
    }

    /**
     * Create attendance records for all active players in the category.
     */
    private function createAttendanceRecords(Training $training)
    {
        $players = $training->category->players()->active()->get();

        foreach ($players as $player) {
            Attendance::create([
                'school_id' => $training->school_id,
                'training_id' => $training->id,
                'player_id' => $player->id,
                'date' => $training->date,
                'status' => 'pending'
            ]);
        }
    }

    /**
     * Get school ID from authenticated user.
     */
    private function getSchoolId(Request $request)
    {
        // TODO: Implementar lógica para obtener school_id del usuario autenticado
        // Por ahora retornamos 1 como placeholder
        return $request->user()->school_id ?? 1;
    }
}