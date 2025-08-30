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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('notification_id')->unique(); // UUID for tracking
            $table->unsignedBigInteger('event_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->string('type'); // 'event_reminder', 'birthday_reminder'
            $table->string('channel'); // 'email', 'sms', 'push', 'webhook'
            $table->string('recipient'); // Email, phone, etc.
            $table->string('status'); // 'pending', 'sent', 'failed', 'delivered'
            $table->text('subject')->nullable();
            $table->text('content')->nullable();
            $table->json('metadata')->nullable(); // Additional data
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            
            $table->index(['event_id']);
            $table->index(['user_id']);
            $table->index(['school_id']);
            $table->index(['type', 'status']);
            $table->index(['channel', 'status']);
            $table->index(['created_at']);
            $table->index(['sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};