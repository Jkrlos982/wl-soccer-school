# Especificaciones Técnicas - Microservicios

## Convenciones Generales

### Estándares de API
- **Protocolo**: HTTP/HTTPS
- **Formato**: JSON
- **Versionado**: `/api/v1/`
- **Autenticación**: Bearer Token (JWT)
- **Status Codes**: Estándar HTTP
- **Rate Limiting**: 1000 requests/hour por usuario

### Estructura de Respuesta Estándar

```json
{
  "success": true,
  "data": {},
  "message": "Operation completed successfully",
  "errors": [],
  "meta": {
    "timestamp": "2024-01-15T10:30:00Z",
    "version": "1.0.0",
    "request_id": "uuid-here"
  }
}
```

### Paginación Estándar

```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7,
    "from": 1,
    "to": 15
  },
  "links": {
    "first": "url",
    "last": "url",
    "prev": null,
    "next": "url"
  }
}
```

---

## 1. Auth Service

### Modelo de Datos

```sql
-- Users Table
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    avatar TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Roles Table
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Permissions Table
CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    display_name VARCHAR(150) NOT NULL,
    description TEXT,
    module VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Role User Pivot
CREATE TABLE role_user (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    assigned_by BIGINT UNSIGNED,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Permission Role Pivot
CREATE TABLE permission_role (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permission_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);
```

### API Endpoints

#### Autenticación

```http
POST /api/v1/auth/register
Content-Type: application/json

{
  "first_name": "Juan",
  "last_name": "Pérez",
  "email": "juan@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+57300123456"
}
```

```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "juan@example.com",
  "password": "password123"
}
```

```http
POST /api/v1/auth/logout
Authorization: Bearer {token}
```

```http
POST /api/v1/auth/refresh
Authorization: Bearer {refresh_token}
```

#### Gestión de Usuarios

```http
GET /api/v1/users?page=1&per_page=15&search=juan
Authorization: Bearer {token}
```

```http
GET /api/v1/users/{uuid}
Authorization: Bearer {token}
```

```http
PUT /api/v1/users/{uuid}
Authorization: Bearer {token}
Content-Type: application/json

{
  "first_name": "Juan Carlos",
  "last_name": "Pérez",
  "phone": "+57300123456",
  "is_active": true
}
```

#### Roles y Permisos

```http
GET /api/v1/roles
Authorization: Bearer {token}
```

```http
POST /api/v1/users/{uuid}/roles
Authorization: Bearer {token}
Content-Type: application/json

{
  "role_ids": [1, 2, 3]
}
```

---

## 2. Financial Service

### Modelo de Datos

```sql
-- Financial Concepts Table
CREATE TABLE financial_concepts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('income', 'expense') NOT NULL,
    category VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    school_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Transactions Table
CREATE TABLE transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    concept_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    payment_method ENUM('cash', 'transfer', 'card', 'check') NOT NULL,
    reference_number VARCHAR(100),
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    school_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (concept_id) REFERENCES financial_concepts(id)
);

-- Accounts Receivable Table
CREATE TABLE accounts_receivable (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    customer_name VARCHAR(200) NOT NULL,
    customer_email VARCHAR(255),
    customer_phone VARCHAR(20),
    amount DECIMAL(12,2) NOT NULL,
    due_date DATE NOT NULL,
    description TEXT,
    status ENUM('pending', 'partial', 'paid', 'overdue') DEFAULT 'pending',
    paid_amount DECIMAL(12,2) DEFAULT 0,
    school_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Vouchers Table
CREATE TABLE vouchers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    voucher_number VARCHAR(50) UNIQUE NOT NULL,
    transaction_id BIGINT UNSIGNED,
    receivable_id BIGINT UNSIGNED,
    type ENUM('receipt', 'invoice', 'payment') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    issued_date DATE NOT NULL,
    pdf_path TEXT,
    school_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id),
    FOREIGN KEY (receivable_id) REFERENCES accounts_receivable(id)
);
```

### API Endpoints

#### Conceptos Financieros

```http
GET /api/v1/financial-concepts?type=income&active=true
Authorization: Bearer {token}
```

```http
POST /api/v1/financial-concepts
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Mensualidad Categoría Sub-15",
  "description": "Pago mensual para jugadores de categoría sub-15",
  "type": "income",
  "category": "tuition"
}
```

#### Transacciones

```http
GET /api/v1/transactions?start_date=2024-01-01&end_date=2024-01-31&type=income
Authorization: Bearer {token}
```

```http
POST /api/v1/transactions
Authorization: Bearer {token}
Content-Type: application/json

{
  "concept_id": "uuid-here",
  "amount": 150000,
  "description": "Pago mensualidad enero",
  "transaction_date": "2024-01-15",
  "payment_method": "transfer",
  "reference_number": "TRF123456"
}
```

#### Cuentas por Cobrar

```http
GET /api/v1/accounts-receivable?status=pending&overdue=true
Authorization: Bearer {token}
```

```http
POST /api/v1/accounts-receivable
Authorization: Bearer {token}
Content-Type: application/json

{
  "customer_name": "María González",
  "customer_email": "maria@example.com",
  "customer_phone": "+57300987654",
  "amount": 200000,
  "due_date": "2024-02-15",
  "description": "Mensualidad febrero + uniforme"
}
```

---

## 3. Sports Service

### Modelo de Datos

```sql
-- Categories Table
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    min_age INT NOT NULL,
    max_age INT NOT NULL,
    description TEXT,
    max_players INT DEFAULT 25,
    is_active BOOLEAN DEFAULT TRUE,
    school_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Players Table
CREATE TABLE players (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    document_type ENUM('CC', 'TI', 'CE', 'PP') NOT NULL,
    document_number VARCHAR(20) UNIQUE NOT NULL,
    birth_date DATE NOT NULL,
    gender ENUM('M', 'F') NOT NULL,
    position VARCHAR(50),
    dominant_foot ENUM('left', 'right', 'both'),
    height DECIMAL(5,2),
    weight DECIMAL(5,2),
    phone VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    emergency_contact_name VARCHAR(200),
    emergency_contact_phone VARCHAR(20),
    photo TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    school_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Training Sessions Table
CREATE TABLE training_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    session_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    location VARCHAR(200),
    coach_notes TEXT,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    school_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Training Attendance Table
CREATE TABLE training_attendance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    training_session_id BIGINT UNSIGNED NOT NULL,
    player_id BIGINT UNSIGNED NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') NOT NULL,
    notes TEXT,
    performance_rating INT CHECK (performance_rating >= 1 AND performance_rating <= 10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (training_session_id) REFERENCES training_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (training_session_id, player_id)
);

-- Matches Table
CREATE TABLE matches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    opponent_name VARCHAR(200) NOT NULL,
    match_date DATE NOT NULL,
    match_time TIME NOT NULL,
    location VARCHAR(200),
    is_home BOOLEAN DEFAULT TRUE,
    our_score INT DEFAULT 0,
    opponent_score INT DEFAULT 0,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled', 'postponed') DEFAULT 'scheduled',
    notes TEXT,
    school_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Player Evaluations Table
CREATE TABLE player_evaluations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    player_id BIGINT UNSIGNED,
    evaluator_name VARCHAR(200) NOT NULL,
    evaluation_date DATE NOT NULL,
    technical_skills INT CHECK (technical_skills >= 1 AND technical_skills <= 10),
    physical_condition INT CHECK (physical_condition >= 1 AND physical_condition <= 10),
    tactical_understanding INT CHECK (tactical_understanding >= 1 AND tactical_understanding <= 10),
    mental_attitude INT CHECK (mental_attitude >= 1 AND mental_attitude <= 10),
    overall_rating INT CHECK (overall_rating >= 1 AND overall_rating <= 10),
    strengths TEXT,
    areas_for_improvement TEXT,
    recommendations TEXT,
    is_external BOOLEAN DEFAULT FALSE,
    candidate_name VARCHAR(200),
    candidate_age INT,
    candidate_position VARCHAR(50),
    school_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES players(id)
);
```

### API Endpoints

#### Jugadores

```http
GET /api/v1/players?category_id=uuid&active=true&search=juan
Authorization: Bearer {token}
```

```http
POST /api/v1/players
Authorization: Bearer {token}
Content-Type: application/json

{
  "first_name": "Carlos",
  "last_name": "Rodríguez",
  "document_type": "TI",
  "document_number": "1234567890",
  "birth_date": "2010-05-15",
  "gender": "M",
  "position": "Delantero",
  "dominant_foot": "right",
  "height": 1.65,
  "weight": 55.5,
  "phone": "+57300111222",
  "email": "carlos@example.com",
  "category_id": "uuid-here"
}
```

#### Entrenamientos

```http
GET /api/v1/training-sessions?category_id=uuid&date_from=2024-01-01&date_to=2024-01-31
Authorization: Bearer {token}
```

```http
POST /api/v1/training-sessions
Authorization: Bearer {token}
Content-Type: application/json

{
  "category_id": "uuid-here",
  "title": "Entrenamiento Técnico",
  "description": "Trabajo de pase y control",
  "session_date": "2024-01-20",
  "start_time": "16:00",
  "end_time": "18:00",
  "location": "Cancha Principal"
}
```

#### Asistencia

```http
POST /api/v1/training-sessions/{uuid}/attendance
Authorization: Bearer {token}
Content-Type: application/json

{
  "attendance": [
    {
      "player_id": "uuid-1",
      "status": "present",
      "performance_rating": 8,
      "notes": "Excelente participación"
    },
    {
      "player_id": "uuid-2",
      "status": "absent",
      "notes": "Enfermo"
    }
  ]
}
```

---

## 4. Notification Service

### Modelo de Datos

```sql
-- Notification Templates Table
CREATE TABLE notification_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('whatsapp', 'email', 'sms') NOT NULL,
    subject VARCHAR(200),
    content TEXT NOT NULL,
    variables JSON,
    is_active BOOLEAN DEFAULT TRUE,
    school_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Notifications Queue Table
CREATE TABLE notifications_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    template_id BIGINT UNSIGNED,
    recipient_type ENUM('user', 'player', 'parent', 'employee') NOT NULL,
    recipient_id BIGINT UNSIGNED NOT NULL,
    recipient_contact VARCHAR(255) NOT NULL,
    type ENUM('whatsapp', 'email', 'sms') NOT NULL,
    subject VARCHAR(200),
    content TEXT NOT NULL,
    attachments JSON,
    scheduled_at TIMESTAMP,
    sent_at TIMESTAMP NULL,
    status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    error_message TEXT,
    attempts INT DEFAULT 0,
    school_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES notification_templates(id)
);
```

### API Endpoints

#### Templates

```http
GET /api/v1/notification-templates?type=whatsapp
Authorization: Bearer {token}
```

```http
POST /api/v1/notification-templates
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Comprobante de Pago",
  "type": "whatsapp",
  "content": "Hola {{player_name}}, adjunto encontrarás el comprobante de pago por valor de ${{amount}}.",
  "variables": ["player_name", "amount", "payment_date"]
}
```

#### Envío de Notificaciones

```http
POST /api/v1/notifications/send
Authorization: Bearer {token}
Content-Type: application/json

{
  "template_id": "uuid-here",
  "recipients": [
    {
      "type": "player",
      "id": "player-uuid",
      "contact": "+57300123456"
    }
  ],
  "variables": {
    "player_name": "Carlos Rodríguez",
    "amount": "150,000",
    "payment_date": "2024-01-15"
  },
  "attachments": [
    {
      "type": "pdf",
      "url": "https://example.com/receipt.pdf",
      "filename": "comprobante_pago.pdf"
    }
  ],
  "scheduled_at": "2024-01-16T09:00:00Z"
}
```

---

## 5. Medical Service

### Modelo de Datos

```sql
-- Medical Records Table
CREATE TABLE medical_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    player_id BIGINT UNSIGNED NOT NULL,
    blood_type VARCHAR(5),
    allergies TEXT,
    chronic_conditions TEXT,
    medications TEXT,
    emergency_medical_contact VARCHAR(200),
    emergency_medical_phone VARCHAR(20),
    insurance_provider VARCHAR(200),
    insurance_number VARCHAR(100),
    last_medical_checkup DATE,
    medical_clearance BOOLEAN DEFAULT FALSE,
    clearance_expiry DATE,
    notes TEXT,
    school_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Injuries Table
CREATE TABLE injuries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    player_id BIGINT UNSIGNED NOT NULL,
    injury_type VARCHAR(100) NOT NULL,
    body_part VARCHAR(100) NOT NULL,
    severity ENUM('minor', 'moderate', 'severe') NOT NULL,
    injury_date DATE NOT NULL,
    description TEXT,
    cause VARCHAR(200),
    initial_diagnosis TEXT,
    estimated_recovery_days INT,
    actual_recovery_days INT,
    status ENUM('active', 'recovering', 'recovered') DEFAULT 'active',
    return_to_play_date DATE,
    school_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Physiotherapy Sessions Table
CREATE TABLE physiotherapy_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    injury_id BIGINT UNSIGNED NOT NULL,
    session_date DATE NOT NULL,
    session_time TIME NOT NULL,
    physiotherapist_name VARCHAR(200),
    treatment_type VARCHAR(100),
    exercises_performed TEXT,
    progress_notes TEXT,
    pain_level_before INT CHECK (pain_level_before >= 0 AND pain_level_before <= 10),
    pain_level_after INT CHECK (pain_level_after >= 0 AND pain_level_after <= 10),
    mobility_improvement ENUM('none', 'slight', 'moderate', 'significant'),
    next_session_date DATE,
    status ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    school_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (injury_id) REFERENCES injuries(id) ON DELETE CASCADE
);
```

### API Endpoints

#### Historial Médico

```http
GET /api/v1/medical-records/{player_uuid}
Authorization: Bearer {token}
```

```http
PUT /api/v1/medical-records/{player_uuid}
Authorization: Bearer {token}
Content-Type: application/json

{
  "blood_type": "O+",
  "allergies": "Ninguna conocida",
  "chronic_conditions": "Asma leve",
  "medications": "Inhalador para asma",
  "insurance_provider": "EPS Sura",
  "last_medical_checkup": "2024-01-10",
  "medical_clearance": true,
  "clearance_expiry": "2024-12-31"
}
```

#### Lesiones

```http
GET /api/v1/injuries?player_id=uuid&status=active
Authorization: Bearer {token}
```

```http
POST /api/v1/injuries
Authorization: Bearer {token}
Content-Type: application/json

{
  "player_id": "uuid-here",
  "injury_type": "Esguince",
  "body_part": "Tobillo derecho",
  "severity": "moderate",
  "injury_date": "2024-01-15",
  "description": "Esguince durante entrenamiento",
  "cause": "Mal apoyo al saltar",
  "estimated_recovery_days": 14
}
```

---

## Configuración Docker

### Dockerfile Base para Servicios Laravel

```dockerfile
FROM php:8.2-fpm-alpine

# Instalar dependencias del sistema
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    mysql-client

# Instalar extensiones PHP
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd

# Instalar Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Crear usuario no-root
RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -S www -G www

# Configurar directorio de trabajo
WORKDIR /var/www

# Copiar archivos de la aplicación
COPY --chown=www:www . /var/www

# Instalar dependencias de Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Configurar permisos
RUN chown -R www:www /var/www && \
    chmod -R 755 /var/www/storage && \
    chmod -R 755 /var/www/bootstrap/cache

# Cambiar a usuario no-root
USER www

EXPOSE 9000

CMD ["php-fpm"]
```

### docker-compose.yml para Desarrollo

```yaml
version: '3.8'

services:
  # API Gateway
  api-gateway:
    build:
      context: ./api-gateway
      dockerfile: Dockerfile
    ports:
      - "8000:9000"
    environment:
      - APP_ENV=local
      - DB_HOST=mysql
      - REDIS_HOST=redis
    depends_on:
      - mysql
      - redis
    volumes:
      - ./api-gateway:/var/www
    networks:
      - wl-school-network

  # Auth Service
  auth-service:
    build:
      context: ./auth-service
      dockerfile: Dockerfile
    environment:
      - APP_ENV=local
      - DB_HOST=mysql
      - DB_DATABASE=auth_db
      - REDIS_HOST=redis
    depends_on:
      - mysql
      - redis
    volumes:
      - ./auth-service:/var/www
    networks:
      - wl-school-network

  # Financial Service
  financial-service:
    build:
      context: ./financial-service
      dockerfile: Dockerfile
    environment:
      - APP_ENV=local
      - DB_HOST=mysql
      - DB_DATABASE=financial_db
    depends_on:
      - mysql
    volumes:
      - ./financial-service:/var/www
    networks:
      - wl-school-network

  # Sports Service
  sports-service:
    build:
      context: ./sports-service
      dockerfile: Dockerfile
    environment:
      - APP_ENV=local
      - DB_HOST=mysql
      - DB_DATABASE=sports_db
    depends_on:
      - mysql
    volumes:
      - ./sports-service:/var/www
    networks:
      - wl-school-network

  # Notification Service
  notification-service:
    build:
      context: ./notification-service
      dockerfile: Dockerfile
    environment:
      - APP_ENV=local
      - DB_HOST=mysql
      - DB_DATABASE=notification_db
      - REDIS_HOST=redis
      - WHATSAPP_API_URL=${WHATSAPP_API_URL}
      - WHATSAPP_API_TOKEN=${WHATSAPP_API_TOKEN}
      - MAIL_MAILER=smtp
      - MAIL_HOST=${MAIL_HOST}
      - MAIL_PORT=${MAIL_PORT}
      - MAIL_USERNAME=${MAIL_USERNAME}
      - MAIL_PASSWORD=${MAIL_PASSWORD}
    depends_on:
      - mysql
      - redis
    volumes:
      - ./notification-service:/var/www
    networks:
      - wl-school-network

  # Medical Service
  medical-service:
    build:
      context: ./medical-service
      dockerfile: Dockerfile
    environment:
      - APP_ENV=local
      - DB_HOST=mysql
      - DB_DATABASE=medical_db
    depends_on:
      - mysql
    volumes:
      - ./medical-service:/var/www
    networks:
      - wl-school-network

  # Database
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: wl_school
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/init:/docker-entrypoint-initdb.d
    networks:
      - wl-school-network

  # Redis
  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    networks:
      - wl-school-network

  # Nginx
  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./docker/nginx/nginx.conf:/etc/nginx/nginx.conf
      - ./docker/nginx/sites:/etc/nginx/sites-available
    depends_on:
      - api-gateway
    networks:
      - wl-school-network

volumes:
  mysql_data:
  redis_data:

networks:
  wl-school-network:
    driver: bridge
```

---

*Este documento será actualizado conforme se desarrollen los microservicios.*