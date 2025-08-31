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
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('review_period');
            $table->date('review_date');
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('review_type', ['annual', 'semi_annual', 'quarterly', 'probationary', 'special']);
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->enum('performance_rating', ['excellent', 'good', 'satisfactory', 'needs_improvement', 'unsatisfactory'])->nullable();
            $table->json('goals_achieved')->nullable();
            $table->json('areas_for_improvement')->nullable();
            $table->text('manager_comments')->nullable();
            $table->text('employee_comments')->nullable();
            $table->decimal('salary_adjustment', 15, 2)->default(0);
            $table->decimal('bonus_amount', 15, 2)->default(0);
            $table->enum('status', ['draft', 'pending_employee', 'pending_manager', 'completed'])->default('draft');
            $table->unsignedBigInteger('reviewer_id')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->index(['employee_id']);
            $table->index(['review_date']);
            $table->index(['review_type']);
            $table->index(['status']);
            $table->index(['performance_rating']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};
