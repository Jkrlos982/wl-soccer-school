# Financial Service Microservice

Microservicio para la gestión financiera del sistema escolar WL-School.

## Características

- **Conceptos Financieros**: Gestión de conceptos de ingresos, gastos y transferencias
- **Transacciones**: Registro y seguimiento de transacciones financieras
- **Cuentas**: Manejo de cuentas contables con diferentes tipos
- **Reportes**: Generación de reportes financieros
- **Dashboard**: Panel de control con métricas financieras

## Modelos Principales

### FinancialConcept
- Conceptos financieros (matrículas, pensiones, gastos operativos, etc.)
- Categorización por tipo: income, expense, transfer
- Asociación por escuela

### Transaction
- Transacciones financieras con referencia única
- Estados: pending, completed, cancelled, failed
- Métodos de pago múltiples
- Metadatos JSON para información adicional

### Account
- Cuentas contables con tipos: asset, liability, equity, revenue, expense
- Balance automático
- Numeración única por cuenta

### TransactionAccount (Pivot)
- Relación many-to-many entre transacciones y cuentas
- Movimientos de débito y crédito
- Contabilidad de doble entrada

## Instalación

### Requisitos
- PHP 8.2+
- Composer
- MySQL 8.0+ o SQLite
- Docker (opcional)

### Instalación Local

```bash
# Clonar dependencias
composer install

# Configurar ambiente
cp .env.example .env
php artisan key:generate

# Configurar base de datos en .env
# Para SQLite (desarrollo):
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite

# Ejecutar migraciones
php artisan migrate

# Iniciar servidor de desarrollo
php artisan serve
```

### Instalación con Docker

```bash
# Construir y levantar servicios
docker-compose up -d --build

# Ejecutar migraciones en el contenedor
docker-compose exec financial-service php artisan migrate

# El servicio estará disponible en http://localhost:8003
```

## Tecnologías

- **Laravel 10**: Framework PHP
- **Laravel Sanctum**: Autenticación API
- **Spatie Query Builder**: Construcción de queries avanzadas
- **Laravel Excel**: Exportación de reportes
- **DomPDF**: Generación de PDFs
- **MySQL/SQLite**: Base de datos
- **Docker**: Containerización
- **Nginx**: Servidor web

## Estructura del Proyecto

```
financial-service/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Requests/
│   └── Models/
├── database/
│   └── migrations/
├── docker/
│   ├── nginx/
│   └── php/
├── routes/
├── Dockerfile
├── docker-compose.yml
└── README.md
```
