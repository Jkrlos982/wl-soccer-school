# Arquitectura de Microservicios - Escuela de Fútbol

## Visión General de la Arquitectura

La aplicación está diseñada siguiendo los principios de arquitectura de microservicios, donde cada servicio es independiente, escalable y mantenible por separado.

## Diagrama de Arquitectura

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Mobile App    │    │   Web Browser   │    │   Admin Panel   │
│   (React PWA)   │    │   (React PWA)   │    │   (React PWA)   │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         └───────────────────────┼───────────────────────┘
                                 │
                    ┌─────────────────┐
                    │   API Gateway   │
                    │   (Laravel)     │
                    └─────────────────┘
                                 │
        ┌────────────────────────┼────────────────────────┐
        │                       │                        │
┌───────▼────────┐    ┌─────────▼────────┐    ┌─────────▼────────┐
│  Auth Service  │    │ Financial Service│    │ Sports Service   │
│   (Laravel)    │    │   (Laravel)      │    │   (Laravel)      │
└────────────────┘    └──────────────────┘    └──────────────────┘
        │                       │                        │
┌───────▼────────┐    ┌─────────▼────────┐    ┌─────────▼────────┐
│ Payroll Service│    │Notification Serv.│    │ Medical Service  │
│   (Laravel)    │    │   (Laravel)      │    │   (Laravel)      │
└────────────────┘    └──────────────────┘    └──────────────────┘
        │                       │                        │
┌───────▼────────┐    ┌─────────▼────────┐    ┌─────────▼────────┐
│ Report Service │    │ Calendar Service │    │Customization Serv│
│   (Laravel)    │    │   (Laravel)      │    │   (Laravel)      │
└────────────────┘    └──────────────────┘    └──────────────────┘
        │                       │                        │
        └───────────────────────┼────────────────────────┘
                                │
                    ┌─────────────────┐
                    │   Message Bus   │
                    │   (Redis/RabbitMQ)│
                    └─────────────────┘
```

## Descripción de Servicios

### 1. API Gateway
**Repositorio**: `wl-school-api-gateway`

**Responsabilidades**:
- Punto de entrada único para todas las peticiones
- Enrutamiento a microservicios
- Autenticación y autorización
- Rate limiting
- Logging y monitoreo
- Transformación de respuestas

**Tecnologías**:
- Laravel 10+
- JWT Authentication
- Redis para cache
- Nginx como proxy reverso

### 2. Auth Service
**Repositorio**: `wl-school-auth-service`

**Responsabilidades**:
- Gestión de usuarios y roles
- Autenticación JWT
- Autorización basada en permisos
- Gestión de sesiones
- Recuperación de contraseñas

**Base de Datos**:
```sql
-- Tablas principales
users
roles
permissions
role_user
permission_role
password_resets
personal_access_tokens
```

### 3. Financial Service
**Repositorio**: `wl-school-financial-service`

**Responsabilidades**:
- Gestión de ingresos y gastos
- Conceptos financieros personalizables
- Cuentas por cobrar/pagar
- Conciliación bancaria
- Generación de comprobantes

**Base de Datos**:
```sql
-- Tablas principales
financial_concepts
transactions
accounts_receivable
accounts_payable
bank_reconciliations
vouchers
```

### 4. Payroll Service
**Repositorio**: `wl-school-payroll-service`

**Responsabilidades**:
- Gestión de empleados
- Cálculo de nómina
- Deducciones y bonificaciones
- Liquidaciones
- Reportes laborales

**Base de Datos**:
```sql
-- Tablas principales
employees
contracts
payroll_periods
payroll_items
deductions
bonuses
liquidations
```

### 5. Sports Service
**Repositorio**: `wl-school-sports-service`

**Responsabilidades**:
- Gestión de jugadores
- Categorías y equipos
- Entrenamientos y partidos
- Estadísticas deportivas
- Evaluaciones y scouting

**Base de Datos**:
```sql
-- Tablas principales
players
categories
teams
training_sessions
matches
statistics
evaluations
scout_reports
tournaments
```

### 6. Notification Service
**Repositorio**: `wl-school-notification-service`

**Responsabilidades**:
- Envío de WhatsApp
- Envío de emails
- Gestión de templates
- Cola de mensajes
- Tracking de entregas

**Integraciones**:
- WhatsApp Business API
- SendGrid/Mailgun
- Twilio

### 7. Medical Service
**Repositorio**: `wl-school-medical-service`

**Responsabilidades**:
- Registro de lesiones
- Seguimiento médico
- Citas de fisioterapia
- Historial clínico
- Reportes médicos

**Base de Datos**:
```sql
-- Tablas principales
medical_records
injuries
treatments
physiotherapy_sessions
medical_appointments
recovery_progress
```

### 8. Report Service
**Repositorio**: `wl-school-report-service`

**Responsabilidades**:
- Generación de reportes
- Análisis de datos
- Exportación múltiple
- Programación de reportes
- Dashboard analytics

**Tecnologías**:
- Laravel Excel
- Chart.js integration
- PDF generation
- Scheduled jobs

### 9. Calendar Service
**Repositorio**: `wl-school-calendar-service`

**Responsabilidades**:
- Gestión de eventos
- Programación de entrenamientos
- Reserva de espacios
- Sincronización externa
- Recordatorios

**Integraciones**:
- Google Calendar API
- Outlook Calendar
- iCal export

### 10. Customization Service
**Repositorio**: `wl-school-customization-service`

**Responsabilidades**:
- Configuración de marca blanca
- Gestión de temas
- Personalización de colores
- Gestión de logos
- Templates personalizados

**Base de Datos**:
```sql
-- Tablas principales
school_configurations
themes
color_schemes
logos
custom_templates
```

## Comunicación Entre Servicios

### Comunicación Síncrona
- **HTTP REST APIs**: Para operaciones que requieren respuesta inmediata
- **GraphQL**: Para consultas complejas (opcional)

### Comunicación Asíncrona
- **Message Bus (Redis/RabbitMQ)**: Para eventos y notificaciones
- **Event Sourcing**: Para auditoría y trazabilidad

### Patrones de Comunicación

1. **Request-Response**: Comunicación directa HTTP
2. **Publish-Subscribe**: Eventos de dominio
3. **Command Query Responsibility Segregation (CQRS)**: Separación de lecturas y escrituras

## Gestión de Datos

### Base de Datos por Servicio
Cada microservicio tiene su propia base de datos:

```
auth_db (MySQL)
financial_db (MySQL)
payroll_db (MySQL)
sports_db (MySQL)
notification_db (Redis)
medical_db (MySQL)
report_db (MySQL)
calendar_db (MySQL)
customization_db (MySQL)
```

### Consistencia de Datos
- **Eventual Consistency**: Para operaciones no críticas
- **Saga Pattern**: Para transacciones distribuidas
- **Event Sourcing**: Para auditoría completa

## Configuración Docker

### Estructura de Contenedores

```yaml
# docker-compose.yml principal
version: '3.8'
services:
  # API Gateway
  api-gateway:
    build: ./api-gateway
    ports:
      - "80:80"
    depends_on:
      - redis
      - mysql
  
  # Microservicios
  auth-service:
    build: ./auth-service
    environment:
      - DB_HOST=mysql
      - REDIS_HOST=redis
  
  financial-service:
    build: ./financial-service
    environment:
      - DB_HOST=mysql
  
  # ... otros servicios
  
  # Infraestructura
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: secret
  
  redis:
    image: redis:alpine
  
  nginx:
    image: nginx:alpine
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
```

### Dockerfile Estándar para Servicios Laravel

```dockerfile
FROM php:8.2-fpm

# Instalar dependencias
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Instalar extensiones PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
WORKDIR /var/www

# Copiar archivos
COPY . /var/www

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader

# Permisos
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www/storage

EXPOSE 9000
CMD ["php-fpm"]
```

## Monitoreo y Logging

### Stack de Monitoreo
- **Prometheus**: Métricas
- **Grafana**: Visualización
- **ELK Stack**: Logs centralizados
- **Jaeger**: Tracing distribuido

### Health Checks
Cada servicio implementa:
- `/health`: Estado básico
- `/ready`: Listo para recibir tráfico
- `/metrics`: Métricas de Prometheus

## Seguridad

### Autenticación y Autorización
- JWT tokens con refresh
- OAuth 2.0 para integraciones
- RBAC (Role-Based Access Control)

### Comunicación Segura
- TLS 1.3 para todas las comunicaciones
- Certificados SSL automáticos
- API Keys para servicios internos

### Protección de Datos
- Encriptación en reposo
- Hashing de contraseñas (bcrypt)
- Sanitización de inputs
- Rate limiting

## Despliegue y CI/CD

### Pipeline de Despliegue
1. **Desarrollo**: Docker Compose local
2. **Testing**: Kubernetes en staging
3. **Producción**: Kubernetes con alta disponibilidad

### Estrategias de Despliegue
- **Blue-Green**: Para actualizaciones sin downtime
- **Canary**: Para releases graduales
- **Rolling Updates**: Para actualizaciones continuas

## Escalabilidad

### Escalamiento Horizontal
- Load balancers por servicio
- Auto-scaling basado en métricas
- Réplicas de base de datos

### Optimización de Performance
- Cache distribuido (Redis)
- CDN para assets estáticos
- Compresión de respuestas
- Lazy loading en frontend

---

*Este documento técnico será actualizado conforme evolucione la arquitectura.*