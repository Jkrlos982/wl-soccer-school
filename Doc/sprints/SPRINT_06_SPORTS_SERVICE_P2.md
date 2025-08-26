# Sprint 6: Sports Service - Parte 2 (Gestión Avanzada Deportiva)

**Duración:** 2 semanas  
**Fase:** 2 - Módulos Financiero y Deportivo Básico  
**Objetivo:** Implementar funcionalidades avanzadas del módulo deportivo con evaluaciones, estadísticas y reportes

## Resumen del Sprint

Este sprint completa el módulo deportivo implementando evaluaciones de jugadores, estadísticas avanzadas, reportes deportivos y funcionalidades de gestión de equipos.

## Objetivos Específicos

- ✅ Implementar sistema de evaluaciones de jugadores
- ✅ Crear estadísticas avanzadas deportivas
- ✅ Desarrollar reportes deportivos completos
- ✅ Implementar gestión de equipos y formaciones
- ✅ Crear dashboard avanzado deportivo

## Tareas Detalladas

### 1. Sistema de Evaluaciones de Jugadores

**Responsable:** Backend Developer Senior  
**Estimación:** 3 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear modelo PlayerEvaluation:**
   ```php
   // Migration: create_player_evaluations_table
   Schema::create('player_evaluations', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('school_id');
       $table->unsignedBigInteger('player_id');
       $table->unsignedBigInteger('evaluator_id'); // Coach/Staff
       $table->unsignedBigInteger('training_id')->nullable();
       $table->date('evaluation_date');
       $table->enum('evaluation_type', ['training', 'match', 'monthly', 'semester']);
       
       // Aspectos técnicos (1-10)
       $table->integer('technical_skills')->nullable();
       $table->integer('ball_control')->nullable();
       $table->integer('passing')->nullable();
       $table->integer('shooting')->nullable();
       $table->integer('dribbling')->nullable();
       
       // Aspectos físicos (1-10)
       $table->integer('speed')->nullable();
       $table->integer('endurance')->nullable();
       $table->integer('strength')->nullable();
       $table->integer('agility')->nullable();
       
       // Aspectos tácticos (1-10)
       $table->integer('positioning')->nullable();
       $table->integer('decision_making')->nullable();
       $table->integer('teamwork')->nullable();
       $table->integer('game_understanding')->nullable();
       
       // Aspectos mentales/actitudinales (1-10)
       $table->integer('attitude')->nullable();
       $table->integer('discipline')->nullable();
       $table->integer('leadership')->nullable();
       $table->integer('commitment')->nullable();
       
       $table->decimal('overall_rating', 3, 1)->nullable(); // Promedio general
       $table->text('strengths')->nullable();
       $table->text('areas_for_improvement')->nullable();
       $table->text('goals_next_period')->nullable();
       $table->text('coach_comments')->nullable();
       $table->json('custom_metrics')->nullable(); // Métricas personalizables
       
       $table->timestamps();
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('player_id')->references('id')->on('players');
       $table->foreign('evaluator_id')->references('id')->on('users');
       $table->foreign('training_id')->references('id')->on('trainings');
       $table->index(['school_id', 'evaluation_date']);
       $table->index(['player_id', 'evaluation_date']);
   });
   ```

2. **Implementar modelo PlayerEvaluation:**
   ```php
   class PlayerEvaluation extends Model
   {
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
       public function school() {
           return $this->belongsTo(School::class);
       }
       
       public function player() {
           return $this->belongsTo(Player::class);
       }
       
       public function evaluator() {
           return $this->belongsTo(User::class, 'evaluator_id');
       }
       
       public function training() {
           return $this->belongsTo(Training::class);
       }
       
       // Métodos auxiliares
       public function calculateOverallRating() {
           $technicalAvg = $this->getTechnicalAverage();
           $physicalAvg = $this->getPhysicalAverage();
           $tacticalAvg = $this->getTacticalAverage();
           $mentalAvg = $this->getMentalAverage();
           
           $averages = array_filter([$technicalAvg, $physicalAvg, $tacticalAvg, $mentalAvg]);
           
           return count($averages) > 0 ? round(array_sum($averages) / count($averages), 1) : null;
       }
       
       public function getTechnicalAverage() {
           $skills = array_filter([
               $this->technical_skills, $this->ball_control, 
               $this->passing, $this->shooting, $this->dribbling
           ]);
           return count($skills) > 0 ? array_sum($skills) / count($skills) : null;
       }
       
       public function getPhysicalAverage() {
           $skills = array_filter([
               $this->speed, $this->endurance, $this->strength, $this->agility
           ]);
           return count($skills) > 0 ? array_sum($skills) / count($skills) : null;
       }
       
       public function getTacticalAverage() {
           $skills = array_filter([
               $this->positioning, $this->decision_making, 
               $this->teamwork, $this->game_understanding
           ]);
           return count($skills) > 0 ? array_sum($skills) / count($skills) : null;
       }
       
       public function getMentalAverage() {
           $skills = array_filter([
               $this->attitude, $this->discipline, $this->leadership, $this->commitment
           ]);
           return count($skills) > 0 ? array_sum($skills) / count($skills) : null;
       }
       
       // Scopes
       public function scopeByPlayer($query, $playerId) {
           return $query->where('player_id', $playerId);
       }
       
       public function scopeByType($query, $type) {
           return $query->where('evaluation_type', $type);
       }
       
       public function scopeRecent($query, $months = 3) {
           return $query->where('evaluation_date', '>=', now()->subMonths($months));
       }
   }
   ```

3. **Crear PlayerEvaluationController:**
   ```php
   class PlayerEvaluationController extends Controller
   {
       public function index(Request $request) {
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
       
       public function store(StorePlayerEvaluationRequest $request) {
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
       
       public function show(PlayerEvaluation $evaluation) {
           $this->authorize('view', $evaluation);
           
           return new PlayerEvaluationResource($evaluation->load([
               'player', 'evaluator', 'training'
           ]));
       }
       
       public function update(UpdatePlayerEvaluationRequest $request, PlayerEvaluation $evaluation) {
           $this->authorize('update', $evaluation);
           
           $evaluation->update($request->validated());
           
           // Recalcular rating general
           $overallRating = $evaluation->calculateOverallRating();
           if ($overallRating) {
               $evaluation->update(['overall_rating' => $overallRating]);
           }
           
           return new PlayerEvaluationResource($evaluation->load(['player', 'evaluator']));
       }
       
       public function destroy(PlayerEvaluation $evaluation) {
           $this->authorize('delete', $evaluation);
           
           $evaluation->delete();
           
           return response()->json(['message' => 'Evaluación eliminada exitosamente']);
       }
       
       public function getPlayerProgress(Player $player, Request $request) {
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
       
       private function calculateProgress($evaluations, $method) {
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
   ```

#### Criterios de Aceptación:
- [ ] Sistema de evaluaciones implementado
- [ ] Cálculo automático de promedios funcionando
- [ ] Seguimiento de progreso operativo
- [ ] Evaluaciones por tipo funcionando

---

### 2. Sistema de Estadísticas Avanzadas

**Responsable:** Backend Developer  
**Estimación:** 3 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear modelo PlayerStatistic:**
   ```php
   // Migration: create_player_statistics_table
   Schema::create('player_statistics', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('school_id');
       $table->unsignedBigInteger('player_id');
       $table->unsignedBigInteger('training_id')->nullable();
       $table->unsignedBigInteger('match_id')->nullable();
       $table->date('date');
       $table->enum('context', ['training', 'match', 'friendly']);
       
       // Estadísticas de entrenamiento
       $table->integer('minutes_played')->default(0);
       $table->integer('goals_scored')->default(0);
       $table->integer('assists')->default(0);
       $table->integer('shots_on_target')->default(0);
       $table->integer('shots_off_target')->default(0);
       $table->integer('passes_completed')->default(0);
       $table->integer('passes_attempted')->default(0);
       $table->integer('tackles_won')->default(0);
       $table->integer('tackles_lost')->default(0);
       $table->integer('interceptions')->default(0);
       $table->integer('fouls_committed')->default(0);
       $table->integer('fouls_received')->default(0);
       $table->integer('yellow_cards')->default(0);
       $table->integer('red_cards')->default(0);
       
       // Estadísticas específicas por posición
       $table->integer('saves')->default(0); // Portero
       $table->integer('goals_conceded')->default(0); // Portero
       $table->integer('clean_sheets')->default(0); // Portero
       $table->integer('crosses_completed')->default(0); // Laterales/Extremos
       $table->integer('dribbles_successful')->default(0); // Atacantes/Extremos
       $table->integer('aerial_duels_won')->default(0); // Defensas/Delanteros
       
       $table->text('notes')->nullable();
       $table->unsignedBigInteger('recorded_by');
       $table->timestamps();
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('player_id')->references('id')->on('players');
       $table->foreign('training_id')->references('id')->on('trainings');
       $table->foreign('recorded_by')->references('id')->on('users');
       $table->index(['school_id', 'date']);
       $table->index(['player_id', 'date']);
       $table->index(['player_id', 'context']);
   });
   ```

2. **Implementar modelo PlayerStatistic:**
   ```php
   class PlayerStatistic extends Model
   {
       protected $fillable = [
           'school_id', 'player_id', 'training_id', 'match_id', 'date', 'context',
           'minutes_played', 'goals_scored', 'assists', 'shots_on_target', 'shots_off_target',
           'passes_completed', 'passes_attempted', 'tackles_won', 'tackles_lost',
           'interceptions', 'fouls_committed', 'fouls_received', 'yellow_cards', 'red_cards',
           'saves', 'goals_conceded', 'clean_sheets', 'crosses_completed',
           'dribbles_successful', 'aerial_duels_won', 'notes', 'recorded_by'
       ];
       
       protected $casts = [
           'date' => 'date'
       ];
       
       // Relaciones
       public function school() {
           return $this->belongsTo(School::class);
       }
       
       public function player() {
           return $this->belongsTo(Player::class);
       }
       
       public function training() {
           return $this->belongsTo(Training::class);
       }
       
       public function recordedBy() {
           return $this->belongsTo(User::class, 'recorded_by');
       }
       
       // Métodos auxiliares
       public function getPassAccuracyAttribute() {
           return $this->passes_attempted > 0 
               ? round(($this->passes_completed / $this->passes_attempted) * 100, 1)
               : 0;
       }
       
       public function getTackleSuccessRateAttribute() {
           $totalTackles = $this->tackles_won + $this->tackles_lost;
           return $totalTackles > 0 
               ? round(($this->tackles_won / $totalTackles) * 100, 1)
               : 0;
       }
       
       public function getShotAccuracyAttribute() {
           $totalShots = $this->shots_on_target + $this->shots_off_target;
           return $totalShots > 0 
               ? round(($this->shots_on_target / $totalShots) * 100, 1)
               : 0;
       }
       
       // Scopes
       public function scopeByPlayer($query, $playerId) {
           return $query->where('player_id', $playerId);
       }
       
       public function scopeByContext($query, $context) {
           return $query->where('context', $context);
       }
       
       public function scopeInPeriod($query, $dateFrom, $dateTo) {
           return $query->whereBetween('date', [$dateFrom, $dateTo]);
       }
   }
   ```

3. **Crear StatisticsController:**
   ```php
   class StatisticsController extends Controller
   {
       public function getPlayerStats(Player $player, Request $request) {
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
       
       public function getCategoryStats(Category $category, Request $request) {
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
       
       public function getSchoolStats(Request $request) {
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
       
       private function calculatePlayerSummary($stats) {
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
       
       private function calculatePlayerAverages($stats) {
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
       
       private function getTopScorers($players, $limit) {
           return $players->map(function($player) {
               return [
                   'player' => new PlayerResource($player),
                   'goals' => $player->statistics->sum('goals_scored'),
                   'games' => $player->statistics->where('context', 'match')->count()
               ];
           })->sortByDesc('goals')->take($limit)->values();
       }
   }
   ```

#### Criterios de Aceptación:
- [ ] Estadísticas de jugadores implementadas
- [ ] Cálculos de promedios funcionando
- [ ] Comparativas por categoría operativas
- [ ] Reportes estadísticos generándose correctamente

---

### 3. Sistema de Reportes Deportivos

**Responsable:** Backend Developer  
**Estimación:** 2 días  
**Prioridad:** Media

#### Subtareas:

1. **Crear ReportsController:**
   ```php
   class SportsReportsController extends Controller
   {
       public function attendanceReport(Request $request) {
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
       
       public function performanceReport(Request $request) {
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
       
       public function trainingReport(Request $request) {
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
       
       public function exportReport(Request $request) {
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
   }
   ```

#### Criterios de Aceptación:
- [ ] Reportes de asistencia generándose
- [ ] Reportes de rendimiento funcionando
- [ ] Reportes de entrenamientos operativos
- [ ] Exportación a PDF/Excel implementada

---

### 4. Frontend - Módulo Deportivo Avanzado

**Responsable:** Frontend Developer  
**Estimación:** 4 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear componentes de evaluaciones:**
   ```typescript
   // EvaluationForm - Formulario de evaluación
   // EvaluationsList - Lista de evaluaciones
   // PlayerProgress - Progreso del jugador
   // EvaluationChart - Gráficos de evaluación
   // SkillRadarChart - Gráfico radar de habilidades
   ```

2. **Crear componentes de estadísticas:**
   ```typescript
   // StatsDashboard - Dashboard de estadísticas
   // PlayerStatsCard - Tarjeta de estadísticas
   // CategoryComparison - Comparación de categorías
   // PerformanceChart - Gráfico de rendimiento
   // TopPerformers - Mejores jugadores
   ```

3. **Crear componentes de reportes:**
   ```typescript
   // ReportsGenerator - Generador de reportes
   // AttendanceReport - Reporte de asistencia
   // PerformanceReport - Reporte de rendimiento
   // TrainingReport - Reporte de entrenamientos
   // ReportFilters - Filtros de reportes
   ```

4. **Implementar dashboard avanzado:**
   ```typescript
   interface AdvancedSportsDashboard {
     // Métricas clave
     keyMetrics: {
       totalPlayers: number;
       averageAttendance: number;
       topPerformer: Player;
       upcomingTrainings: Training[];
     };
     
     // Gráficos
     charts: {
       attendanceTrend: ChartData;
       performanceDistribution: ChartData;
       categoryComparison: ChartData;
       monthlyProgress: ChartData;
     };
     
     // Alertas
     alerts: {
       lowAttendance: Player[];
       missedEvaluations: Player[];
       upcomingDeadlines: any[];
     };
   }
   ```

#### Criterios de Aceptación:
- [ ] Componentes de evaluaciones funcionando
- [ ] Dashboard de estadísticas operativo
- [ ] Generador de reportes implementado
- [ ] Gráficos y visualizaciones funcionando

---

## API Endpoints Implementados

### Player Evaluations
```
GET    /api/v1/sports/evaluations
POST   /api/v1/sports/evaluations
GET    /api/v1/sports/evaluations/{id}
PUT    /api/v1/sports/evaluations/{id}
DELETE /api/v1/sports/evaluations/{id}
GET    /api/v1/sports/players/{id}/progress
```

### Statistics
```
GET    /api/v1/sports/statistics/player/{id}
GET    /api/v1/sports/statistics/category/{id}
GET    /api/v1/sports/statistics/school
POST   /api/v1/sports/statistics
```

### Reports
```
GET    /api/v1/sports/reports/attendance
GET    /api/v1/sports/reports/performance
GET    /api/v1/sports/reports/training
POST   /api/v1/sports/reports/export
```

## Definición de Terminado (DoD)

### Criterios Técnicos:
- [ ] Sistema de evaluaciones implementado
- [ ] Estadísticas avanzadas funcionando
- [ ] Reportes deportivos generándose
- [ ] Frontend avanzado operativo
- [ ] Exportación de reportes funcionando

### Criterios de Calidad:
- [ ] Tests unitarios > 85% cobertura
- [ ] Tests de integración pasando
- [ ] Performance validada (< 1s reportes)
- [ ] UX validada con entrenadores
- [ ] Documentación API actualizada

### Criterios de Negocio:
- [ ] Evaluaciones reflejan progreso real
- [ ] Estadísticas son precisas y útiles
- [ ] Reportes proporcionan insights valiosos
- [ ] Dashboard facilita toma de decisiones

## Riesgos Identificados

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| Complejidad cálculos estadísticos | Media | Alto | Validación matemática, tests exhaustivos |
| Performance reportes grandes | Alta | Medio | Optimización consultas, cache, paginación |
| UX compleja evaluaciones | Media | Alto | Prototipado, testing usuarios, simplificación |
| Precisión datos estadísticos | Baja | Alto | Validaciones robustas, auditoría datos |

## Métricas de Éxito

- **Report generation time**: < 3s para reportes estándar
- **Data accuracy**: 100% precisión en cálculos
- **User adoption**: > 70% entrenadores usando evaluaciones
- **Dashboard load time**: < 2s
- **Export success rate**: > 98%

## Entregables

1. **Sistema de Evaluaciones** - Evaluación completa de jugadores
2. **Estadísticas Avanzadas** - Métricas y análisis deportivo
3. **Reportes Deportivos** - Generación y exportación
4. **Dashboard Avanzado** - Visualización integral
5. **Documentación** - Guías de uso y API

## Preguntas para Retrospectiva

1. **¿Las evaluaciones capturan aspectos relevantes del rendimiento?**
2. **¿Los cálculos estadísticos son precisos y útiles?**
3. **¿Los reportes proporcionan insights valiosos?**
4. **¿El dashboard facilita la toma de decisiones?**
5. **¿La exportación de reportes es eficiente?**
6. **¿Qué mejoras podemos hacer en visualización de datos?**
7. **¿Los entrenadores encuentran valor en las herramientas?**