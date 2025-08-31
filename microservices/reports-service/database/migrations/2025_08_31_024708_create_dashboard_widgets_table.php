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
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['chart', 'metric', 'table', 'text', 'iframe', 'custom']);
            $table->enum('chart_type', ['line', 'bar', 'pie', 'doughnut', 'area', 'scatter', 'gauge'])->nullable();
            $table->json('data_source'); // Data source configuration
            $table->json('configuration'); // Widget-specific configuration
            $table->json('styling')->nullable(); // CSS/styling options
            $table->json('filters')->nullable(); // Available filters
            $table->integer('refresh_interval')->default(300); // Seconds
            $table->integer('cache_duration')->default(600); // Seconds
            $table->boolean('is_real_time')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false);
            $table->integer('sort_order')->default(0);
            $table->string('grid_position')->nullable(); // Grid layout position
            $table->json('permissions')->nullable(); // Access permissions
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index(['is_real_time', 'is_active']);
            $table->index('sort_order');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};
