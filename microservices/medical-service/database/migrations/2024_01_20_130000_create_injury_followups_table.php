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
        Schema::create('injury_followups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('injury_id');
            $table->unsignedBigInteger('school_id');
            
            // Información del seguimiento
            $table->date('followup_date');
            $table->time('followup_time')->nullable();
            $table->enum('followup_type', ['medical', 'physiotherapy', 'training', 'assessment']);
            $table->string('conducted_by'); // Profesional que realizó el seguimiento
            $table->string('location')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'rescheduled'])->default('scheduled');
            $table->unsignedBigInteger('player_id');
            
            // Evaluación física
            $table->integer('pain_level')->nullable(); // 0-10 scale
            $table->text('mobility_assessment')->nullable();
            $table->text('strength_assessment')->nullable();
            $table->json('functional_tests')->nullable();
            $table->enum('progress_evaluation', ['improved', 'same', 'worsened', 'significantly_improved'])->nullable();
            $table->text('observations');
            $table->json('recommendations')->nullable();
            $table->date('next_followup_date')->nullable();
            $table->json('treatment_modifications')->nullable();
            $table->json('medication_changes')->nullable();
            $table->text('therapy_progress')->nullable();
            $table->enum('return_to_activity_level', ['none', 'light', 'moderate', 'full'])->default('none');
            $table->enum('clearance_status', ['not_cleared', 'partially_cleared', 'cleared'])->default('not_cleared');
            $table->json('attachments')->nullable();
            $table->text('notes')->nullable();
            
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            $table->foreign('injury_id')->references('id')->on('injuries')->onDelete('cascade');
            
            $table->index(['injury_id', 'followup_date']);
            $table->index(['school_id', 'followup_date']);
            $table->index(['followup_type', 'followup_date']);
            $table->index(['next_followup_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('injury_followups');
    }
};