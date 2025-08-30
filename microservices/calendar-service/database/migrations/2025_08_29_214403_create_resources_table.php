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
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name'); // Nombre del recurso
            $table->string('type'); // field, equipment, room, vehicle
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->integer('capacity')->nullable(); // Capacidad máxima
            
            // Disponibilidad
            $table->json('availability_schedule')->nullable(); // Horarios disponibles
            $table->boolean('requires_approval')->default(false);
            $table->decimal('hourly_rate', 10, 2)->nullable(); // Costo por hora
            
            // Estado
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['available', 'maintenance', 'reserved', 'unavailable'])->default('available');
            
            // Configuración
            $table->json('equipment_included')->nullable(); // Equipos incluidos
            $table->json('booking_rules')->nullable(); // Reglas de reserva
            $table->json('metadata')->nullable();
            
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            // Note: Foreign key to schools table will be added when schools table exists
            // $table->foreign('school_id')->references('id')->on('schools');
            // $table->foreign('created_by')->references('id')->on('users');
            $table->index(['school_id', 'type']);
            $table->index(['school_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
