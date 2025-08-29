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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('template_id')->nullable();
            $table->enum('type', ['whatsapp', 'email', 'sms', 'push', 'system']);
            $table->enum('category', [
                'payment_reminder', 'payment_confirmation', 'training_reminder',
                'attendance_alert', 'evaluation_ready', 'general_announcement',
                'birthday_greeting', 'welcome_message', 'account_created',
                'password_reset', 'invoice_generated', 'custom'
            ]);
            
            // Destinatario
            $table->string('recipient_type'); // User, Player, Parent, Coach, etc.
            $table->unsignedBigInteger('recipient_id');
            $table->string('recipient_phone')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('recipient_name');
            
            // Contenido
            $table->string('subject')->nullable();
            $table->text('content');
            $table->json('variables')->nullable(); // Variables utilizadas
            $table->json('media_urls')->nullable();
            
            // Estado y seguimiento
            $table->enum('status', [
                'pending', 'queued', 'sending', 'sent', 'delivered', 
                'read', 'failed', 'cancelled'
            ])->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            
            // Información del proveedor
            $table->string('provider')->nullable(); // whatsapp, twilio, pusher, etc.
            $table->string('provider_message_id')->nullable();
            $table->json('provider_response')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            
            // Metadata
            $table->string('reference_type')->nullable(); // Payment, Training, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('template_id')->references('id')->on('notification_templates');
            
            // Índices
            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'type']);
            $table->index(['recipient_type', 'recipient_id']);
            $table->index(['scheduled_at', 'status']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};