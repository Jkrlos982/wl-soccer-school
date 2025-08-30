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
        Schema::create('calendar_integrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('calendar_id');
            $table->enum('provider', ['google', 'outlook', 'apple', 'caldav']);
            $table->string('external_calendar_id');
            $table->string('access_token')->nullable();
            $table->string('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('provider_settings')->nullable(); // Provider-specific settings
            $table->boolean('is_active')->default(true);
            $table->boolean('sync_enabled')->default(true);
            $table->enum('sync_direction', ['import', 'export', 'bidirectional'])->default('bidirectional');
            $table->timestamp('last_sync_at')->nullable();
            $table->json('sync_status')->nullable(); // Sync status and errors
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('calendar_id')->references('id')->on('calendars')->onDelete('cascade');
            
            // Indexes
            $table->index(['user_id', 'provider']);
            $table->index(['calendar_id', 'is_active']);
            $table->index('external_calendar_id');
            $table->unique(['user_id', 'provider', 'external_calendar_id'], 'cal_int_user_provider_ext_cal_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_integrations');
    }
};
