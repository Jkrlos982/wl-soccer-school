<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PlayerEvaluation;
use App\Models\Player;
use App\Models\Training;
use App\Models\User;
use Carbon\Carbon;

class PlayerEvaluationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some players, trainings, and users for the evaluations
        $players = Player::limit(10)->get();
        $trainings = Training::limit(5)->get();
        $evaluators = User::limit(3)->get();
        
        if ($players->isEmpty() || $evaluators->isEmpty()) {
            $this->command->info('No players or users found. Please run PlayerSeeder and UserSeeder first.');
            return;
        }
        
        $evaluationTypes = ['training', 'match', 'monthly', 'semester'];
        
        foreach ($players as $player) {
            // Create 3-5 evaluations per player
            $numEvaluations = rand(3, 5);
            
            for ($i = 0; $i < $numEvaluations; $i++) {
                $evaluator = $evaluators->random();
                $training = $trainings->isNotEmpty() ? $trainings->random() : null;
                $evaluationType = $evaluationTypes[array_rand($evaluationTypes)];
                
                // Generate evaluation date within the last 3 months
                $evaluationDate = Carbon::now()->subDays(rand(1, 90));
                
                PlayerEvaluation::create([
                    'school_id' => $player->school_id,
                    'player_id' => $player->id,
                    'evaluator_id' => $evaluator->id,
                    'training_id' => $training?->id,
                    'evaluation_date' => $evaluationDate,
                    'evaluation_type' => $evaluationType,
                    
                    // Technical Skills (1-10 scale)
                    'technical_skills' => rand(5, 9),
                    'ball_control' => rand(4, 10),
                    'passing' => rand(5, 9),
                    'shooting' => rand(3, 8),
                    'dribbling' => rand(4, 9),
                    
                    // Physical Skills
                    'speed' => rand(5, 10),
                    'endurance' => rand(6, 10),
                    'strength' => rand(4, 8),
                    'agility' => rand(5, 9),
                    
                    // Tactical Skills
                    'positioning' => rand(5, 9),
                    'decision_making' => rand(4, 8),
                    'teamwork' => rand(6, 10),
                    'game_understanding' => rand(4, 8),
                    
                    // Mental/Attitudinal Skills
                    'attitude' => rand(7, 10),
                    'discipline' => rand(6, 10),
                    'leadership' => rand(3, 8),
                    'commitment' => rand(7, 10),
                    
                    // Overall rating
                    'overall_rating' => rand(5, 9),
                    
                    // Comments
                    'strengths' => $this->getRandomStrengths(),
                    'areas_for_improvement' => $this->getRandomAreasForImprovement(),
                    'goals_next_period' => $this->getRandomShortTermGoals(),
                    'coach_comments' => $this->getRandomGeneralComment(),
                    'custom_metrics' => json_encode([
                        'attendance_rate' => rand(80, 100),
                        'improvement_trend' => rand(1, 5)
                    ]),
                ]);
            }
        }
        
        $this->command->info('PlayerEvaluation seeder completed successfully!');
    }
    
    private function getRandomStrengths(): string
    {
        $strengths = [
            'Excelente control del balón y técnica individual',
            'Gran velocidad y capacidad de desborde',
            'Muy buena visión de juego y pase',
            'Liderazgo natural y comunicación en el campo',
            'Excelente condición física y resistencia',
            'Muy buena definición y remate',
            'Gran capacidad de trabajo en equipo',
            'Excelente actitud y disciplina',
            'Muy buena lectura del juego defensivo',
            'Gran capacidad de adaptación a diferentes posiciones'
        ];
        
        return $strengths[array_rand($strengths)];
    }
    
    private function getRandomAreasForImprovement(): string
    {
        $areas = [
            'Mejorar la precisión en los pases largos',
            'Trabajar en la definición con pierna no hábil',
            'Mejorar la comunicación con los compañeros',
            'Desarrollar más confianza en situaciones de presión',
            'Mejorar la velocidad de reacción defensiva',
            'Trabajar en el juego aéreo',
            'Mejorar la toma de decisiones bajo presión',
            'Desarrollar más variedad en el juego ofensivo',
            'Mejorar la concentración durante todo el partido',
            'Trabajar en la resistencia para los últimos minutos'
        ];
        
        return $areas[array_rand($areas)];
    }
    
    private function getRandomGeneralComment(): string
    {
        $comments = [
            'Jugador con gran potencial que muestra constante mejora en cada entrenamiento.',
            'Excelente actitud y disposición para aprender. Sigue las instrucciones muy bien.',
            'Jugador técnicamente sólido que necesita ganar más confianza en el juego.',
            'Gran compromiso con el equipo y muy buena relación con sus compañeros.',
            'Jugador versátil que puede adaptarse a diferentes posiciones según las necesidades.',
            'Muestra gran dedicación en los entrenamientos y siempre busca mejorar.',
            'Jugador con buenas condiciones físicas que debe trabajar más en el aspecto táctico.',
            'Excelente comportamiento dentro y fuera del campo. Un ejemplo para sus compañeros.'
        ];
        
        return $comments[array_rand($comments)];
    }
    
    private function getRandomShortTermGoals(): string
    {
        $goals = [
            'Mejorar la precisión en los pases en las próximas 4 semanas',
            'Aumentar la participación en jugadas ofensivas',
            'Trabajar en la comunicación durante los partidos',
            'Mejorar la condición física general',
            'Desarrollar más confianza en situaciones de 1vs1',
            'Perfeccionar la técnica de remate',
            'Mejorar el posicionamiento defensivo',
            'Aumentar la velocidad de ejecución en las jugadas'
        ];
        
        return $goals[array_rand($goals)];
    }
    
    private function getRandomLongTermGoals(): string
    {
        $goals = [
            'Convertirse en un referente técnico del equipo',
            'Desarrollar capacidades de liderazgo',
            'Alcanzar un nivel competitivo para torneos regionales',
            'Mejorar significativamente en todas las áreas evaluadas',
            'Convertirse en un jugador más completo y versátil',
            'Desarrollar la capacidad de jugar en diferentes posiciones',
            'Alcanzar consistencia en el rendimiento durante toda la temporada',
            'Convertirse en un ejemplo de disciplina y compromiso para el equipo'
        ];
        
        return $goals[array_rand($goals)];
    }
}