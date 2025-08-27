<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FinancialConcept;
use Illuminate\Support\Facades\DB;

class FinancialConceptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Limpiar tabla antes de insertar (manejando foreign keys)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('financial_concepts')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Conceptos de Ingresos
        $incomesConcepts = [
            [
                'name' => 'Mensualidades',
                'description' => 'Pagos mensuales de estudiantes',
                'code' => 'mensualidades',
                'type' => 'income',
                'category' => 'educacion',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Inscripciones',
                'description' => 'Pagos de inscripción',
                'code' => 'inscripciones',
                'type' => 'income',
                'category' => 'educacion',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Uniformes',
                'description' => 'Venta de uniformes y equipamiento',
                'code' => 'uniformes',
                'type' => 'income',
                'category' => 'ventas',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Eventos',
                'description' => 'Ingresos por eventos especiales',
                'code' => 'eventos',
                'type' => 'income',
                'category' => 'eventos',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Patrocinios',
                'description' => 'Ingresos por patrocinios',
                'code' => 'patrocinios',
                'type' => 'income',
                'category' => 'patrocinios',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Otros Ingresos',
                'description' => 'Otros ingresos diversos',
                'code' => 'otros_ingresos',
                'type' => 'income',
                'category' => 'otros',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Conceptos de Gastos
        $expensesConcepts = [
            [
                'name' => 'Salarios',
                'description' => 'Pagos de salarios y honorarios',
                'code' => 'salarios',
                'type' => 'expense',
                'category' => 'personal',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Servicios',
                'description' => 'Servicios públicos y mantenimiento',
                'code' => 'servicios',
                'type' => 'expense',
                'category' => 'operativos',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Equipamiento',
                'description' => 'Compra de equipamiento deportivo',
                'code' => 'equipamiento',
                'type' => 'expense',
                'category' => 'equipamiento',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Transporte',
                'description' => 'Gastos de transporte',
                'code' => 'transporte',
                'type' => 'expense',
                'category' => 'operativos',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Marketing',
                'description' => 'Gastos de marketing y publicidad',
                'code' => 'marketing',
                'type' => 'expense',
                'category' => 'marketing',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Otros Gastos',
                'description' => 'Otros gastos diversos',
                'code' => 'otros_gastos',
                'type' => 'expense',
                'category' => 'otros',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insertar conceptos de ingresos
        foreach ($incomesConcepts as $concept) {
            FinancialConcept::create($concept);
        }

        // Insertar conceptos de gastos
        foreach ($expensesConcepts as $concept) {
            FinancialConcept::create($concept);
        }

        $this->command->info('Conceptos financieros por defecto creados exitosamente.');
    }
}