<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\FinancialConcept;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FinancialReportExport;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Generar Estado de Resultados (Income Statement)
     */
    public function incomeStatement(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'account_id' => 'nullable|exists:accounts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $accountId = $request->account_id;

        // Obtener ingresos (conceptos de tipo income)
        $incomes = $this->getTransactionsByConceptType('income', $startDate, $endDate, $accountId);
        
        // Obtener gastos (conceptos de tipo expense)
        $expenses = $this->getTransactionsByConceptType('expense', $startDate, $endDate, $accountId);

        $totalIncome = $incomes->sum('amount');
        $totalExpenses = $expenses->sum('amount');
        $netIncome = $totalIncome - $totalExpenses;

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d')
                ],
                'incomes' => [
                    'total' => $totalIncome,
                    'details' => $incomes->groupBy('concept.name')->map(function ($group) {
                        return [
                            'concept' => $group->first()->concept->name,
                            'amount' => $group->sum('amount'),
                            'transactions_count' => $group->count()
                        ];
                    })->values()
                ],
                'expenses' => [
                    'total' => $totalExpenses,
                    'details' => $expenses->groupBy('concept.name')->map(function ($group) {
                        return [
                            'concept' => $group->first()->concept->name,
                            'amount' => $group->sum('amount'),
                            'transactions_count' => $group->count()
                        ];
                    })->values()
                ],
                'net_income' => $netIncome,
                'profit_margin' => $totalIncome > 0 ? ($netIncome / $totalIncome) * 100 : 0
            ]
        ]);
    }

    /**
     * Generar Flujo de Caja (Cash Flow Statement)
     */
    public function cashFlow(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'account_id' => 'nullable|exists:accounts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $accountId = $request->account_id;

        // Flujo de efectivo operativo
        $operatingInflows = $this->getTransactionsByConceptType('income', $startDate, $endDate, $accountId);
        $operatingOutflows = $this->getTransactionsByConceptType('expense', $startDate, $endDate, $accountId);
        
        // Flujo de efectivo de inversión
        $investmentInflows = $this->getTransactionsByConceptType('investment_income', $startDate, $endDate, $accountId);
        $investmentOutflows = $this->getTransactionsByConceptType('investment_expense', $startDate, $endDate, $accountId);
        
        // Flujo de efectivo de financiamiento
        $financingInflows = $this->getTransactionsByConceptType('financing_income', $startDate, $endDate, $accountId);
        $financingOutflows = $this->getTransactionsByConceptType('financing_expense', $startDate, $endDate, $accountId);

        $operatingCashFlow = $operatingInflows->sum('amount') - $operatingOutflows->sum('amount');
        $investmentCashFlow = $investmentInflows->sum('amount') - $investmentOutflows->sum('amount');
        $financingCashFlow = $financingInflows->sum('amount') - $financingOutflows->sum('amount');
        $netCashFlow = $operatingCashFlow + $investmentCashFlow + $financingCashFlow;

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d')
                ],
                'operating_activities' => [
                    'inflows' => $operatingInflows->sum('amount'),
                    'outflows' => $operatingOutflows->sum('amount'),
                    'net_cash_flow' => $operatingCashFlow
                ],
                'investment_activities' => [
                    'inflows' => $investmentInflows->sum('amount'),
                    'outflows' => $investmentOutflows->sum('amount'),
                    'net_cash_flow' => $investmentCashFlow
                ],
                'financing_activities' => [
                    'inflows' => $financingInflows->sum('amount'),
                    'outflows' => $financingOutflows->sum('amount'),
                    'net_cash_flow' => $financingCashFlow
                ],
                'net_cash_flow' => $netCashFlow
            ]
        ]);
    }

    /**
     * Obtener datos para gráficos financieros
     */
    public function chartData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:revenue_trend,expense_distribution,profit_trend,category_revenue',
            'period' => 'required|in:monthly,quarterly,yearly',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'account_id' => 'nullable|exists:accounts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $type = $request->type;
        $period = $request->period;
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->subMonths(6);
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now();
        $accountId = $request->account_id;

        switch ($type) {
            case 'revenue_trend':
                return $this->getRevenueTrendData($period, $startDate, $endDate, $accountId);
            case 'expense_distribution':
                return $this->getExpenseDistributionData($startDate, $endDate, $accountId);
            case 'profit_trend':
                return $this->getProfitTrendData($period, $startDate, $endDate, $accountId);
            case 'category_revenue':
                return $this->getCategoryRevenueData($startDate, $endDate, $accountId);
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid chart type'
                ], 400);
        }
    }

    /**
     * Obtener datos de tendencia de ingresos
     */
    private function getRevenueTrendData($period, $startDate, $endDate, $accountId = null): JsonResponse
    {
        $dateFormat = $period === 'monthly' ? '%Y-%m' : ($period === 'quarterly' ? '%Y-Q%q' : '%Y');
        $groupBy = $period === 'monthly' ? 'YEAR(transaction_date), MONTH(transaction_date)' : 
                  ($period === 'quarterly' ? 'YEAR(transaction_date), QUARTER(transaction_date)' : 'YEAR(transaction_date)');

        $query = Transaction::join('financial_concepts', 'transactions.concept_id', '=', 'financial_concepts.id')
            ->where('financial_concepts.type', 'income')
            ->where('transactions.status', 'approved')
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate]);

        if ($accountId) {
            $query->where('transactions.account_id', $accountId);
        }

        $data = $query->selectRaw(
                "DATE_FORMAT(transaction_date, '{$dateFormat}') as period, 
                 SUM(amount) as value"
            )
            ->groupByRaw($groupBy)
            ->orderBy('period')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->period,
                    'value' => (float) $item->value
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Obtener datos de distribución de gastos
     */
    private function getExpenseDistributionData($startDate, $endDate, $accountId = null): JsonResponse
    {
        $query = Transaction::join('financial_concepts', 'transactions.concept_id', '=', 'financial_concepts.id')
            ->where('financial_concepts.type', 'expense')
            ->where('transactions.status', 'approved')
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate]);

        if ($accountId) {
            $query->where('transactions.account_id', $accountId);
        }

        $data = $query->select('financial_concepts.name')
            ->selectRaw('SUM(transactions.amount) as value')
            ->groupBy('financial_concepts.id', 'financial_concepts.name')
            ->orderByDesc('value')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'value' => (float) $item->value
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Obtener datos de tendencia de ganancias
     */
    private function getProfitTrendData($period, $startDate, $endDate, $accountId = null): JsonResponse
    {
        $dateFormat = $period === 'monthly' ? '%Y-%m' : ($period === 'quarterly' ? '%Y-Q%q' : '%Y');
        $groupBy = $period === 'monthly' ? 'YEAR(transaction_date), MONTH(transaction_date)' : 
                  ($period === 'quarterly' ? 'YEAR(transaction_date), QUARTER(transaction_date)' : 'YEAR(transaction_date)');

        $baseQuery = Transaction::join('financial_concepts', 'transactions.concept_id', '=', 'financial_concepts.id')
            ->where('transactions.status', 'approved')
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate]);

        if ($accountId) {
            $baseQuery->where('transactions.account_id', $accountId);
        }

        // Ingresos por período
        $incomes = (clone $baseQuery)
            ->where('financial_concepts.type', 'income')
            ->selectRaw(
                "DATE_FORMAT(transaction_date, '{$dateFormat}') as period, 
                 SUM(amount) as income"
            )
            ->groupByRaw($groupBy)
            ->pluck('income', 'period');

        // Gastos por período
        $expenses = (clone $baseQuery)
            ->where('financial_concepts.type', 'expense')
            ->selectRaw(
                "DATE_FORMAT(transaction_date, '{$dateFormat}') as period, 
                 SUM(amount) as expenses"
            )
            ->groupByRaw($groupBy)
            ->pluck('expenses', 'period');

        // Combinar datos
        $periods = collect($incomes->keys())->merge($expenses->keys())->unique()->sort();
        $data = $periods->map(function ($period) use ($incomes, $expenses) {
            $income = $incomes->get($period, 0);
            $expense = $expenses->get($period, 0);
            return [
                'name' => $period,
                'income' => (float) $income,
                'expenses' => (float) $expense,
                'profit' => (float) ($income - $expense)
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Obtener datos de ingresos por categoría
     */
    private function getCategoryRevenueData($startDate, $endDate, $accountId = null): JsonResponse
    {
        $query = Transaction::join('financial_concepts', 'transactions.concept_id', '=', 'financial_concepts.id')
            ->where('financial_concepts.type', 'income')
            ->where('transactions.status', 'approved')
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate]);

        if ($accountId) {
            $query->where('transactions.account_id', $accountId);
        }

        $data = $query->select('financial_concepts.name')
            ->selectRaw('SUM(transactions.amount) as value')
            ->groupBy('financial_concepts.id', 'financial_concepts.name')
            ->orderByDesc('value')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'value' => (float) $item->value
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Generar Balance General (Balance Sheet)
     */
    public function balanceSheet(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'account_id' => 'nullable|exists:accounts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $date = Carbon::parse($request->date);
        $accountId = $request->account_id;

        // Activos
        $assets = $this->getBalanceByConceptType('asset', $date, $accountId);
        
        // Pasivos
        $liabilities = $this->getBalanceByConceptType('liability', $date, $accountId);
        
        // Patrimonio
        $equity = $this->getBalanceByConceptType('equity', $date, $accountId);

        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equity->sum('balance');

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date->format('Y-m-d'),
                'assets' => [
                    'total' => $totalAssets,
                    'details' => $assets->map(function ($item) {
                        return [
                            'concept' => $item->name,
                            'balance' => $item->balance
                        ];
                    })
                ],
                'liabilities' => [
                    'total' => $totalLiabilities,
                    'details' => $liabilities->map(function ($item) {
                        return [
                            'concept' => $item->name,
                            'balance' => $item->balance
                        ];
                    })
                ],
                'equity' => [
                    'total' => $totalEquity,
                    'details' => $equity->map(function ($item) {
                        return [
                            'concept' => $item->name,
                            'balance' => $item->balance
                        ];
                    })
                ],
                'total_liabilities_and_equity' => $totalLiabilities + $totalEquity,
                'balance_check' => $totalAssets === ($totalLiabilities + $totalEquity)
            ]
        ]);
    }

    /**
     * Obtener resumen de reportes
     */
    public function summary(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'account_id' => 'nullable|exists:accounts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $accountId = $request->account_id;

        // Métricas básicas
        $totalTransactions = Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->when($accountId, fn($q) => $q->where('account_id', $accountId))
            ->count();

        $totalIncome = $this->getTransactionsByConceptType('income', $startDate, $endDate, $accountId)->sum('amount');
        $totalExpenses = $this->getTransactionsByConceptType('expense', $startDate, $endDate, $accountId)->sum('amount');
        
        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d')
                ],
                'metrics' => [
                    'total_transactions' => $totalTransactions,
                    'total_income' => $totalIncome,
                    'total_expenses' => $totalExpenses,
                    'net_income' => $totalIncome - $totalExpenses,
                    'average_transaction' => $totalTransactions > 0 ? ($totalIncome + $totalExpenses) / $totalTransactions : 0
                ]
            ]
        ]);
    }

    /**
     * Método auxiliar para obtener transacciones por tipo de concepto
     */
    private function getTransactionsByConceptType(string $conceptType, Carbon $startDate, Carbon $endDate, ?int $accountId = null)
    {
        return Transaction::with('concept')
            ->whereHas('concept', function ($query) use ($conceptType) {
                $query->where('type', $conceptType);
            })
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->when($accountId, fn($q) => $q->where('account_id', $accountId))
            ->get();
    }

    /**
     * Exportar reporte a Excel
     */
    public function exportExcel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|in:income_statement,cash_flow,balance_sheet,summary',
            'start_date' => 'required_unless:report_type,balance_sheet|date',
            'end_date' => 'required_unless:report_type,balance_sheet|date|after_or_equal:start_date',
            'date' => 'required_if:report_type,balance_sheet|date',
            'account_id' => 'nullable|exists:accounts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $reportData = $this->getReportData($request);
        $filename = $this->generateFilename($request->report_type, 'xlsx');

        return Excel::download(
            new FinancialReportExport($reportData, $request->report_type),
            $filename
        );
    }

    /**
     * Exportar reporte a PDF
     */
    public function exportPdf(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|in:income_statement,cash_flow,balance_sheet,summary',
            'start_date' => 'required_unless:report_type,balance_sheet|date',
            'end_date' => 'required_unless:report_type,balance_sheet|date|after_or_equal:start_date',
            'date' => 'required_if:report_type,balance_sheet|date',
            'account_id' => 'nullable|exists:accounts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $reportData = $this->getReportData($request);
        $filename = $this->generateFilename($request->report_type, 'pdf');

        $pdf = Pdf::loadView('reports.financial', [
            'reportData' => $reportData,
            'reportType' => $request->report_type
        ]);

        return $pdf->download($filename);
    }

    /**
     * Exportar reporte a CSV
     */
    public function exportCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|in:income_statement,cash_flow,balance_sheet,summary',
            'start_date' => 'required_unless:report_type,balance_sheet|date',
            'end_date' => 'required_unless:report_type,balance_sheet|date|after_or_equal:start_date',
            'date' => 'required_if:report_type,balance_sheet|date',
            'account_id' => 'nullable|exists:accounts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $reportData = $this->getReportData($request);
        $filename = $this->generateFilename($request->report_type, 'csv');
        $csvData = $this->convertToCsv($reportData, $request->report_type);

        return response($csvData, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Obtener datos del reporte según el tipo
     */
    private function getReportData(Request $request): array
    {
        switch ($request->report_type) {
            case 'income_statement':
                $response = $this->incomeStatement($request);
                break;
            case 'cash_flow':
                $response = $this->cashFlow($request);
                break;
            case 'balance_sheet':
                $response = $this->balanceSheet($request);
                break;
            case 'summary':
                $response = $this->summary($request);
                break;
            default:
                throw new \InvalidArgumentException('Invalid report type');
        }

        return json_decode($response->getContent(), true)['data'];
    }

    /**
     * Generar nombre de archivo
     */
    private function generateFilename(string $reportType, string $extension): string
    {
        $reportNames = [
            'income_statement' => 'Estado_de_Resultados',
            'cash_flow' => 'Flujo_de_Caja',
            'balance_sheet' => 'Balance_General',
            'summary' => 'Resumen_Financiero'
        ];

        $reportName = $reportNames[$reportType] ?? 'Reporte_Financiero';
        $timestamp = now()->format('Y-m-d_H-i-s');

        return "{$reportName}_{$timestamp}.{$extension}";
    }

    /**
     * Convertir datos a formato CSV
     */
    private function convertToCsv(array $data, string $reportType): string
    {
        $csv = '';
        
        switch ($reportType) {
            case 'income_statement':
                $csv .= "Estado de Resultados\n";
                $csv .= "Período: {$data['period']['start_date']} - {$data['period']['end_date']}\n\n";
                
                $csv .= "INGRESOS\n";
                $csv .= "Concepto,Monto,Transacciones\n";
                foreach ($data['incomes']['details'] as $income) {
                    $csv .= "{$income['concept']},{$income['amount']},{$income['transactions_count']}\n";
                }
                $csv .= "Total Ingresos,{$data['incomes']['total']}\n\n";
                
                $csv .= "GASTOS\n";
                $csv .= "Concepto,Monto,Transacciones\n";
                foreach ($data['expenses']['details'] as $expense) {
                    $csv .= "{$expense['concept']},{$expense['amount']},{$expense['transactions_count']}\n";
                }
                $csv .= "Total Gastos,{$data['expenses']['total']}\n\n";
                
                $csv .= "Utilidad Neta,{$data['net_income']}\n";
                $csv .= "Margen de Utilidad,{$data['profit_margin']}%\n";
                break;
                
            case 'cash_flow':
                $csv .= "Flujo de Caja\n";
                $csv .= "Período: {$data['period']['start_date']} - {$data['period']['end_date']}\n\n";
                
                $csv .= "ACTIVIDADES OPERATIVAS\n";
                $csv .= "Entradas,{$data['operating_activities']['inflows']}\n";
                $csv .= "Salidas,{$data['operating_activities']['outflows']}\n";
                $csv .= "Flujo Neto,{$data['operating_activities']['net_cash_flow']}\n\n";
                
                $csv .= "ACTIVIDADES DE INVERSIÓN\n";
                $csv .= "Entradas,{$data['investment_activities']['inflows']}\n";
                $csv .= "Salidas,{$data['investment_activities']['outflows']}\n";
                $csv .= "Flujo Neto,{$data['investment_activities']['net_cash_flow']}\n\n";
                
                $csv .= "ACTIVIDADES DE FINANCIAMIENTO\n";
                $csv .= "Entradas,{$data['financing_activities']['inflows']}\n";
                $csv .= "Salidas,{$data['financing_activities']['outflows']}\n";
                $csv .= "Flujo Neto,{$data['financing_activities']['net_cash_flow']}\n\n";
                
                $csv .= "Flujo de Caja Neto,{$data['net_cash_flow']}\n";
                break;
                
            case 'balance_sheet':
                $csv .= "Balance General\n";
                $csv .= "Fecha: {$data['date']}\n\n";
                
                $csv .= "ACTIVOS\n";
                $csv .= "Concepto,Saldo\n";
                foreach ($data['assets']['details'] as $asset) {
                    $csv .= "{$asset['concept']},{$asset['balance']}\n";
                }
                $csv .= "Total Activos,{$data['assets']['total']}\n\n";
                
                $csv .= "PASIVOS\n";
                $csv .= "Concepto,Saldo\n";
                foreach ($data['liabilities']['details'] as $liability) {
                    $csv .= "{$liability['concept']},{$liability['balance']}\n";
                }
                $csv .= "Total Pasivos,{$data['liabilities']['total']}\n\n";
                
                $csv .= "PATRIMONIO\n";
                $csv .= "Concepto,Saldo\n";
                foreach ($data['equity']['details'] as $equity) {
                    $csv .= "{$equity['concept']},{$equity['balance']}\n";
                }
                $csv .= "Total Patrimonio,{$data['equity']['total']}\n\n";
                
                $csv .= "Total Pasivos + Patrimonio,{$data['total_liabilities_and_equity']}\n";
                $csv .= "Balance Cuadrado," . ($data['balance_check'] ? 'Sí' : 'No') . "\n";
                break;
                
            case 'summary':
                $csv .= "Resumen Financiero\n";
                $csv .= "Período: {$data['period']['start_date']} - {$data['period']['end_date']}\n\n";
                
                $csv .= "MÉTRICAS\n";
                $csv .= "Total Transacciones,{$data['metrics']['total_transactions']}\n";
                $csv .= "Total Ingresos,{$data['metrics']['total_income']}\n";
                $csv .= "Total Gastos,{$data['metrics']['total_expenses']}\n";
                $csv .= "Utilidad Neta,{$data['metrics']['net_income']}\n";
                $csv .= "Transacción Promedio,{$data['metrics']['average_transaction']}\n";
                break;
        }
        
        return $csv;
    }

    /**
     * Método auxiliar para obtener balance por tipo de concepto
     */
    private function getBalanceByConceptType(string $conceptType, Carbon $date, ?int $accountId = null)
    {
        return FinancialConcept::where('type', $conceptType)
            ->with(['transactions' => function ($query) use ($date, $accountId) {
                $query->where('transaction_date', '<=', $date)
                    ->when($accountId, fn($q) => $q->where('account_id', $accountId));
            }])
            ->get()
            ->map(function ($concept) {
                $concept->balance = $concept->transactions->sum('amount');
                return $concept;
            });
    }
}
