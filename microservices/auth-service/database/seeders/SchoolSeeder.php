<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\School;
use Carbon\Carbon;

class SchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $schools = [
            [
                'name' => 'Colegio Demo WL School',
                'subdomain' => 'demo',
                'description' => 'Colegio de demostración para WL School System',
                'address' => 'Calle Principal 123, Ciudad Demo',
                'phone' => '+57 300 123 4567',
                'email' => 'info@demo.wlschool.com',
                'website' => 'https://demo.wlschool.com',
                'subscription_type' => 'premium',
                'subscription_expires_at' => Carbon::now()->addYear(),
                'is_active' => true,
                'theme_config' => [
                    'primary_color' => '#3B82F6',
                    'secondary_color' => '#10B981',
                    'logo_position' => 'left',
                    'theme_mode' => 'light'
                ],
                'settings' => [
                    'allow_registration' => true,
                    'require_email_verification' => true,
                    'max_students' => 1000,
                    'timezone' => 'America/Bogota',
                    'language' => 'es'
                ]
            ],
            [
                'name' => 'Instituto Tecnológico San José',
                'subdomain' => 'sanjose',
                'description' => 'Instituto de educación técnica y tecnológica',
                'address' => 'Avenida Tecnológica 456, San José',
                'phone' => '+57 301 987 6543',
                'email' => 'contacto@sanjose.edu.co',
                'website' => 'https://sanjose.edu.co',
                'subscription_type' => 'basic',
                'subscription_expires_at' => Carbon::now()->addMonths(6),
                'is_active' => true,
                'theme_config' => [
                    'primary_color' => '#DC2626',
                    'secondary_color' => '#F59E0B',
                    'logo_position' => 'center',
                    'theme_mode' => 'light'
                ],
                'settings' => [
                    'allow_registration' => false,
                    'require_email_verification' => true,
                    'max_students' => 500,
                    'timezone' => 'America/Bogota',
                    'language' => 'es'
                ]
            ],
            [
                'name' => 'Colegio Bilingüe Internacional',
                'subdomain' => 'bilingue',
                'description' => 'Educación bilingüe de alta calidad',
                'address' => 'Zona Norte 789, Ciudad Internacional',
                'phone' => '+57 302 456 7890',
                'email' => 'admissions@bilingue.edu.co',
                'website' => 'https://bilingue.edu.co',
                'subscription_type' => 'enterprise',
                'subscription_expires_at' => Carbon::now()->addYears(2),
                'is_active' => true,
                'theme_config' => [
                    'primary_color' => '#7C3AED',
                    'secondary_color' => '#06B6D4',
                    'logo_position' => 'left',
                    'theme_mode' => 'light'
                ],
                'settings' => [
                    'allow_registration' => true,
                    'require_email_verification' => true,
                    'max_students' => 2000,
                    'timezone' => 'America/Bogota',
                    'language' => 'es'
                ]
            ]
        ];

        foreach ($schools as $schoolData) {
            // Convert arrays to JSON for database storage
            $schoolData['theme_config'] = json_encode($schoolData['theme_config']);
            $schoolData['settings'] = json_encode($schoolData['settings']);
            
            School::firstOrCreate(
                ['subdomain' => $schoolData['subdomain']],
                $schoolData
            );
        }
    }
}
