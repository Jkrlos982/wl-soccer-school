# Sistema de Transacciones - Financial Service

## Descripción General

El Sistema de Transacciones implementa un flujo completo de gestión financiera con sistema de aprobaciones, validaciones de negocio y manejo automático de balances contables.

## Características Principales

### 1. Sistema de Aprobaciones
- **Estados disponibles**: `pending`, `approved`, `rejected`, `cancelled`, `completed`
- **Flujo de aprobación**: Las transacciones inician en estado `pending` y requieren aprobación
- **Trazabilidad**: Registro de quién aprobó/rechazó y cuándo
- **Notas de aprobación**: Comentarios del aprobador

### 2. Numeración Automática
- **Formato**: `YYYY-MM-TIPO-NNNN`
- **Ejemplo**: `2025-01-ING-0001` (Ingreso), `2025-01-GAS-0001` (Gasto)
- **Secuencial**: Numeración automática por tipo y mes

### 3. Validaciones de Negocio
- Validación de balances suficientes
- Verificación de cuentas activas
- Validación de conceptos financieros
- Control de fechas (no futuras)
- Validación de montos positivos

### 4. Manejo de Balances
- Actualización automática de balances de cuentas
- Soporte para múltiples cuentas por transacción
- Reversión de balances en caso de cancelación
- Registro de movimientos contables

## Estructura de Archivos

```
app/
├── Http/
│   ├── Controllers/
│   │   └── TransactionController.php     # Controlador principal
│   └── Requests/
│       ├── CreateTransactionRequest.php  # Validaciones para crear
│       └── UpdateTransactionRequest.php  # Validaciones para actualizar
├── Models/
│   └── Transaction.php                   # Modelo con scopes y métodos
└── Services/
    └── TransactionService.php            # Lógica de negocio

database/
├── migrations/
│   ├── 2025_08_27_201546_create_transactions_table.php
│   └── 2025_08_27_230624_update_transactions_table_for_approval_system.php
└── seeders/
    └── TransactionSeeder.php             # Datos de prueba
```

## API Endpoints

### Gestión Básica
```
GET    /api/v1/transactions              # Listar transacciones (con filtros)
POST   /api/v1/transactions              # Crear nueva transacción
GET    /api/v1/transactions/{id}         # Ver transacción específica
PUT    /api/v1/transactions/{id}         # Actualizar transacción
DELETE /api/v1/transactions/{id}         # Eliminar transacción
```

### Sistema de Aprobaciones
```
PATCH  /api/v1/transactions/{id}/status  # Cambiar estado (aprobar/rechazar)
```

### Estadísticas y Reportes
```
GET    /api/v1/transactions/statistics   # Estadísticas generales
```

### Utilidades
```
POST   /api/v1/transactions/generate-reference  # Generar número de referencia
```

## Uso del API

### Crear Transacción
```json
POST /api/v1/transactions
{
  "financial_concept_id": 1,
  "description": "Matrícula estudiante Juan Pérez",
  "amount": 500000.00,
  "transaction_date": "2025-01-27",
  "payment_method": "bank_transfer",
  "accounts": [
    {
      "account_id": 1,
      "type": "credit",
      "amount": 500000.00
    }
  ],
  "metadata": {
    "student_id": 123,
    "student_name": "Juan Pérez"
  }
}
```

### Aprobar Transacción
```json
PATCH /api/v1/transactions/1/status
{
  "status": "approved",
  "approval_notes": "Documentación verificada y completa"
}
```

### Filtrar Transacciones
```
GET /api/v1/transactions?status=pending&date_from=2025-01-01&date_to=2025-01-31&concept_type=income
```

### Obtener Estadísticas
```json
GET /api/v1/transactions/statistics?school_id=1&date_from=2025-01-01&date_to=2025-01-31

Respuesta:
{
  "total_transactions": 150,
  "total_amount": 15000000.00,
  "by_status": {
    "pending": 25,
    "approved": 100,
    "rejected": 15,
    "cancelled": 10
  },
  "by_type": {
    "income": {
      "count": 80,
      "amount": 12000000.00
    },
    "expense": {
      "count": 70,
      "amount": 3000000.00
    }
  }
}
```

## Campos del Modelo Transaction

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `school_id` | bigint | ID de la institución |
| `financial_concept_id` | bigint | ID del concepto financiero |
| `reference_number` | string | Número de referencia único |
| `description` | text | Descripción de la transacción |
| `amount` | decimal(15,2) | Monto de la transacción |
| `transaction_date` | date | Fecha de la transacción |
| `status` | enum | Estado actual |
| `payment_method` | string | Método de pago |
| `metadata` | json | Datos adicionales |
| `created_by` | bigint | Usuario que creó |
| `approved_by` | bigint | Usuario que aprobó |
| `approved_at` | timestamp | Fecha de aprobación |
| `approval_notes` | text | Notas de aprobación |

## Estados de Transacción

- **`pending`**: Transacción creada, pendiente de aprobación
- **`approved`**: Transacción aprobada, balances actualizados
- **`rejected`**: Transacción rechazada
- **`cancelled`**: Transacción cancelada, balances revertidos
- **`completed`**: Transacción completada (estado final)

## Validaciones Implementadas

### CreateTransactionRequest
- `financial_concept_id`: Requerido, debe existir
- `amount`: Requerido, numérico, mayor a 0
- `transaction_date`: Requerida, fecha válida, no futura
- `accounts`: Requerido, array con cuentas válidas
- `accounts.*.account_id`: Debe existir y pertenecer a la misma escuela

### UpdateTransactionRequest
- Campos opcionales para actualización parcial
- Mismas validaciones que creación cuando se proporcionan

## Métodos del TransactionService

- `createTransaction()`: Crear nueva transacción
- `updateTransactionStatus()`: Cambiar estado con validaciones
- `generateReferenceNumber()`: Generar número único
- `updateAccountBalances()`: Actualizar balances
- `reverseAccountBalances()`: Revertir balances
- `validateBusinessRules()`: Validaciones de negocio

## Scopes del Modelo

- `forSchool($schoolId)`: Filtrar por institución
- `withStatus($status)`: Filtrar por estado
- `betweenDates($start, $end)`: Filtrar por rango de fechas
- `pending()`: Solo transacciones pendientes
- `approved()`: Solo transacciones aprobadas
- `byConceptType($type)`: Filtrar por tipo de concepto

## Instalación y Configuración

1. **Ejecutar migraciones**:
   ```bash
   php artisan migrate
   ```

2. **Ejecutar seeders** (opcional):
   ```bash
   php artisan db:seed --class=TransactionSeeder
   ```

3. **Configurar permisos**: Asegurar que los usuarios tengan permisos para aprobar transacciones

## Consideraciones de Seguridad

- Todas las rutas requieren autenticación (`auth:sanctum`)
- Validación de pertenencia a la institución
- Registro de auditoría en campos `created_by` y `approved_by`
- Validaciones de negocio para prevenir inconsistencias

## Próximas Mejoras

- [ ] Notificaciones automáticas para aprobaciones
- [ ] Workflow de aprobación multinivel
- [ ] Integración con sistema de reportes avanzados
- [ ] API para exportación de datos
- [ ] Dashboard en tiempo real