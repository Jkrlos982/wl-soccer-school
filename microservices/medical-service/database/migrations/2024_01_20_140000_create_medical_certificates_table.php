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
        Schema::create('medical_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('medical_record_id')->nullable();
            $table->unsignedBigInteger('medical_exam_id')->nullable();
            
            // Información del certificado
            $table->string('certificate_number')->unique();
            $table->enum('certificate_type', [
                'fitness_to_play', 'medical_clearance', 'injury_report',
                'return_to_play', 'medical_exemption', 'vaccination_record'
            ]);
            $table->string('title');
            $table->text('description');
            
            // Validez del certificado
            $table->date('issue_date');
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->boolean('is_permanent')->default(false);
            
            // Información médica
            $table->string('issued_by'); // Médico que emite
            $table->string('doctor_license')->nullable();
            $table->string('medical_center')->nullable();
            $table->text('medical_findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->json('restrictions')->nullable();
            
            // Estado del certificado
            $table->enum('status', ['draft', 'issued', 'expired', 'revoked'])->default('draft');
            $table->text('revocation_reason')->nullable();
            $table->date('revocation_date')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            
            // Archivos
            $table->string('pdf_path')->nullable();
            $table->string('digital_signature')->nullable();
            $table->json('attachments')->nullable();
            
            // Notificaciones
            $table->boolean('notify_expiration')->default(true);
            $table->integer('notification_days_before')->default(30);
            $table->timestamp('last_notification_sent')->nullable();
            
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            $table->foreign('medical_record_id')->references('id')->on('medical_records');
            $table->foreign('medical_exam_id')->references('id')->on('medical_exams');
            
            $table->index(['school_id', 'certificate_type']);
            $table->index(['player_id', 'status']);
            $table->index(['valid_until', 'status']);
            $table->index(['issue_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_certificates');
    }
};