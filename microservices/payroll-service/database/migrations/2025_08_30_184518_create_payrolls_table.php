<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('payroll_period_id');
            $table->decimal('base_salary', 15, 2);
            $table->decimal('gross_salary', 15, 2);
            $table->decimal('total_earnings', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('total_taxes', 15, 2)->default(0);
            $table->decimal('employer_contributions', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2);
            $table->integer('worked_days')->default(0);
            $table->decimal('worked_hours', 8, 2)->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('overtime_amount', 15, 2)->default(0);
            $table->enum('status', ['draft', 'calculated', 'approved', 'paid', 'rechazada'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('payroll_period_id')->references('id')->on('payroll_periods')->onDelete('cascade');
            $table->index(['employee_id']);
            $table->index(['payroll_period_id']);
            $table->index(['status']);
            $table->unique(['employee_id', 'payroll_period_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
