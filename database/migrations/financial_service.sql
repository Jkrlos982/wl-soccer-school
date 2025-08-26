-- Migraciones para Financial Service
-- Base de datos: wl_school_financial

USE wl_school_financial;

-- Tabla de estudiantes (referencia)
CREATE TABLE students (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    student_code VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    grade_level VARCHAR(20),
    status ENUM('active', 'inactive', 'graduated', 'transferred') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_student_code (student_code),
    INDEX idx_status (status)
);

-- Tabla de conceptos de pago
CREATE TABLE payment_concepts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    type ENUM('tuition', 'fee', 'uniform', 'transport', 'food', 'materials', 'other') NOT NULL,
    is_recurring BOOLEAN DEFAULT FALSE,
    recurrence_period ENUM('monthly', 'quarterly', 'semester', 'annual') NULL,
    grade_levels JSON, -- Array de niveles aplicables
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_is_active (is_active)
);

-- Tabla de matrículas
CREATE TABLE enrollments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    academic_year VARCHAR(10) NOT NULL,
    grade_level VARCHAR(20) NOT NULL,
    enrollment_date DATE NOT NULL,
    tuition_amount DECIMAL(10,2) NOT NULL,
    discount_percentage DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_student_year (student_id, academic_year),
    INDEX idx_status (status),
    INDEX idx_academic_year (academic_year)
);

-- Tabla de facturas
CREATE TABLE invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(20) UNIQUE NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    enrollment_id BIGINT UNSIGNED,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0,
    balance DECIMAL(10,2) NOT NULL,
    status ENUM('draft', 'sent', 'paid', 'partial', 'overdue', 'cancelled') DEFAULT 'draft',
    payment_terms VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE SET NULL,
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
);

-- Tabla de items de factura
CREATE TABLE invoice_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    payment_concept_id BIGINT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    discount_percentage DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_concept_id) REFERENCES payment_concepts(id) ON DELETE RESTRICT,
    INDEX idx_invoice_id (invoice_id)
);

-- Tabla de pagos
CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_number VARCHAR(20) UNIQUE NOT NULL,
    invoice_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'credit_card', 'debit_card', 'check', 'online') NOT NULL,
    reference_number VARCHAR(100),
    bank_name VARCHAR(100),
    transaction_id VARCHAR(100),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    notes TEXT,
    processed_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_payment_number (payment_number),
    INDEX idx_invoice_id (invoice_id),
    INDEX idx_student_id (student_id),
    INDEX idx_payment_date (payment_date),
    INDEX idx_status (status)
);

-- Tabla de descuentos y becas
CREATE TABLE scholarships (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('percentage', 'fixed_amount') NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    max_amount DECIMAL(10,2),
    applicable_concepts JSON, -- Array de conceptos aplicables
    requirements TEXT,
    valid_from DATE NOT NULL,
    valid_until DATE,
    max_beneficiaries INT,
    current_beneficiaries INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_valid_dates (valid_from, valid_until)
);

-- Tabla de asignación de becas a estudiantes
CREATE TABLE student_scholarships (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    scholarship_id BIGINT UNSIGNED NOT NULL,
    assigned_date DATE NOT NULL,
    valid_from DATE NOT NULL,
    valid_until DATE,
    status ENUM('active', 'expired', 'revoked') DEFAULT 'active',
    assigned_by BIGINT UNSIGNED,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE,
    INDEX idx_student_scholarship (student_id, scholarship_id),
    INDEX idx_status (status)
);

-- Tabla de reportes financieros
CREATE TABLE financial_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_type ENUM('daily_income', 'monthly_summary', 'student_balance', 'overdue_payments', 'scholarship_summary') NOT NULL,
    report_date DATE NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    data JSON NOT NULL,
    generated_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_type (report_type),
    INDEX idx_report_date (report_date)
);

-- Insertar conceptos de pago por defecto
INSERT INTO payment_concepts (name, description, amount, type, is_recurring, recurrence_period, grade_levels) VALUES
('Matrícula Anual', 'Costo de matrícula para el año académico', 500000.00, 'tuition', FALSE, NULL, JSON_ARRAY('all')),
('Pensión Mensual', 'Costo mensual de pensión', 300000.00, 'tuition', TRUE, 'monthly', JSON_ARRAY('all')),
('Uniforme Escolar', 'Costo del uniforme escolar completo', 150000.00, 'uniform', FALSE, NULL, JSON_ARRAY('all')),
('Transporte Escolar', 'Servicio de transporte mensual', 80000.00, 'transport', TRUE, 'monthly', JSON_ARRAY('all')),
('Alimentación', 'Servicio de alimentación mensual', 120000.00, 'food', TRUE, 'monthly', JSON_ARRAY('all')),
('Materiales Didácticos', 'Materiales y útiles escolares', 100000.00, 'materials', FALSE, NULL, JSON_ARRAY('all'));