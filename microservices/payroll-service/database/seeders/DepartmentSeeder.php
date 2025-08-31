<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Recursos Humanos',
                'code' => 'RH',
                'description' => 'Departamento encargado de la gestión del talento humano',
                'manager_id' => null,
                'budget' => 50000000.00,
                'status' => 'active'
            ],
            [
                'name' => 'Tecnología',
                'code' => 'TI',
                'description' => 'Departamento de Tecnología de la Información',
                'manager_id' => null,
                'budget' => 120000000.00,
                'status' => 'active'
            ],
            [
                'name' => 'Desarrollo de Software',
                'code' => 'DEV',
                'description' => 'Equipo de desarrollo de aplicaciones',
                'manager_id' => null,
                'budget' => 80000000.00,
                'status' => 'active'
            ],
            [
                'name' => 'Infraestructura TI',
                'code' => 'INFRA',
                'description' => 'Equipo de infraestructura y sistemas',
                'manager_id' => null,
                'budget' => 40000000.00,
                'status' => 'active'
            ],
            [
                'name' => 'Ventas',
                'code' => 'VEN',
                'description' => 'Departamento de ventas y comercialización',
                'manager_id' => null,
                'budget' => 90000000.00,
                'status' => 'active'
            ],
            [
                'name' => 'Marketing',
                'code' => 'MKT',
                'description' => 'Departamento de marketing y comunicaciones',
                'manager_id' => null,
                'budget' => 60000000.00,
                'status' => 'active'
            ],
            [
                'name' => 'Finanzas',
                'code' => 'FIN',
                'description' => 'Departamento financiero y contable',
                'manager_id' => null,
                'budget' => 70000000.00,
                'status' => 'active'
            ],
            [
                'name' => 'Contabilidad',
                'code' => 'CONT',
                'description' => 'Área de contabilidad y reportes financieros',
                'manager_id' => null,
                'budget' => 30000000.00,
                'status' => 'active'
            ],
            [
                'name' => 'Operaciones',
                'code' => 'OPS',
                'description' => 'Departamento de operaciones y logística',
                'manager_id' => null,
                'budget' => 85000000.00,
                'status' => 'active'
            ],
            [
                'name' => 'Calidad',
                'code' => 'QA',
                'description' => 'Departamento de aseguramiento de calidad',
                'manager_id' => null,
                'budget' => 35000000.00,
                'status' => 'active'
            ]
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }
    }
}