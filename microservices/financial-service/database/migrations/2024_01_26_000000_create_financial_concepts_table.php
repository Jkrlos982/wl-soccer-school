<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinancialConceptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('financial_concepts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('code')->unique();
            $table->enum('type', ['income', 'expense']);
            $table->string('category');
            $table->unsignedBigInteger('template_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            $table->index(['school_id', 'type']);
            $table->index(['school_id', 'is_active']);
            $table->index(['type', 'category']);
            $table->index(['is_default', 'is_active']);
            
            $table->foreign('template_id')->references('id')->on('concept_templates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('financial_concepts');
    }
}