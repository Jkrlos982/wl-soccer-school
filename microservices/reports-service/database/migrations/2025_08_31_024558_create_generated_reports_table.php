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
        Schema::create('generated_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'expired']);
            $table->enum('format', ['pdf', 'excel', 'csv', 'html']);
            $table->json('parameters')->nullable(); // Parameters used for generation
            $table->string('file_path')->nullable(); // Path to generated file
            $table->string('file_name')->nullable();
            $table->bigInteger('file_size')->nullable(); // File size in bytes
            $table->string('file_hash')->nullable(); // File integrity hash
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable(); // Additional metadata
            $table->text('error_message')->nullable();
            $table->integer('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            $table->unsignedBigInteger('generated_by');
            $table->timestamps();
            
            $table->foreign('template_id')->references('id')->on('report_templates')->onDelete('cascade');
            $table->index(['template_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index('generated_by');
            $table->index('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_reports');
    }
};
