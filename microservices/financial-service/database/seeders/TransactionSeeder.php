<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\FinancialConcept;
use App\Models\Account;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get some financial concepts and accounts for testing
        $incomeConcept = FinancialConcept::where('type', 'income')->first();
        $expenseConcept = FinancialConcept::where('type', 'expense')->first();
        $account = Account::first();
        
        if (!$incomeConcept || !$expenseConcept || !$account) {
            $this->command->warn('Please run FinancialConceptSeeder and create accounts first.');
            return;
        }

        $transactions = [
            [
                'school_id' => 1,
                'financial_concept_id' => $incomeConcept->id,
                'reference_number' => '2025-01-ING-0001',
                'description' => 'Matrícula estudiante Juan Pérez',
                'amount' => 500000.00,
                'transaction_date' => Carbon::now()->subDays(5),
                'status' => 'pending',
                'payment_method' => 'bank_transfer',
                'created_by' => 1,
                'metadata' => [
                    'student_id' => 123,
                    'student_name' => 'Juan Pérez',
                    'academic_period' => '2025-1'
                ]
            ],
            [
                'school_id' => 1,
                'financial_concept_id' => $incomeConcept->id,
                'reference_number' => '2025-01-ING-0002',
                'description' => 'Pensión mensual María García',
                'amount' => 300000.00,
                'transaction_date' => Carbon::now()->subDays(3),
                'status' => 'approved',
                'payment_method' => 'cash',
                'created_by' => 1,
                'approved_by' => 2,
                'approved_at' => Carbon::now()->subDays(2),
                'approval_notes' => 'Pago verificado y aprobado',
                'metadata' => [
                    'student_id' => 124,
                    'student_name' => 'María García',
                    'payment_period' => '2025-01'
                ]
            ],
            [
                'school_id' => 1,
                'financial_concept_id' => $expenseConcept->id,
                'reference_number' => '2025-01-GAS-0001',
                'description' => 'Compra de material didáctico',
                'amount' => 150000.00,
                'transaction_date' => Carbon::now()->subDays(1),
                'status' => 'pending',
                'payment_method' => 'credit_card',
                'created_by' => 1,
                'metadata' => [
                    'supplier' => 'Papelería Escolar S.A.S',
                    'invoice_number' => 'FAC-2025-001'
                ]
            ],
            [
                'school_id' => 1,
                'financial_concept_id' => $expenseConcept->id,
                'reference_number' => '2025-01-GAS-0002',
                'description' => 'Pago servicios públicos enero',
                'amount' => 250000.00,
                'transaction_date' => Carbon::now(),
                'status' => 'rejected',
                'payment_method' => 'bank_transfer',
                'created_by' => 1,
                'approved_by' => 2,
                'approved_at' => Carbon::now()->subHours(2),
                'approval_notes' => 'Falta documentación de soporte',
                'metadata' => [
                    'service_type' => 'utilities',
                    'period' => '2025-01'
                ]
            ]
        ];

        foreach ($transactions as $transactionData) {
            $transaction = Transaction::create($transactionData);
            
            // Associate with account (for demonstration)
            $transaction->accounts()->attach($account->id, [
                'type' => $transaction->financialConcept->type === 'income' ? 'credit' : 'debit',
                'amount' => $transaction->amount
            ]);
        }

        $this->command->info('Transaction seeder completed successfully!');
    }
}
