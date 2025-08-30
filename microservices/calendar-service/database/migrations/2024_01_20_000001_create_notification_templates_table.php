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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type'); // 'event_reminder', 'birthday_reminder', etc.
            $table->string('channel'); // 'email', 'sms', 'push', 'webhook'
            $table->string('subject')->nullable();
            $table->text('body');
            $table->json('variables')->nullable(); // Available template variables
            $table->boolean('is_active')->default(true);
            $table->string('language', 5)->default('es'); // Language code
            $table->integer('priority')->default(1); // Template priority
            $table->timestamps();
            
            $table->index(['type', 'channel']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};