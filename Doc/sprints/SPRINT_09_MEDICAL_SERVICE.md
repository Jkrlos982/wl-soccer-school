# Sprint 9: Medical Service (Servicio Médico)

**Duración:** 2 semanas  
**Fase:** 4 - Módulos Médicos y Nómina  
**Objetivo:** Implementar sistema completo de gestión médica con historiales, exámenes, lesiones y seguimiento de salud de jugadores

## Resumen del Sprint

Este sprint implementa el microservicio médico con gestión de historiales clínicos, control de exámenes médicos, seguimiento de lesiones, certificados médicos y reportes de salud, cumpliendo con normativas de protección de datos médicos.

## Objetivos Específicos

- ✅ Implementar microservicio médico
- ✅ Crear sistema de historiales clínicos
- ✅ Gestionar exámenes médicos obligatorios
- ✅ Implementar seguimiento de lesiones
- ✅ Generar certificados médicos
- ✅ Crear reportes de salud y estadísticas
- ✅ Implementar alertas médicas automáticas

## Tareas Detalladas

### 1. Configuración Base del Microservicio

**Responsable:** Backend Developer Senior  
**Estimación:** 1 día  
**Prioridad:** Alta

#### Subtareas:

1. **Crear estructura del microservicio:**
   ```bash
   # Crear directorio del servicio
   mkdir wl-school-medical-service
   cd wl-school-medical-service
   
   # Inicializar Laravel
   composer create-project laravel/laravel . "10.*"
   
   # Instalar dependencias específicas
   composer require:
     - barryvdh/laravel-dompdf (PDF generation)
     - spatie/laravel-permission (Permissions)
     - spatie/laravel-activitylog (Activity logging)
     - spatie/laravel-medialibrary (File management)
     - intervention/image (Image processing)
     - league/csv (CSV export/import)
     - maatwebsite/excel (Excel export/import)
   ```

2. **Configurar variables de entorno:**
   ```env
   # .env
   APP_NAME="WL School Medical Service"
   APP_URL=http://localhost:8006
   
   # Database
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=wl_school_medical
   DB_USERNAME=root
   DB_PASSWORD=password
   
   # Queue
   QUEUE_CONNECTION=redis
   REDIS_HOST=redis
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   
   # File Storage
   FILESYSTEM_DISK=local
   AWS_ACCESS_KEY_ID=your_aws_key
   AWS_SECRET_ACCESS_KEY=your_aws_secret
   AWS_DEFAULT_REGION=us-east-1
   AWS_BUCKET=wl-school-medical
   
   # Medical Configuration
   MEDICAL_EXAM_VALIDITY_MONTHS=12
   INJURY_FOLLOWUP_DAYS=30
   CERTIFICATE_VALIDITY_DAYS=90
   
   # External Services
   AUTH_SERVICE_URL=http://auth-service:8001
   NOTIFICATION_SERVICE_URL=http://notification-service:8003
   SPORTS_SERVICE_URL=http://sports-service:8004
   CALENDAR_SERVICE_URL=http://calendar-service:8005
   
   # Security & Privacy
   MEDICAL_DATA_ENCRYPTION=true
   GDPR_COMPLIANCE=true
   DATA_RETENTION_YEARS=7
   
   # PDF Configuration
   PDF_PAPER_SIZE=A4
   PDF_ORIENTATION=portrait
   PDF_MARGIN_TOP=15
   PDF_MARGIN_BOTTOM=15
   PDF_MARGIN_LEFT=15
   PDF_MARGIN_RIGHT=15
   ```

3. **Configurar Docker:**
   ```dockerfile
   # Dockerfile
   FROM php:8.2-fpm
   
   # Install dependencies
   RUN apt-get update && apt-get install -y \
       git \
       curl \
       libpng-dev \
       libonig-dev \
       libxml2-dev \
       libzip-dev \
       zip \
       unzip \
       wkhtmltopdf \
       supervisor
   
   # Install PHP extensions
   RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip
   
   # Install Composer
   COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
   
   # Set working directory
   WORKDIR /var/www
   
   # Copy application
   COPY . /var/www
   
   # Install dependencies
   RUN composer install --no-dev --optimize-autoloader
   
   # Set permissions
   RUN chown -R www-data:www-data /var/www
   RUN chmod -R 755 /var/www/storage
   RUN chmod -R 755 /var/www/bootstrap/cache
   
   # Create directories for medical files
   RUN mkdir -p /var/www/storage/app/medical/records
   RUN mkdir -p /var/www/storage/app/medical/certificates
   RUN mkdir -p /var/www/storage/app/medical/exams
   
   # Configure supervisor
   COPY docker/supervisor/laravel-worker.conf /etc/supervisor/conf.d/
   
   EXPOSE 9000
   CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
   ```

#### Criterios de Aceptación:
- [ ] Microservicio configurado y funcionando
- [ ] Docker container operativo
- [ ] Variables de entorno configuradas
- [ ] Dependencias médicas instaladas

---

### 2. Modelos y Migraciones Base

**Responsable:** Backend Developer  
**Estimación:** 2 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear migración MedicalRecords:**
   ```php
   // Migration: create_medical_records_table
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
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('player_id')->references('id')->on('players');
       $table->foreign('created_by')->references('id')->on('users');
       $table->foreign('updated_by')->references('id')->on('users');
       $table->foreign('consent_given_by')->references('id')->on('users');
       
       $table->index(['school_id', 'player_id']);
       $table->index(['school_id', 'is_active']);
       $table->index(['medical_clearance', 'clearance_expiry_date']);
       $table->index(['next_medical_exam']);
   });
   ```

2. **Crear migración MedicalExams:**
   ```php
   // Migration: create_medical_exams_table
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
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('player_id')->references('id')->on('players');
       $table->foreign('scheduled_by')->references('id')->on('users');
       $table->foreign('completed_by')->references('id')->on('users');
       
       $table->index(['school_id', 'exam_date']);
       $table->index(['player_id', 'exam_type']);
       $table->index(['status', 'exam_date']);
       $table->index(['valid_until']);
       $table->index(['requires_followup', 'followup_date']);
   });
   ```

3. **Crear migración Injuries:**
   ```php
   // Migration: create_injuries_table
   Schema::create('injuries', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('medical_record_id');
       $table->unsignedBigInteger('school_id');
       $table->unsignedBigInteger('player_id');
       
       // Información de la lesión
       $table->string('injury_code')->unique(); // Código único de la lesión
       $table->string('injury_type'); // muscle, bone, ligament, etc.
       $table->string('body_part'); // knee, ankle, shoulder, etc.
       $table->string('severity'); // mild, moderate, severe
       $table->text('description');
       
       // Circunstancias de la lesión
       $table->datetime('injury_datetime');
       $table->string('injury_location')->nullable(); // Dónde ocurrió
       $table->enum('injury_context', ['training', 'match', 'other']);
       $table->text('injury_mechanism')->nullable(); // Cómo ocurrió
       $table->boolean('witnessed')->default(false);
       $table->json('witnesses')->nullable(); // Testigos
       
       // Diagnóstico y tratamiento
       $table->string('diagnosis')->nullable();
       $table->string('diagnosed_by')->nullable(); // Médico que diagnosticó
       $table->date('diagnosis_date')->nullable();
       $table->json('treatment_plan')->nullable();
       $table->json('medications_prescribed')->nullable();
       $table->boolean('requires_surgery')->default(false);
       $table->date('surgery_date')->nullable();
       
       // Recuperación y seguimiento
       $table->enum('status', ['active', 'recovering', 'recovered', 'chronic'])->default('active');
       $table->integer('estimated_recovery_days')->nullable();
       $table->date('expected_return_date')->nullable();
       $table->date('actual_return_date')->nullable();
       $table->boolean('cleared_to_play')->default(false);
       $table->date('clearance_date')->nullable();
       $table->string('cleared_by')->nullable(); // Médico que dio el alta
       
       // Prevención y recomendaciones
       $table->json('prevention_measures')->nullable();
       $table->text('return_to_play_protocol')->nullable();
       $table->boolean('requires_monitoring')->default(true);
       $table->json('monitoring_schedule')->nullable();
       
       // Impacto en el rendimiento
       $table->integer('training_days_missed')->default(0);
       $table->integer('matches_missed')->default(0);
       $table->decimal('performance_impact_percentage', 5, 2)->nullable();
       
       // Archivos y documentación
       $table->json('medical_reports')->nullable();
       $table->json('imaging_studies')->nullable(); // Radiografías, resonancias, etc.
       $table->json('progress_photos')->nullable();
       
       $table->unsignedBigInteger('reported_by');
       $table->unsignedBigInteger('updated_by')->nullable();
       $table->timestamps();
       
       $table->foreign('medical_record_id')->references('id')->on('medical_records')->onDelete('cascade');
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('player_id')->references('id')->on('players');
       $table->foreign('reported_by')->references('id')->on('users');
       $table->foreign('updated_by')->references('id')->on('users');
       
       $table->index(['school_id', 'injury_datetime']);
       $table->index(['player_id', 'status']);
       $table->index(['injury_type', 'body_part']);
       $table->index(['status', 'expected_return_date']);
       $table->index(['cleared_to_play']);
   });
   ```

4. **Crear migración InjuryFollowups:**
   ```php
   // Migration: create_injury_followups_table
   Schema::create('injury_followups', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('injury_id');
       $table->unsignedBigInteger('school_id');
       
       // Información del seguimiento
       $table->date('followup_date');
       $table->time('followup_time')->nullable();
       $table->enum('followup_type', ['medical', 'physiotherapy', 'training', 'assessment']);
       $table->string('conducted_by'); // Profesional que realizó el seguimiento
       
       // Evaluación del progreso
       $table->enum('pain_level', ['none', 'mild', 'moderate', 'severe']);
       $table->enum('mobility_level', ['full', 'limited', 'restricted', 'immobile']);
       $table->enum('progress_status', ['excellent', 'good', 'fair', 'poor', 'worsened']);
       $table->integer('progress_percentage')->default(0); // 0-100%
       
       // Observaciones y recomendaciones
       $table->text('observations');
       $table->text('recommendations')->nullable();
       $table->json('exercises_prescribed')->nullable();
       $table->json('restrictions')->nullable();
       
       // Próximo seguimiento
       $table->date('next_followup_date')->nullable();
       $table->text('next_followup_notes')->nullable();
       
       // Archivos adjuntos
       $table->json('attachments')->nullable();
       
       $table->unsignedBigInteger('created_by');
       $table->timestamps();
       
       $table->foreign('injury_id')->references('id')->on('injuries')->onDelete('cascade');
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('created_by')->references('id')->on('users');
       
       $table->index(['injury_id', 'followup_date']);
       $table->index(['school_id', 'followup_date']);
       $table->index(['followup_type', 'followup_date']);
       $table->index(['next_followup_date']);
   });
   ```

5. **Crear migración MedicalCertificates:**
   ```php
   // Migration: create_medical_certificates_table
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
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('player_id')->references('id')->on('players');
       $table->foreign('medical_record_id')->references('id')->on('medical_records');
       $table->foreign('medical_exam_id')->references('id')->on('medical_exams');
       $table->foreign('created_by')->references('id')->on('users');
       $table->foreign('updated_by')->references('id')->on('users');
       $table->foreign('revoked_by')->references('id')->on('users');
       
       $table->index(['school_id', 'certificate_type']);
       $table->index(['player_id', 'status']);
       $table->index(['valid_until', 'status']);
       $table->index(['issue_date']);
   });
   ```

#### Criterios de Aceptación:
- [ ] Migraciones ejecutadas correctamente
- [ ] Modelos implementados con relaciones
- [ ] Índices de base de datos optimizados
- [ ] Estructura de datos médicos completa

---

### 3. Implementación de Modelos y Servicios

**Responsable:** Backend Developer Senior  
**Estimación:** 2 días  
**Prioridad:** Alta

#### Subtareas:

1. **Implementar modelo MedicalRecord:**
   ```php
   class MedicalRecord extends Model
   {
       use HasFactory, LogsActivity;
       
       protected $fillable = [
           'school_id', 'player_id', 'record_number', 'blood_type',
           'height', 'weight', 'allergies', 'chronic_conditions',
           'medications', 'emergency_contacts', 'insurance_provider',
           'insurance_policy_number', 'insurance_expiry_date',
           'primary_doctor_name', 'primary_doctor_phone', 'primary_doctor_email',
           'is_active', 'status', 'notes', 'last_medical_exam',
           'next_medical_exam', 'medical_clearance', 'clearance_expiry_date',
           'access_log', 'consent_given', 'consent_date', 'consent_given_by',
           'created_by', 'updated_by'
       ];
       
       protected $casts = [
           'allergies' => 'array',
           'chronic_conditions' => 'array',
           'medications' => 'array',
           'emergency_contacts' => 'array',
           'access_log' => 'array',
           'last_medical_exam' => 'date',
           'next_medical_exam' => 'date',
           'insurance_expiry_date' => 'date',
           'clearance_expiry_date' => 'date',
           'consent_date' => 'date',
           'is_active' => 'boolean',
           'medical_clearance' => 'boolean',
           'consent_given' => 'boolean'
       ];
       
       // Relaciones
       public function school() {
           return $this->belongsTo(School::class);
       }
       
       public function player() {
           return $this->belongsTo(Player::class);
       }
       
       public function medicalExams() {
           return $this->hasMany(MedicalExam::class);
       }
       
       public function injuries() {
           return $this->hasMany(Injury::class);
       }
       
       public function certificates() {
           return $this->hasMany(MedicalCertificate::class);
       }
       
       public function creator() {
           return $this->belongsTo(User::class, 'created_by');
       }
       
       public function updater() {
           return $this->belongsTo(User::class, 'updated_by');
       }
       
       // Métodos auxiliares
       public function generateRecordNumber() {
           $year = now()->year;
           $schoolCode = str_pad($this->school_id, 3, '0', STR_PAD_LEFT);
           $sequence = str_pad(
               MedicalRecord::where('school_id', $this->school_id)
                   ->whereYear('created_at', $year)
                   ->count() + 1,
               4, '0', STR_PAD_LEFT
           );
           
           return "MR-{$year}-{$schoolCode}-{$sequence}";
       }
       
       public function isMedicalExamDue() {
           if (!$this->next_medical_exam) {
               return true;
           }
           
           return $this->next_medical_exam->isPast();
       }
       
       public function isClearanceExpired() {
           if (!$this->medical_clearance || !$this->clearance_expiry_date) {
               return true;
           }
           
           return $this->clearance_expiry_date->isPast();
       }
       
       public function hasActiveInjuries() {
           return $this->injuries()
               ->whereIn('status', ['active', 'recovering'])
               ->exists();
       }
       
       public function getLatestMedicalExam() {
           return $this->medicalExams()
               ->where('status', 'completed')
               ->orderBy('exam_date', 'desc')
               ->first();
       }
       
       public function getBMI() {
           if (!$this->height || !$this->weight) {
               return null;
           }
           
           $heightInMeters = $this->height / 100;
           return round($this->weight / ($heightInMeters * $heightInMeters), 2);
       }
       
       public function getBMICategory() {
           $bmi = $this->getBMI();
           
           if (!$bmi) return null;
           
           return match(true) {
               $bmi < 18.5 => 'Bajo peso',
               $bmi < 25 => 'Peso normal',
               $bmi < 30 => 'Sobrepeso',
               default => 'Obesidad'
           };
       }
       
       public function logAccess($userId, $action = 'view') {
           $accessLog = $this->access_log ?? [];
           $accessLog[] = [
               'user_id' => $userId,
               'action' => $action,
               'timestamp' => now()->toISOString(),
               'ip_address' => request()->ip()
           ];
           
           // Mantener solo los últimos 100 accesos
           if (count($accessLog) > 100) {
               $accessLog = array_slice($accessLog, -100);
           }
           
           $this->update(['access_log' => $accessLog]);
       }
       
       // Scopes
       public function scopeActive($query) {
           return $query->where('is_active', true);
       }
       
       public function scopeWithValidClearance($query) {
           return $query->where('medical_clearance', true)
               ->where(function($q) {
                   $q->whereNull('clearance_expiry_date')
                     ->orWhere('clearance_expiry_date', '>', now());
               });
       }
       
       public function scopeExamDue($query) {
           return $query->where(function($q) {
               $q->whereNull('next_medical_exam')
                 ->orWhere('next_medical_exam', '<=', now());
           });
       }
       
       public function scopeForSchool($query, $schoolId) {
           return $query->where('school_id', $schoolId);
       }
   }
   ```

2. **Implementar MedicalService:**
   ```php
   class MedicalService
   {
       public function createMedicalRecord($playerData, $medicalData, $userId)
       {
           DB::beginTransaction();
           
           try {
               $medicalRecord = MedicalRecord::create([
                   'school_id' => $playerData['school_id'],
                   'player_id' => $playerData['player_id'],
                   'record_number' => $this->generateRecordNumber($playerData['school_id']),
                   ...$medicalData,
                   'created_by' => $userId
               ]);
               
               // Crear examen médico inicial si es necesario
               if ($medicalData['schedule_initial_exam'] ?? false) {
                   $this->scheduleInitialMedicalExam($medicalRecord, $userId);
               }
               
               // Log de creación
               $medicalRecord->logAccess($userId, 'create');
               
               DB::commit();
               
               return $medicalRecord;
           } catch (\Exception $e) {
               DB::rollback();
               throw $e;
           }
       }
       
       public function scheduleMedicalExam($medicalRecordId, $examData, $userId)
       {
           $medicalRecord = MedicalRecord::findOrFail($medicalRecordId);
           
           $exam = MedicalExam::create([
               'medical_record_id' => $medicalRecord->id,
               'school_id' => $medicalRecord->school_id,
               'player_id' => $medicalRecord->player_id,
               'exam_code' => $this->generateExamCode(),
               ...$examData,
               'scheduled_by' => $userId
           ]);
           
           // Crear evento en el calendario
           $this->createCalendarEvent($exam);
           
           // Enviar notificación
           $this->sendExamNotification($exam, 'scheduled');
           
           return $exam;
       }
       
       public function completeMedicalExam($examId, $results, $userId)
       {
           DB::beginTransaction();
           
           try {
               $exam = MedicalExam::findOrFail($examId);
               
               $exam->update([
                   'status' => 'completed',
                   'result' => $results['result'],
                   'observations' => $results['observations'],
                   'vital_signs' => $results['vital_signs'],
                   'physical_tests' => $results['physical_tests'],
                   'recommendations' => $results['recommendations'],
                   'valid_from' => $results['valid_from'],
                   'valid_until' => $results['valid_until'],
                   'requires_followup' => $results['requires_followup'] ?? false,
                   'followup_date' => $results['followup_date'] ?? null,
                   'completed_by' => $userId
               ]);
               
               // Actualizar registro médico
               $medicalRecord = $exam->medicalRecord;
               $medicalRecord->update([
                   'last_medical_exam' => $exam->exam_date,
                   'next_medical_exam' => $this->calculateNextExamDate($exam),
                   'medical_clearance' => $results['result'] === 'approved',
                   'clearance_expiry_date' => $results['result'] === 'approved' ? $results['valid_until'] : null
               ]);
               
               // Generar certificado si es aprobado
               if ($results['result'] === 'approved') {
                   $this->generateMedicalCertificate($exam, 'fitness_to_play', $userId);
               }
               
               // Programar seguimiento si es necesario
               if ($results['requires_followup']) {
                   $this->scheduleFollowup($exam, $results['followup_date']);
               }
               
               DB::commit();
               
               // Enviar notificación de resultados
               $this->sendExamNotification($exam, 'completed');
               
               return $exam;
           } catch (\Exception $e) {
               DB::rollback();
               throw $e;
           }
       }
       
       public function reportInjury($injuryData, $userId)
       {
           DB::beginTransaction();
           
           try {
               $injury = Injury::create([
                   'injury_code' => $this->generateInjuryCode(),
                   ...$injuryData,
                   'reported_by' => $userId
               ]);
               
               // Crear primer seguimiento
               $this->createInjuryFollowup($injury->id, [
                   'followup_date' => now()->toDateString(),
                   'followup_type' => 'assessment',
                   'conducted_by' => $injuryData['initial_assessor'] ?? 'Sistema',
                   'observations' => 'Reporte inicial de lesión',
                   'progress_status' => 'fair',
                   'progress_percentage' => 0
               ], $userId);
               
               // Programar seguimientos automáticos
               $this->scheduleInjuryFollowups($injury);
               
               // Actualizar disponibilidad del jugador
               $this->updatePlayerAvailability($injury->player_id, false);
               
               DB::commit();
               
               // Enviar notificaciones
               $this->sendInjuryNotification($injury, 'reported');
               
               return $injury;
           } catch (\Exception $e) {
               DB::rollback();
               throw $e;
           }
       }
       
       public function generateMedicalCertificate($source, $type, $userId)
       {
           $certificate = MedicalCertificate::create([
               'school_id' => $source->school_id,
               'player_id' => $source->player_id,
               'medical_record_id' => $source->medical_record_id ?? null,
               'medical_exam_id' => $source instanceof MedicalExam ? $source->id : null,
               'certificate_number' => $this->generateCertificateNumber(),
               'certificate_type' => $type,
               'title' => $this->getCertificateTitle($type),
               'description' => $this->getCertificateDescription($source, $type),
               'issue_date' => now()->toDateString(),
               'valid_from' => now()->toDateString(),
               'valid_until' => $this->calculateCertificateExpiry($type),
               'issued_by' => $source->doctor_name ?? 'Sistema Médico',
               'doctor_license' => $source->doctor_license_number ?? null,
               'medical_center' => $source->medical_center ?? null,
               'status' => 'issued',
               'created_by' => $userId
           ]);
           
           // Generar PDF
           $pdfPath = $this->generateCertificatePDF($certificate);
           $certificate->update(['pdf_path' => $pdfPath]);
           
           return $certificate;
       }
       
       private function generateRecordNumber($schoolId)
       {
           $year = now()->year;
           $schoolCode = str_pad($schoolId, 3, '0', STR_PAD_LEFT);
           $sequence = str_pad(
               MedicalRecord::where('school_id', $schoolId)
                   ->whereYear('created_at', $year)
                   ->count() + 1,
               4, '0', STR_PAD_LEFT
           );
           
           return "MR-{$year}-{$schoolCode}-{$sequence}";
       }
       
       private function generateExamCode()
       {
           return 'EX-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
       }
       
       private function generateInjuryCode()
       {
           return 'INJ-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
       }
       
       private function generateCertificateNumber()
       {
           return 'CERT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8));
       }
       
       private function calculateNextExamDate($exam)
       {
           $validityMonths = config('medical.exam_validity_months', 12);
           return Carbon::parse($exam->exam_date)->addMonths($validityMonths);
       }
       
       private function calculateCertificateExpiry($type)
       {
           $validityDays = match($type) {
               'fitness_to_play' => 365,
               'medical_clearance' => 180,
               'return_to_play' => 90,
               'injury_report' => 30,
               default => 90
           };
           
           return now()->addDays($validityDays)->toDateString();
       }
   }
   ```

#### Criterios de Aceptación:
- [ ] Modelos implementados con todas las relaciones
- [ ] MedicalService funcionando correctamente
- [ ] Generación automática de códigos únicos
- [ ] Cálculos médicos (BMI, fechas) funcionando

---

## API Endpoints Implementados

### Medical Records
```
GET    /api/v1/medical-records
POST   /api/v1/medical-records
GET    /api/v1/medical-records/{id}
PUT    /api/v1/medical-records/{id}
DELETE /api/v1/medical-records/{id}
GET    /api/v1/medical-records/{id}/summary
POST   /api/v1/medical-records/{id}/consent
```

### Medical Exams
```
GET    /api/v1/medical-exams
POST   /api/v1/medical-exams
GET    /api/v1/medical-exams/{id}
PUT    /api/v1/medical-exams/{id}
POST   /api/v1/medical-exams/{id}/complete
GET    /api/v1/medical-exams/due
GET    /api/v1/medical-exams/scheduled
```

### Injuries
```
GET    /api/v1/injuries
POST   /api/v1/injuries
GET    /api/v1/injuries/{id}
PUT    /api/v1/injuries/{id}
POST   /api/v1/injuries/{id}/followup
POST   /api/v1/injuries/{id}/clearance
GET    /api/v1/injuries/active
GET    /api/v1/injuries/statistics
```

### Medical Certificates
```
GET    /api/v1/certificates
POST   /api/v1/certificates
GET    /api/v1/certificates/{id}
GET    /api/v1/certificates/{id}/download
POST   /api/v1/certificates/{id}/revoke
GET    /api/v1/certificates/expiring
```

## Definición de Terminado (DoD)

### Criterios Técnicos:
- [ ] Microservicio médico funcionando
- [ ] Historiales clínicos completos
- [ ] Sistema de exámenes médicos operativo
- [ ] Seguimiento de lesiones implementado
- [ ] Generación de certificados automática
- [ ] API REST completa y documentada

### Criterios de Calidad:
- [ ] Tests unitarios > 90% cobertura
- [ ] Tests de integración médica
- [ ] Validación de datos médicos
- [ ] Encriptación de datos sensibles
- [ ] Cumplimiento GDPR implementado

### Criterios de Negocio:
- [ ] Historiales médicos creándose correctamente
- [ ] Exámenes programándose y completándose
- [ ] Lesiones registrándose y siguiéndose
- [ ] Certificados generándose automáticamente
- [ ] Alertas médicas funcionando

## Riesgos Identificados

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| Privacidad datos médicos | Alta | Crítico | Encriptación, auditoría, GDPR |
| Complejidad médica | Media | Alto | Consulta expertos, validaciones |
| Performance consultas | Media | Medio | Índices, cache, paginación |
| Integración calendario | Baja | Medio | APIs bien definidas |

## Métricas de Éxito

- **Data accuracy**: > 99% datos médicos correctos
- **Exam compliance**: > 95% exámenes al día
- **Injury tracking**: 100% lesiones registradas
- **Certificate generation**: < 5 min generación
- **Privacy compliance**: 100% cumplimiento GDPR

## Entregables

1. **Microservicio Medical** - Servicio completo funcionando
2. **Historiales Clínicos** - Sistema completo de registros
3. **Gestión Exámenes** - Programación y seguimiento
4. **Seguimiento Lesiones** - Registro y recuperación
5. **Certificados Médicos** - Generación automática
6. **Reportes Médicos** - Estadísticas y análisis

## Variables de Entorno

```env
# Medical Configuration
MEDICAL_EXAM_VALIDITY_MONTHS=12
INJURY_FOLLOWUP_DAYS=30
CERTIFICATE_VALIDITY_DAYS=90

# Security & Privacy
MEDICAL_DATA_ENCRYPTION=true
GDPR_COMPLIANCE=true
DATA_RETENTION_YEARS=7

# PDF Configuration
PDF_PAPER_SIZE=A4
PDF_ORIENTATION=portrait
```

## Preguntas para Retrospectiva

1. **¿Qué funcionó bien en este sprint?**
   - ¿El sistema de historiales médicos es intuitivo?
   - ¿La generación de certificados es eficiente?

2. **¿Qué obstáculos encontramos?**
   - ¿La complejidad médica fue manejable?
   - ¿Los requisitos de privacidad fueron claros?

3. **¿Qué podemos mejorar?**
   - ¿Cómo optimizar las consultas médicas?
   - ¿El flujo de exámenes es eficiente?

4. **¿Qué aprendimos?**
   - ¿Qué mejores prácticas para datos médicos?
   - ¿Cómo mejorar la experiencia del usuario médico?

5. **¿Estamos listos para el siguiente sprint?**
   - ¿Todos los endpoints médicos funcionan?
   - ¿La seguridad está implementada correctamente?
   - ¿Los reportes son útiles?