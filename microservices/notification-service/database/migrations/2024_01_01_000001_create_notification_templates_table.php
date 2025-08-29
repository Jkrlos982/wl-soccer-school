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
            $table->unsignedBigInteger('school_id');
            $table->string('name'); // Nombre descriptivo
            $table->string('code')->unique(); // Código único del template
            $table->enum('type', ['whatsapp', 'email', 'sms', 'push', 'system']);
            $table->enum('category', [
                'payment_reminder', 'payment_confirmation', 'training_reminder',
                'attendance_alert', 'evaluation_ready', 'general_announcement',
                'birthday_greeting', 'welcome_message', 'account_created',
                'password_reset', 'invoice_generated', 'custom'
            ]);
            $table->string('subject')->nullable(); // Para emails
            $table->text('content'); // Contenido con variables {{variable}}
            $table->json('variables')->nullable(); // Variables disponibles
            $table->json('media_urls')->nullable(); // URLs de imágenes/archivos
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // Template por defecto
            $table->json('settings')->nullable(); // Configuraciones específicas
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            // Índices
            $table->index(['school_id', 'type']);
            $table->index(['school_id', 'category']);
            $table->index(['code', 'is_active']);
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