-- Migraciones para Payroll Service
-- Base de datos: wl_school_payroll

USE wl_school_payroll;

-- Tabla de empleados
CREATE TABLE employees (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    employee_code VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    identification_type ENUM('cedula', 'passport', 'foreign_id') NOT NULL,
    identification_number VARCHAR(20) UNIQUE NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    marital_status ENUM('single', 'married', 'divorced', 'widowed') DEFAULT 'single',
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(10),
    emergency_contact_name VARCHAR(150),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_relationship VARCHAR(50),
    hire_date DATE NOT NULL,
    termination_date DATE NULL,
    status ENUM('active', 'inactive', 'terminated', 'on_leave') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_employee_code (employee_code),
    INDEX idx_identification (identification_number),
    INDEX idx_status (status)
);

-- Tabla de departamentos
CREATE TABLE departments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    manager_id BIGINT UNSIGNED,
    budget DECIMAL(12,2),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES employees(id) ON DELETE SET NULL,
    INDEX idx_name (name),
    INDEX idx_is_active (is_active)
);

-- Tabla de posiciones/cargos
CREATE TABLE positions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    department_id BIGINT UNSIGNED NOT NULL,
    level ENUM('entry', 'junior', 'senior', 'lead', 'manager', 'director') NOT NULL,
    min_salary DECIMAL(10,2),
    max_salary DECIMAL(10,2),
    requirements TEXT,
    responsibilities TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    INDEX idx_title (title),
    INDEX idx_department (department_id),
    INDEX idx_is_active (is_active)
);

-- Tabla de asignaciones de empleados a posiciones
CREATE TABLE employee_positions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    position_id BIGINT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    base_salary DECIMAL(10,2) NOT NULL,
    salary_type ENUM('monthly', 'hourly', 'daily') DEFAULT 'monthly',
    working_hours_per_week INT DEFAULT 40,
    is_current BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE,
    INDEX idx_employee_position (employee_id, position_id),
    INDEX idx_is_current (is_current),
    INDEX idx_dates (start_date, end_date)
);

-- Tabla de conceptos de nómina
CREATE TABLE payroll_concepts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('earning', 'deduction', 'tax', 'benefit') NOT NULL,
    calculation_type ENUM('fixed', 'percentage', 'formula', 'hours') NOT NULL,
    value DECIMAL(10,4), -- Valor fijo o porcentaje
    formula TEXT, -- Fórmula de cálculo si aplica
    is_taxable BOOLEAN DEFAULT TRUE,
    affects_social_security BOOLEAN DEFAULT TRUE,
    is_mandatory BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_type (type),
    INDEX idx_is_active (is_active)
);

-- Tabla de períodos de nómina
CREATE TABLE payroll_periods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    period_type ENUM('weekly', 'biweekly', 'monthly', 'quarterly') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    pay_date DATE NOT NULL,
    status ENUM('draft', 'processing', 'approved', 'paid', 'closed') DEFAULT 'draft',
    total_employees INT DEFAULT 0,
    total_gross DECIMAL(12,2) DEFAULT 0,
    total_deductions DECIMAL(12,2) DEFAULT 0,
    total_net DECIMAL(12,2) DEFAULT 0,
    processed_by BIGINT UNSIGNED,
    approved_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_period_dates (start_date, end_date),
    INDEX idx_status (status),
    INDEX idx_pay_date (pay_date)
);

-- Tabla de nóminas individuales
CREATE TABLE payrolls (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payroll_number VARCHAR(20) UNIQUE NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    period_id BIGINT UNSIGNED NOT NULL,
    base_salary DECIMAL(10,2) NOT NULL,
    worked_days INT DEFAULT 30,
    worked_hours DECIMAL(8,2) DEFAULT 0,
    overtime_hours DECIMAL(8,2) DEFAULT 0,
    gross_salary DECIMAL(10,2) NOT NULL,
    total_earnings DECIMAL(10,2) DEFAULT 0,
    total_deductions DECIMAL(10,2) DEFAULT 0,
    total_taxes DECIMAL(10,2) DEFAULT 0,
    net_salary DECIMAL(10,2) NOT NULL,
    status ENUM('draft', 'calculated', 'approved', 'paid') DEFAULT 'draft',
    payment_method ENUM('bank_transfer', 'check', 'cash') DEFAULT 'bank_transfer',
    bank_account VARCHAR(50),
    payment_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (period_id) REFERENCES payroll_periods(id) ON DELETE CASCADE,
    INDEX idx_payroll_number (payroll_number),
    INDEX idx_employee_period (employee_id, period_id),
    INDEX idx_status (status),
    INDEX idx_payment_date (payment_date)
);

-- Tabla de detalles de nómina
CREATE TABLE payroll_details (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payroll_id BIGINT UNSIGNED NOT NULL,
    concept_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(8,2) DEFAULT 1,
    rate DECIMAL(10,4) DEFAULT 0,
    amount DECIMAL(10,2) NOT NULL,
    is_taxable BOOLEAN DEFAULT TRUE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payroll_id) REFERENCES payrolls(id) ON DELETE CASCADE,
    FOREIGN KEY (concept_id) REFERENCES payroll_concepts(id) ON DELETE CASCADE,
    INDEX idx_payroll_id (payroll_id),
    INDEX idx_concept_id (concept_id)
);

-- Tabla de asistencia
CREATE TABLE attendance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    check_in_time TIME,
    check_out_time TIME,
    break_start_time TIME,
    break_end_time TIME,
    total_hours DECIMAL(4,2) DEFAULT 0,
    overtime_hours DECIMAL(4,2) DEFAULT 0,
    status ENUM('present', 'absent', 'late', 'half_day', 'sick_leave', 'vacation', 'personal_leave') NOT NULL,
    notes TEXT,
    approved_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_employee_date (employee_id, attendance_date),
    INDEX idx_attendance_date (attendance_date),
    INDEX idx_status (status)
);

-- Tabla de solicitudes de permisos/vacaciones
CREATE TABLE leave_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    leave_type ENUM('vacation', 'sick_leave', 'personal_leave', 'maternity_leave', 'paternity_leave', 'bereavement', 'other') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    days_requested INT NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    requested_date DATE NOT NULL,
    reviewed_by BIGINT UNSIGNED,
    reviewed_date DATE,
    review_comments TEXT,
    is_paid BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES employees(id) ON DELETE SET NULL,
    INDEX idx_employee_id (employee_id),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
);

-- Tabla de beneficios de empleados
CREATE TABLE employee_benefits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    benefit_type ENUM('health_insurance', 'dental_insurance', 'life_insurance', 'retirement_plan', 'meal_vouchers', 'transport_allowance', 'education_allowance', 'other') NOT NULL,
    provider VARCHAR(100),
    policy_number VARCHAR(50),
    monthly_cost DECIMAL(8,2) DEFAULT 0,
    employee_contribution DECIMAL(8,2) DEFAULT 0,
    employer_contribution DECIMAL(8,2) DEFAULT 0,
    start_date DATE NOT NULL,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_employee_benefit (employee_id, benefit_type),
    INDEX idx_is_active (is_active)
);

-- Tabla de evaluaciones de desempeño
CREATE TABLE performance_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    reviewer_id BIGINT UNSIGNED NOT NULL,
    review_period_start DATE NOT NULL,
    review_period_end DATE NOT NULL,
    overall_rating ENUM('excellent', 'good', 'satisfactory', 'needs_improvement', 'unsatisfactory') NOT NULL,
    goals_achievement DECIMAL(3,1), -- Porcentaje de logro de objetivos
    strengths TEXT,
    areas_for_improvement TEXT,
    development_plan TEXT,
    salary_increase_recommended BOOLEAN DEFAULT FALSE,
    recommended_increase_percentage DECIMAL(5,2),
    promotion_recommended BOOLEAN DEFAULT FALSE,
    status ENUM('draft', 'completed', 'acknowledged') DEFAULT 'draft',
    review_date DATE NOT NULL,
    employee_acknowledgment_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_employee_id (employee_id),
    INDEX idx_review_date (review_date),
    INDEX idx_status (status)
);

-- Tabla de reportes de nómina
CREATE TABLE payroll_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_type ENUM('payroll_summary', 'tax_report', 'social_security_report', 'department_costs', 'employee_earnings') NOT NULL,
    report_date DATE NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    data JSON NOT NULL,
    generated_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_type (report_type),
    INDEX idx_report_date (report_date)
);

-- Insertar departamentos por defecto
INSERT INTO departments (name, description) VALUES
('Administración', 'Departamento administrativo y directivo'),
('Académico', 'Departamento de profesores y coordinadores académicos'),
('Servicios Generales', 'Personal de mantenimiento, seguridad y servicios'),
('Tecnología', 'Departamento de sistemas y tecnología'),
('Recursos Humanos', 'Gestión del talento humano'),
('Finanzas', 'Departamento financiero y contable'),
('Enfermería', 'Servicios médicos y de salud'),
('Psicología', 'Servicios de orientación y psicología'),
('Deportes', 'Coordinación deportiva y entrenadores'),
('Biblioteca', 'Servicios bibliotecarios');

-- Insertar conceptos de nómina por defecto
INSERT INTO payroll_concepts (code, name, description, type, calculation_type, value, is_taxable, affects_social_security) VALUES
('SUELDO_BASE', 'Sueldo Base', 'Salario base mensual', 'earning', 'fixed', 0, TRUE, TRUE),
('HORAS_EXTRA', 'Horas Extras', 'Pago por horas extras trabajadas', 'earning', 'hours', 1.5, TRUE, TRUE),
('BONIFICACION', 'Bonificación', 'Bonificación por desempeño', 'earning', 'fixed', 0, TRUE, TRUE),
('AUXILIO_TRANSPORTE', 'Auxilio de Transporte', 'Subsidio de transporte', 'earning', 'fixed', 140606, FALSE, FALSE),
('PRIMA_SERVICIOS', 'Prima de Servicios', 'Prima de servicios semestral', 'earning', 'percentage', 8.33, TRUE, TRUE),
('CESANTIAS', 'Cesantías', 'Cesantías anuales', 'earning', 'percentage', 8.33, FALSE, FALSE),
('INT_CESANTIAS', 'Intereses Cesantías', 'Intereses sobre cesantías', 'earning', 'percentage', 1.0, FALSE, FALSE),
('VACACIONES', 'Vacaciones', 'Vacaciones anuales', 'earning', 'percentage', 4.17, FALSE, FALSE),
('SALUD_EMP', 'Salud Empleado', 'Aporte salud empleado (4%)', 'deduction', 'percentage', 4.0, FALSE, TRUE),
('PENSION_EMP', 'Pensión Empleado', 'Aporte pensión empleado (4%)', 'deduction', 'percentage', 4.0, FALSE, TRUE),
('RETENCION_FUENTE', 'Retención en la Fuente', 'Retención en la fuente', 'tax', 'percentage', 0, FALSE, FALSE),
('PRESTAMO', 'Préstamo', 'Descuento por préstamo', 'deduction', 'fixed', 0, FALSE, FALSE),
('SALUD_EMP_PATRON', 'Salud Empleador', 'Aporte salud empleador (8.5%)', 'benefit', 'percentage', 8.5, FALSE, TRUE),
('PENSION_EMP_PATRON', 'Pensión Empleador', 'Aporte pensión empleador (12%)', 'benefit', 'percentage', 12.0, FALSE, TRUE),
('ARP', 'ARP', 'Riesgos laborales', 'benefit', 'percentage', 0.522, FALSE, TRUE),
('CAJA_COMPENSACION', 'Caja de Compensación', 'Caja de compensación (4%)', 'benefit', 'percentage', 4.0, FALSE, TRUE),
('ICBF', 'ICBF', 'Instituto Colombiano de Bienestar Familiar (3%)', 'benefit', 'percentage', 3.0, FALSE, TRUE),
('SENA', 'SENA', 'Servicio Nacional de Aprendizaje (2%)', 'benefit', 'percentage', 2.0, FALSE, TRUE);