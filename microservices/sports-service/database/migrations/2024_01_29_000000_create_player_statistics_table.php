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
        Schema::create('player_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('training_id')->nullable();
            $table->unsignedBigInteger('match_id')->nullable();
            $table->date('date');
            $table->enum('context', ['training', 'match', 'friendly']);
            
            // Estadísticas de entrenamiento
            $table->integer('minutes_played')->default(0);
            $table->integer('goals_scored')->default(0);
            $table->integer('assists')->default(0);
            $table->integer('shots_on_target')->default(0);
            $table->integer('shots_off_target')->default(0);
            $table->integer('passes_completed')->default(0);
            $table->integer('passes_attempted')->default(0);
            $table->integer('tackles_won')->default(0);
            $table->integer('tackles_lost')->default(0);
            $table->integer('interceptions')->default(0);
            $table->integer('fouls_committed')->default(0);
            $table->integer('fouls_received')->default(0);
            $table->integer('yellow_cards')->default(0);
            $table->integer('red_cards')->default(0);
            
            // Estadísticas específicas por posición
            $table->integer('saves')->default(0); // Portero
            $table->integer('goals_conceded')->default(0); // Portero
            $table->integer('clean_sheets')->default(0); // Portero
            $table->integer('crosses_completed')->default(0); // Laterales/Extremos
            $table->integer('dribbles_successful')->default(0); // Atacantes/Extremos
            $table->integer('aerial_duels_won')->default(0); // Defensas/Delanteros
            
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->timestamps();
            
            $table->foreign('school_id')->references('id')->on('schools');
            $table->foreign('player_id')->references('id')->on('players');
            $table->foreign('training_id')->references('id')->on('trainings');
            $table->foreign('recorded_by')->references('id')->on('users');
            $table->index(['school_id', 'date']);
            $table->index(['player_id', 'date']);
            $table->index(['player_id', 'context']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_statistics');
    }
};