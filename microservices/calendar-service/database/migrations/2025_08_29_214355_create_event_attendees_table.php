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
        Schema::create('event_attendees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->string('attendee_type'); // User, Player, Coach, Parent
            $table->unsignedBigInteger('attendee_id');
            $table->string('attendee_name');
            $table->string('attendee_email')->nullable();
            $table->string('attendee_phone')->nullable();
            
            // Estado de participación
            $table->enum('status', ['pending', 'accepted', 'declined', 'tentative'])->default('pending');
            $table->enum('role', ['organizer', 'required', 'optional', 'resource'])->default('required');
            $table->boolean('is_organizer')->default(false);
            
            // Confirmación y asistencia
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->text('response_comment')->nullable();
            
            // Notificaciones
            $table->boolean('send_reminders')->default(true);
            $table->timestamp('last_reminder_sent')->nullable();
            
            $table->timestamps();
            
            $table->foreign('event_id')->references('id')->on('calendar_events')->onDelete('cascade');
            $table->unique(['event_id', 'attendee_type', 'attendee_id'], 'unique_event_attendee');
            $table->index(['event_id', 'status']);
            $table->index(['attendee_type', 'attendee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_attendees');
    }
};
