<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\PlayerEvaluation;
use App\Models\Training;
use App\Models\PlayerStatistic;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use InvalidArgumentException;

class SportsReportsController extends Controller
{
    /**
     * Generate attendance report
     */
    public function attendanceReport(Request $request): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $categoryId = $request->category_id;
        $dateFrom = $request->date_from ?? now()->subMonth()->toDateString();
        $dateTo = $request->date_to ?? now()->toDateString();
        
        $query = Attendance::with(['player', 'training.category'])
            ->where('school_id', $schoolId)
            ->whereBetween('date', [$dateFrom, $dateTo]);
            
        if ($categoryId) {
            $query->whereHas('training', fn($q) => $q->where('category_id', $categoryId));
        }
        
        $attendances = $query->get();
        
        $report = [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'summary' => [
                'total_sessions' => $attendances->groupBy('training_id')->count(),
                'total_attendances' => $attendances->count(),
                'present_count' => $attendances->where('status', 'present')->count(),
                'absent_count' => $attendances->where('status', 'absent')->count(),
                'late_count' => $attendances->where('status', 'late')->count(),
                'overall_rate' => $this->calculateAttendanceRate($attendances)
            ],
            'by_category' => $this->groupAttendanceByCategory($attendances),
            'by_player' => $this->groupAttendanceByPlayer($attendances),
            'trends' => $this->getAttendanceTrends($attendances)
        ];
        
        return response()->json($report);
    }
    
    /**
     * Generate performance report
     */
    public function performanceReport(Request $request): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $categoryId = $request->category_id;
        $period = $request->period ?? 'month';
        
        $dateFrom = match($period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'season' => now()->startOfYear(),
            default => now()->subMonth()
        };
        
        $query = PlayerEvaluation::with(['player', 'player.category'])
            ->where('school_id', $schoolId)
            ->where('evaluation_date', '>=', $dateFrom);
            
        if ($categoryId) {
            $query->whereHas('player', fn($q) => $q->where('category_id', $categoryId));
        }
        
        $evaluations = $query->get();
        
        $report = [
            'period' => ['from' => $dateFrom->toDateString(), 'to' => now()->toDateString()],
            'summary' => [
                'total_evaluations' => $evaluations->count(),
                'players_evaluated' => $evaluations->groupBy('player_id')->count(),
                'average_rating' => round($evaluations->avg('overall_rating'), 1),
                'top_performers' => $this->getTopPerformers($evaluations, 10)
            ],
            'by_category' => $this->groupPerformanceByCategory($evaluations),
            'skill_analysis' => $this->analyzeSkillDistribution($evaluations),
            'improvement_tracking' => $this->trackImprovements($evaluations)
        ];
        
        return response()->json($report);
    }
    
    /**
     * Generate training report
     */
    public function trainingReport(Request $request): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $categoryId = $request->category_id;
        $dateFrom = $request->date_from ?? now()->subMonth()->toDateString();
        $dateTo = $request->date_to ?? now()->toDateString();
        
        $query = Training::with(['category', 'coach', 'attendances'])
            ->where('school_id', $schoolId)
            ->whereBetween('date', [$dateFrom, $dateTo]);
            
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        $trainings = $query->get();
        
        $report = [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'summary' => [
                'total_trainings' => $trainings->count(),
                'completed_trainings' => $trainings->where('status', 'completed')->count(),
                'cancelled_trainings' => $trainings->where('status', 'cancelled')->count(),
                'average_attendance' => $this->calculateTrainingAttendance($trainings)
            ],
            'by_category' => $this->groupTrainingsByCategory($trainings),
            'by_coach' => $this->groupTrainingsByCoach($trainings),
            'weekly_distribution' => $this->getWeeklyDistribution($trainings),
            'objectives_analysis' => $this->analyzeObjectives($trainings)
        ];
        
        return response()->json($report);
    }
    
    /**
     * Export report in different formats
     */
    public function exportReport(Request $request)
    {
        $reportType = $request->report_type; // attendance, performance, training
        $format = $request->format ?? 'pdf'; // pdf, excel
        
        $reportData = match($reportType) {
            'attendance' => $this->attendanceReport($request)->getData(),
            'performance' => $this->performanceReport($request)->getData(),
            'training' => $this->trainingReport($request)->getData(),
            default => throw new InvalidArgumentException('Invalid report type')
        };
        
        return match($format) {
            'pdf' => $this->generatePdfReport($reportType, $reportData),
            'excel' => $this->generateExcelReport($reportType, $reportData),
            default => throw new InvalidArgumentException('Invalid format')
        };
    }
    
    /**
     * Calculate attendance rate
     */
    private function calculateAttendanceRate($attendances): float
    {
        if ($attendances->isEmpty()) {
            return 0;
        }
        
        $presentCount = $attendances->where('status', 'present')->count();
        $totalCount = $attendances->count();
        
        return round(($presentCount / $totalCount) * 100, 2);
    }
    
    /**
     * Group attendance by category
     */
    private function groupAttendanceByCategory($attendances): array
    {
        return $attendances->groupBy('training.category.name')
            ->map(function ($categoryAttendances, $categoryName) {
                return [
                    'category' => $categoryName,
                    'total_sessions' => $categoryAttendances->groupBy('training_id')->count(),
                    'total_attendances' => $categoryAttendances->count(),
                    'present_count' => $categoryAttendances->where('status', 'present')->count(),
                    'absent_count' => $categoryAttendances->where('status', 'absent')->count(),
                    'late_count' => $categoryAttendances->where('status', 'late')->count(),
                    'attendance_rate' => $this->calculateAttendanceRate($categoryAttendances)
                ];
            })->values()->toArray();
    }
    
    /**
     * Group attendance by player
     */
    private function groupAttendanceByPlayer($attendances): array
    {
        return $attendances->groupBy('player_id')
            ->map(function ($playerAttendances) {
                $player = $playerAttendances->first()->player;
                return [
                    'player_id' => $player->id,
                    'player_name' => $player->full_name,
                    'category' => $player->category->name ?? 'N/A',
                    'total_sessions' => $playerAttendances->count(),
                    'present_count' => $playerAttendances->where('status', 'present')->count(),
                    'absent_count' => $playerAttendances->where('status', 'absent')->count(),
                    'late_count' => $playerAttendances->where('status', 'late')->count(),
                    'attendance_rate' => $this->calculateAttendanceRate($playerAttendances)
                ];
            })->values()->toArray();
    }
    
    /**
     * Get attendance trends
     */
    private function getAttendanceTrends($attendances): array
    {
        return $attendances->groupBy(function ($attendance) {
            return Carbon::parse($attendance->date)->format('Y-m-d');
        })->map(function ($dailyAttendances, $date) {
            return [
                'date' => $date,
                'total_sessions' => $dailyAttendances->groupBy('training_id')->count(),
                'present_count' => $dailyAttendances->where('status', 'present')->count(),
                'absent_count' => $dailyAttendances->where('status', 'absent')->count(),
                'attendance_rate' => $this->calculateAttendanceRate($dailyAttendances)
            ];
        })->values()->toArray();
    }
    
    /**
     * Get top performers
     */
    private function getTopPerformers($evaluations, int $limit = 10): array
    {
        return $evaluations->groupBy('player_id')
            ->map(function ($playerEvaluations) {
                $player = $playerEvaluations->first()->player;
                return [
                    'player_id' => $player->id,
                    'player_name' => $player->full_name,
                    'category' => $player->category->name ?? 'N/A',
                    'average_rating' => round($playerEvaluations->avg('overall_rating'), 2),
                    'evaluations_count' => $playerEvaluations->count()
                ];
            })
            ->sortByDesc('average_rating')
            ->take($limit)
            ->values()
            ->toArray();
    }
    
    /**
     * Group performance by category
     */
    private function groupPerformanceByCategory($evaluations): array
    {
        return $evaluations->groupBy('player.category.name')
            ->map(function ($categoryEvaluations, $categoryName) {
                return [
                    'category' => $categoryName,
                    'total_evaluations' => $categoryEvaluations->count(),
                    'players_evaluated' => $categoryEvaluations->groupBy('player_id')->count(),
                    'average_rating' => round($categoryEvaluations->avg('overall_rating'), 2),
                    'best_performer' => $this->getTopPerformers($categoryEvaluations, 1)[0] ?? null
                ];
            })->values()->toArray();
    }
    
    /**
     * Analyze skill distribution
     */
    private function analyzeSkillDistribution($evaluations): array
    {
        $skills = ['technical_skills', 'tactical_skills', 'physical_condition', 'mental_attitude'];
        $analysis = [];
        
        foreach ($skills as $skill) {
            $analysis[$skill] = [
                'average' => round($evaluations->avg($skill), 2),
                'max' => $evaluations->max($skill),
                'min' => $evaluations->min($skill),
                'distribution' => $this->getSkillDistribution($evaluations, $skill)
            ];
        }
        
        return $analysis;
    }
    
    /**
     * Get skill distribution
     */
    private function getSkillDistribution($evaluations, string $skill): array
    {
        $ranges = [
            'excellent' => [9, 10],
            'good' => [7, 8],
            'average' => [5, 6],
            'below_average' => [3, 4],
            'poor' => [1, 2]
        ];
        
        $distribution = [];
        foreach ($ranges as $level => $range) {
            $count = $evaluations->whereBetween($skill, $range)->count();
            $distribution[$level] = [
                'count' => $count,
                'percentage' => $evaluations->count() > 0 ? round(($count / $evaluations->count()) * 100, 1) : 0
            ];
        }
        
        return $distribution;
    }
    
    /**
     * Track improvements
     */
    private function trackImprovements($evaluations): array
    {
        return $evaluations->groupBy('player_id')
            ->map(function ($playerEvaluations) {
                $player = $playerEvaluations->first()->player;
                $sorted = $playerEvaluations->sortBy('evaluation_date');
                $first = $sorted->first();
                $last = $sorted->last();
                
                if ($sorted->count() < 2) {
                    return null;
                }
                
                return [
                    'player_id' => $player->id,
                    'player_name' => $player->full_name,
                    'category' => $player->category->name ?? 'N/A',
                    'first_evaluation' => $first->overall_rating,
                    'last_evaluation' => $last->overall_rating,
                    'improvement' => round($last->overall_rating - $first->overall_rating, 2),
                    'improvement_percentage' => $first->overall_rating > 0 ? 
                        round((($last->overall_rating - $first->overall_rating) / $first->overall_rating) * 100, 1) : 0
                ];
            })
            ->filter()
            ->sortByDesc('improvement')
            ->values()
            ->toArray();
    }
    
    /**
     * Calculate training attendance
     */
    private function calculateTrainingAttendance($trainings): float
    {
        if ($trainings->isEmpty()) {
            return 0;
        }
        
        $totalAttendances = $trainings->sum(function ($training) {
            return $training->attendances->count();
        });
        
        $presentAttendances = $trainings->sum(function ($training) {
            return $training->attendances->where('status', 'present')->count();
        });
        
        return $totalAttendances > 0 ? round(($presentAttendances / $totalAttendances) * 100, 2) : 0;
    }
    
    /**
     * Group trainings by category
     */
    private function groupTrainingsByCategory($trainings): array
    {
        return $trainings->groupBy('category.name')
            ->map(function ($categoryTrainings, $categoryName) {
                return [
                    'category' => $categoryName,
                    'total_trainings' => $categoryTrainings->count(),
                    'completed_trainings' => $categoryTrainings->where('status', 'completed')->count(),
                    'cancelled_trainings' => $categoryTrainings->where('status', 'cancelled')->count(),
                    'average_attendance' => $this->calculateTrainingAttendance($categoryTrainings)
                ];
            })->values()->toArray();
    }
    
    /**
     * Group trainings by coach
     */
    private function groupTrainingsByCoach($trainings): array
    {
        return $trainings->groupBy('coach_id')
            ->map(function ($coachTrainings) {
                $coach = $coachTrainings->first()->coach;
                return [
                    'coach_id' => $coach->id,
                    'coach_name' => $coach->full_name,
                    'total_trainings' => $coachTrainings->count(),
                    'completed_trainings' => $coachTrainings->where('status', 'completed')->count(),
                    'cancelled_trainings' => $coachTrainings->where('status', 'cancelled')->count(),
                    'average_attendance' => $this->calculateTrainingAttendance($coachTrainings)
                ];
            })->values()->toArray();
    }
    
    /**
     * Get weekly distribution
     */
    private function getWeeklyDistribution($trainings): array
    {
        return $trainings->groupBy(function ($training) {
            return Carbon::parse($training->date)->format('W-Y'); // Week-Year
        })->map(function ($weekTrainings, $week) {
            return [
                'week' => $week,
                'total_trainings' => $weekTrainings->count(),
                'completed_trainings' => $weekTrainings->where('status', 'completed')->count(),
                'cancelled_trainings' => $weekTrainings->where('status', 'cancelled')->count(),
                'average_attendance' => $this->calculateTrainingAttendance($weekTrainings)
            ];
        })->values()->toArray();
    }
    
    /**
     * Analyze objectives
     */
    private function analyzeObjectives($trainings): array
    {
        $objectives = $trainings->pluck('objectives')->filter()->flatten();
        
        if ($objectives->isEmpty()) {
            return [];
        }
        
        $totalObjectives = $objectives->count();
        
        return $objectives->groupBy(function ($objective) {
            return $objective;
        })->map(function ($objectiveGroup, $objective) use ($totalObjectives) {
            return [
                'objective' => $objective,
                'frequency' => $objectiveGroup->count(),
                'percentage' => round(($objectiveGroup->count() / $totalObjectives) * 100, 1)
            ];
        })->sortByDesc('frequency')->values()->toArray();
    }
    
    /**
     * Generate PDF report
     */
    private function generatePdfReport(string $reportType, $reportData)
    {
        // TODO: Implement PDF generation using a library like DomPDF or TCPDF
        return response()->json([
            'message' => 'PDF generation not implemented yet',
            'report_type' => $reportType,
            'data' => $reportData
        ]);
    }
    
    /**
     * Generate Excel report
     */
    private function generateExcelReport(string $reportType, $reportData)
    {
        // TODO: Implement Excel generation using a library like PhpSpreadsheet
        return response()->json([
            'message' => 'Excel generation not implemented yet',
            'report_type' => $reportType,
            'data' => $reportData
        ]);
    }
}