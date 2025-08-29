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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name'); // Sub-8, Sub-10, Sub-12, etc.
            $table->text('description')->nullable();
            $table->integer('min_age');
            $table->integer('max_age');
            $table->enum('gender', ['male', 'female', 'mixed']);
            $table->integer('max_players')->default(25);
            $table->json('training_days'); // ["monday", "wednesday", "friday"]
            $table->time('training_start_time');
            $table->time('training_end_time');
            $table->string('field_location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('coach_id')->nullable();
            $table->timestamps();
            
            $table->foreign('school_id')->references('id')->on('schools');
            $table->foreign('coach_id')->references('id')->on('users');
            $table->index(['school_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};