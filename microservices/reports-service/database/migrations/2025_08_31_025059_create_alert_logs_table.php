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
        Schema::create('alert_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('alert_id');
            $table->enum('event_type', ['triggered', 'resolved', 'acknowledged', 'escalated']);
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->text('message');
            $table->json('trigger_data')->nullable(); // Data that triggered the alert
            $table->json('notification_sent')->nullable(); // Notification details
            $table->boolean('is_acknowledged')->default(false);
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('alert_id')->references('id')->on('alerts')->onDelete('cascade');
            $table->index(['alert_id', 'event_type']);
            $table->index(['event_type', 'created_at']);
            $table->index('acknowledged_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_logs');
    }
};
