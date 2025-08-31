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
        Schema::create('payroll_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['payroll_summary', 'detailed_payroll', 'tax_report', 'attendance_report', 'benefits_report']);
            $table->unsignedBigInteger('payroll_period_id')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->json('filters')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->enum('format', ['pdf', 'excel', 'csv'])->default('pdf');
            $table->enum('status', ['generating', 'completed', 'failed'])->default('generating');
            $table->unsignedBigInteger('generated_by');
            $table->timestamp('generated_at')->nullable();
            $table->integer('total_records')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->foreign('payroll_period_id')->references('id')->on('payroll_periods')->onDelete('set null');
            $table->foreign('generated_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['type']);
            $table->index(['status']);
            $table->index(['generated_at']);
            $table->index(['payroll_period_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_reports');
    }
};
