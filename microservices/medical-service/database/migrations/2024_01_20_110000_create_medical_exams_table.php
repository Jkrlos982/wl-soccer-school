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
        Schema::create('medical_exams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('medical_record_id');
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('player_id');
            
            // Información del examen
            $table->string('exam_type'); // annual, pre_season, injury_return, etc.
            $table->string('exam_code')->unique(); // Código único del examen
            $table->date('exam_date');
            $table->time('exam_time')->nullable();
            $table->string('location')->nullable();
            
            // Médico examinador
            $table->string('doctor_name');
            $table->string('doctor_license_number')->nullable();
            $table->string('doctor_specialty')->nullable();
            $table->string('medical_center')->nullable();
            
            // Resultados del examen
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            $table->enum('result', ['approved', 'conditional', 'rejected', 'pending'])->nullable();
            $table->text('observations')->nullable();
            $table->json('vital_signs')->nullable(); // Signos vitales
            $table->json('physical_tests')->nullable(); // Pruebas físicas
            $table->json('recommendations')->nullable(); // Recomendaciones médicas
            
            // Validez y seguimiento
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('requires_followup')->default(false);
            $table->date('followup_date')->nullable();
            $table->text('followup_notes')->nullable();
            
            // Archivos adjuntos
            $table->json('attachments')->nullable(); // Archivos del examen
            $table->string('certificate_path')->nullable(); // Certificado generado
            
            // Costo y facturación
            $table->decimal('cost', 10, 2)->nullable();
            $table->boolean('paid')->default(false);
            $table->date('payment_date')->nullable();
            $table->string('invoice_number')->nullable();
            
            $table->unsignedBigInteger('scheduled_by');
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamps();
            
            $table->foreign('medical_record_id')->references('id')->on('medical_records')->onDelete('cascade');
            
            $table->index(['school_id', 'exam_date']);
            $table->index(['player_id', 'exam_type']);
            $table->index(['status', 'exam_date']);
            $table->index(['valid_until']);
            $table->index(['requires_followup', 'followup_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_exams');
    }
};