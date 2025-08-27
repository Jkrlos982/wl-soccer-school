-- Migraciones para Medical Service
-- Base de datos: wl_school_medical

USE wl_school_medical;

-- Tabla de estudiantes (referencia)
CREATE TABLE students (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    student_code VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NULL,
    emergency_contact_name VARCHAR(150),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_relationship VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_student_code (student_code)
);

-- Tabla de personal médico
CREATE TABLE medical_staff (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    specialization VARCHAR(100),
    license_number VARCHAR(50),
    position ENUM('doctor', 'nurse', 'psychologist', 'dentist', 'therapist', 'coordinator') NOT NULL,
    hire_date DATE,
    status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_position (position),
    INDEX idx_status (status)
);

-- Tabla de historiales médicos
CREATE TABLE medical_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    record_number VARCHAR(20) UNIQUE NOT NULL,
    allergies TEXT,
    chronic_conditions TEXT,
    medications TEXT,
    medical_history TEXT,
    family_medical_history TEXT,
    dietary_restrictions TEXT,
    physical_limitations TEXT,
    insurance_provider VARCHAR(100),
    insurance_policy_number VARCHAR(50),
    primary_physician_name VARCHAR(150),
    primary_physician_phone VARCHAR(20),
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES medical_staff(id) ON DELETE SET NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_record_number (record_number)
);

-- Tabla de citas médicas
CREATE TABLE medical_appointments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    medical_staff_id BIGINT UNSIGNED NOT NULL,
    appointment_date DATETIME NOT NULL,
    duration_minutes INT DEFAULT 30,
    type ENUM('checkup', 'consultation', 'emergency', 'vaccination', 'dental', 'psychological', 'therapy') NOT NULL,
    reason TEXT,
    status ENUM('scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    location VARCHAR(100),
    notes TEXT,
    parent_notified BOOLEAN DEFAULT FALSE,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (medical_staff_id) REFERENCES medical_staff(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_staff_date (medical_staff_id, appointment_date),
    INDEX idx_status (status),
    INDEX idx_appointment_date (appointment_date)
);

-- Tabla de consultas médicas
CREATE TABLE medical_consultations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appointment_id BIGINT UNSIGNED,
    student_id BIGINT UNSIGNED NOT NULL,
    medical_staff_id BIGINT UNSIGNED NOT NULL,
    consultation_date DATETIME NOT NULL,
    chief_complaint TEXT,
    symptoms TEXT,
    vital_signs JSON, -- Temperatura, presión, peso, altura, etc.
    physical_examination TEXT,
    diagnosis TEXT,
    treatment_plan TEXT,
    medications_prescribed TEXT,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE NULL,
    recommendations TEXT,
    referral_needed BOOLEAN DEFAULT FALSE,
    referral_to VARCHAR(150),
    parent_informed BOOLEAN DEFAULT FALSE,
    return_to_class BOOLEAN DEFAULT TRUE,
    restrictions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES medical_appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (medical_staff_id) REFERENCES medical_staff(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_consultation_date (consultation_date),
    INDEX idx_follow_up (follow_up_required, follow_up_date)
);

-- Tabla de vacunas
CREATE TABLE vaccines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    manufacturer VARCHAR(100),
    doses_required INT DEFAULT 1,
    interval_days INT, -- Días entre dosis
    age_range_min INT, -- Edad mínima en meses
    age_range_max INT, -- Edad máxima en meses
    is_mandatory BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_is_mandatory (is_mandatory),
    INDEX idx_is_active (is_active)
);

-- Tabla de registro de vacunación
CREATE TABLE vaccination_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    vaccine_id BIGINT UNSIGNED NOT NULL,
    dose_number INT NOT NULL,
    vaccination_date DATE NOT NULL,
    administered_by BIGINT UNSIGNED,
    batch_number VARCHAR(50),
    expiration_date DATE,
    location VARCHAR(100),
    adverse_reactions TEXT,
    next_dose_due DATE,
    certificate_number VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (vaccine_id) REFERENCES vaccines(id) ON DELETE CASCADE,
    FOREIGN KEY (administered_by) REFERENCES medical_staff(id) ON DELETE SET NULL,
    INDEX idx_student_vaccine (student_id, vaccine_id),
    INDEX idx_vaccination_date (vaccination_date),
    INDEX idx_next_dose_due (next_dose_due)
);

-- Tabla de incidentes médicos
CREATE TABLE medical_incidents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    incident_number VARCHAR(20) UNIQUE NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    incident_date DATETIME NOT NULL,
    location VARCHAR(150),
    type ENUM('injury', 'illness', 'accident', 'allergic_reaction', 'emergency', 'other') NOT NULL,
    severity ENUM('minor', 'moderate', 'severe', 'critical') NOT NULL,
    description TEXT NOT NULL,
    immediate_action_taken TEXT,
    medical_staff_id BIGINT UNSIGNED,
    parent_contacted BOOLEAN DEFAULT FALSE,
    parent_contact_time DATETIME,
    hospital_referral BOOLEAN DEFAULT FALSE,
    hospital_name VARCHAR(150),
    outcome TEXT,
    follow_up_required BOOLEAN DEFAULT FALSE,
    reported_by BIGINT UNSIGNED,
    witnesses TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (medical_staff_id) REFERENCES medical_staff(id) ON DELETE SET NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_incident_date (incident_date),
    INDEX idx_type_severity (type, severity),
    INDEX idx_incident_number (incident_number)
);

-- Tabla de medicamentos en enfermería
CREATE TABLE medication_inventory (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    generic_name VARCHAR(150),
    brand_name VARCHAR(150),
    dosage_form ENUM('tablet', 'capsule', 'liquid', 'injection', 'cream', 'drops', 'inhaler') NOT NULL,
    strength VARCHAR(50),
    current_stock INT DEFAULT 0,
    minimum_stock INT DEFAULT 10,
    expiration_date DATE,
    batch_number VARCHAR(50),
    supplier VARCHAR(100),
    storage_requirements TEXT,
    prescription_required BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_expiration_date (expiration_date),
    INDEX idx_current_stock (current_stock),
    INDEX idx_is_active (is_active)
);

-- Tabla de administración de medicamentos
CREATE TABLE medication_administration (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    medication_id BIGINT UNSIGNED NOT NULL,
    medical_staff_id BIGINT UNSIGNED NOT NULL,
    consultation_id BIGINT UNSIGNED,
    administered_date DATETIME NOT NULL,
    dosage VARCHAR(50) NOT NULL,
    route ENUM('oral', 'topical', 'injection', 'inhalation', 'drops') NOT NULL,
    reason TEXT,
    parent_consent BOOLEAN DEFAULT FALSE,
    side_effects TEXT,
    effectiveness TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (medication_id) REFERENCES medication_inventory(id) ON DELETE CASCADE,
    FOREIGN KEY (medical_staff_id) REFERENCES medical_staff(id) ON DELETE CASCADE,
    FOREIGN KEY (consultation_id) REFERENCES medical_consultations(id) ON DELETE SET NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_administered_date (administered_date)
);

-- Tabla de exámenes médicos periódicos
CREATE TABLE health_screenings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    screening_type ENUM('vision', 'hearing', 'dental', 'growth', 'scoliosis', 'general') NOT NULL,
    screening_date DATE NOT NULL,
    performed_by BIGINT UNSIGNED NOT NULL,
    results JSON, -- Resultados específicos según el tipo
    status ENUM('normal', 'abnormal', 'requires_follow_up') NOT NULL,
    recommendations TEXT,
    parent_notified BOOLEAN DEFAULT FALSE,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES medical_staff(id) ON DELETE CASCADE,
    INDEX idx_student_type (student_id, screening_type),
    INDEX idx_screening_date (screening_date),
    INDEX idx_status (status)
);

-- Tabla de reportes médicos
CREATE TABLE medical_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_type ENUM('monthly_summary', 'incident_report', 'vaccination_status', 'health_screening_summary', 'medication_usage') NOT NULL,
    report_date DATE NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    data JSON NOT NULL,
    generated_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES medical_staff(id) ON DELETE SET NULL,
    INDEX idx_report_type (report_type),
    INDEX idx_report_date (report_date)
);

-- Insertar vacunas por defecto
INSERT INTO vaccines (name, description, doses_required, age_range_min, age_range_max, is_mandatory) VALUES
('BCG', 'Vacuna contra tuberculosis', 1, 0, 12, TRUE),
('Hepatitis B', 'Vacuna contra hepatitis B', 3, 0, 72, TRUE),
('DPT', 'Vacuna contra difteria, tos ferina y tétanos', 5, 2, 72, TRUE),
('Polio', 'Vacuna contra poliomielitis', 4, 2, 72, TRUE),
('MMR', 'Vacuna contra sarampión, paperas y rubéola', 2, 12, 72, TRUE),
('Varicela', 'Vacuna contra varicela', 2, 12, 144, TRUE),
('Hepatitis A', 'Vacuna contra hepatitis A', 2, 12, 144, TRUE),
('HPV', 'Vacuna contra virus del papiloma humano', 2, 108, 180, FALSE),
('Influenza', 'Vacuna contra influenza estacional', 1, 6, NULL, FALSE),
('COVID-19', 'Vacuna contra COVID-19', 2, 60, NULL, FALSE);

-- Insertar medicamentos básicos
INSERT INTO medication_inventory (name, generic_name, dosage_form, strength, current_stock, minimum_stock, prescription_required) VALUES
('Paracetamol', 'Acetaminofén', 'tablet', '500mg', 100, 20, FALSE),
('Ibuprofeno', 'Ibuprofeno', 'tablet', '400mg', 50, 15, FALSE),
('Suero Oral', 'Sales de rehidratación oral', 'liquid', '250ml', 30, 10, FALSE),
('Alcohol Antiséptico', 'Alcohol etílico', 'liquid', '70%', 20, 5, FALSE),
('Gasas Estériles', 'Gasas estériles', 'cream', '10x10cm', 200, 50, FALSE),
('Vendas Elásticas', 'Vendas elásticas', 'cream', '5cm', 50, 10, FALSE),
('Termómetro Digital', 'Termómetro digital', 'other', 'Digital', 10, 2, FALSE);