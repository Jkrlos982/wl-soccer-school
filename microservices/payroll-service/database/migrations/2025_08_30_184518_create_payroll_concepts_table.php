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
        Schema::create('payroll_concepts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['earning', 'deduction', 'tax', 'benefit']);
            $table->enum('calculation_type', ['fixed', 'percentage', 'formula']);
            $table->decimal('default_value', 15, 4)->nullable();
            $table->text('formula')->nullable();
            $table->boolean('is_taxable')->default(true);
            $table->boolean('affects_social_security')->default(true);
            $table->boolean('is_mandatory')->default(false);
            $table->integer('display_order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['type']);
            $table->index(['status']);
            $table->index(['code']);
            $table->index(['display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_concepts');
    }
};
