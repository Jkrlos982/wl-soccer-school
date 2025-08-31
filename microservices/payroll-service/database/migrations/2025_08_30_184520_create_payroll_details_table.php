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
        Schema::create('payroll_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_id');
            $table->unsignedBigInteger('payroll_concept_id');
            $table->decimal('amount', 15, 2);
            $table->decimal('base_amount', 15, 2)->nullable();
            $table->decimal('rate', 15, 4)->nullable();
            $table->integer('quantity')->default(1);
            $table->text('calculation_details')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
            $table->foreign('payroll_concept_id')->references('id')->on('payroll_concepts')->onDelete('cascade');
            $table->index(['payroll_id']);
            $table->index(['payroll_concept_id']);
            $table->unique(['payroll_id', 'payroll_concept_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_details');
    }
};
