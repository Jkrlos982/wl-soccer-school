<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = School::all();
        $users = User::all();

        if ($schools->isEmpty() || $users->isEmpty()) {
            $this->command->info('No schools or users found. Please run SchoolSeeder and create users first.');
            return;
        }

        $categoryTemplates = [
            [
                'name' => 'Sub-10',
                'description' => 'Categoría para niños menores de 10 años',
                'min_age' => 6,
                'max_age' => 9,
                'gender' => 'mixed',
            ],
            [
                'name' => 'Sub-12',
                'description' => 'Categoría para niños de 10 a 11 años',
                'min_age' => 10,
                'max_age' => 11,
                'gender' => 'mixed',
            ],
            [
                'name' => 'Sub-14',
                'description' => 'Categoría para adolescentes de 12 a 13 años',
                'min_age' => 12,
                'max_age' => 13,
                'gender' => 'mixed',
            ],
            [
                'name' => 'Sub-16',
                'description' => 'Categoría para adolescentes de 14 a 15 años',
                'min_age' => 14,
                'max_age' => 15,
                'gender' => 'mixed',
            ],
            [
                'name' => 'Sub-18',
                'description' => 'Categoría para jóvenes de 16 a 17 años',
                'min_age' => 16,
                'max_age' => 17,
                'gender' => 'mixed',
            ],
        ];

        foreach ($schools as $school) {
            foreach ($categoryTemplates as $template) {
                Category::create([
                    'school_id' => $school->id,
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'min_age' => $template['min_age'],
                    'max_age' => $template['max_age'],
                    'gender' => $template['gender'],
                    'max_players' => 25,
                    'training_days' => json_encode(['monday', 'wednesday', 'friday']),
                    'training_start_time' => '15:00:00',
                    'training_end_time' => '17:00:00',
                    'field_location' => 'Campo Principal',
                    'is_active' => true,
                    'coach_id' => $users->random()->id,
                ]);
            }
        }
    }
}