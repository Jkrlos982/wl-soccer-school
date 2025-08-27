-- Migraciones para Report Service
-- Base de datos: wl_school_reports

USE wl_school_reports;

-- Tabla de categorías de reportes
CREATE TABLE report_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    color VARCHAR(7), -- Color hex
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_is_active (is_active),
    INDEX idx_sort_order (sort_order)
);

-- Tabla de plantillas de reportes
CREATE TABLE report_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    template_type ENUM('academic', 'financial', 'administrative', 'medical', 'sports', 'attendance', 'behavioral', 'custom') NOT NULL,
    output_format ENUM('pdf', 'excel', 'csv', 'html', 'json') DEFAULT 'pdf',
    template_content LONGTEXT, -- HTML/JSON template
    sql_query LONGTEXT, -- Query para obtener datos
    parameters JSON, -- Parámetros configurables
    filters JSON, -- Filtros disponibles
    charts_config JSON, -- Configuración de gráficos
    permissions JSON, -- Permisos por rol
    is_public BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES report_categories(id) ON DELETE CASCADE,
    INDEX idx_category (category_id),
    INDEX idx_template_type (template_type),
    INDEX idx_is_active (is_active),
    INDEX idx_created_by (created_by)
);

-- Tabla de reportes generados
CREATE TABLE generated_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_number VARCHAR(20) UNIQUE NOT NULL,
    template_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    parameters JSON, -- Parámetros utilizados
    filters JSON, -- Filtros aplicados
    data JSON, -- Datos del reporte
    output_format ENUM('pdf', 'excel', 'csv', 'html', 'json') NOT NULL,
    file_path VARCHAR(500), -- Ruta del archivo generado
    file_size BIGINT UNSIGNED, -- Tamaño en bytes
    status ENUM('generating', 'completed', 'failed', 'expired') DEFAULT 'generating',
    generation_time INT UNSIGNED, -- Tiempo de generación en segundos
    error_message TEXT,
    generated_by BIGINT UNSIGNED NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL, -- Fecha de expiración
    downloaded_count INT DEFAULT 0,
    last_downloaded_at TIMESTAMP NULL,
    is_scheduled BOOLEAN DEFAULT FALSE,
    schedule_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES report_templates(id) ON DELETE CASCADE,
    INDEX idx_report_number (report_number),
    INDEX idx_template_id (template_id),
    INDEX idx_status (status),
    INDEX idx_generated_by (generated_by),
    INDEX idx_generated_at (generated_at),
    INDEX idx_expires_at (expires_at)
);

-- Tabla de programación de reportes
CREATE TABLE report_schedules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    frequency ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom') NOT NULL,
    cron_expression VARCHAR(100), -- Para frecuencias custom
    parameters JSON, -- Parámetros por defecto
    filters JSON, -- Filtros por defecto
    recipients JSON, -- Lista de destinatarios
    delivery_method ENUM('email', 'download', 'both') DEFAULT 'email',
    output_format ENUM('pdf', 'excel', 'csv', 'html') DEFAULT 'pdf',
    is_active BOOLEAN DEFAULT TRUE,
    last_run_at TIMESTAMP NULL,
    next_run_at TIMESTAMP NULL,
    run_count INT DEFAULT 0,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES report_templates(id) ON DELETE CASCADE,
    INDEX idx_template_id (template_id),
    INDEX idx_frequency (frequency),
    INDEX idx_is_active (is_active),
    INDEX idx_next_run_at (next_run_at),
    INDEX idx_created_by (created_by)
);

-- Tabla de ejecuciones de reportes programados
CREATE TABLE schedule_executions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    schedule_id BIGINT UNSIGNED NOT NULL,
    report_id BIGINT UNSIGNED NULL,
    execution_date TIMESTAMP NOT NULL,
    status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
    start_time TIMESTAMP NULL,
    end_time TIMESTAMP NULL,
    execution_time INT UNSIGNED, -- Tiempo en segundos
    error_message TEXT,
    recipients_notified INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES report_schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (report_id) REFERENCES generated_reports(id) ON DELETE SET NULL,
    INDEX idx_schedule_id (schedule_id),
    INDEX idx_execution_date (execution_date),
    INDEX idx_status (status)
);

-- Tabla de dashboards
CREATE TABLE dashboards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    dashboard_type ENUM('executive', 'academic', 'financial', 'operational', 'student', 'parent', 'teacher', 'custom') NOT NULL,
    layout JSON NOT NULL, -- Configuración del layout
    widgets JSON NOT NULL, -- Configuración de widgets
    filters JSON, -- Filtros globales
    refresh_interval INT DEFAULT 300, -- Intervalo de actualización en segundos
    permissions JSON, -- Permisos por rol
    is_public BOOLEAN DEFAULT FALSE,
    is_default BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dashboard_type (dashboard_type),
    INDEX idx_is_active (is_active),
    INDEX idx_is_default (is_default),
    INDEX idx_created_by (created_by)
);

-- Tabla de widgets de dashboard
CREATE TABLE dashboard_widgets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dashboard_id BIGINT UNSIGNED NOT NULL,
    widget_type ENUM('chart', 'table', 'metric', 'progress', 'list', 'calendar', 'map', 'custom') NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    data_source VARCHAR(100) NOT NULL, -- Fuente de datos
    query_config JSON NOT NULL, -- Configuración de la consulta
    chart_config JSON, -- Configuración específica del gráfico
    position_x INT NOT NULL,
    position_y INT NOT NULL,
    width INT NOT NULL,
    height INT NOT NULL,
    refresh_interval INT DEFAULT 300,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (dashboard_id) REFERENCES dashboards(id) ON DELETE CASCADE,
    INDEX idx_dashboard_id (dashboard_id),
    INDEX idx_widget_type (widget_type),
    INDEX idx_is_active (is_active)
);

-- Tabla de métricas del sistema
CREATE TABLE system_metrics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(100) NOT NULL,
    metric_type ENUM('counter', 'gauge', 'histogram', 'summary') NOT NULL,
    value DECIMAL(15,4) NOT NULL,
    unit VARCHAR(20),
    tags JSON, -- Etiquetas adicionales
    timestamp TIMESTAMP NOT NULL,
    service_name VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metric_name (metric_name),
    INDEX idx_timestamp (timestamp),
    INDEX idx_service_name (service_name),
    INDEX idx_metric_timestamp (metric_name, timestamp)
);

-- Tabla de reportes de auditoría
CREATE TABLE audit_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_type ENUM('user_activity', 'data_changes', 'system_access', 'financial_transactions', 'academic_changes', 'security_events') NOT NULL,
    entity_type VARCHAR(50), -- Tipo de entidad auditada
    entity_id BIGINT UNSIGNED, -- ID de la entidad
    user_id BIGINT UNSIGNED, -- Usuario que realizó la acción
    action VARCHAR(50) NOT NULL, -- Acción realizada
    old_values JSON, -- Valores anteriores
    new_values JSON, -- Valores nuevos
    ip_address VARCHAR(45),
    user_agent TEXT,
    timestamp TIMESTAMP NOT NULL,
    additional_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_type (report_type),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_user_id (user_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_action (action)
);

-- Tabla de alertas y notificaciones de reportes
CREATE TABLE report_alerts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alert_type ENUM('threshold', 'anomaly', 'schedule_failure', 'data_quality', 'performance') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    metric_name VARCHAR(100),
    threshold_value DECIMAL(15,4),
    current_value DECIMAL(15,4),
    condition_met BOOLEAN DEFAULT FALSE,
    recipients JSON, -- Lista de destinatarios
    notification_sent BOOLEAN DEFAULT FALSE,
    notification_sent_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_alert_type (alert_type),
    INDEX idx_severity (severity),
    INDEX idx_condition_met (condition_met),
    INDEX idx_is_active (is_active)
);

-- Tabla de configuración de reportes
CREATE TABLE report_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json', 'array') DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key),
    INDEX idx_is_public (is_public)
);

-- Insertar categorías de reportes por defecto
INSERT INTO report_categories (name, description, icon, color, sort_order) VALUES
('Académicos', 'Reportes relacionados con el rendimiento académico', 'academic-cap', '#3B82F6', 1),
('Financieros', 'Reportes de ingresos, gastos y estados financieros', 'currency-dollar', '#10B981', 2),
('Administrativos', 'Reportes de gestión administrativa y operativa', 'clipboard-document-list', '#8B5CF6', 3),
('Médicos', 'Reportes de salud y servicios médicos', 'heart', '#EF4444', 4),
('Deportivos', 'Reportes de actividades deportivas y competencias', 'trophy', '#F59E0B', 5),
('Asistencia', 'Reportes de asistencia de estudiantes y personal', 'calendar-days', '#06B6D4', 6),
('Comportamiento', 'Reportes disciplinarios y de comportamiento', 'shield-check', '#84CC16', 7),
('Recursos Humanos', 'Reportes de nómina y gestión de personal', 'users', '#F97316', 8),
('Personalizados', 'Reportes creados por usuarios', 'cog-6-tooth', '#6B7280', 9);

-- Insertar plantillas de reportes por defecto
INSERT INTO report_templates (category_id, name, description, template_type, output_format, is_public, is_active) VALUES
(1, 'Boletín de Calificaciones', 'Reporte individual de calificaciones por estudiante', 'academic', 'pdf', TRUE, TRUE),
(1, 'Reporte de Rendimiento por Curso', 'Análisis de rendimiento académico por curso', 'academic', 'excel', TRUE, TRUE),
(1, 'Estadísticas Académicas Generales', 'Resumen estadístico del rendimiento académico', 'academic', 'pdf', TRUE, TRUE),
(2, 'Estado de Cuenta Estudiante', 'Detalle de pagos y deudas por estudiante', 'financial', 'pdf', TRUE, TRUE),
(2, 'Reporte de Ingresos Mensual', 'Resumen de ingresos por mes', 'financial', 'excel', TRUE, TRUE),
(2, 'Análisis de Cartera', 'Reporte de cuentas por cobrar', 'financial', 'excel', TRUE, TRUE),
(3, 'Reporte de Matrícula', 'Estadísticas de matrícula por período', 'administrative', 'pdf', TRUE, TRUE),
(3, 'Inventario de Recursos', 'Listado de recursos y materiales', 'administrative', 'excel', TRUE, TRUE),
(4, 'Historial Médico Estudiante', 'Registro médico completo por estudiante', 'medical', 'pdf', FALSE, TRUE),
(4, 'Reporte de Vacunación', 'Estado de vacunación por curso', 'medical', 'excel', TRUE, TRUE),
(5, 'Resultados Deportivos', 'Resultados de competencias deportivas', 'sports', 'pdf', TRUE, TRUE),
(5, 'Estadísticas de Equipos', 'Rendimiento de equipos deportivos', 'sports', 'excel', TRUE, TRUE),
(6, 'Reporte de Asistencia Mensual', 'Asistencia de estudiantes por mes', 'attendance', 'excel', TRUE, TRUE),
(6, 'Ausentismo por Curso', 'Análisis de ausentismo escolar', 'attendance', 'pdf', TRUE, TRUE),
(7, 'Reporte Disciplinario', 'Incidentes y medidas disciplinarias', 'behavioral', 'pdf', FALSE, TRUE),
(8, 'Nómina Mensual', 'Reporte detallado de nómina', 'administrative', 'excel', FALSE, TRUE);

-- Insertar dashboards por defecto
INSERT INTO dashboards (name, description, dashboard_type, layout, widgets, is_public, is_default, sort_order, created_by) VALUES
('Dashboard Ejecutivo', 'Vista general para directivos', 'executive', '{}', '[]', TRUE, TRUE, 1, 1),
('Dashboard Académico', 'Métricas académicas para coordinadores', 'academic', '{}', '[]', TRUE, FALSE, 2, 1),
('Dashboard Financiero', 'Indicadores financieros', 'financial', '{}', '[]', TRUE, FALSE, 3, 1),
('Dashboard Operativo', 'Métricas operativas diarias', 'operational', '{}', '[]', TRUE, FALSE, 4, 1);

-- Insertar configuraciones por defecto
INSERT INTO report_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('max_report_size', '50', 'number', 'Tamaño máximo de reporte en MB', FALSE),
('report_retention_days', '90', 'number', 'Días de retención de reportes generados', FALSE),
('default_output_format', 'pdf', 'string', 'Formato de salida por defecto', TRUE),
('enable_scheduled_reports', 'true', 'boolean', 'Habilitar reportes programados', FALSE),
('max_concurrent_reports', '5', 'number', 'Máximo de reportes concurrentes', FALSE),
('dashboard_refresh_interval', '300', 'number', 'Intervalo de actualización de dashboards en segundos', TRUE),
('enable_report_caching', 'true', 'boolean', 'Habilitar caché de reportes', FALSE),
('report_cache_ttl', '3600', 'number', 'Tiempo de vida del caché en segundos', FALSE);