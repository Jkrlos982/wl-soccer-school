-- WL School Notification Service Database Schema
-- Created for microservices architecture

USE wl_school_notifications;

-- Notifications table - stores all notification records
CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) NOT NULL UNIQUE,
    type VARCHAR(50) NOT NULL, -- email, sms, whatsapp, push, in_app
    channel VARCHAR(50) NOT NULL, -- specific channel within type
    recipient_type VARCHAR(50) NOT NULL, -- user, student, parent, teacher, admin
    recipient_id BIGINT UNSIGNED NOT NULL,
    recipient_contact VARCHAR(255) NOT NULL, -- email, phone, device_token
    subject VARCHAR(255) NULL,
    title VARCHAR(255) NULL,
    message TEXT NOT NULL,
    data JSON NULL, -- additional data for the notification
    template_id BIGINT UNSIGNED NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    status ENUM('pending', 'sent', 'delivered', 'failed', 'read') DEFAULT 'pending',
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    failed_at TIMESTAMP NULL,
    failure_reason TEXT NULL,
    retry_count INT DEFAULT 0,
    max_retries INT DEFAULT 3,
    external_id VARCHAR(255) NULL, -- ID from external service (Twilio, etc.)
    cost DECIMAL(8,4) DEFAULT 0.0000, -- cost of sending notification
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_recipient (recipient_type, recipient_id),
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_scheduled (scheduled_at),
    INDEX idx_created (created_at),
    INDEX idx_uuid (uuid)
);

-- Notification templates table
CREATE TABLE IF NOT EXISTS notification_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    type VARCHAR(50) NOT NULL, -- email, sms, whatsapp, push
    subject VARCHAR(255) NULL,
    title VARCHAR(255) NULL,
    body TEXT NOT NULL,
    variables JSON NULL, -- available variables for template
    is_active BOOLEAN DEFAULT TRUE,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (name),
    INDEX idx_type (type),
    INDEX idx_active (is_active)
);

-- Notification preferences table - user preferences for notifications
CREATE TABLE IF NOT EXISTS notification_preferences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    user_type VARCHAR(50) NOT NULL, -- student, parent, teacher, admin
    notification_type VARCHAR(50) NOT NULL, -- attendance, grades, payments, etc.
    email_enabled BOOLEAN DEFAULT TRUE,
    sms_enabled BOOLEAN DEFAULT FALSE,
    whatsapp_enabled BOOLEAN DEFAULT FALSE,
    push_enabled BOOLEAN DEFAULT TRUE,
    in_app_enabled BOOLEAN DEFAULT TRUE,
    frequency ENUM('immediate', 'daily', 'weekly', 'never') DEFAULT 'immediate',
    quiet_hours_start TIME NULL,
    quiet_hours_end TIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_type (user_id, user_type, notification_type),
    INDEX idx_user (user_id, user_type)
);

-- Device tokens table - for push notifications
CREATE TABLE IF NOT EXISTS device_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    user_type VARCHAR(50) NOT NULL,
    token VARCHAR(500) NOT NULL,
    platform ENUM('ios', 'android', 'web') NOT NULL,
    device_info JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_token (token),
    INDEX idx_user (user_id, user_type),
    INDEX idx_active (is_active)
);

-- Notification batches table - for bulk notifications
CREATE TABLE IF NOT EXISTS notification_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    total_count INT DEFAULT 0,
    sent_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);

-- Notification batch items table
CREATE TABLE IF NOT EXISTS notification_batch_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id BIGINT UNSIGNED NOT NULL,
    notification_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (batch_id) REFERENCES notification_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
    INDEX idx_batch (batch_id),
    INDEX idx_status (status)
);

-- Webhook logs table - for incoming webhooks
CREATE TABLE IF NOT EXISTS webhook_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(50) NOT NULL, -- twilio, whatsapp, firebase, etc.
    event_type VARCHAR(100) NOT NULL,
    payload JSON NOT NULL,
    headers JSON NULL,
    notification_id BIGINT UNSIGNED NULL,
    processed BOOLEAN DEFAULT FALSE,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE SET NULL,
    INDEX idx_provider (provider),
    INDEX idx_processed (processed),
    INDEX idx_created (created_at)
);

-- Notification statistics table - for analytics
CREATE TABLE IF NOT EXISTS notification_statistics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    type VARCHAR(50) NOT NULL,
    channel VARCHAR(50) NOT NULL,
    total_sent INT DEFAULT 0,
    total_delivered INT DEFAULT 0,
    total_failed INT DEFAULT 0,
    total_read INT DEFAULT 0,
    total_cost DECIMAL(10,4) DEFAULT 0.0000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_date_type_channel (date, type, channel),
    INDEX idx_date (date),
    INDEX idx_type (type)
);

-- Insert default notification templates
INSERT INTO notification_templates (name, type, subject, title, body, variables) VALUES
('welcome_email', 'email', 'Bienvenido a WL School', 'Bienvenido', 'Hola {{name}}, bienvenido a WL School. Tu cuenta ha sido creada exitosamente.', '["name", "email", "school_name"]'),
('payment_reminder', 'email', 'Recordatorio de Pago - WL School', 'Recordatorio de Pago', 'Estimado {{parent_name}}, le recordamos que tiene un pago pendiente por ${{amount}} con vencimiento el {{due_date}}.', '["parent_name", "student_name", "amount", "due_date", "concept"]'),
('attendance_alert', 'sms', NULL, 'Alerta de Asistencia', '{{student_name}} no asistió a clases hoy {{date}}. Para más información contacte la escuela.', '["student_name", "date", "parent_name"]'),
('grade_notification', 'push', NULL, 'Nueva Calificación', '{{student_name}} tiene una nueva calificación en {{subject}}: {{grade}}', '["student_name", "subject", "grade", "teacher_name"]'),
('event_reminder', 'whatsapp', NULL, 'Recordatorio de Evento', 'Recordatorio: {{event_name}} será el {{event_date}} a las {{event_time}}. ¡No faltes!', '["event_name", "event_date", "event_time", "location"]');

-- Insert default notification preferences for common notification types
INSERT INTO notification_preferences (user_id, user_type, notification_type, email_enabled, sms_enabled, whatsapp_enabled, push_enabled, in_app_enabled) VALUES
(0, 'default', 'attendance', TRUE, TRUE, FALSE, TRUE, TRUE),
(0, 'default', 'grades', TRUE, FALSE, FALSE, TRUE, TRUE),
(0, 'default', 'payments', TRUE, TRUE, TRUE, TRUE, TRUE),
(0, 'default', 'events', TRUE, FALSE, TRUE, TRUE, TRUE),
(0, 'default', 'announcements', TRUE, FALSE, FALSE, TRUE, TRUE),
(0, 'default', 'emergency', TRUE, TRUE, TRUE, TRUE, TRUE);

-- Create indexes for better performance
CREATE INDEX idx_notifications_composite ON notifications(type, status, created_at);
CREATE INDEX idx_notifications_recipient_status ON notifications(recipient_type, recipient_id, status);
CREATE INDEX idx_device_tokens_user_active ON device_tokens(user_id, user_type, is_active);

-- Create views for common queries
CREATE VIEW notification_summary AS
SELECT 
    DATE(created_at) as date,
    type,
    status,
    COUNT(*) as count,
    SUM(cost) as total_cost
FROM notifications 
GROUP BY DATE(created_at), type, status;

CREATE VIEW user_notification_stats AS
SELECT 
    recipient_type,
    recipient_id,
    COUNT(*) as total_notifications,
    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_count,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
    SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count
FROM notifications 
GROUP BY recipient_type, recipient_id;