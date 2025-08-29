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
            $table->unsignedBigInteger('notification_id');
            $table->enum('event', [
                'created', 'queued', 'sending', 'sent', 'delivered', 
                'read', 'failed', 'retry', 'cancelled'
            ]);
            $table->text('description')->nullable();
            $table->json('data')->nullable(); // Datos adicionales del evento
            $table->timestamp('occurred_at');
            $table->timestamps();
            
            $table->foreign('notification_id')->references('id')->on('notifications')->onDelete('cascade');
            $table->index(['notification_id', 'event']);
            $table->index(['notification_id', 'occurred_at']);
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