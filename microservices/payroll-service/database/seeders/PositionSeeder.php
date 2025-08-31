<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Position;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            [
                'title' => 'Gerente General',
                'code' => 'GG-001',
                'description' => 'Máximo ejecutivo de la empresa',
                'department_id' => 1, // Recursos Humanos
                'min_salary' => 15000000.00,
                'max_salary' => 25000000.00,
                'requirements' => 'Título profesional, MBA, mínimo 10 años de experiencia gerencial',
                'responsibilities' => 'Dirección estratégica, toma de decisiones ejecutivas, representación legal',
                'level' => 'director',
                'status' => 'active'
            ],
            [
                'title' => 'Director de Tecnología',
                'code' => 'TI-DIR-001',
                'description' => 'Director del área de tecnología',
                'department_id' => 2, // Tecnología
                'min_salary' => 8000000.00,
                'max_salary' => 12000000.00,
                'requirements' => 'Ingeniería en Sistemas, mínimo 8 años de experiencia en TI',
                'responsibilities' => 'Estrategia tecnológica, gestión de equipos de TI, arquitectura de sistemas',
                'level' => 'director',
                'status' => 'active'
            ],
            [
                'title' => 'Desarrollador Senior',
                'code' => 'DEV-SEN-001',
                'description' => 'Desarrollador de software senior',
                'department_id' => 3, // Desarrollo de Software
                'min_salary' => 4000000.00,
                'max_salary' => 6000000.00,
                'requirements' => 'Ingeniería en Sistemas, mínimo 5 años de experiencia en desarrollo',
                'responsibilities' => 'Desarrollo de aplicaciones, mentoring, arquitectura de software',
                'level' => 'senior',
                'status' => 'active'
            ],
            [
                'title' => 'Desarrollador Junior',
                'code' => 'DEV-JUN-001',
                'description' => 'Desarrollador de software junior',
                'department_id' => 3, // Desarrollo de Software
                'min_salary' => 2500000.00,
                'max_salary' => 3500000.00,
                'requirements' => 'Tecnólogo o Ingeniería en Sistemas, 1-3 años de experiencia',
                'responsibilities' => 'Desarrollo de funcionalidades, testing, documentación',
                'level' => 'junior',
                'status' => 'active'
            ],
            [
                'title' => 'Analista de Ventas',
                'code' => 'VEN-ANA-001',
                'description' => 'Analista del área comercial',
                'department_id' => 5, // Ventas
                'min_salary' => 2800000.00,
                'max_salary' => 4000000.00,
                'requirements' => 'Título profesional en áreas comerciales, 2-4 años de experiencia',
                'responsibilities' => 'Análisis de ventas, reportes comerciales, seguimiento de clientes',
                'level' => 'mid',
                'status' => 'active'
            ],
            [
                'title' => 'Contador',
                'code' => 'FIN-CON-001',
                'description' => 'Contador del área financiera',
                'department_id' => 7, // Finanzas
                'min_salary' => 3000000.00,
                'max_salary' => 4500000.00,
                'requirements' => 'Contador Público, mínimo 3 años de experiencia',
                'responsibilities' => 'Contabilidad general, reportes financieros, cumplimiento tributario',
                'level' => 'mid',
                'status' => 'active'
            ],
            [
                'title' => 'Especialista en Recursos Humanos',
                'code' => 'RH-ESP-001',
                'description' => 'Especialista en gestión humana',
                'department_id' => 1, // Recursos Humanos
                'min_salary' => 3200000.00,
                'max_salary' => 4800000.00,
                'requirements' => 'Psicología o Administración, mínimo 3 años de experiencia en RRHH',
                'responsibilities' => 'Reclutamiento, selección, nómina, bienestar laboral',
                'level' => 'mid',
                'status' => 'active'
            ],
            [
                'title' => 'Coordinador de Marketing',
                'code' => 'MKT-COO-001',
                'description' => 'Coordinador del área de marketing',
                'department_id' => 6, // Marketing
                'min_salary' => 3500000.00,
                'max_salary' => 5000000.00,
                'requirements' => 'Marketing, Comunicación Social, mínimo 4 años de experiencia',
                'responsibilities' => 'Estrategias de marketing, campañas publicitarias, redes sociales',
                'level' => 'lead',
                'status' => 'active'
            ],
            [
                'title' => 'Analista de Calidad',
                'code' => 'QA-ANA-001',
                'description' => 'Analista de aseguramiento de calidad',
                'department_id' => 10, // Calidad
                'min_salary' => 2800000.00,
                'max_salary' => 4200000.00,
                'requirements' => 'Ingeniería Industrial o afines, mínimo 2 años de experiencia',
                'responsibilities' => 'Control de calidad, auditorías, mejora continua',
                'level' => 'mid',
                'status' => 'active'
            ]
        ];

        foreach ($positions as $position) {
            Position::create($position);
        }
    }
}