<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test users
        User::factory(5)->create();

        // Create test user if it doesn't exist
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User']
        );
        
        // Run other seeders in correct order
        $this->call([
            SchoolSeeder::class,
            CategorySeeder::class,
            TeamSeeder::class,
            PlayerSeeder::class,
            TrainingSeeder::class,
            PlayerEvaluationSeeder::class,
        ]);
    }
}
