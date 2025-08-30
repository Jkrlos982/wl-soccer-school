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
        Schema::create('event_resources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('resource_id');
            $table->integer('quantity')->default(1);
            $table->enum('status', ['requested', 'confirmed', 'cancelled'])->default('requested');
            $table->text('notes')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->timestamps();
            
            $table->foreign('event_id')->references('id')->on('calendar_events')->onDelete('cascade');
            $table->foreign('resource_id')->references('id')->on('resources');
            $table->unique(['event_id', 'resource_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_resources');
    }
};
