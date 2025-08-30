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
        Schema::create('processed_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('school_id')->nullable();
            $table->string('reminder_type'); // 'event_reminder', 'birthday_reminder'
            $table->integer('minutes_before'); // Minutes before event
            $table->string('status'); // 'sent', 'failed', 'skipped'
            $table->timestamp('scheduled_for'); // When it was scheduled to be sent
            $table->timestamp('processed_at'); // When it was actually processed
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Additional processing data
            $table->timestamps();
            
            // Composite unique index to prevent duplicate reminders
            $table->unique(['event_id', 'user_id', 'reminder_type', 'minutes_before'], 'unique_reminder');
            
            $table->index(['event_id']);
            $table->index(['user_id']);
            $table->index(['school_id']);
            $table->index(['reminder_type']);
            $table->index(['status']);
            $table->index(['scheduled_for']);
            $table->index(['processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processed_reminders');
    }
};