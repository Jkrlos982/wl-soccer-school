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
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('pay_date');
            $table->enum('period_type', ['weekly', 'biweekly', 'monthly', 'quarterly']);
            $table->enum('status', ['draft', 'processing', 'approved', 'paid', 'closed'])->default('draft');
            $table->integer('year');
            $table->integer('month');
            $table->integer('period_number');
            $table->decimal('total_gross', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('total_net', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
            
            $table->index(['status']);
            $table->index(['start_date', 'end_date']);
            $table->index(['year', 'month']);
            $table->index(['period_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};
