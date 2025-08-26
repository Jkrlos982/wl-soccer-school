# Sprint 3: Financial Service - Parte 1

**Duración:** 2 semanas  
**Fase:** 2 - Módulos Financiero y Deportivo Básico  
**Objetivo:** Implementar el núcleo del sistema financiero con gestión de conceptos, transacciones e ingresos/gastos

## Resumen del Sprint

Este sprint se enfoca en crear la base del módulo financiero, permitiendo a las escuelas gestionar sus ingresos y gastos de manera personalizable, con conceptos configurables y un sistema robusto de transacciones.

## Objetivos Específicos

- ✅ Desarrollar microservicio financiero completo
- ✅ Implementar CRUD de conceptos financieros personalizables
- ✅ Crear sistema de registro de ingresos y gastos
- ✅ Establecer API endpoints completos
- ✅ Desarrollar frontend del módulo financiero

## Tareas Detalladas

### 1. Microservicio Financiero (Financial Service)

**Responsable:** Backend Developer Senior  
**Estimación:** 4 días  
**Prioridad:** Alta

#### Subtareas:

1. **Configurar proyecto Laravel para Financial Service:**
   ```bash
   composer create-project laravel/laravel financial-service
   composer require laravel/sanctum
   composer require spatie/laravel-query-builder
   composer require maatwebsite/excel
   composer require barryvdh/laravel-dompdf
   ```

2. **Crear modelos y migraciones:**
   
   **FinancialConcept Model:**
   ```php
   // Tabla: financial_concepts
   // Campos: id, school_id, name, description, type (income/expense),
   // category, is_active, created_by, created_at, updated_at
   ```
   
   **Transaction Model:**
   ```php
   // Tabla: transactions
   // Campos: id, school_id, concept_id, amount, description, 
   // transaction_date, type, reference_number, created_by,
   // approved_by, approved_at, status, metadata (JSON)
   ```
   
   **Account Model:**
   ```php
   // Tabla: accounts
   // Campos: id, school_id, name, type (cash/bank), 
   // account_number, balance, is_active
   ```
   
   **TransactionAccount Model:**
   ```php
   // Tabla: transaction_accounts (pivot)
   // Campos: transaction_id, account_id, amount, type (debit/credit)
   ```

3. **Implementar relaciones entre modelos:**
   ```php
   // FinancialConcept
   public function transactions() { return $this->hasMany(Transaction::class, 'concept_id'); }
   public function school() { return $this->belongsTo(School::class); }
   
   // Transaction
   public function concept() { return $this->belongsTo(FinancialConcept::class); }
   public function accounts() { return $this->belongsToMany(Account::class)->withPivot('amount', 'type'); }
   public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
   ```

4. **Crear controladores:**
   - `FinancialConceptController` - CRUD conceptos
   - `TransactionController` - Gestión de transacciones
   - `AccountController` - Gestión de cuentas
   - `ReportController` - Reportes financieros básicos
   - `DashboardController` - Métricas del dashboard

5. **Implementar validaciones:**
   ```php
   // CreateTransactionRequest
   public function rules() {
       return [
           'concept_id' => 'required|exists:financial_concepts,id',
           'amount' => 'required|numeric|min:0.01',
           'description' => 'required|string|max:500',
           'transaction_date' => 'required|date',
           'accounts' => 'required|array|min:1',
           'accounts.*.account_id' => 'required|exists:accounts,id',
           'accounts.*.amount' => 'required|numeric',
           'accounts.*.type' => 'required|in:debit,credit'
       ];
   }
   ```

#### Criterios de Aceptación:
- [ ] Modelos creados con relaciones correctas
- [ ] Controladores implementados con validaciones
- [ ] CRUD completo de conceptos financieros
- [ ] Sistema de transacciones funcionando
- [ ] Gestión de cuentas implementada
- [ ] Tests unitarios > 85% cobertura

---

### 2. Sistema de Conceptos Financieros Personalizables

**Responsable:** Backend Developer  
**Estimación:** 2 días  
**Prioridad:** Alta

#### Subtareas:

1. **Definir categorías de conceptos por defecto:**
   ```php
   // Ingresos
   'mensualidades' => 'Pagos mensuales de estudiantes',
   'inscripciones' => 'Pagos de inscripción',
   'uniformes' => 'Venta de uniformes y equipamiento',
   'eventos' => 'Ingresos por eventos especiales',
   'patrocinios' => 'Ingresos por patrocinios',
   'otros_ingresos' => 'Otros ingresos diversos'
   
   // Gastos
   'salarios' => 'Pagos de salarios y honorarios',
   'servicios' => 'Servicios públicos y mantenimiento',
   'equipamiento' => 'Compra de equipamiento deportivo',
   'transporte' => 'Gastos de transporte',
   'marketing' => 'Gastos de marketing y publicidad',
   'otros_gastos' => 'Otros gastos diversos'
   ```

2. **Crear seeder para conceptos por defecto:**
   ```php
   // FinancialConceptSeeder
   // Crear conceptos base para cada escuela nueva
   // Permitir personalización posterior
   ```

3. **Implementar API para gestión de conceptos:**
   ```php
   // GET /api/v1/financial/concepts
   // POST /api/v1/financial/concepts
   // PUT /api/v1/financial/concepts/{id}
   // DELETE /api/v1/financial/concepts/{id}
   // GET /api/v1/financial/concepts/categories
   ```

4. **Crear sistema de templates:**
   ```php
   // ConceptTemplate Model
   // Templates predefinidos que las escuelas pueden usar
   // Facilitar setup inicial
   ```

#### Criterios de Aceptación:
- [ ] Conceptos por defecto creados
- [ ] API de gestión de conceptos funcional
- [ ] Sistema de categorías implementado
- [ ] Templates de conceptos disponibles
- [ ] Validaciones de negocio funcionando

---

### 3. Sistema de Transacciones

**Responsable:** Backend Developer  
**Estimación:** 3 días  
**Prioridad:** Alta

#### Subtareas:

1. **Implementar lógica de transacciones:**
   ```php
   class TransactionService {
       public function createTransaction(array $data): Transaction {
           DB::beginTransaction();
           try {
               // Crear transacción
               $transaction = Transaction::create($data);
               
               // Actualizar balances de cuentas
               $this->updateAccountBalances($transaction, $data['accounts']);
               
               // Registrar en auditoría
               $this->logTransaction($transaction);
               
               DB::commit();
               return $transaction;
           } catch (Exception $e) {
               DB::rollback();
               throw $e;
           }
       }
   }
   ```

2. **Crear sistema de aprobaciones:**
   ```php
   // Estados: pending, approved, rejected, cancelled
   // Workflow de aprobación configurable
   // Notificaciones automáticas
   ```

3. **Implementar filtros y búsquedas:**
   ```php
   // Filtrar por fecha, concepto, monto, estado
   // Búsqueda por descripción
   // Ordenamiento múltiple
   // Paginación optimizada
   ```

4. **Crear sistema de numeración:**
   ```php
   // Generar números de referencia únicos
   // Formato: YYYY-MM-TIPO-NNNN
   // Ejemplo: 2024-01-ING-0001
   ```

5. **Implementar validaciones de negocio:**
   ```php
   // Validar balance de cuentas
   // Verificar límites de transacción
   // Validar fechas de transacción
   // Verificar permisos de usuario
   ```

#### Criterios de Aceptación:
- [ ] Transacciones se crean correctamente
- [ ] Balances de cuentas se actualizan
- [ ] Sistema de aprobaciones funcional
- [ ] Filtros y búsquedas implementados
- [ ] Numeración automática funcionando
- [ ] Validaciones de negocio activas

---

### 4. Frontend del Módulo Financiero

**Responsable:** Frontend Developer  
**Estimación:** 4 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear componentes base:**
   ```typescript
   // FinancialLayout - Layout del módulo
   // TransactionList - Lista de transacciones
   // TransactionForm - Formulario de transacción
   // ConceptManager - Gestión de conceptos
   // AccountManager - Gestión de cuentas
   // FinancialDashboard - Dashboard con métricas
   ```

2. **Implementar gestión de estado:**
   ```typescript
   // Redux slices
   interface FinancialState {
     transactions: Transaction[];
     concepts: FinancialConcept[];
     accounts: Account[];
     filters: TransactionFilters;
     isLoading: boolean;
     error: string | null;
   }
   ```

3. **Crear servicios de API:**
   ```typescript
   class FinancialService {
     async getTransactions(filters?: TransactionFilters): Promise<PaginatedResponse<Transaction>>
     async createTransaction(data: CreateTransactionData): Promise<Transaction>
     async updateTransaction(id: string, data: UpdateTransactionData): Promise<Transaction>
     async deleteTransaction(id: string): Promise<void>
     
     async getConcepts(): Promise<FinancialConcept[]>
     async createConcept(data: CreateConceptData): Promise<FinancialConcept>
     
     async getAccounts(): Promise<Account[]>
     async createAccount(data: CreateAccountData): Promise<Account>
   }
   ```

4. **Implementar formularios con validación:**
   ```typescript
   // Usar react-hook-form + yup
   const transactionSchema = yup.object({
     concept_id: yup.string().required('Concepto es requerido'),
     amount: yup.number().positive('Monto debe ser positivo').required(),
     description: yup.string().required('Descripción es requerida'),
     transaction_date: yup.date().required('Fecha es requerida'),
     accounts: yup.array().min(1, 'Debe seleccionar al menos una cuenta')
   });
   ```

5. **Crear dashboard financiero:**
   ```typescript
   // Métricas principales:
   // - Ingresos del mes
   // - Gastos del mes
   // - Balance actual
   // - Transacciones pendientes
   // - Gráficos de tendencias
   ```

6. **Implementar filtros avanzados:**
   ```typescript
   // Filtros por:
   // - Rango de fechas
   // - Tipo de transacción
   // - Concepto
   // - Monto (rango)
   // - Estado
   // - Cuenta
   ```

#### Criterios de Aceptación:
- [ ] Componentes financieros funcionando
- [ ] CRUD de transacciones en frontend
- [ ] Gestión de conceptos implementada
- [ ] Dashboard con métricas básicas
- [ ] Filtros y búsquedas funcionales
- [ ] Validaciones de formularios activas

---

### 5. Reportes Financieros Básicos

**Responsable:** Backend Developer + Frontend Developer  
**Estimación:** 2 días  
**Prioridad:** Media

#### Subtareas:

1. **Crear controlador de reportes:**
   ```php
   class FinancialReportController {
       public function incomeStatement(Request $request) {
           // Estado de resultados
           // Ingresos vs Gastos por período
       }
       
       public function cashFlow(Request $request) {
           // Flujo de caja
           // Entradas y salidas por período
       }
       
       public function balanceSheet(Request $request) {
           // Balance general
           // Activos, pasivos y patrimonio
       }
   }
   ```

2. **Implementar exportación:**
   ```php
   // Exportar a Excel
   // Exportar a PDF
   // Exportar a CSV
   ```

3. **Crear componentes de reportes:**
   ```typescript
   // ReportGenerator - Generador de reportes
   // ReportViewer - Visualizador de reportes
   // ReportFilters - Filtros para reportes
   // ChartComponents - Gráficos financieros
   ```

4. **Implementar gráficos:**
   ```typescript
   // Usar Chart.js o Recharts
   // Gráfico de ingresos vs gastos
   // Gráfico de tendencias
   // Gráfico por categorías
   ```

#### Criterios de Aceptación:
- [ ] Reportes básicos implementados
- [ ] Exportación funcionando
- [ ] Gráficos financieros creados
- [ ] Filtros de reportes funcionales

---

## API Endpoints Implementados

### Financial Service
```
# Conceptos Financieros
GET    /api/v1/financial/concepts
POST   /api/v1/financial/concepts
GET    /api/v1/financial/concepts/{id}
PUT    /api/v1/financial/concepts/{id}
DELETE /api/v1/financial/concepts/{id}
GET    /api/v1/financial/concepts/categories

# Transacciones
GET    /api/v1/financial/transactions
POST   /api/v1/financial/transactions
GET    /api/v1/financial/transactions/{id}
PUT    /api/v1/financial/transactions/{id}
DELETE /api/v1/financial/transactions/{id}
POST   /api/v1/financial/transactions/{id}/approve
POST   /api/v1/financial/transactions/{id}/reject

# Cuentas
GET    /api/v1/financial/accounts
POST   /api/v1/financial/accounts
GET    /api/v1/financial/accounts/{id}
PUT    /api/v1/financial/accounts/{id}
DELETE /api/v1/financial/accounts/{id}
GET    /api/v1/financial/accounts/{id}/balance

# Dashboard y Reportes
GET    /api/v1/financial/dashboard
GET    /api/v1/financial/reports/income-statement
GET    /api/v1/financial/reports/cash-flow
GET    /api/v1/financial/reports/balance-sheet
POST   /api/v1/financial/reports/export
```

## Definición de Terminado (DoD)

### Criterios Técnicos:
- [ ] Financial Service completamente funcional
- [ ] CRUD de conceptos implementado
- [ ] Sistema de transacciones operativo
- [ ] Frontend financiero funcionando
- [ ] Reportes básicos implementados
- [ ] API endpoints documentados

### Criterios de Calidad:
- [ ] Tests unitarios > 85% cobertura
- [ ] Tests de integración pasando
- [ ] Code review completado
- [ ] Performance validada (< 500ms queries)
- [ ] Validaciones de negocio funcionando

### Criterios de Negocio:
- [ ] Conceptos personalizables funcionando
- [ ] Transacciones se registran correctamente
- [ ] Balances se calculan correctamente
- [ ] Reportes muestran datos precisos
- [ ] UX intuitiva para usuarios finales

## Riesgos Identificados

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| Complejidad contable | Alta | Alto | Consultar experto contable, simplificar |
| Performance con muchas transacciones | Media | Alto | Índices BD, paginación, cache |
| Validaciones de negocio | Media | Medio | Tests exhaustivos, validación con usuarios |
| Integridad de datos | Alta | Crítico | Transacciones BD, auditoría, backups |

## Métricas de Éxito

- **Query performance**: < 500ms para listados
- **Transaction creation**: < 200ms
- **Report generation**: < 2s
- **Data accuracy**: 100% integridad en balances
- **User satisfaction**: > 4.0/5 en usabilidad

## Entregables

1. **Financial Service** - Microservicio completo
2. **Conceptos Financieros** - Sistema personalizable
3. **Sistema de Transacciones** - CRUD completo
4. **Frontend Financiero** - Interfaz completa
5. **Reportes Básicos** - Estado de resultados y flujo de caja
6. **Documentación** - API docs y guías de usuario

## Configuración de Entorno

### Variables de Entorno Financial Service
```env
DB_HOST=mysql-financial
DB_DATABASE=wl_school_financial
REDIS_HOST=redis
AUTH_SERVICE_URL=http://auth-service:8000
FILE_STORAGE_DISK=local
REPORT_EXPORT_PATH=/app/storage/reports
```

## Datos de Prueba

### Conceptos de Ejemplo
```json
{
  "income_concepts": [
    {"name": "Mensualidad", "category": "mensualidades"},
    {"name": "Inscripción", "category": "inscripciones"},
    {"name": "Uniforme", "category": "uniformes"}
  ],
  "expense_concepts": [
    {"name": "Salario Entrenador", "category": "salarios"},
    {"name": "Electricidad", "category": "servicios"},
    {"name": "Balones", "category": "equipamiento"}
  ]
}
```

## Retrospectiva

### Preguntas para la retrospectiva:
1. ¿El sistema de conceptos es suficientemente flexible?
2. ¿Las transacciones cubren todos los casos de uso?
3. ¿Los reportes proporcionan la información necesaria?
4. ¿La interfaz es intuitiva para usuarios no técnicos?
5. ¿Qué funcionalidades financieras adicionales son prioritarias?

---

**Sprint Anterior:** Sprint 2 - Auth Service y API Gateway  
**Próximo Sprint:** Sprint 4 - Financial Service (Parte 2) - Cuentas por Cobrar