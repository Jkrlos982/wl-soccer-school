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
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['financial', 'academic', 'operational', 'custom']);
            $table->enum('format', ['pdf', 'excel', 'csv', 'html']);
            $table->json('parameters')->nullable(); // Report parameters schema
            $table->json('data_sources'); // Data source configurations
            $table->longText('template_content'); // Template content (HTML, etc.)
            $table->json('styling')->nullable(); // CSS/styling configurations
            $table->json('charts_config')->nullable(); // Chart configurations
            $table->enum('frequency', ['manual', 'daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->default('manual');
            $table->json('schedule_config')->nullable(); // Cron schedule configuration
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index(['frequency', 'is_active']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_templates');
    }
};
