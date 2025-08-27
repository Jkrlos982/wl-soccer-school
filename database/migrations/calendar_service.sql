-- Migraciones para Calendar Service
-- Base de datos: wl_school_calendar

USE wl_school_calendar;

-- Tabla de tipos de eventos
CREATE TABLE event_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    color VARCHAR(7) NOT NULL, -- Color hex
    icon VARCHAR(50),
    is_public BOOLEAN DEFAULT TRUE,
    requires_approval BOOLEAN DEFAULT FALSE,
    max_duration INT, -- Duración máxima en minutos
    default_duration INT DEFAULT 60, -- Duración por defecto en minutos
    is_recurring BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_is_active (is_active),
    INDEX idx_is_public (is_public)
);

-- Tabla de calendarios
CREATE TABLE calendars (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    calendar_type ENUM('personal', 'academic', 'institutional', 'sports', 'medical', 'administrative') NOT NULL,
    owner_id BIGINT UNSIGNED,
    owner_type ENUM('user', 'department', 'course', 'institution') DEFAULT 'user',
    color VARCHAR(7) NOT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    is_default BOOLEAN DEFAULT FALSE,
    timezone VARCHAR(50) DEFAULT 'America/Bogota',
    settings JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_calendar_type (calendar_type),
    INDEX idx_owner (owner_id, owner_type),
    INDEX idx_is_public (is_public),
    INDEX idx_is_active (is_active)
);

-- Tabla de eventos
CREATE TABLE events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    calendar_id BIGINT UNSIGNED NOT NULL,
    event_type_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description LONGTEXT,
    location VARCHAR(200),
    virtual_link VARCHAR(500),
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    timezone VARCHAR(50) DEFAULT 'America/Bogota',
    is_all_day BOOLEAN DEFAULT FALSE,
    is_recurring BOOLEAN DEFAULT FALSE,
    recurrence_rule TEXT, -- RRULE format
    recurrence_end_date DATE,
    parent_event_id BIGINT UNSIGNED, -- Para eventos recurrentes
    status ENUM('scheduled', 'confirmed', 'cancelled', 'completed', 'postponed') DEFAULT 'scheduled',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    visibility ENUM('public', 'private', 'confidential') DEFAULT 'public',
    max_attendees INT,
    requires_confirmation BOOLEAN DEFAULT FALSE,
    confirmation_deadline DATETIME,
    reminder_settings JSON,
    custom_fields JSON,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (calendar_id) REFERENCES calendars(id) ON DELETE CASCADE,
    FOREIGN KEY (event_type_id) REFERENCES event_types(id) ON DELETE RESTRICT,
    FOREIGN KEY (parent_event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_calendar_id (calendar_id),
    INDEX idx_event_type_id (event_type_id),
    INDEX idx_start_datetime (start_datetime),
    INDEX idx_end_datetime (end_datetime),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by),
    INDEX idx_date_range (start_datetime, end_datetime)
);

-- Tabla de asistentes a eventos
CREATE TABLE event_attendees (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    attendee_id BIGINT UNSIGNED NOT NULL,
    attendee_type ENUM('user', 'student', 'teacher', 'parent', 'external') DEFAULT 'user',
    attendee_name VARCHAR(150),
    attendee_email VARCHAR(255),
    role ENUM('organizer', 'required', 'optional', 'resource') DEFAULT 'required',
    status ENUM('pending', 'accepted', 'declined', 'tentative', 'no_response') DEFAULT 'pending',
    response_datetime DATETIME,
    notes TEXT,
    check_in_datetime DATETIME,
    check_out_datetime DATETIME,
    attended BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_event_attendee (event_id, attendee_id, attendee_type),
    INDEX idx_event_id (event_id),
    INDEX idx_attendee (attendee_id, attendee_type),
    INDEX idx_status (status),
    INDEX idx_attended (attended)
);

-- Tabla de recursos para eventos
CREATE TABLE event_resources (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    resource_type ENUM('room', 'equipment', 'vehicle', 'person', 'other') NOT NULL,
    capacity INT,
    location VARCHAR(200),
    specifications JSON,
    availability_schedule JSON,
    hourly_rate DECIMAL(8,2) DEFAULT 0,
    requires_approval BOOLEAN DEFAULT FALSE,
    contact_person VARCHAR(150),
    contact_email VARCHAR(255),
    contact_phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_resource_type (resource_type),
    INDEX idx_is_active (is_active)
);

-- Tabla de reservas de recursos
CREATE TABLE resource_bookings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    resource_id BIGINT UNSIGNED NOT NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    quantity INT DEFAULT 1,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    cost DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    approved_by BIGINT UNSIGNED,
    approved_at DATETIME,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (resource_id) REFERENCES event_resources(id) ON DELETE CASCADE,
    INDEX idx_event_id (event_id),
    INDEX idx_resource_id (resource_id),
    INDEX idx_datetime_range (start_datetime, end_datetime),
    INDEX idx_status (status)
);

-- Tabla de horarios académicos
CREATE TABLE academic_schedules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    schedule_name VARCHAR(150) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester ENUM('1', '2', 'summer', 'annual') NOT NULL,
    course_id BIGINT UNSIGNED,
    course_name VARCHAR(150),
    teacher_id BIGINT UNSIGNED,
    teacher_name VARCHAR(150),
    classroom VARCHAR(100),
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_academic_year (academic_year),
    INDEX idx_course_id (course_id),
    INDEX idx_teacher_id (teacher_id),
    INDEX idx_day_time (day_of_week, start_time),
    INDEX idx_date_range (start_date, end_date),
    INDEX idx_is_active (is_active)
);

-- Tabla de citas médicas
CREATE TABLE medical_appointments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id BIGINT UNSIGNED NOT NULL,
    patient_name VARCHAR(150) NOT NULL,
    doctor_id BIGINT UNSIGNED,
    doctor_name VARCHAR(150),
    appointment_type ENUM('consultation', 'checkup', 'vaccination', 'emergency', 'follow_up') NOT NULL,
    appointment_datetime DATETIME NOT NULL,
    duration INT DEFAULT 30, -- Duración en minutos
    status ENUM('scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    symptoms TEXT,
    notes TEXT,
    diagnosis TEXT,
    treatment TEXT,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patient_id (patient_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_appointment_datetime (appointment_datetime),
    INDEX idx_appointment_type (appointment_type),
    INDEX idx_status (status)
);

-- Tabla de reuniones
CREATE TABLE meetings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    meeting_type ENUM('staff', 'parent_teacher', 'student_council', 'board', 'department', 'disciplinary', 'other') NOT NULL,
    organizer_id BIGINT UNSIGNED NOT NULL,
    meeting_datetime DATETIME NOT NULL,
    duration INT DEFAULT 60, -- Duración en minutos
    location VARCHAR(200),
    virtual_link VARCHAR(500),
    agenda LONGTEXT,
    minutes LONGTEXT,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled', 'postponed') DEFAULT 'scheduled',
    is_recurring BOOLEAN DEFAULT FALSE,
    recurrence_rule TEXT,
    requires_preparation BOOLEAN DEFAULT FALSE,
    preparation_deadline DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_meeting_type (meeting_type),
    INDEX idx_organizer_id (organizer_id),
    INDEX idx_meeting_datetime (meeting_datetime),
    INDEX idx_status (status)
);

-- Tabla de participantes en reuniones
CREATE TABLE meeting_participants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    meeting_id BIGINT UNSIGNED NOT NULL,
    participant_id BIGINT UNSIGNED NOT NULL,
    participant_type ENUM('user', 'student', 'parent', 'external') DEFAULT 'user',
    participant_name VARCHAR(150),
    participant_email VARCHAR(255),
    role ENUM('organizer', 'presenter', 'participant', 'observer') DEFAULT 'participant',
    attendance_status ENUM('required', 'optional', 'declined', 'attended', 'absent') DEFAULT 'required',
    joined_at DATETIME,
    left_at DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
    INDEX idx_meeting_id (meeting_id),
    INDEX idx_participant (participant_id, participant_type),
    INDEX idx_attendance_status (attendance_status)
);

-- Tabla de recordatorios
CREATE TABLE reminders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED,
    meeting_id BIGINT UNSIGNED,
    appointment_id BIGINT UNSIGNED,
    user_id BIGINT UNSIGNED NOT NULL,
    reminder_type ENUM('email', 'sms', 'push', 'in_app') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT,
    remind_at DATETIME NOT NULL,
    status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    sent_at DATETIME,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES medical_appointments(id) ON DELETE CASCADE,
    INDEX idx_event_id (event_id),
    INDEX idx_meeting_id (meeting_id),
    INDEX idx_appointment_id (appointment_id),
    INDEX idx_user_id (user_id),
    INDEX idx_remind_at (remind_at),
    INDEX idx_status (status)
);

-- Tabla de disponibilidad de usuarios
CREATE TABLE user_availability (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    availability_type ENUM('available', 'busy', 'tentative', 'out_of_office') DEFAULT 'available',
    is_recurring BOOLEAN DEFAULT TRUE,
    specific_date DATE, -- Para disponibilidad específica de un día
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_day_of_week (day_of_week),
    INDEX idx_specific_date (specific_date),
    INDEX idx_availability_type (availability_type)
);

-- Tabla de configuraciones de calendario
CREATE TABLE calendar_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    setting_type ENUM('user', 'global') DEFAULT 'user',
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_setting (user_id, setting_key),
    INDEX idx_user_id (user_id),
    INDEX idx_setting_type (setting_type)
);

-- Tabla de días festivos y no laborables
CREATE TABLE holidays (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    holiday_date DATE NOT NULL,
    holiday_type ENUM('national', 'religious', 'institutional', 'academic') NOT NULL,
    is_recurring BOOLEAN DEFAULT FALSE,
    recurrence_rule TEXT,
    affects_classes BOOLEAN DEFAULT TRUE,
    affects_exams BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_holiday_date (holiday_date),
    INDEX idx_holiday_type (holiday_type),
    INDEX idx_is_active (is_active)
);

-- Insertar tipos de eventos por defecto
INSERT INTO event_types (name, description, color, icon, is_public, default_duration) VALUES
('Clase Regular', 'Clase académica regular', '#3B82F6', 'academic-cap', TRUE, 45),
('Examen', 'Evaluación o examen', '#EF4444', 'document-text', TRUE, 90),
('Reunión de Padres', 'Reunión con padres de familia', '#10B981', 'users', TRUE, 60),
('Evento Deportivo', 'Competencia o actividad deportiva', '#F59E0B', 'trophy', TRUE, 120),
('Actividad Cultural', 'Evento cultural o artístico', '#8B5CF6', 'musical-note', TRUE, 90),
('Reunión de Personal', 'Reunión del personal docente/administrativo', '#06B6D4', 'user-group', FALSE, 60),
('Cita Médica', 'Consulta médica', '#EF4444', 'heart', FALSE, 30),
('Tutoría', 'Sesión de tutoría individual', '#84CC16', 'chat-bubble-left-right', TRUE, 45),
('Ceremonia', 'Ceremonia de graduación u otro evento especial', '#F97316', 'star', TRUE, 180),
('Mantenimiento', 'Actividad de mantenimiento de instalaciones', '#6B7280', 'wrench-screwdriver', FALSE, 120);

-- Insertar calendarios por defecto
INSERT INTO calendars (name, description, calendar_type, color, is_public, is_default) VALUES
('Calendario Académico', 'Calendario principal de actividades académicas', 'academic', '#3B82F6', TRUE, TRUE),
('Calendario Deportivo', 'Eventos y competencias deportivas', 'sports', '#F59E0B', TRUE, FALSE),
('Calendario Médico', 'Citas y actividades médicas', 'medical', '#EF4444', FALSE, FALSE),
('Calendario Administrativo', 'Reuniones y eventos administrativos', 'administrative', '#8B5CF6', FALSE, FALSE),
('Calendario Institucional', 'Eventos generales de la institución', 'institutional', '#10B981', TRUE, FALSE);

-- Insertar recursos por defecto
INSERT INTO event_resources (name, description, resource_type, capacity, location) VALUES
('Aula 101', 'Salón de clases estándar', 'room', 30, 'Edificio A - Primer Piso'),
('Aula 102', 'Salón de clases estándar', 'room', 30, 'Edificio A - Primer Piso'),
('Laboratorio de Ciencias', 'Laboratorio equipado para experimentos', 'room', 25, 'Edificio B - Segundo Piso'),
('Auditorio Principal', 'Auditorio para eventos grandes', 'room', 200, 'Edificio Principal'),
('Cancha de Fútbol', 'Campo de fútbol principal', 'room', 50, 'Área Deportiva'),
('Proyector Portátil', 'Proyector para presentaciones', 'equipment', 1, 'Almacén de Equipos'),
('Sistema de Sonido', 'Equipo de audio para eventos', 'equipment', 1, 'Almacén de Equipos'),
('Bus Escolar 1', 'Vehículo para transporte estudiantil', 'vehicle', 40, 'Parqueadero Principal'),
('Enfermería', 'Consultorio médico', 'room', 5, 'Edificio Administrativo');

-- Insertar días festivos por defecto (Colombia)
INSERT INTO holidays (name, description, holiday_date, holiday_type, is_recurring, affects_classes) VALUES
('Año Nuevo', 'Celebración de Año Nuevo', '2024-01-01', 'national', TRUE, TRUE),
('Día de los Reyes Magos', 'Epifanía', '2024-01-08', 'religious', TRUE, TRUE),
('Día de San José', 'Día de San José', '2024-03-25', 'religious', TRUE, TRUE),
('Jueves Santo', 'Jueves Santo', '2024-03-28', 'religious', FALSE, TRUE),
('Viernes Santo', 'Viernes Santo', '2024-03-29', 'religious', FALSE, TRUE),
('Día del Trabajo', 'Día Internacional del Trabajo', '2024-05-01', 'national', TRUE, TRUE),
('Ascensión del Señor', 'Ascensión del Señor', '2024-05-13', 'religious', FALSE, TRUE),
('Corpus Christi', 'Corpus Christi', '2024-06-03', 'religious', FALSE, TRUE),
('Sagrado Corazón de Jesús', 'Sagrado Corazón de Jesús', '2024-06-10', 'religious', FALSE, TRUE),
('San Pedro y San Pablo', 'San Pedro y San Pablo', '2024-07-01', 'religious', TRUE, TRUE),
('Día de la Independencia', 'Independencia de Colombia', '2024-07-20', 'national', TRUE, TRUE),
('Batalla de Boyacá', 'Batalla de Boyacá', '2024-08-07', 'national', TRUE, TRUE),
('Asunción de la Virgen', 'Asunción de la Virgen', '2024-08-19', 'religious', TRUE, TRUE),
('Día de la Raza', 'Día de la Raza', '2024-10-14', 'national', TRUE, TRUE),
('Todos los Santos', 'Día de Todos los Santos', '2024-11-04', 'religious', TRUE, TRUE),
('Independencia de Cartagena', 'Independencia de Cartagena', '2024-11-11', 'national', TRUE, TRUE),
('Inmaculada Concepción', 'Inmaculada Concepción', '2024-12-08', 'religious', TRUE, TRUE),
('Navidad', 'Celebración de Navidad', '2024-12-25', 'religious', TRUE, TRUE);

-- Insertar configuraciones por defecto
INSERT INTO calendar_settings (setting_type, setting_key, setting_value) VALUES
('global', 'default_view', 'month'),
('global', 'week_starts_on', 'monday'),
('global', 'working_hours_start', '07:00'),
('global', 'working_hours_end', '18:00'),
('global', 'default_event_duration', '60'),
('global', 'allow_overlapping_events', 'false'),
('global', 'auto_accept_invitations', 'false'),
('global', 'default_reminder_time', '15'),
('global', 'max_events_per_day', '10'),
('global', 'enable_recurring_events', 'true');