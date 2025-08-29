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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('user_type'); // User, Player, Parent
            $table->unsignedBigInteger('user_id');
            $table->string('category'); // payment_reminder, training_reminder, etc.
            $table->boolean('whatsapp_enabled')->default(true);
            $table->boolean('email_enabled')->default(true);
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('push_enabled')->default(true);
            $table->json('schedule_preferences')->nullable(); // Horarios preferidos
            $table->timestamps();
            
            $table->unique(['school_id', 'user_type', 'user_id', 'category'], 'unique_user_category_preference');
            $table->index(['school_id', 'user_type', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};