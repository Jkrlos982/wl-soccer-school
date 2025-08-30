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
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('player_id');
            $table->string('record_number')->unique(); // Número de historia clínica
            
            // Información personal médica
            $table->string('blood_type')->nullable(); // Tipo de sangre
            $table->decimal('height', 5, 2)->nullable(); // Altura en cm
            $table->decimal('weight', 5, 2)->nullable(); // Peso en kg
            $table->json('allergies')->nullable(); // Alergias conocidas
            $table->json('chronic_conditions')->nullable(); // Condiciones crónicas
            $table->json('medications')->nullable(); // Medicamentos actuales
            $table->json('emergency_contacts')->nullable(); // Contactos de emergencia
            
            // Información del seguro médico
            $table->string('insurance_provider')->nullable();
            $table->string('insurance_policy_number')->nullable();
            $table->date('insurance_expiry_date')->nullable();
            
            // Información del médico de cabecera
            $table->string('primary_doctor_name')->nullable();
            $table->string('primary_doctor_phone')->nullable();
            $table->string('primary_doctor_email')->nullable();
            
            // Estado del registro
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['complete', 'incomplete', 'under_review'])->default('incomplete');
            $table->text('notes')->nullable();
            
            // Fechas importantes
            $table->date('last_medical_exam')->nullable();
            $table->date('next_medical_exam')->nullable();
            $table->boolean('medical_clearance')->default(false);
            $table->date('clearance_expiry_date')->nullable();
            
            // Auditoría y privacidad
            $table->json('access_log')->nullable(); // Log de accesos
            $table->boolean('consent_given')->default(false);
            $table->date('consent_date')->nullable();
            $table->unsignedBigInteger('consent_given_by')->nullable(); // Parent/Guardian
            
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index(['school_id', 'player_id']);
            $table->index(['school_id', 'is_active']);
            $table->index(['medical_clearance', 'clearance_expiry_date']);
            $table->index(['next_medical_exam']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};