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
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->string('timezone')->default('UTC');
            $table->boolean('is_all_day')->default(false);
            $table->enum('status', ['confirmed', 'tentative', 'cancelled'])->default('confirmed');
            $table->enum('visibility', ['public', 'private', 'confidential'])->default('private');
            $table->unsignedBigInteger('calendar_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('external_id')->nullable(); // For Google/Outlook integration
            $table->string('external_provider')->nullable(); // google, outlook, etc.
            $table->json('attendees')->nullable(); // JSON field for attendees
            $table->json('recurrence_rule')->nullable(); // JSON field for recurrence rules
            $table->string('recurrence_id')->nullable(); // For recurring events
            $table->json('reminders')->nullable(); // JSON field for reminders
            $table->json('metadata')->nullable(); // Additional metadata
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('calendar_id')->references('id')->on('calendars')->onDelete('cascade');
            
            // Indexes
            $table->index(['calendar_id', 'start_date']);
            $table->index(['start_date', 'end_date']);
            $table->index(['status', 'visibility']);
            $table->index('external_id');
            $table->index('recurrence_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
