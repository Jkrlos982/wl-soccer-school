<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AccountReceivable;
use App\Models\Payment;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanInstallment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class CollectionController extends Controller
{
    /**
     * Get collection dashboard statistics
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $today = Carbon::today();
            $thisMonth = Carbon::now()->startOfMonth();
            $lastMonth = Carbon::now()->subMonth()->startOfMonth();

            // Accounts Receivable Statistics
            $totalReceivables = AccountReceivable::forSchool($schoolId)->sum('amount');
            $pendingReceivables = AccountReceivable::forSchool($schoolId)->pending()->sum('amount');
            $overdueReceivables = AccountReceivable::forSchool($schoolId)->overdue()->sum('amount');
            $collectedReceivables = AccountReceivable::forSchool($schoolId)->paid()->sum('amount');

            // Payment Statistics
            $totalPayments = Payment::forSchool($schoolId)->confirmed()->sum('amount');
            $paymentsThisMonth = Payment::forSchool($schoolId)
                ->confirmed()
                ->where('payment_date', '>=', $thisMonth)
                ->sum('amount');
            $paymentsLastMonth = Payment::forSchool($schoolId)
                ->confirmed()
                ->where('payment_date', '>=', $lastMonth)
                ->where('payment_date', '<', $thisMonth)
                ->sum('amount');

            // Collection Rate
            $collectionRate = $totalReceivables > 0 ? ($collectedReceivables / $totalReceivables) * 100 : 0;

            // Due Soon (next 7 days)
            $dueSoon = AccountReceivable::forSchool($schoolId)
                ->pending()
                ->whereBetween('due_date', [$today, $today->copy()->addDays(7)])
                ->sum('amount');

            // Overdue by periods
            $overdue1to30 = AccountReceivable::forSchool($schoolId)
                ->overdue()
                ->whereBetween('due_date', [$today->copy()->subDays(30), $today->copy()->subDay()])
                ->sum('amount');
            
            $overdue31to60 = AccountReceivable::forSchool($schoolId)
                ->overdue()
                ->whereBetween('due_date', [$today->copy()->subDays(60), $today->copy()->subDays(31)])
                ->sum('amount');
            
            $overdueOver60 = AccountReceivable::forSchool($schoolId)
                ->overdue()
                ->where('due_date', '<', $today->copy()->subDays(60))
                ->sum('amount');

            // Payment Plan Statistics
            $activePaymentPlans = PaymentPlan::forSchool($schoolId)->active()->count();
            $completedPaymentPlans = PaymentPlan::forSchool($schoolId)->completed()->count();
            $totalPaymentPlanAmount = PaymentPlan::forSchool($schoolId)->active()->sum('total_amount');

            $dashboard = [
                'receivables' => [
                    'total' => round($totalReceivables, 2),
                    'pending' => round($pendingReceivables, 2),
                    'overdue' => round($overdueReceivables, 2),
                    'collected' => round($collectedReceivables, 2),
                    'collection_rate' => round($collectionRate, 2),
                    'due_soon' => round($dueSoon, 2)
                ],
                'payments' => [
                    'total' => round($totalPayments, 2),
                    'this_month' => round($paymentsThisMonth, 2),
                    'last_month' => round($paymentsLastMonth, 2),
                    'growth_rate' => $paymentsLastMonth > 0 ? round((($paymentsThisMonth - $paymentsLastMonth) / $paymentsLastMonth) * 100, 2) : 0
                ],
                'overdue_aging' => [
                    '1_to_30_days' => round($overdue1to30, 2),
                    '31_to_60_days' => round($overdue31to60, 2),
                    'over_60_days' => round($overdueOver60, 2)
                ],
                'payment_plans' => [
                    'active' => $activePaymentPlans,
                    'completed' => $completedPaymentPlans,
                    'total_amount' => round($totalPaymentPlanAmount, 2)
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $dashboard
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get aging report
     */
    public function agingReport(Request $request): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $today = Carbon::today();
            
            $agingData = AccountReceivable::forSchool($schoolId)
                ->with(['concept', 'createdBy'])
                ->pending()
                ->get()
                ->map(function ($receivable) use ($today) {
                    $daysOverdue = $receivable->due_date < $today ? $today->diffInDays($receivable->due_date) : 0;
                    
                    $agingBucket = 'current';
                    if ($daysOverdue > 0) {
                        if ($daysOverdue <= 30) {
                            $agingBucket = '1-30';
                        } elseif ($daysOverdue <= 60) {
                            $agingBucket = '31-60';
                        } elseif ($daysOverdue <= 90) {
                            $agingBucket = '61-90';
                        } else {
                            $agingBucket = '90+';
                        }
                    }
                    
                    return [
                        'id' => $receivable->id,
                        'student_id' => $receivable->student_id,
                        'concept' => $receivable->concept->name ?? 'N/A',
                        'amount' => $receivable->amount,
                        'remaining_amount' => $receivable->remaining_amount,
                        'due_date' => $receivable->due_date->format('Y-m-d'),
                        'days_overdue' => $daysOverdue,
                        'aging_bucket' => $agingBucket,
                        'created_at' => $receivable->created_at->format('Y-m-d')
                    ];
                })
                ->groupBy('aging_bucket');

            // Calculate totals by aging bucket
            $summary = [
                'current' => [
                    'count' => $agingData->get('current', collect())->count(),
                    'amount' => $agingData->get('current', collect())->sum('remaining_amount')
                ],
                '1-30' => [
                    'count' => $agingData->get('1-30', collect())->count(),
                    'amount' => $agingData->get('1-30', collect())->sum('remaining_amount')
                ],
                '31-60' => [
                    'count' => $agingData->get('31-60', collect())->count(),
                    'amount' => $agingData->get('31-60', collect())->sum('remaining_amount')
                ],
                '61-90' => [
                    'count' => $agingData->get('61-90', collect())->count(),
                    'amount' => $agingData->get('61-90', collect())->sum('remaining_amount')
                ],
                '90+' => [
                    'count' => $agingData->get('90+', collect())->count(),
                    'amount' => $agingData->get('90+', collect())->sum('remaining_amount')
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'details' => $agingData
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating aging report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get collection trends
     */
    public function trends(Request $request): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $months = $request->get('months', 12); // Default to 12 months
            $startDate = Carbon::now()->subMonths($months)->startOfMonth();

            // Monthly collection trends
            $monthlyCollections = Payment::forSchool($schoolId)
                ->confirmed()
                ->where('payment_date', '>=', $startDate)
                ->select(
                    DB::raw('YEAR(payment_date) as year'),
                    DB::raw('MONTH(payment_date) as month'),
                    DB::raw('SUM(amount) as total_amount'),
                    DB::raw('COUNT(*) as payment_count')
                )
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    return [
                        'period' => sprintf('%04d-%02d', $item->year, $item->month),
                        'total_amount' => round($item->total_amount, 2),
                        'payment_count' => $item->payment_count
                    ];
                });

            // Monthly receivables created
            $monthlyReceivables = AccountReceivable::forSchool($schoolId)
                ->where('created_at', '>=', $startDate)
                ->select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('SUM(amount) as total_amount'),
                    DB::raw('COUNT(*) as receivable_count')
                )
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    return [
                        'period' => sprintf('%04d-%02d', $item->year, $item->month),
                        'total_amount' => round($item->total_amount, 2),
                        'receivable_count' => $item->receivable_count
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'collections' => $monthlyCollections,
                    'receivables' => $monthlyReceivables
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating trends report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment method statistics
     */
    public function paymentMethods(Request $request): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

            $paymentMethods = Payment::forSchool($schoolId)
                ->confirmed()
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->select(
                    'payment_method',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(amount) as total_amount'),
                    DB::raw('AVG(amount) as average_amount')
                )
                ->groupBy('payment_method')
                ->orderBy('total_amount', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'method' => $item->payment_method,
                        'count' => $item->count,
                        'total_amount' => round($item->total_amount, 2),
                        'average_amount' => round($item->average_amount, 2)
                    ];
                });

            $totalAmount = $paymentMethods->sum('total_amount');
            $paymentMethodsWithPercentage = $paymentMethods->map(function ($item) use ($totalAmount) {
                $item['percentage'] = $totalAmount > 0 ? round(($item['total_amount'] / $totalAmount) * 100, 2) : 0;
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ],
                    'methods' => $paymentMethodsWithPercentage,
                    'total_amount' => round($totalAmount, 2)
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating payment methods report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get overdue accounts summary
     */
    public function overdueAccounts(Request $request): JsonResponse
    {
        try {
            $schoolId = $request->header('X-School-ID');
            
            if (!$schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School ID is required'
                ], 400);
            }

            $overdueAccounts = AccountReceivable::forSchool($schoolId)
                ->with(['concept', 'createdBy'])
                ->overdue()
                ->get()
                ->map(function ($receivable) {
                    return [
                        'id' => $receivable->id,
                        'student_id' => $receivable->student_id,
                        'concept' => $receivable->concept->name ?? 'N/A',
                        'amount' => $receivable->amount,
                        'remaining_amount' => $receivable->remaining_amount,
                        'due_date' => $receivable->due_date->format('Y-m-d'),
                        'days_overdue' => Carbon::today()->diffInDays($receivable->due_date),
                        'created_at' => $receivable->created_at->format('Y-m-d')
                    ];
                })
                ->sortByDesc('days_overdue')
                ->values();

            $summary = [
                'total_count' => $overdueAccounts->count(),
                'total_amount' => round($overdueAccounts->sum('remaining_amount'), 2),
                'average_days_overdue' => $overdueAccounts->count() > 0 ? round($overdueAccounts->avg('days_overdue'), 1) : 0
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'accounts' => $overdueAccounts
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving overdue accounts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}