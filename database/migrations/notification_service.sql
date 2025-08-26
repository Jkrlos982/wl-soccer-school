-- Migraciones para Notification Service
-- Base de datos: wl_school_notifications

USE wl_school_notifications;

-- Tabla de plantillas de notificación
CREATE TABLE notification_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('email', 'sms', 'push', 'in_app') NOT NULL,
    category ENUM('academic', 'financial', 'sports', 'medical', 'general', 'emergency') NOT NULL,
    subject VARCHAR(255),
    title VARCHAR(150),
    body TEXT NOT NULL,
    variables JSON, -- Variables disponibles para la plantilla
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type_category (type, category),
    INDEX idx_is_active (is_active)
);

-- Tabla de canales de notificación
CREATE TABLE notification_channels (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('email', 'sms', 'push', 'webhook') NOT NULL,
    configuration JSON NOT NULL, -- Configuración específica del canal
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_is_active (is_active)
);

-- Tabla de grupos de destinatarios
CREATE TABLE recipient_groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    criteria JSON, -- Criterios para incluir usuarios automáticamente
    is_dynamic BOOLEAN DEFAULT FALSE, -- Si se actualiza automáticamente
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
);

-- Tabla de miembros de grupos
CREATE TABLE group_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    added_by BIGINT UNSIGNED,
    FOREIGN KEY (group_id) REFERENCES recipient_groups(id) ON DELETE CASCADE,
    UNIQUE KEY unique_group_user (group_id, user_id),
    INDEX idx_user_id (user_id)
);

-- Tabla de notificaciones
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    template_id BIGINT UNSIGNED,
    sender_id BIGINT UNSIGNED,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('email', 'sms', 'push', 'in_app', 'broadcast') NOT NULL,
    category ENUM('academic', 'financial', 'sports', 'medical', 'general', 'emergency') NOT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    status ENUM('draft', 'scheduled', 'sending', 'sent', 'failed', 'cancelled') DEFAULT 'draft',
    metadata JSON, -- Datos adicionales específicos del tipo
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES notification_templates(id) ON DELETE SET NULL,
    INDEX idx_uuid (uuid),
    INDEX idx_type_category (type, category),
    INDEX idx_status (status),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_priority (priority)
);

-- Tabla de destinatarios de notificaciones
CREATE TABLE notification_recipients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notification_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    recipient_type ENUM('to', 'cc', 'bcc') DEFAULT 'to',
    contact_info VARCHAR(255), -- Email, teléfono, etc.
    delivery_status ENUM('pending', 'sent', 'delivered', 'failed', 'bounced') DEFAULT 'pending',
    delivered_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    clicked_at TIMESTAMP NULL,
    error_message TEXT,
    attempts INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
    INDEX idx_notification_user (notification_id, user_id),
    INDEX idx_delivery_status (delivery_status),
    INDEX idx_user_id (user_id)
);

-- Tabla de preferencias de notificación por usuario
CREATE TABLE user_notification_preferences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    category ENUM('academic', 'financial', 'sports', 'medical', 'general', 'emergency') NOT NULL,
    email_enabled BOOLEAN DEFAULT TRUE,
    sms_enabled BOOLEAN DEFAULT FALSE,
    push_enabled BOOLEAN DEFAULT TRUE,
    in_app_enabled BOOLEAN DEFAULT TRUE,
    frequency ENUM('immediate', 'daily_digest', 'weekly_digest', 'disabled') DEFAULT 'immediate',
    quiet_hours_start TIME DEFAULT '22:00:00',
    quiet_hours_end TIME DEFAULT '07:00:00',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_category (user_id, category),
    INDEX idx_user_id (user_id)
);

-- Tabla de dispositivos para push notifications
CREATE TABLE user_devices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    device_token VARCHAR(255) NOT NULL,
    device_type ENUM('ios', 'android', 'web') NOT NULL,
    device_name VARCHAR(100),
    app_version VARCHAR(20),
    os_version VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_device_token (device_token),
    INDEX idx_is_active (is_active)
);

-- Tabla de campañas de notificación
CREATE TABLE notification_campaigns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    template_id BIGINT UNSIGNED,
    target_groups JSON, -- IDs de grupos objetivo
    target_criteria JSON, -- Criterios de segmentación
    scheduled_at TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    status ENUM('draft', 'scheduled', 'running', 'completed', 'paused', 'cancelled') DEFAULT 'draft',
    total_recipients INT DEFAULT 0,
    sent_count INT DEFAULT 0,
    delivered_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES notification_templates(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_scheduled_at (scheduled_at)
);

-- Tabla de eventos de notificación (para tracking)
CREATE TABLE notification_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notification_id BIGINT UNSIGNED NOT NULL,
    recipient_id BIGINT UNSIGNED NOT NULL,
    event_type ENUM('sent', 'delivered', 'opened', 'clicked', 'bounced', 'complained', 'unsubscribed') NOT NULL,
    event_data JSON,
    user_agent TEXT,
    ip_address VARCHAR(45),
    occurred_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES notification_recipients(id) ON DELETE CASCADE,
    INDEX idx_notification_id (notification_id),
    INDEX idx_event_type (event_type),
    INDEX idx_occurred_at (occurred_at)
);

-- Tabla de configuración del sistema de notificaciones
CREATE TABLE notification_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    is_encrypted BOOLEAN DEFAULT FALSE,
    updated_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
);

-- Insertar plantillas por defecto
INSERT INTO notification_templates (name, type, category, subject, title, body, variables) VALUES
('Bienvenida Estudiante', 'email', 'general', 'Bienvenido a WL-School', 'Bienvenido {{student_name}}', 
 'Estimado {{student_name}},\n\nBienvenido a WL-School. Tu código de estudiante es: {{student_code}}.\n\nSaludos cordiales,\nEquipo WL-School', 
 JSON_ARRAY('student_name', 'student_code')),

('Recordatorio Pago', 'email', 'financial', 'Recordatorio de Pago - Factura {{invoice_number}}', 'Recordatorio de Pago', 
 'Estimado padre de familia,\n\nLe recordamos que tiene una factura pendiente por ${{amount}} con vencimiento {{due_date}}.\n\nFactura: {{invoice_number}}\n\nGracias por su atención.', 
 JSON_ARRAY('invoice_number', 'amount', 'due_date', 'student_name')),

('Calificación Publicada', 'push', 'academic', NULL, 'Nueva Calificación', 
 'Se ha publicado una nueva calificación para {{subject}}. Calificación: {{grade}}', 
 JSON_ARRAY('subject', 'grade', 'student_name')),

('Partido Programado', 'in_app', 'sports', NULL, 'Nuevo Partido', 
 'Se ha programado un partido de {{sport}} para el {{match_date}} contra {{opponent_team}}.', 
 JSON_ARRAY('sport', 'match_date', 'opponent_team', 'team_name')),

('Emergencia Médica', 'sms', 'medical', NULL, 'Emergencia Médica', 
 'URGENTE: Su hijo {{student_name}} requiere atención médica. Contacte inmediatamente al colegio.', 
 JSON_ARRAY('student_name'));

-- Insertar grupos por defecto
INSERT INTO recipient_groups (name, description, criteria, is_dynamic) VALUES
('Todos los Padres', 'Todos los padres de familia registrados', JSON_OBJECT('role', 'parent'), TRUE),
('Todos los Estudiantes', 'Todos los estudiantes activos', JSON_OBJECT('role', 'student', 'status', 'active'), TRUE),
('Profesores', 'Todo el personal docente', JSON_OBJECT('role', 'teacher'), TRUE),
('Administradores', 'Personal administrativo', JSON_OBJECT('role', 'admin'), TRUE),
('Estudiantes Primaria', 'Estudiantes de primaria', JSON_OBJECT('role', 'student', 'grade_level', 'primary'), TRUE),
('Estudiantes Secundaria', 'Estudiantes de secundaria', JSON_OBJECT('role', 'student', 'grade_level', 'secondary'), TRUE);

-- Insertar configuraciones por defecto
INSERT INTO notification_settings (setting_key, setting_value, description) VALUES
('smtp_host', 'smtp.gmail.com', 'Servidor SMTP para envío de emails'),
('smtp_port', '587', 'Puerto SMTP'),
('smtp_encryption', 'tls', 'Tipo de encriptación SMTP'),
('sms_provider', 'twilio', 'Proveedor de SMS'),
('push_provider', 'firebase', 'Proveedor de push notifications'),
('max_daily_emails', '1000', 'Máximo de emails por día'),
('max_daily_sms', '500', 'Máximo de SMS por día'),
('retry_attempts', '3', 'Intentos de reenvío en caso de fallo'),
('batch_size', '100', 'Tamaño de lote para envío masivo');