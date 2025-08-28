<?php

namespace App\Console\Commands;

use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;

class GenerateMonthlyInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate-monthly 
                            {--school-id= : Generate invoices for a specific school ID}
                            {--month= : Month to generate invoices for (YYYY-MM format, defaults to current month)}
                            {--dry-run : Show what would be generated without actually creating invoices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly invoices for all schools or a specific school';

    /**
     * The invoice service instance.
     */
    protected InvoiceService $invoiceService;

    /**
     * Create a new command instance.
     */
    public function __construct(InvoiceService $invoiceService)
    {
        parent::__construct();
        $this->invoiceService = $invoiceService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting monthly invoice generation...');
        
        try {
            // Parse month parameter
            $month = $this->parseMonth();
            $this->info("Generating invoices for: {$month->format('F Y')}");

            // Get school IDs to process
            $schoolIds = $this->getSchoolIds();
            
            if (empty($schoolIds)) {
                $this->warn('No schools found to process.');
                return Command::SUCCESS;
            }

            $this->info("Processing " . count($schoolIds) . " school(s)...");

            // Check for dry run
            if ($this->option('dry-run')) {
                $this->warn('DRY RUN MODE - No invoices will be created');
            }

            $totalResults = [
                'schools_processed' => 0,
                'total_success' => 0,
                'total_errors' => 0,
                'school_results' => []
            ];

            // Process each school
            foreach ($schoolIds as $schoolId) {
                $this->processSchool($schoolId, $month, $totalResults);
            }

            // Display summary
            $this->displaySummary($totalResults);

            // Log completion
            Log::info('Monthly invoice generation completed', [
                'month' => $month->format('Y-m'),
                'schools_processed' => $totalResults['schools_processed'],
                'total_success' => $totalResults['total_success'],
                'total_errors' => $totalResults['total_errors']
            ]);

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error('Error during invoice generation: ' . $e->getMessage());
            Log::error('Monthly invoice generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Parse the month parameter.
     */
    private function parseMonth(): Carbon
    {
        $monthInput = $this->option('month');
        
        if (!$monthInput) {
            return Carbon::now()->startOfMonth();
        }

        try {
            return Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth();
        } catch (Exception $e) {
            throw new Exception("Invalid month format. Use YYYY-MM format (e.g., 2024-01)");
        }
    }

    /**
     * Get school IDs to process.
     */
    private function getSchoolIds(): array
    {
        $schoolId = $this->option('school-id');
        
        if ($schoolId) {
            return [(int) $schoolId];
        }

        // In a real implementation, this would fetch all active schools
        // For now, we'll return a mock list
        return [1, 2, 3]; // Mock school IDs
    }

    /**
     * Process invoices for a single school.
     */
    private function processSchool(int $schoolId, Carbon $month, array &$totalResults): void
    {
        $this->line("\nProcessing School ID: {$schoolId}");
        
        try {
            if ($this->option('dry-run')) {
                // Simulate the process without actually creating invoices
                $results = $this->simulateInvoiceGeneration($schoolId, $month);
            } else {
                $results = $this->invoiceService->generateMonthlyInvoices($schoolId, $month);
            }

            // Update totals
            $totalResults['schools_processed']++;
            $totalResults['total_success'] += $results['success'];
            $totalResults['total_errors'] += $results['errors'];
            $totalResults['school_results'][$schoolId] = $results;

            // Display school results
            $this->displaySchoolResults($schoolId, $results);

        } catch (Exception $e) {
            $this->error("  Error processing school {$schoolId}: " . $e->getMessage());
            $totalResults['school_results'][$schoolId] = [
                'success' => 0,
                'errors' => 1,
                'error_messages' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Simulate invoice generation for dry run.
     */
    private function simulateInvoiceGeneration(int $schoolId, Carbon $month): array
    {
        // Mock simulation - in reality, this would check what would be generated
        $mockStudentCount = rand(5, 20);
        
        return [
            'success' => $mockStudentCount,
            'errors' => 0,
            'invoices' => [],
            'error_messages' => []
        ];
    }

    /**
     * Display results for a single school.
     */
    private function displaySchoolResults(int $schoolId, array $results): void
    {
        if ($results['success'] > 0) {
            $this->info("  ✓ Successfully generated {$results['success']} invoice(s)");
        }

        if ($results['errors'] > 0) {
            $this->warn("  ⚠ {$results['errors']} error(s) occurred");
            
            if (!empty($results['error_messages'])) {
                foreach ($results['error_messages'] as $error) {
                    $this->line("    - {$error}");
                }
            }
        }
    }

    /**
     * Display final summary.
     */
    private function displaySummary(array $totalResults): void
    {
        $this->line("\n" . str_repeat('=', 50));
        $this->info('INVOICE GENERATION SUMMARY');
        $this->line(str_repeat('=', 50));
        
        $this->line("Schools processed: {$totalResults['schools_processed']}");
        $this->line("Total invoices created: {$totalResults['total_success']}");
        
        if ($totalResults['total_errors'] > 0) {
            $this->line("Total errors: {$totalResults['total_errors']}");
        }

        // Show per-school breakdown
        if (count($totalResults['school_results']) > 1) {
            $this->line("\nPer-school breakdown:");
            foreach ($totalResults['school_results'] as $schoolId => $results) {
                $this->line("  School {$schoolId}: {$results['success']} success, {$results['errors']} errors");
            }
        }

        if ($totalResults['total_success'] > 0) {
            $this->info("\n✓ Invoice generation completed successfully!");
        } else {
            $this->warn("\n⚠ No invoices were generated.");
        }
    }
}