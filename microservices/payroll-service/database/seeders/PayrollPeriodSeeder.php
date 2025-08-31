<?php

namespace Database\Seeders;

use App\Models\PayrollPeriod;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PayrollPeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $periods = [];
        
        // Generate payroll periods for 2023 and 2024
        $years = [2023, 2024];
        
        foreach ($years as $year) {
            // Monthly periods
            for ($month = 1; $month <= 12; $month++) {
                $startDate = Carbon::create($year, $month, 1);
                $endDate = $startDate->copy()->endOfMonth();
                
                $periods[] = [
                    'name' => 'Nómina ' . $startDate->format('F Y'),
                    'period_type' => 'monthly',
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'pay_date' => $endDate->copy()->addDays(5)->format('Y-m-d'),
                    'year' => $year,
                    'month' => $month,
                    'period_number' => $month,
                    'status' => $year == 2023 || ($year == 2024 && $month < Carbon::now()->month) ? 'closed' : 
                               ($year == 2024 && $month == Carbon::now()->month ? 'processing' : 'draft'),
                    'notes' => 'Período de nómina mensual para ' . $startDate->format('F Y'),
                ];
            }
            
            // Bi-weekly periods (quincenal)
            $periodNumber = 1;
            for ($month = 1; $month <= 12; $month++) {
                // First fortnight
                $startDate1 = Carbon::create($year, $month, 1);
                $endDate1 = Carbon::create($year, $month, 15);
                
                $periods[] = [
                    'name' => 'Quincena 1 - ' . $startDate1->format('F Y'),
                    'period_type' => 'biweekly',
                    'start_date' => $startDate1->format('Y-m-d'),
                    'end_date' => $endDate1->format('Y-m-d'),
                    'pay_date' => $endDate1->copy()->addDays(3)->format('Y-m-d'),
                    'year' => $year,
                    'month' => $month,
                    'period_number' => $periodNumber++,
                    'status' => $year == 2023 || ($year == 2024 && $month < Carbon::now()->month) ? 'closed' : 
                               ($year == 2024 && $month == Carbon::now()->month ? 'processing' : 'draft'),
                    'notes' => 'Primera quincena de ' . $startDate1->format('F Y'),
                ];
                
                // Second fortnight
                $startDate2 = Carbon::create($year, $month, 16);
                $endDate2 = $startDate2->copy()->endOfMonth();
                
                $periods[] = [
                    'name' => 'Quincena 2 - ' . $startDate2->format('F Y'),
                    'period_type' => 'biweekly',
                    'start_date' => $startDate2->format('Y-m-d'),
                    'end_date' => $endDate2->format('Y-m-d'),
                    'pay_date' => $endDate2->copy()->addDays(3)->format('Y-m-d'),
                    'year' => $year,
                    'month' => $month,
                    'period_number' => $periodNumber++,
                    'status' => $year == 2023 || ($year == 2024 && $month < Carbon::now()->month) ? 'closed' : 
                               ($year == 2024 && $month == Carbon::now()->month ? 'processing' : 'draft'),
                    'notes' => 'Segunda quincena de ' . $startDate2->format('F Y'),
                ];
            }
        }
        
        // Add some weekly periods for testing
        $currentDate = Carbon::now()->startOfWeek();
        for ($i = 0; $i < 8; $i++) {
            $startDate = $currentDate->copy()->addWeeks($i);
            $endDate = $startDate->copy()->endOfWeek();
            
            $periods[] = [
                'name' => 'Semana ' . $startDate->format('W') . ' - ' . $startDate->format('Y'),
                'period_type' => 'weekly',
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'pay_date' => $endDate->copy()->addDays(2)->format('Y-m-d'),
                'year' => $startDate->year,
                'month' => $startDate->month,
                'period_number' => $startDate->weekOfYear,
                'status' => $i < 4 ? 'closed' : ($i == 4 ? 'processing' : 'draft'),
                'notes' => 'Período semanal del ' . $startDate->format('d/m/Y') . ' al ' . $endDate->format('d/m/Y'),
            ];
        }
        
        foreach ($periods as $period) {
            PayrollPeriod::create($period);
        }
    }
}