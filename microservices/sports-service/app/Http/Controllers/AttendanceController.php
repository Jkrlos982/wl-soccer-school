<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAttendanceRequest;
use App\Http\Requests\BulkUpdateAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\PlayerResource;
use App\Models\Attendance;
use App\Models\Training;
use App\Models\Player;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $attendances = Attendance::with(['training.category', 'player'])
            ->where('school_id', $this->getSchoolId($request))
            ->when($request->training_id, fn($q, $training) => $q->byTraining($training))
            ->when($request->player_id, fn($q, $player) => $q->byPlayer($player))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->date_from, fn($q, $date) => $q->where('date', '>=', $date))
            ->when($request->date_to, fn($q, $date) => $q->where('date', '<=', $date))
            ->orderBy('date', 'desc')
            ->paginate(50);
            
        return AttendanceResource::collection($attendances);
    }
    
    public function getByTraining(Training $training)
    {
        $this->authorize('view', $training);
        
        $attendances = $training->attendances()
            ->with(['player'])
            ->orderBy('player_id')
            ->get();
            
        return AttendanceResource::collection($attendances);
    }
    
    public function updateAttendance(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        $this->authorize('update', $attendance);
        
        $data = $request->validated();
        $data['recorded_by'] = $request->user()->id;
        $data['recorded_at'] = now();
        
        // Si se marca como presente y se proporciona hora de llegada
        if ($data['status'] === 'present' && $request->arrival_time) {
            $data['arrival_time'] = $request->arrival_time;
            
            // Determinar si llegó tarde
            $trainingStart = $attendance->training->start_time;
            $arrivalTime = Carbon::parse($request->arrival_time);
            
            if ($arrivalTime->gt($trainingStart)) {
                $data['status'] = 'late';
            }
        }
        
        $attendance->update($data);
        
        return new AttendanceResource($attendance->load(['training', 'player']));
    }
    
    public function bulkUpdateAttendance(BulkUpdateAttendanceRequest $request)
    {
        DB::beginTransaction();
        try {
            $attendances = [];
            
            foreach ($request->attendances as $attendanceData) {
                $attendance = Attendance::findOrFail($attendanceData['id']);
                $this->authorize('update', $attendance);
                
                $updateData = [
                    'status' => $attendanceData['status'],
                    'notes' => $attendanceData['notes'] ?? null,
                    'recorded_by' => $request->user()->id,
                    'recorded_at' => now()
                ];
                
                if (isset($attendanceData['arrival_time'])) {
                    $updateData['arrival_time'] = $attendanceData['arrival_time'];
                }
                
                $attendance->update($updateData);
                $attendances[] = $attendance;
            }
            
            DB::commit();
            
            return AttendanceResource::collection(collect($attendances));
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
    
    public function getPlayerAttendanceStats(Player $player, Request $request)
    {
        $this->authorize('view', $player);
        
        $period = $request->period ?? 30; // días
        $dateFrom = now()->subDays($period);
        
        $stats = [
            'total_trainings' => $player->attendances()
                ->where('date', '>=', $dateFrom)
                ->count(),
            'present' => $player->attendances()
                ->where('date', '>=', $dateFrom)
                ->present()
                ->count(),
            'absent' => $player->attendances()
                ->where('date', '>=', $dateFrom)
                ->absent()
                ->count(),
            'late' => $player->attendances()
                ->where('date', '>=', $dateFrom)
                ->late()
                ->count(),
            'excused' => $player->attendances()
                ->where('date', '>=', $dateFrom)
                ->where('status', 'excused')
                ->count()
        ];
        
        $stats['attendance_rate'] = $stats['total_trainings'] > 0 
            ? round((($stats['present'] + $stats['late']) / $stats['total_trainings']) * 100, 2)
            : 0;
            
        return response()->json($stats);
    }
    
    public function getCategoryAttendanceReport(Category $category, Request $request)
    {
        $this->authorize('view', $category);
        
        $dateFrom = $request->date_from ?? now()->subMonth()->toDateString();
        $dateTo = $request->date_to ?? now()->toDateString();
        
        $report = $category->players()->active()->get()->map(function($player) use ($dateFrom, $dateTo) {
            $attendances = $player->attendances()
                ->whereBetween('date', [$dateFrom, $dateTo])
                ->get();
                
            return [
                'player' => new PlayerResource($player),
                'total_trainings' => $attendances->count(),
                'present' => $attendances->where('status', 'present')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'late' => $attendances->where('status', 'late')->count(),
                'excused' => $attendances->where('status', 'excused')->count(),
                'attendance_rate' => $attendances->count() > 0 
                    ? round((($attendances->where('status', 'present')->count() + $attendances->where('status', 'late')->count()) / $attendances->count()) * 100, 2)
                    : 0
            ];
        });
        
        return response()->json($report);
    }
    
    /**
     * Obtener el school_id del usuario autenticado
     */
    private function getSchoolId(Request $request)
    {
        // TODO: Implementar lógica para obtener school_id del usuario
        // Por ahora retornamos 1 como placeholder
        return 1;
    }
}