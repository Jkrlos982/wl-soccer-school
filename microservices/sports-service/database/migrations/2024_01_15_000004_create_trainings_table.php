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
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('category_id');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location');
            $table->enum('type', ['training', 'match', 'friendly', 'tournament']);
            $table->text('objectives')->nullable();
            $table->text('activities')->nullable();
            $table->text('observations')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->unsignedBigInteger('coach_id');
            $table->json('weather_conditions')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->timestamps();
            
            $table->foreign('school_id')->references('id')->on('schools');
            $table->foreign('category_id')->references('id')->on('categories');
            $table->foreign('coach_id')->references('id')->on('users');
            $table->index(['school_id', 'date']);
            $table->index(['category_id', 'date']);
            $table->index(['coach_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};