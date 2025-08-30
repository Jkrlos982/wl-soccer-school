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
        Schema::create('injuries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('medical_record_id');
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('player_id');
            
            // Información de la lesión
            $table->string('injury_code')->unique(); // Código único de la lesión
            $table->string('injury_type'); // muscle, bone, ligament, etc.
            $table->string('body_part'); // knee, ankle, shoulder, etc.
            $table->string('severity'); // mild, moderate, severe
            $table->text('description');
            
            // Circunstancias de la lesión
            $table->datetime('injury_datetime');
            $table->string('injury_location')->nullable(); // Dónde ocurrió
            $table->enum('injury_context', ['training', 'match', 'other']);
            $table->text('injury_mechanism')->nullable(); // Cómo ocurrió
            $table->boolean('witnessed')->default(false);
            $table->json('witnesses')->nullable(); // Testigos
            
            // Diagnóstico y tratamiento
            $table->string('diagnosis')->nullable();
            $table->string('diagnosed_by')->nullable(); // Médico que diagnosticó
            $table->date('diagnosis_date')->nullable();
            $table->json('treatment_plan')->nullable();
            $table->json('medications_prescribed')->nullable();
            $table->boolean('requires_surgery')->default(false);
            $table->date('surgery_date')->nullable();
            
            // Recuperación y seguimiento
            $table->enum('status', ['active', 'recovering', 'recovered', 'chronic'])->default('active');
            $table->integer('estimated_recovery_days')->nullable();
            $table->date('expected_return_date')->nullable();
            $table->date('actual_return_date')->nullable();
            $table->boolean('cleared_to_play')->default(false);
            $table->date('clearance_date')->nullable();
            $table->string('cleared_by')->nullable(); // Médico que dio el alta
            
            // Prevención y recomendaciones
            $table->json('prevention_measures')->nullable();
            $table->text('return_to_play_protocol')->nullable();
            $table->boolean('requires_monitoring')->default(true);
            $table->json('monitoring_schedule')->nullable();
            
            // Impacto en el rendimiento
            $table->integer('training_days_missed')->default(0);
            $table->integer('matches_missed')->default(0);
            $table->decimal('performance_impact_percentage', 5, 2)->nullable();
            
            // Archivos y documentación
            $table->json('medical_reports')->nullable();
            $table->json('imaging_studies')->nullable(); // Radiografías, resonancias, etc.
            $table->json('progress_photos')->nullable();
            
            $table->unsignedBigInteger('reported_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            $table->foreign('medical_record_id')->references('id')->on('medical_records')->onDelete('cascade');
            
            $table->index(['school_id', 'injury_datetime']);
            $table->index(['player_id', 'status']);
            $table->index(['injury_type', 'body_part']);
            $table->index(['status', 'expected_return_date']);
            $table->index(['cleared_to_play']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('injuries');
    }
};