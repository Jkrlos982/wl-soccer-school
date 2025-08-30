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
        Schema::create('event_invitations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->string('email');
            $table->string('name')->nullable();
            $table->unsignedBigInteger('user_id')->nullable(); // If the invitee is a registered user
            $table->enum('status', ['pending', 'accepted', 'declined', 'tentative'])->default('pending');
            $table->enum('role', ['attendee', 'organizer', 'optional'])->default('attendee');
            $table->string('invitation_token')->unique();
            $table->timestamp('invited_at');
            $table->timestamp('responded_at')->nullable();
            $table->text('response_message')->nullable();
            $table->json('notification_preferences')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('event_id')->references('id')->on('calendar_events')->onDelete('cascade');
            
            // Indexes
            $table->index(['event_id', 'status']);
            $table->index(['email', 'status']);
            $table->index('user_id');
            $table->index('invitation_token');
            $table->unique(['event_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_invitations');
    }
};
