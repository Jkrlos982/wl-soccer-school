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
        Schema::create('player_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('evaluator_id'); // Coach/Staff
            $table->unsignedBigInteger('training_id')->nullable();
            $table->date('evaluation_date');
            $table->enum('evaluation_type', ['training', 'match', 'monthly', 'semester']);
            
            // Aspectos técnicos (1-10)
            $table->integer('technical_skills')->nullable();
            $table->integer('ball_control')->nullable();
            $table->integer('passing')->nullable();
            $table->integer('shooting')->nullable();
            $table->integer('dribbling')->nullable();
            
            // Aspectos físicos (1-10)
            $table->integer('speed')->nullable();
            $table->integer('endurance')->nullable();
            $table->integer('strength')->nullable();
            $table->integer('agility')->nullable();
            
            // Aspectos tácticos (1-10)
            $table->integer('positioning')->nullable();
            $table->integer('decision_making')->nullable();
            $table->integer('teamwork')->nullable();
            $table->integer('game_understanding')->nullable();
            
            // Aspectos mentales/actitudinales (1-10)
            $table->integer('attitude')->nullable();
            $table->integer('discipline')->nullable();
            $table->integer('leadership')->nullable();
            $table->integer('commitment')->nullable();
            
            $table->decimal('overall_rating', 3, 1)->nullable(); // Promedio general
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('goals_next_period')->nullable();
            $table->text('coach_comments')->nullable();
            $table->json('custom_metrics')->nullable(); // Métricas personalizables
            
            $table->timestamps();
            
            $table->foreign('school_id')->references('id')->on('schools');
            $table->foreign('player_id')->references('id')->on('players');
            $table->foreign('evaluator_id')->references('id')->on('users');
            $table->foreign('training_id')->references('id')->on('trainings');
            $table->index(['school_id', 'evaluation_date']);
            $table->index(['player_id', 'evaluation_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_evaluations');
    }
};