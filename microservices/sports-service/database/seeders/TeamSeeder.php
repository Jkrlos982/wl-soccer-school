<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\School;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = School::all();
        $categories = Category::all();

        if ($schools->isEmpty() || $categories->isEmpty()) {
            $this->command->info('No schools or categories found. Please run SchoolSeeder and CategorySeeder first.');
            return;
        }

        $users = \App\Models\User::all();
        $teamNames = [
            'Águilas', 'Leones', 'Tigres', 'Halcones', 'Lobos', 'Panteras',
            'Dragones', 'Cóndores', 'Jaguares', 'Pumas', 'Zorros', 'Búhos'
        ];

        foreach ($schools as $school) {
            foreach ($categories->take(3) as $category) {
                $teamName = $teamNames[array_rand($teamNames)];
                
                Team::create([
                    'name' => $teamName . ' ' . $category->name,
                    'school_id' => $school->id,
                    'category_id' => $category->id,
                    'coach_id' => $users->random()->id ?? null,
                    'description' => 'Equipo de fútbol de la categoría ' . $category->name,
                    'max_players' => 25,
                    'season' => '2024',
                    'field_location' => 'Campo Principal',
                    'is_active' => true,
                    'registration_open' => true,
                ]);
            }
        }
    }
}