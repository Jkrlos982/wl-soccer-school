<?php

namespace Database\Seeders;

use App\Models\Training;
use App\Models\School;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TrainingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = School::all();
        $categories = Category::all();
        $users = User::all();

        if ($schools->isEmpty() || $categories->isEmpty() || $users->isEmpty()) {
            $this->command->info('No schools, categories, or users found. Please run the respective seeders first.');
            return;
        }

        $trainingTypes = ['training', 'match', 'friendly', 'tournament'];
        $objectives = [
            'Mejorar el control del balón',
            'Desarrollar la resistencia física',
            'Practicar jugadas de ataque',
            'Fortalecer la defensa',
            'Mejorar la precisión en los pases',
            'Desarrollar la velocidad',
            'Practicar tiros a portería',
            'Mejorar el juego en equipo'
        ];

        foreach ($schools as $school) {
            foreach ($categories as $category) {
                // Create trainings for the past 3 months and next 2 months
                $startDate = Carbon::now()->subMonths(3);
                $endDate = Carbon::now()->addMonths(2);
                
                $currentDate = $startDate->copy();
                
                while ($currentDate <= $endDate) {
                    // Create 2-3 trainings per week
                    if (in_array($currentDate->dayOfWeek, [1, 3, 5])) { // Monday, Wednesday, Friday
                        $trainingDate = $currentDate->copy();
                        $startTime = $trainingDate->setTime(rand(14, 18), [0, 30][rand(0, 1)]);
                        $endTime = $startTime->copy()->addHours(rand(1, 2))->addMinutes([0, 30][rand(0, 1)]);
                        
                        Training::create([
                            'school_id' => $school->id,
                            'category_id' => $category->id,
                            'coach_id' => $users->random()->id,
                            'date' => $trainingDate->format('Y-m-d'),
                            'start_time' => $startTime->format('H:i:s'),
                            'end_time' => $endTime->format('H:i:s'),
                            'location' => 'Campo de fútbol ' . rand(1, 3),
                            'type' => $trainingTypes[array_rand($trainingTypes)],
                            'objectives' => $objectives[array_rand($objectives)],
                            'activities' => 'Calentamiento, ejercicios técnicos, práctica táctica, partido',
                            'observations' => rand(0, 1) ? 'Notas adicionales del entrenamiento' : null,
                            'status' => 'scheduled',
                            'weather_conditions' => json_encode([
                                'temperature' => rand(15, 30),
                                'humidity' => rand(40, 80),
                                'wind_speed' => rand(0, 15)
                            ]),
                            'duration_minutes' => rand(60, 120),
                        ]);
                    }
                    
                    $currentDate->addDay();
                }
            }
        }
    }
}