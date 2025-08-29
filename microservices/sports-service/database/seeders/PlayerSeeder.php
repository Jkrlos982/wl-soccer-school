<?php

namespace Database\Seeders;

use App\Models\Player;
use App\Models\School;
use App\Models\Category;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PlayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = School::all();
        $categories = Category::all();
        $teams = Team::all();

        if ($schools->isEmpty() || $categories->isEmpty()) {
            $this->command->info('No schools or categories found. Please run SchoolSeeder and CategorySeeder first.');
            return;
        }

        $positions = ['Portero', 'Defensa Central', 'Lateral Derecho', 'Lateral Izquierdo', 'Mediocampista Defensivo', 'Mediocampista Central', 'Mediocampista Ofensivo', 'Extremo Derecho', 'Extremo Izquierdo', 'Delantero Centro'];
        $documentTypes = ['TI', 'CC', 'CE'];
        $genders = ['male', 'female'];
        $relationships = ['Padre', 'Madre', 'Abuelo', 'Abuela', 'Tío', 'Tía', 'Hermano', 'Hermana'];

        foreach ($schools as $school) {
            foreach ($categories as $category) {
                $playersCount = rand(15, 25);
                $team = $teams->where('school_id', $school->id)->where('category_id', $category->id)->first();
                
                for ($i = 0; $i < $playersCount; $i++) {
                    $gender = $genders[array_rand($genders)];
                    $firstName = $gender === 'male' ? fake()->firstNameMale() : fake()->firstNameFemale();
                    
                    // Calculate birth date based on category age range
                    $currentYear = Carbon::now()->year;
                    $birthYear = $currentYear - rand($category->min_age, $category->max_age);
                    $birthDate = Carbon::create($birthYear, rand(1, 12), rand(1, 28));
                    
                    Player::create([
                        'school_id' => $school->id,
                        'category_id' => $category->id,
                        'team_id' => $team ? $team->id : null,
                        'first_name' => $firstName,
                        'last_name' => fake()->lastName(),
                        'birth_date' => $birthDate->format('Y-m-d'),
                        'gender' => $gender,
                        'document_type' => $documentTypes[array_rand($documentTypes)],
                        'document_number' => fake()->unique()->numerify('##########'),
                        'address' => fake()->address(),
                        'phone' => fake()->phoneNumber(),
                        'email' => fake()->email(),
                        'emergency_contact_name' => fake()->name(),
                        'emergency_contact_phone' => fake()->phoneNumber(),
                        'emergency_contact_relationship' => $relationships[array_rand($relationships)],
                        'medical_conditions' => rand(0, 1) ? fake()->sentence() : null,
                        'allergies' => rand(0, 1) ? fake()->word() : null,
                        'medications' => rand(0, 1) ? fake()->word() : null,
                        'position' => $positions[array_rand($positions)],
                        'jersey_number' => rand(1, 99),
                        'is_active' => true,
                        'enrollment_date' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
                        'notes' => rand(0, 1) ? fake()->sentence() : null,
                    ]);
                }
            }
        }
    }
}