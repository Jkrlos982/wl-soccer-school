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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('training_id');
            $table->unsignedBigInteger('player_id');
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late', 'excused', 'pending'])
                  ->default('pending');
            $table->time('arrival_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('school_id')
                  ->references('id')
                  ->on('schools')
                  ->onDelete('cascade');
                  
            $table->foreign('training_id')
                  ->references('id')
                  ->on('trainings')
                  ->onDelete('cascade');
                  
            $table->foreign('player_id')
                  ->references('id')
                  ->on('players')
                  ->onDelete('cascade');
            
            // Indexes for better performance
            $table->index(['training_id', 'status']);
            $table->index(['player_id', 'date']);
            $table->index(['school_id', 'date']);
            $table->index('date');
            
            // Unique constraint to prevent duplicate attendance records
            $table->unique(['training_id', 'player_id'], 'unique_training_player_attendance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};