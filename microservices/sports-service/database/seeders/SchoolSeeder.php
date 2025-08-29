<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = [
            [
                'name' => 'Colegio San José',
                'code' => 'CSJ001',
                'description' => 'Institución educativa con enfoque en deportes y formación integral',
                'address' => 'Calle 123 #45-67, Bogotá',
                'phone' => '601-234-5678',
                'email' => 'info@colegiosanjose.edu.co',
                'website' => 'https://colegiosanjose.edu.co',
                'is_active' => true,
            ],
            [
                'name' => 'Instituto Técnico Nacional',
                'code' => 'ITN002',
                'description' => 'Instituto técnico con programas deportivos especializados',
                'address' => 'Carrera 45 #12-34, Medellín',
                'phone' => '604-567-8901',
                'email' => 'info@itn.edu.co',
                'website' => 'https://itn.edu.co',
                'is_active' => true,
            ],
            [
                'name' => 'Colegio La Esperanza',
                'code' => 'CLE003',
                'description' => 'Colegio con tradición en formación deportiva y académica',
                'address' => 'Avenida 67 #89-12, Cali',
                'phone' => '602-345-6789',
                'email' => 'info@laesperanza.edu.co',
                'website' => 'https://laesperanza.edu.co',
                'is_active' => true,
            ],
        ];

        foreach ($schools as $school) {
            School::firstOrCreate(
                ['code' => $school['code']],
                $school
            );
        }
    }
}