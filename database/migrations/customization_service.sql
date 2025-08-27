-- Migraciones para Customization Service
-- Base de datos: wl_school_customization

USE wl_school_customization;

-- Tabla de temas del sistema
CREATE TABLE themes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    display_name VARCHAR(150) NOT NULL,
    description TEXT,
    version VARCHAR(20) DEFAULT '1.0.0',
    author VARCHAR(100),
    preview_image VARCHAR(500),
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    is_premium BOOLEAN DEFAULT FALSE,
    price DECIMAL(8,2) DEFAULT 0,
    download_count INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_is_default (is_default),
    INDEX idx_is_active (is_active),
    INDEX idx_is_premium (is_premium)
);

-- Tabla de configuraciones de tema
CREATE TABLE theme_configurations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    theme_id BIGINT UNSIGNED NOT NULL,
    config_key VARCHAR(100) NOT NULL,
    config_value TEXT,
    config_type ENUM('color', 'font', 'size', 'boolean', 'string', 'number', 'json') NOT NULL,
    category ENUM('colors', 'typography', 'layout', 'components', 'animations', 'other') DEFAULT 'other',
    display_name VARCHAR(150),
    description TEXT,
    default_value TEXT,
    validation_rules JSON,
    sort_order INT DEFAULT 0,
    is_customizable BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_theme_config (theme_id, config_key),
    INDEX idx_theme_id (theme_id),
    INDEX idx_config_key (config_key),
    INDEX idx_category (category)
);

-- Tabla de personalizaciones por usuario/institución
CREATE TABLE user_customizations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    institution_id BIGINT UNSIGNED,
    customization_type ENUM('user', 'institution', 'role', 'global') NOT NULL,
    theme_id BIGINT UNSIGNED NOT NULL,
    custom_css LONGTEXT,
    custom_js LONGTEXT,
    logo_url VARCHAR(500),
    favicon_url VARCHAR(500),
    brand_colors JSON,
    typography_settings JSON,
    layout_settings JSON,
    component_settings JSON,
    is_active BOOLEAN DEFAULT TRUE,
    applied_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_institution_id (institution_id),
    INDEX idx_customization_type (customization_type),
    INDEX idx_is_active (is_active)
);

-- Tabla de configuraciones del sistema
CREATE TABLE system_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_group VARCHAR(50) NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json', 'array', 'file', 'color', 'email', 'url') DEFAULT 'string',
    display_name VARCHAR(150),
    description TEXT,
    default_value TEXT,
    validation_rules JSON,
    is_public BOOLEAN DEFAULT FALSE,
    is_editable BOOLEAN DEFAULT TRUE,
    requires_restart BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_group_key (setting_group, setting_key),
    INDEX idx_setting_group (setting_group),
    INDEX idx_setting_key (setting_key),
    INDEX idx_is_public (is_public)
);

-- Tabla de configuraciones por rol
CREATE TABLE role_configurations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL,
    config_key VARCHAR(100) NOT NULL,
    config_value TEXT,
    config_type ENUM('permission', 'ui_element', 'feature', 'limit', 'setting') NOT NULL,
    display_name VARCHAR(150),
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_role_config (role_name, config_key),
    INDEX idx_role_name (role_name),
    INDEX idx_config_type (config_type),
    INDEX idx_is_active (is_active)
);

-- Tabla de plantillas de personalización
CREATE TABLE customization_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    template_type ENUM('theme', 'layout', 'component', 'page', 'email', 'report') NOT NULL,
    category VARCHAR(50),
    template_data JSON NOT NULL,
    preview_image VARCHAR(500),
    is_public BOOLEAN DEFAULT FALSE,
    is_premium BOOLEAN DEFAULT FALSE,
    price DECIMAL(8,2) DEFAULT 0,
    usage_count INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_template_type (template_type),
    INDEX idx_category (category),
    INDEX idx_is_public (is_public),
    INDEX idx_created_by (created_by)
);

-- Tabla de widgets personalizables
CREATE TABLE custom_widgets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    display_name VARCHAR(150) NOT NULL,
    description TEXT,
    widget_type ENUM('chart', 'table', 'card', 'list', 'calendar', 'form', 'custom') NOT NULL,
    component_code LONGTEXT NOT NULL,
    css_styles LONGTEXT,
    js_code LONGTEXT,
    configuration_schema JSON,
    default_config JSON,
    permissions JSON,
    is_active BOOLEAN DEFAULT TRUE,
    is_public BOOLEAN DEFAULT FALSE,
    version VARCHAR(20) DEFAULT '1.0.0',
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_widget_type (widget_type),
    INDEX idx_is_active (is_active),
    INDEX idx_created_by (created_by)
);

-- Tabla de instancias de widgets por usuario
CREATE TABLE user_widget_instances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    widget_id BIGINT UNSIGNED NOT NULL,
    instance_name VARCHAR(150),
    configuration JSON,
    position_x INT DEFAULT 0,
    position_y INT DEFAULT 0,
    width INT DEFAULT 4,
    height INT DEFAULT 3,
    dashboard_page VARCHAR(50) DEFAULT 'main',
    is_visible BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (widget_id) REFERENCES custom_widgets(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_widget_id (widget_id),
    INDEX idx_dashboard_page (dashboard_page),
    INDEX idx_is_visible (is_visible)
);

-- Tabla de menús personalizables
CREATE TABLE custom_menus (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    display_name VARCHAR(150) NOT NULL,
    menu_type ENUM('main', 'sidebar', 'footer', 'context', 'mobile') NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    icon VARCHAR(50),
    url VARCHAR(500),
    route_name VARCHAR(100),
    external_link BOOLEAN DEFAULT FALSE,
    target VARCHAR(20) DEFAULT '_self',
    permissions JSON,
    roles JSON,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    is_visible BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES custom_menus(id) ON DELETE CASCADE,
    INDEX idx_menu_type (menu_type),
    INDEX idx_parent_id (parent_id),
    INDEX idx_sort_order (sort_order),
    INDEX idx_is_active (is_active)
);

-- Tabla de páginas personalizables
CREATE TABLE custom_pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    content LONGTEXT,
    meta_title VARCHAR(200),
    meta_description TEXT,
    meta_keywords VARCHAR(500),
    page_type ENUM('static', 'dynamic', 'landing', 'form', 'dashboard') DEFAULT 'static',
    template_id BIGINT UNSIGNED,
    layout VARCHAR(50) DEFAULT 'default',
    css_styles LONGTEXT,
    js_code LONGTEXT,
    permissions JSON,
    is_published BOOLEAN DEFAULT FALSE,
    is_public BOOLEAN DEFAULT TRUE,
    publish_date TIMESTAMP NULL,
    expire_date TIMESTAMP NULL,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES customization_templates(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_page_type (page_type),
    INDEX idx_is_published (is_published),
    INDEX idx_created_by (created_by)
);

-- Tabla de formularios personalizables
CREATE TABLE custom_forms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    form_schema JSON NOT NULL,
    validation_rules JSON,
    styling JSON,
    submission_settings JSON,
    notification_settings JSON,
    is_active BOOLEAN DEFAULT TRUE,
    is_public BOOLEAN DEFAULT FALSE,
    requires_auth BOOLEAN DEFAULT TRUE,
    max_submissions INT,
    submission_count INT DEFAULT 0,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_is_active (is_active),
    INDEX idx_created_by (created_by)
);

-- Tabla de envíos de formularios
CREATE TABLE form_submissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED,
    submission_data JSON NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    status ENUM('pending', 'processed', 'approved', 'rejected') DEFAULT 'pending',
    processed_by BIGINT UNSIGNED,
    processed_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES custom_forms(id) ON DELETE CASCADE,
    INDEX idx_form_id (form_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Tabla de notificaciones personalizables
CREATE TABLE notification_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    title VARCHAR(200) NOT NULL,
    template_type ENUM('email', 'sms', 'push', 'in_app', 'webhook') NOT NULL,
    subject VARCHAR(300),
    content LONGTEXT NOT NULL,
    variables JSON,
    styling JSON,
    trigger_events JSON,
    conditions JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_template_type (template_type),
    INDEX idx_is_active (is_active),
    INDEX idx_created_by (created_by)
);

-- Tabla de configuraciones de idioma
CREATE TABLE language_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    language_code VARCHAR(10) NOT NULL,
    language_name VARCHAR(50) NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    is_rtl BOOLEAN DEFAULT FALSE,
    date_format VARCHAR(20) DEFAULT 'Y-m-d',
    time_format VARCHAR(20) DEFAULT 'H:i:s',
    currency_symbol VARCHAR(10) DEFAULT '$',
    currency_position ENUM('before', 'after') DEFAULT 'before',
    decimal_separator VARCHAR(5) DEFAULT '.',
    thousands_separator VARCHAR(5) DEFAULT ',',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_language_code (language_code),
    INDEX idx_is_default (is_default),
    INDEX idx_is_active (is_active)
);

-- Tabla de traducciones
CREATE TABLE translations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    language_code VARCHAR(10) NOT NULL,
    translation_key VARCHAR(200) NOT NULL,
    translation_value TEXT NOT NULL,
    context VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (language_code) REFERENCES language_settings(language_code) ON DELETE CASCADE,
    UNIQUE KEY unique_lang_key (language_code, translation_key),
    INDEX idx_language_code (language_code),
    INDEX idx_translation_key (translation_key),
    INDEX idx_context (context)
);

-- Insertar temas por defecto
INSERT INTO themes (name, display_name, description, is_default, is_active) VALUES
('default', 'Tema Por Defecto', 'Tema estándar del sistema WL-School', TRUE, TRUE),
('dark', 'Tema Oscuro', 'Tema con colores oscuros para mejor experiencia nocturna', FALSE, TRUE),
('light', 'Tema Claro', 'Tema con colores claros y brillantes', FALSE, TRUE),
('blue', 'Tema Azul Corporativo', 'Tema con paleta de colores azules', FALSE, TRUE),
('green', 'Tema Verde Naturaleza', 'Tema con paleta de colores verdes', FALSE, TRUE);

-- Insertar configuraciones de tema por defecto
INSERT INTO theme_configurations (theme_id, config_key, config_value, config_type, category, display_name, description, default_value) VALUES
(1, 'primary_color', '#3B82F6', 'color', 'colors', 'Color Primario', 'Color principal del tema', '#3B82F6'),
(1, 'secondary_color', '#64748B', 'color', 'colors', 'Color Secundario', 'Color secundario del tema', '#64748B'),
(1, 'accent_color', '#10B981', 'color', 'colors', 'Color de Acento', 'Color de acento para elementos destacados', '#10B981'),
(1, 'background_color', '#FFFFFF', 'color', 'colors', 'Color de Fondo', 'Color de fondo principal', '#FFFFFF'),
(1, 'text_color', '#1F2937', 'color', 'colors', 'Color de Texto', 'Color principal del texto', '#1F2937'),
(1, 'font_family', 'Inter, sans-serif', 'font', 'typography', 'Familia de Fuente', 'Fuente principal del sistema', 'Inter, sans-serif'),
(1, 'font_size_base', '16px', 'size', 'typography', 'Tamaño de Fuente Base', 'Tamaño base de la fuente', '16px'),
(1, 'border_radius', '8px', 'size', 'layout', 'Radio de Borde', 'Radio de borde por defecto', '8px'),
(1, 'sidebar_width', '280px', 'size', 'layout', 'Ancho de Sidebar', 'Ancho del menú lateral', '280px');

-- Insertar configuraciones del sistema por defecto
INSERT INTO system_settings (setting_group, setting_key, setting_value, setting_type, display_name, description, default_value, is_public, is_editable) VALUES
('general', 'site_name', 'WL-School', 'string', 'Nombre del Sitio', 'Nombre de la institución educativa', 'WL-School', TRUE, TRUE),
('general', 'site_description', 'Sistema de Gestión Educativa', 'string', 'Descripción del Sitio', 'Descripción breve de la institución', 'Sistema de Gestión Educativa', TRUE, TRUE),
('general', 'contact_email', 'info@wl-school.com', 'email', 'Email de Contacto', 'Email principal de contacto', 'info@wl-school.com', TRUE, TRUE),
('general', 'contact_phone', '+57 300 123 4567', 'string', 'Teléfono de Contacto', 'Teléfono principal de contacto', '', TRUE, TRUE),
('general', 'address', 'Calle 123 #45-67, Bogotá, Colombia', 'string', 'Dirección', 'Dirección física de la institución', '', TRUE, TRUE),
('general', 'timezone', 'America/Bogota', 'string', 'Zona Horaria', 'Zona horaria del sistema', 'America/Bogota', FALSE, TRUE),
('general', 'default_language', 'es', 'string', 'Idioma Por Defecto', 'Idioma por defecto del sistema', 'es', TRUE, TRUE),
('ui', 'items_per_page', '25', 'number', 'Elementos por Página', 'Número de elementos por página en listados', '25', TRUE, TRUE),
('ui', 'enable_dark_mode', 'true', 'boolean', 'Habilitar Modo Oscuro', 'Permitir a usuarios cambiar a modo oscuro', 'true', TRUE, TRUE),
('ui', 'enable_animations', 'true', 'boolean', 'Habilitar Animaciones', 'Mostrar animaciones en la interfaz', 'true', TRUE, TRUE),
('security', 'session_timeout', '120', 'number', 'Tiempo de Sesión', 'Tiempo de inactividad antes de cerrar sesión (minutos)', '120', FALSE, TRUE),
('security', 'password_min_length', '8', 'number', 'Longitud Mínima de Contraseña', 'Número mínimo de caracteres para contraseñas', '8', FALSE, TRUE),
('security', 'require_password_complexity', 'true', 'boolean', 'Requerir Complejidad de Contraseña', 'Exigir mayúsculas, minúsculas, números y símbolos', 'true', FALSE, TRUE),
('notifications', 'enable_email_notifications', 'true', 'boolean', 'Habilitar Notificaciones Email', 'Enviar notificaciones por correo electrónico', 'true', TRUE, TRUE),
('notifications', 'enable_sms_notifications', 'false', 'boolean', 'Habilitar Notificaciones SMS', 'Enviar notificaciones por SMS', 'false', TRUE, TRUE),
('academic', 'academic_year_start', '02-01', 'string', 'Inicio Año Académico', 'Fecha de inicio del año académico (MM-DD)', '02-01', TRUE, TRUE),
('academic', 'grading_scale', '1-5', 'string', 'Escala de Calificación', 'Escala de calificación utilizada', '1-5', TRUE, TRUE);

-- Insertar idiomas por defecto
INSERT INTO language_settings (language_code, language_name, is_default, is_active, date_format, time_format, currency_symbol) VALUES
('es', 'Español', TRUE, TRUE, 'd/m/Y', 'H:i', '$'),
('en', 'English', FALSE, TRUE, 'm/d/Y', 'h:i A', '$'),
('fr', 'Français', FALSE, FALSE, 'd/m/Y', 'H:i', '€'),
('pt', 'Português', FALSE, FALSE, 'd/m/Y', 'H:i', 'R$');

-- Insertar widgets por defecto
INSERT INTO custom_widgets (name, display_name, description, widget_type, component_code, is_active, is_public, created_by) VALUES
('student_count', 'Contador de Estudiantes', 'Widget que muestra el número total de estudiantes', 'card', '<div class="widget-card"><h3>Estudiantes</h3><div class="count">{{count}}</div></div>', TRUE, TRUE, 1),
('recent_payments', 'Pagos Recientes', 'Lista de los pagos más recientes', 'list', '<div class="widget-list"><h3>Pagos Recientes</h3><ul>{{#each payments}}<li>{{name}} - {{amount}}</li>{{/each}}</ul></div>', TRUE, TRUE, 1),
('attendance_chart', 'Gráfico de Asistencia', 'Gráfico de asistencia semanal', 'chart', '<div class="widget-chart"><canvas id="attendance-chart"></canvas></div>', TRUE, TRUE, 1),
('upcoming_events', 'Próximos Eventos', 'Calendario de próximos eventos', 'calendar', '<div class="widget-calendar"><h3>Próximos Eventos</h3><div class="events">{{#each events}}<div class="event">{{title}} - {{date}}</div>{{/each}}</div></div>', TRUE, TRUE, 1);

-- Insertar menús por defecto
INSERT INTO custom_menus (name, display_name, menu_type, icon, route_name, sort_order, is_active) VALUES
('dashboard', 'Dashboard', 'main', 'home', 'dashboard', 1, TRUE),
('students', 'Estudiantes', 'main', 'users', 'students.index', 2, TRUE),
('teachers', 'Profesores', 'main', 'academic-cap', 'teachers.index', 3, TRUE),
('courses', 'Cursos', 'main', 'book-open', 'courses.index', 4, TRUE),
('grades', 'Calificaciones', 'main', 'chart-bar', 'grades.index', 5, TRUE),
('attendance', 'Asistencia', 'main', 'calendar', 'attendance.index', 6, TRUE),
('finances', 'Finanzas', 'main', 'currency-dollar', 'finances.index', 7, TRUE),
('reports', 'Reportes', 'main', 'document-text', 'reports.index', 8, TRUE),
('settings', 'Configuración', 'main', 'cog', 'settings.index', 9, TRUE);