<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Category;
use App\Models\PlayerStatistic;
use App\Models\Training;
use App\Http\Resources\PlayerResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function getPlayerStatistics(Player $player, Request $request): JsonResponse
    {
        $this->authorize('view', $player);
        
        $period = $request->period ?? 'season'; // season, month, all
        $context = $request->context; // training, match, all
        
        $query = $player->statistics();
        
        // Filtrar por período
        switch ($period) {
            case 'month':
                $query->where('date', '>=', now()->subMonth());
                break;
            case 'season':
                $query->where('date', '>=', now()->startOfYear());
                break;
        }
        
        // Filtrar por contexto
        if ($context && $context !== 'all') {
            $query->byContext($context);
        }
        
        $stats = $query->get();
        
        return response()->json([
            'summary' => $this->calculatePlayerSummary($stats),
            'averages' => $this->calculatePlayerAverages($stats),
            'totals' => $this->calculatePlayerTotals($stats),
            'recent_form' => $this->getRecentForm($player, 5),
            'position_specific' => $this->getPositionSpecificStats($player, $stats)
        ]);
    }
    
    public function getCategoryStatistics(Category $category, Request $request): JsonResponse
    {
        $this->authorize('view', $category);
        
        $period = $request->period ?? 'month';
        $dateFrom = match($period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'season' => now()->startOfYear(),
            default => now()->subMonth()
        };
        
        $players = $category->players()->active()->with(['statistics' => function($q) use ($dateFrom) {
            $q->where('date', '>=', $dateFrom);
        }])->get();
        
        $categoryStats = [
            'top_scorers' => $this->getTopScorers($players, 10),
            'top_assisters' => $this->getTopAssisters($players, 10),
            'best_attendance' => $this->getBestAttendance($players),
            'discipline_stats' => $this->getDisciplineStats($players),
            'team_totals' => $this->getTeamTotals($players)
        ];
        
        return response()->json($categoryStats);
    }
    
    public function getSchoolStatistics(Request $request): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $period = $request->period ?? 'month';
        
        $dateFrom = match($period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'season' => now()->startOfYear(),
            default => now()->subMonth()
        };
        
        $stats = [
            'overview' => [
                'total_players' => Player::where('school_id', $schoolId)->active()->count(),
                'total_categories' => Category::where('school_id', $schoolId)->active()->count(),
                'total_trainings' => Training::where('school_id', $schoolId)
                    ->where('date', '>=', $dateFrom)->count(),
                'average_attendance' => $this->getSchoolAttendanceRate($schoolId, $dateFrom)
            ],
            'top_performers' => $this->getSchoolTopPerformers($schoolId, $dateFrom),
            'category_comparison' => $this->getCategoryComparison($schoolId, $dateFrom),
            'monthly_trends' => $this->getMonthlyTrends($schoolId)
        ];
        
        return response()->json($stats);
    }
    
    private function calculatePlayerSummary($stats): array
    {
        return [
            'games_played' => $stats->where('context', 'match')->count(),
            'trainings_attended' => $stats->where('context', 'training')->count(),
            'total_goals' => $stats->sum('goals_scored'),
            'total_assists' => $stats->sum('assists'),
            'total_minutes' => $stats->sum('minutes_played'),
            'yellow_cards' => $stats->sum('yellow_cards'),
            'red_cards' => $stats->sum('red_cards')
        ];
    }
    
    private function calculatePlayerAverages($stats): ?array
    {
        $gamesCount = $stats->where('context', 'match')->count();
        
        if ($gamesCount === 0) {
            return null;
        }
        
        return [
            'goals_per_game' => round($stats->sum('goals_scored') / $gamesCount, 2),
            'assists_per_game' => round($stats->sum('assists') / $gamesCount, 2),
            'minutes_per_game' => round($stats->sum('minutes_played') / $gamesCount, 1),
            'pass_accuracy' => round($stats->avg('pass_accuracy'), 1),
            'shot_accuracy' => round($stats->avg('shot_accuracy'), 1)
        ];
    }
    
    private function calculatePlayerTotals($stats): array
    {
        return [
            'total_shots' => $stats->sum('shots_on_target') + $stats->sum('shots_off_target'),
            'shots_on_target' => $stats->sum('shots_on_target'),
            'shots_off_target' => $stats->sum('shots_off_target'),
            'total_passes' => $stats->sum('passes_attempted'),
            'passes_completed' => $stats->sum('passes_completed'),
            'tackles_won' => $stats->sum('tackles_won'),
            'tackles_lost' => $stats->sum('tackles_lost'),
            'interceptions' => $stats->sum('interceptions'),
            'fouls_committed' => $stats->sum('fouls_committed'),
            'fouls_received' => $stats->sum('fouls_received')
        ];
    }
    
    private function getRecentForm(Player $player, int $games): array
    {
        $recentStats = $player->statistics()
            ->matches()
            ->orderBy('date', 'desc')
            ->limit($games)
            ->get();
            
        return $recentStats->map(function($stat) {
            return [
                'date' => $stat->date->format('Y-m-d'),
                'goals' => $stat->goals_scored,
                'assists' => $stat->assists,
                'minutes' => $stat->minutes_played,
                'rating' => $this->calculateMatchRating($stat)
            ];
        })->toArray();
    }
    
    private function getPositionSpecificStats(Player $player, $stats): array
    {
        $position = strtolower($player->position ?? 'field');
        
        $baseStats = [
            'goals' => $stats->sum('goals_scored'),
            'assists' => $stats->sum('assists'),
            'minutes' => $stats->sum('minutes_played')
        ];
        
        switch ($position) {
            case 'goalkeeper':
            case 'portero':
                return array_merge($baseStats, [
                    'saves' => $stats->sum('saves'),
                    'goals_conceded' => $stats->sum('goals_conceded'),
                    'clean_sheets' => $stats->sum('clean_sheets')
                ]);
                
            case 'defender':
            case 'defensa':
                return array_merge($baseStats, [
                    'tackles_won' => $stats->sum('tackles_won'),
                    'interceptions' => $stats->sum('interceptions'),
                    'aerial_duels_won' => $stats->sum('aerial_duels_won')
                ]);
                
            case 'midfielder':
            case 'centrocampista':
                return array_merge($baseStats, [
                    'passes_completed' => $stats->sum('passes_completed'),
                    'passes_attempted' => $stats->sum('passes_attempted'),
                    'pass_accuracy' => $stats->avg('pass_accuracy')
                ]);
                
            case 'forward':
            case 'delantero':
                return array_merge($baseStats, [
                    'shots_on_target' => $stats->sum('shots_on_target'),
                    'shots_off_target' => $stats->sum('shots_off_target'),
                    'dribbles_successful' => $stats->sum('dribbles_successful')
                ]);
                
            default:
                return $baseStats;
        }
    }
    
    private function getTopScorers($players, int $limit): array
    {
        return $players->map(function($player) {
            return [
                'player' => new PlayerResource($player),
                'goals' => $player->statistics->sum('goals_scored'),
                'games' => $player->statistics->where('context', 'match')->count()
            ];
        })->sortByDesc('goals')->take($limit)->values()->toArray();
    }
    
    private function getTopAssisters($players, int $limit): array
    {
        return $players->map(function($player) {
            return [
                'player' => new PlayerResource($player),
                'assists' => $player->statistics->sum('assists'),
                'games' => $player->statistics->where('context', 'match')->count()
            ];
        })->sortByDesc('assists')->take($limit)->values()->toArray();
    }
    
    private function getBestAttendance($players): array
    {
        return $players->map(function($player) {
            $totalTrainings = $player->statistics->where('context', 'training')->count();
            $categoryTrainings = $player->category->trainings()->count();
            $attendanceRate = $categoryTrainings > 0 ? ($totalTrainings / $categoryTrainings) * 100 : 0;
            
            return [
                'player' => new PlayerResource($player),
                'attendance_rate' => round($attendanceRate, 1),
                'trainings_attended' => $totalTrainings,
                'total_trainings' => $categoryTrainings
            ];
        })->sortByDesc('attendance_rate')->take(10)->values()->toArray();
    }
    
    private function getDisciplineStats($players): array
    {
        $totalYellow = $players->sum(function($player) {
            return $player->statistics->sum('yellow_cards');
        });
        
        $totalRed = $players->sum(function($player) {
            return $player->statistics->sum('red_cards');
        });
        
        return [
            'total_yellow_cards' => $totalYellow,
            'total_red_cards' => $totalRed,
            'most_disciplined' => $players->filter(function($player) {
                return $player->statistics->sum('yellow_cards') === 0 && 
                       $player->statistics->sum('red_cards') === 0;
            })->count()
        ];
    }
    
    private function getTeamTotals($players): array
    {
        return [
            'total_goals' => $players->sum(function($player) {
                return $player->statistics->sum('goals_scored');
            }),
            'total_assists' => $players->sum(function($player) {
                return $player->statistics->sum('assists');
            }),
            'total_minutes' => $players->sum(function($player) {
                return $player->statistics->sum('minutes_played');
            }),
            'games_played' => $players->max(function($player) {
                return $player->statistics->where('context', 'match')->count();
            })
        ];
    }
    
    private function getSchoolAttendanceRate(int $schoolId, $dateFrom): float
    {
        $totalTrainings = Training::where('school_id', $schoolId)
            ->where('date', '>=', $dateFrom)
            ->count();
            
        if ($totalTrainings === 0) {
            return 0;
        }
        
        $attendanceStats = PlayerStatistic::where('school_id', $schoolId)
            ->where('context', 'training')
            ->where('date', '>=', $dateFrom)
            ->count();
            
        $totalPlayers = Player::where('school_id', $schoolId)->active()->count();
        $expectedAttendances = $totalTrainings * $totalPlayers;
        
        return $expectedAttendances > 0 ? round(($attendanceStats / $expectedAttendances) * 100, 1) : 0;
    }
    
    private function getSchoolTopPerformers(int $schoolId, $dateFrom): array
    {
        $topScorers = PlayerStatistic::select('player_id', DB::raw('SUM(goals_scored) as total_goals'))
            ->where('school_id', $schoolId)
            ->where('date', '>=', $dateFrom)
            ->groupBy('player_id')
            ->orderBy('total_goals', 'desc')
            ->limit(5)
            ->with('player')
            ->get();
            
        return $topScorers->map(function($stat) {
            return [
                'player' => new PlayerResource($stat->player),
                'goals' => $stat->total_goals
            ];
        })->toArray();
    }
    
    private function getCategoryComparison(int $schoolId, $dateFrom): array
    {
        $categories = Category::where('school_id', $schoolId)->active()->get();
        
        return $categories->map(function($category) use ($dateFrom) {
            $stats = PlayerStatistic::whereHas('player', function($q) use ($category) {
                $q->where('category_id', $category->id);
            })->where('date', '>=', $dateFrom)->get();
            
            return [
                'category' => $category->name,
                'total_goals' => $stats->sum('goals_scored'),
                'total_assists' => $stats->sum('assists'),
                'average_attendance' => $this->calculateCategoryAttendance($category, $dateFrom)
            ];
        })->toArray();
    }
    
    private function getMonthlyTrends(int $schoolId): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $stats = PlayerStatistic::where('school_id', $schoolId)
                ->whereMonth('date', $date->month)
                ->whereYear('date', $date->year)
                ->get();
                
            $months[] = [
                'month' => $date->format('Y-m'),
                'goals' => $stats->sum('goals_scored'),
                'assists' => $stats->sum('assists'),
                'trainings' => $stats->where('context', 'training')->count()
            ];
        }
        
        return $months;
    }
    
    private function calculateCategoryAttendance(Category $category, $dateFrom): float
    {
        $totalTrainings = $category->trainings()->where('date', '>=', $dateFrom)->count();
        $totalPlayers = $category->players()->active()->count();
        
        if ($totalTrainings === 0 || $totalPlayers === 0) {
            return 0;
        }
        
        $attendanceCount = PlayerStatistic::whereHas('player', function($q) use ($category) {
            $q->where('category_id', $category->id);
        })->where('context', 'training')
          ->where('date', '>=', $dateFrom)
          ->count();
          
        $expectedAttendances = $totalTrainings * $totalPlayers;
        
        return round(($attendanceCount / $expectedAttendances) * 100, 1);
    }
    
    private function calculateMatchRating(PlayerStatistic $stat): float
    {
        // Simple rating calculation based on performance metrics
        $rating = 5.0; // Base rating
        
        // Goals and assists boost
        $rating += ($stat->goals_scored * 1.5);
        $rating += ($stat->assists * 1.0);
        
        // Pass accuracy bonus
        if ($stat->passes_attempted > 0) {
            $passAccuracy = ($stat->passes_completed / $stat->passes_attempted) * 100;
            $rating += ($passAccuracy - 70) * 0.02; // Bonus/penalty based on 70% baseline
        }
        
        // Cards penalty
        $rating -= ($stat->yellow_cards * 0.3);
        $rating -= ($stat->red_cards * 1.0);
        
        // Ensure rating is between 1 and 10
        return max(1.0, min(10.0, round($rating, 1)));
    }
}