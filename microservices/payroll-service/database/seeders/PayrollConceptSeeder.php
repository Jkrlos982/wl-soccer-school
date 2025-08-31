<?php

namespace Database\Seeders;

use App\Models\PayrollConcept;
use Illuminate\Database\Seeder;

class PayrollConceptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $concepts = [
            // Ingresos (Earnings)
            [
                'code' => 'SALARIO_BASE',
                'name' => 'Salario Base',
                'description' => 'Salario base del empleado',
                'type' => 'earning',
                'calculation_type' => 'fixed',
                'formula' => null,
                'is_taxable' => true,
                'affects_social_security' => true,
                'status' => 'active',
                'display_order' => 1
            ],
            [
                'code' => 'HORAS_EXTRA',
                'name' => 'Horas Extra',
                'description' => 'Pago por horas extra trabajadas',
                'type' => 'earning',
                'calculation_type' => 'formula',
                'formula' => '(base_salary / 240) * overtime_hours * 1.25',
                'is_taxable' => true,
                'affects_social_security' => true,
                'status' => 'active',
                'display_order' => 2
            ],
            [
                'code' => 'BONIFICACION',
                'name' => 'Bonificación',
                'description' => 'Bonificación adicional',
                'type' => 'earning',
                'calculation_type' => 'fixed',
                'formula' => null,
                'is_taxable' => true,
                'affects_social_security' => true,
                'status' => 'active',
                'display_order' => 3
            ],
            [
                'code' => 'COMISION',
                'name' => 'Comisión',
                'description' => 'Comisión por ventas o rendimiento',
                'type' => 'earning',
                'calculation_type' => 'fixed',
                'formula' => null,
                'is_taxable' => true,
                'affects_social_security' => true,
                'status' => 'active',
                'display_order' => 4
            ],
            [
                'code' => 'SUBSIDIO_TRANSPORTE',
                'name' => 'Subsidio de Transporte',
                'description' => 'Subsidio para transporte',
                'type' => 'earning',
                'calculation_type' => 'fixed',
                'formula' => null,
                'is_taxable' => false,
                'affects_social_security' => false,
                'status' => 'active',
                'display_order' => 5
            ],
            [
                'code' => 'AUXILIO_ALIMENTACION',
                'name' => 'Auxilio de Alimentación',
                'description' => 'Auxilio para alimentación',
                'type' => 'earning',
                'calculation_type' => 'fixed',
                'formula' => null,
                'is_taxable' => false,
                'affects_social_security' => false,
                'status' => 'active',
                'display_order' => 6
            ],

            // Deducciones (Deductions)
            [
                'code' => 'SALUD_EMPLEADO',
                'name' => 'Salud Empleado (4%)',
                'description' => 'Aporte del empleado a salud',
                'type' => 'deduction',
                'calculation_type' => 'percentage',
                'formula' => 'taxable_income * 0.04',
                'is_taxable' => false,
                'affects_social_security' => false,
                'status' => 'active',
                'display_order' => 10
            ],
            [
                'code' => 'PENSION_EMPLEADO',
                'name' => 'Pensión Empleado (4%)',
                'description' => 'Aporte del empleado a pensión',
                'type' => 'deduction',
                'calculation_type' => 'percentage',
                'formula' => 'taxable_income * 0.04',
                'is_taxable' => false,
                'affects_social_security' => false,
                'status' => 'active',
                'display_order' => 11
            ],
            [
                'code' => 'RETENCION_FUENTE',
                'name' => 'Retención en la Fuente',
                'description' => 'Retención de impuesto sobre la renta',
                'type' => 'deduction',
                'calculation_type' => 'formula',
                'formula' => 'calculateIncomeTax(taxable_income)',
                'is_taxable' => false,
                'affects_social_security' => false,
                'status' => 'active',
                'display_order' => 12
            ],
            [
                'code' => 'PRESTAMO_EMPRESA',
                'name' => 'Préstamo Empresa',
                'description' => 'Descuento por préstamo de la empresa',
                'type' => 'deduction',
                'calculation_type' => 'fixed',
                'formula' => null,
                'is_taxable' => false,
                'affects_social_security' => false,
                'status' => 'active',
                'display_order' => 13
            ],
            [
                'code' => 'EMBARGO',
                'name' => 'Embargo Judicial',
                'description' => 'Descuento por embargo judicial',
                'type' => 'deduction',
                'calculation_type' => 'fixed',
                'formula' => null,
                'is_taxable' => false,
                'affects_social_security' => false,
                'status' => 'active',
                'display_order' => 14
            ],
            [
                'code' => 'FONDO_SOLIDARIDAD',
                'name' => 'Fondo de Solidaridad (1%)',
                'description' => 'Aporte al fondo de solidaridad pensional',
                'type' => 'deduction',
                'calculation_type' => 'percentage',
                'formula' => 'taxable_income * 0.01',
                'is_taxable' => false,
                'affects_social_security' => false,
                'status' => 'active',
                'display_order' => 15
            ],

            // Aportes Patronales (Employer Contributions)
            [
                'code' => 'SALUD_PATRONAL',
                'name' => 'Salud Patronal (8.5%)',
                'description' => 'Aporte patronal a salud',
                'type' => 'deduction',
                'calculation_type' => 'percentage',
                'formula' => 'taxable_income * 0.085',
                'is_taxable' => false,
                'affects_social_security' => false,
                'status' => 'active',
                'display_order' => 20
            ],
            [
                'code' => 'PENSION_PATRONAL',
                'name' => 'Pensión Patronal (12%)',
                'description' => 'Aporte patronal a pensión',
                'type' => 'deduction',
                'calculation_type' => 'percentage',
                'formula' => 'taxable_income * 0.12',
                'is_taxable' => false,
                'affects_social_security' => false,
                'status' => 'active',
                'display_order' => 21
            ],
            [
                'code' => 'ARL',
                'name' => 'ARL (0.522%)',
                'description' => 'Aporte a riesgos laborales',
                'type' => 'deduction',
                'calculation_type' => 'percentage',
                'formula' => 'taxable_income * 0.00522',
                'is_taxable' => false,
                'affects_social_security' => false,
                'status' => 'active',
                'display_order' => 22
            ],
            [
                'code' => 'CAJA_COMPENSACION',
                'name' => 'Caja de Compensación (4%)',
                'description' => 'Aporte a caja de compensación familiar',
                'type' => 'deduction',
                'calculation_type' => 'percentage',
                'formula' => 'taxable_income * 0.04',
                'is_taxable' => false,
                'affects_social_security' => false,
                'status' => 'active',
                'display_order' => 23
            ],
            [
                'code' => 'ICBF',
                'name' => 'ICBF (3%)',
                'description' => 'Aporte al Instituto Colombiano de Bienestar Familiar',
                'type' => 'deduction',
                'calculation_type' => 'percentage',
                'formula' => 'taxable_income * 0.03',
                'is_taxable' => false,
                'affects_social_security' => false,
                'status' => 'active',
                'display_order' => 24
            ],
            [
                'code' => 'SENA',
                'name' => 'SENA (2%)',
                'description' => 'Aporte al Servicio Nacional de Aprendizaje',
                'type' => 'deduction',
                'calculation_type' => 'percentage',
                'formula' => 'taxable_income * 0.02',
                'is_taxable' => false,
                'affects_social_security' => false,
                'status' => 'active',
                'display_order' => 25
            ],

            // Prestaciones Sociales (Social Benefits)
            [
                'code' => 'CESANTIAS',
                'name' => 'Cesantías',
                'description' => 'Provisión de cesantías',
                'type' => 'benefit',
                'calculation_type' => 'formula',
                'formula' => '(base_salary * days_worked) / 360',
                'is_taxable' => false,
                'affects_social_security' => false,
                'status' => 'active',
                'display_order' => 30
            ],
            [
                'code' => 'INTERESES_CESANTIAS',
                'name' => 'Intereses sobre Cesantías',
                'description' => 'Intereses sobre cesantías (12% anual)',
                'type' => 'benefit',
                'calculation_type' => 'formula',
                'formula' => 'cesantias_accumulated * 0.12',
                'is_taxable' => false,
                'affects_social_security' => false,
                'status' => 'active',
                'display_order' => 31
            ],
            [
                'code' => 'PRIMA_SERVICIOS',
                'name' => 'Prima de Servicios',
                'description' => 'Prima de servicios semestral',
                'type' => 'benefit',
                'calculation_type' => 'formula',
                'formula' => '(base_salary * days_worked) / 360',
                'is_taxable' => true,
                'affects_social_security' => true,
                'status' => 'active',
                'display_order' => 32
            ],
            [
                'code' => 'VACACIONES',
                'name' => 'Vacaciones',
                'description' => 'Provisión de vacaciones',
                'type' => 'benefit',
                'calculation_type' => 'formula',
                'formula' => '(base_salary * days_worked) / 720',
                'is_taxable' => true,
                'affects_social_security' => true,
                'status' => 'active',
                'display_order' => 33
            ]
        ];

        foreach ($concepts as $concept) {
            PayrollConcept::create($concept);
        }
    }
}