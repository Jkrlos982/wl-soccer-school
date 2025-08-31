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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['threshold', 'anomaly', 'trend', 'custom']);
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->enum('status', ['active', 'inactive', 'triggered', 'resolved']);
            $table->json('conditions'); // Alert conditions and rules
            $table->json('data_source'); // Data source to monitor
            $table->json('notification_config'); // Notification settings
            $table->json('recipients'); // Who to notify
            $table->integer('check_interval')->default(300); // Check frequency in seconds
            $table->integer('cooldown_period')->default(3600); // Cooldown in seconds
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('trigger_count')->default(0);
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index(['severity', 'status']);
            $table->index(['status', 'last_checked_at']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
