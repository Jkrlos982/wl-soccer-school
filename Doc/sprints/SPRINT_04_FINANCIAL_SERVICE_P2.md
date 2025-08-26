# Sprint 4: Financial Service - Parte 2 (Cuentas por Cobrar)

**Duración:** 2 semanas  
**Fase:** 2 - Módulos Financiero y Deportivo Básico  
**Objetivo:** Completar el sistema financiero con gestión de cuentas por cobrar, comprobantes de pago y facturación

## Resumen del Sprint

Este sprint completa el módulo financiero implementando la gestión de cuentas por cobrar, generación de comprobantes de pago, facturación automática y sistema de seguimiento de pagos pendientes.

## Objetivos Específicos

- ✅ Implementar sistema de cuentas por cobrar
- ✅ Crear generación automática de comprobantes
- ✅ Desarrollar sistema de facturación
- ✅ Implementar seguimiento de pagos pendientes
- ✅ Crear notificaciones de pago automáticas

## Tareas Detalladas

### 1. Sistema de Cuentas por Cobrar

**Responsable:** Backend Developer Senior  
**Estimación:** 4 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear modelos para cuentas por cobrar:**
   
   **AccountReceivable Model:**
   ```php
   // Tabla: accounts_receivable
   // Campos: id, school_id, student_id, concept_id, amount, 
   // due_date, status, description, created_by, created_at, updated_at
   ```
   
   **Payment Model:**
   ```php
   // Tabla: payments
   // Campos: id, school_id, account_receivable_id, amount, 
   // payment_date, payment_method, reference_number, 
   // voucher_path, status, created_by, created_at, updated_at
   ```
   
   **PaymentPlan Model:**
   ```php
   // Tabla: payment_plans
   // Campos: id, school_id, student_id, total_amount, 
   // installments, frequency, start_date, status, created_at, updated_at
   ```
   
   **PaymentPlanInstallment Model:**
   ```php
   // Tabla: payment_plan_installments
   // Campos: id, payment_plan_id, installment_number, amount, 
   // due_date, status, paid_date, payment_id
   ```

2. **Implementar relaciones entre modelos:**
   ```php
   // AccountReceivable
   public function student() { return $this->belongsTo(Student::class); }
   public function concept() { return $this->belongsTo(FinancialConcept::class); }
   public function payments() { return $this->hasMany(Payment::class); }
   public function remainingAmount() { return $this->amount - $this->payments->sum('amount'); }
   
   // Payment
   public function accountReceivable() { return $this->belongsTo(AccountReceivable::class); }
   public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
   
   // PaymentPlan
   public function student() { return $this->belongsTo(Student::class); }
   public function installments() { return $this->hasMany(PaymentPlanInstallment::class); }
   ```

3. **Crear controladores:**
   - `AccountReceivableController` - Gestión de cuentas por cobrar
   - `PaymentController` - Registro de pagos
   - `PaymentPlanController` - Planes de pago
   - `VoucherController` - Generación de comprobantes
   - `CollectionController` - Reportes de cobranza

4. **Implementar servicios de negocio:**
   ```php
   class AccountReceivableService {
       public function createAccountReceivable(array $data): AccountReceivable {
           // Crear cuenta por cobrar
           // Generar número de referencia
           // Crear notificación automática
       }
       
       public function registerPayment(AccountReceivable $ar, array $paymentData): Payment {
           DB::beginTransaction();
           try {
               // Registrar pago
               // Actualizar estado de cuenta por cobrar
               // Generar comprobante
               // Crear transacción financiera
               // Enviar notificación
               DB::commit();
           } catch (Exception $e) {
               DB::rollback();
               throw $e;
           }
       }
   }
   ```

#### Criterios de Aceptación:
- [ ] Modelos de cuentas por cobrar creados
- [ ] Sistema de pagos funcionando
- [ ] Planes de pago implementados
- [ ] Servicios de negocio operativos
- [ ] Validaciones de integridad activas

---

### 2. Generación de Comprobantes de Pago

**Responsable:** Backend Developer  
**Estimación:** 3 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear sistema de templates para comprobantes:**
   ```php
   // VoucherTemplate Model
   // Campos: id, school_id, name, type, template_html, 
   // variables, is_default, created_at, updated_at
   ```

2. **Implementar generador de PDFs:**
   ```php
   class VoucherGenerator {
       public function generatePaymentVoucher(Payment $payment): string {
           $template = $this->getTemplate('payment_voucher');
           $html = $this->renderTemplate($template, [
               'payment' => $payment,
               'student' => $payment->accountReceivable->student,
               'school' => $payment->school,
               'date' => now()->format('d/m/Y'),
               'amount' => number_format($payment->amount, 2)
           ]);
           
           return $this->generatePDF($html);
       }
   }
   ```

3. **Crear templates por defecto:**
   ```html
   <!-- payment_voucher_template.html -->
   <div class="voucher">
       <div class="header">
           <img src="{{ school.logo }}" alt="Logo">
           <h1>{{ school.name }}</h1>
       </div>
       <div class="content">
           <h2>Comprobante de Pago</h2>
           <p><strong>Número:</strong> {{ payment.reference_number }}</p>
           <p><strong>Fecha:</strong> {{ date }}</p>
           <p><strong>Estudiante:</strong> {{ student.full_name }}</p>
           <p><strong>Concepto:</strong> {{ payment.accountReceivable.concept.name }}</p>
           <p><strong>Monto:</strong> ${{ amount }}</p>
       </div>
       <div class="footer">
           <p>Gracias por su pago</p>
       </div>
   </div>
   ```

4. **Implementar numeración automática:**
   ```php
   // Formato: COMP-YYYY-MM-NNNN
   // Ejemplo: COMP-2024-01-0001
   class VoucherNumberGenerator {
       public function generateNumber(string $type = 'COMP'): string {
           $date = now();
           $prefix = $type . '-' . $date->format('Y-m');
           
           $lastNumber = Payment::where('reference_number', 'like', $prefix . '%')
               ->orderBy('reference_number', 'desc')
               ->value('reference_number');
               
           $sequence = $lastNumber ? intval(substr($lastNumber, -4)) + 1 : 1;
           
           return $prefix . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
       }
   }
   ```

#### Criterios de Aceptación:
- [ ] Templates de comprobantes creados
- [ ] Generación de PDFs funcionando
- [ ] Numeración automática implementada
- [ ] Personalización por escuela activa

---

### 3. Sistema de Facturación Automática

**Responsable:** Backend Developer  
**Estimación:** 3 días  
**Prioridad:** Media

#### Subtareas:

1. **Crear modelos de facturación:**
   ```php
   // Invoice Model
   // Campos: id, school_id, student_id, invoice_number, 
   // issue_date, due_date, subtotal, tax_amount, total, 
   // status, notes, created_at, updated_at
   
   // InvoiceItem Model
   // Campos: id, invoice_id, concept_id, description, 
   // quantity, unit_price, total, created_at, updated_at
   ```

2. **Implementar generación automática de facturas:**
   ```php
   class InvoiceService {
       public function generateMonthlyInvoices(): void {
           $students = Student::with('activeEnrollments')->get();
           
           foreach ($students as $student) {
               $this->generateStudentInvoice($student);
           }
       }
       
       private function generateStudentInvoice(Student $student): Invoice {
           $invoice = Invoice::create([
               'student_id' => $student->id,
               'invoice_number' => $this->generateInvoiceNumber(),
               'issue_date' => now(),
               'due_date' => now()->addDays(30),
               'status' => 'pending'
           ]);
           
           // Agregar items de factura (mensualidad, etc.)
           $this->addInvoiceItems($invoice, $student);
           
           return $invoice;
       }
   }
   ```

3. **Crear comando para facturación automática:**
   ```php
   // php artisan invoices:generate-monthly
   class GenerateMonthlyInvoices extends Command {
       protected $signature = 'invoices:generate-monthly';
       
       public function handle(InvoiceService $invoiceService) {
           $this->info('Generando facturas mensuales...');
           $invoiceService->generateMonthlyInvoices();
           $this->info('Facturas generadas exitosamente.');
       }
   }
   ```

4. **Implementar estados de factura:**
   ```php
   // Estados: draft, pending, paid, overdue, cancelled
   // Transiciones automáticas basadas en fechas y pagos
   ```

#### Criterios de Aceptación:
- [ ] Modelos de facturación creados
- [ ] Generación automática funcionando
- [ ] Comando de facturación implementado
- [ ] Estados de factura manejados correctamente

---

### 4. Frontend - Gestión de Cuentas por Cobrar

**Responsable:** Frontend Developer  
**Estimación:** 4 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear componentes para cuentas por cobrar:**
   ```typescript
   // AccountsReceivableList - Lista de cuentas por cobrar
   // AccountReceivableDetail - Detalle de cuenta
   // PaymentForm - Formulario de registro de pago
   // PaymentPlanForm - Formulario de plan de pago
   // VoucherViewer - Visualizador de comprobantes
   // CollectionDashboard - Dashboard de cobranza
   ```

2. **Implementar gestión de estado:**
   ```typescript
   interface AccountsReceivableState {
     accountsReceivable: AccountReceivable[];
     payments: Payment[];
     paymentPlans: PaymentPlan[];
     filters: ARFilters;
     selectedAR: AccountReceivable | null;
     isLoading: boolean;
     error: string | null;
   }
   ```

3. **Crear servicios de API:**
   ```typescript
   class AccountsReceivableService {
     async getAccountsReceivable(filters?: ARFilters): Promise<PaginatedResponse<AccountReceivable>>
     async createAccountReceivable(data: CreateARData): Promise<AccountReceivable>
     async registerPayment(arId: string, paymentData: PaymentData): Promise<Payment>
     async createPaymentPlan(data: PaymentPlanData): Promise<PaymentPlan>
     async generateVoucher(paymentId: string): Promise<Blob>
     async getCollectionReport(filters: ReportFilters): Promise<CollectionReport>
   }
   ```

4. **Implementar dashboard de cobranza:**
   ```typescript
   // Métricas principales:
   // - Total por cobrar
   // - Pagos del día/mes
   // - Cuentas vencidas
   // - Próximos vencimientos
   // - Gráfico de cobranza por período
   // - Top estudiantes con deuda
   ```

5. **Crear formularios de pago:**
   ```typescript
   const paymentSchema = yup.object({
     amount: yup.number().positive().required('Monto es requerido'),
     payment_date: yup.date().required('Fecha es requerida'),
     payment_method: yup.string().required('Método de pago es requerido'),
     reference_number: yup.string().when('payment_method', {
       is: (method: string) => ['transfer', 'check'].includes(method),
       then: yup.string().required('Número de referencia es requerido')
     })
   });
   ```

6. **Implementar filtros avanzados:**
   ```typescript
   // Filtros por:
   // - Estado (pendiente, pagado, vencido)
   // - Estudiante
   // - Concepto
   // - Rango de fechas
   // - Monto
   // - Método de pago
   ```

#### Criterios de Aceptación:
- [ ] Componentes de cuentas por cobrar funcionando
- [ ] Dashboard de cobranza implementado
- [ ] Registro de pagos funcional
- [ ] Generación de comprobantes en frontend
- [ ] Filtros y búsquedas operativos

---

### 5. Notificaciones Automáticas de Pago

**Responsable:** Backend Developer + Integration  
**Estimación:** 2 días  
**Prioridad:** Media

#### Subtareas:

1. **Crear sistema de notificaciones:**
   ```php
   class PaymentNotificationService {
       public function sendPaymentReminder(AccountReceivable $ar): void {
           // Enviar recordatorio de pago
           // WhatsApp + Email
       }
       
       public function sendPaymentConfirmation(Payment $payment): void {
           // Confirmar pago recibido
           // Adjuntar comprobante
       }
       
       public function sendOverdueNotification(AccountReceivable $ar): void {
           // Notificar pago vencido
           // Incluir información de mora
       }
   }
   ```

2. **Crear templates de notificación:**
   ```php
   // Templates para:
   // - Recordatorio de pago (3 días antes)
   // - Confirmación de pago
   // - Notificación de vencimiento
   // - Pago vencido
   ```

3. **Implementar comandos programados:**
   ```php
   // Comando diario para enviar recordatorios
   // Comando diario para notificar vencimientos
   // Comando semanal para pagos vencidos
   ```

4. **Crear configuración de notificaciones:**
   ```php
   // Permitir a cada escuela configurar:
   // - Días de anticipación para recordatorios
   // - Frecuencia de notificaciones de mora
   // - Templates personalizados
   ```

#### Criterios de Aceptación:
- [ ] Notificaciones automáticas funcionando
- [ ] Templates personalizables
- [ ] Comandos programados activos
- [ ] Configuración por escuela implementada

---

## API Endpoints Implementados

### Accounts Receivable
```
# Cuentas por Cobrar
GET    /api/v1/financial/accounts-receivable
POST   /api/v1/financial/accounts-receivable
GET    /api/v1/financial/accounts-receivable/{id}
PUT    /api/v1/financial/accounts-receivable/{id}
DELETE /api/v1/financial/accounts-receivable/{id}
GET    /api/v1/financial/accounts-receivable/overdue

# Pagos
GET    /api/v1/financial/payments
POST   /api/v1/financial/payments
GET    /api/v1/financial/payments/{id}
PUT    /api/v1/financial/payments/{id}
DELETE /api/v1/financial/payments/{id}
GET    /api/v1/financial/payments/{id}/voucher

# Planes de Pago
GET    /api/v1/financial/payment-plans
POST   /api/v1/financial/payment-plans
GET    /api/v1/financial/payment-plans/{id}
PUT    /api/v1/financial/payment-plans/{id}
DELETE /api/v1/financial/payment-plans/{id}

# Facturas
GET    /api/v1/financial/invoices
POST   /api/v1/financial/invoices
GET    /api/v1/financial/invoices/{id}
PUT    /api/v1/financial/invoices/{id}
POST   /api/v1/financial/invoices/generate-monthly

# Comprobantes
GET    /api/v1/financial/vouchers/templates
POST   /api/v1/financial/vouchers/generate
GET    /api/v1/financial/vouchers/{id}/download

# Reportes de Cobranza
GET    /api/v1/financial/collection/dashboard
GET    /api/v1/financial/collection/aging-report
GET    /api/v1/financial/collection/payment-history
```

## Definición de Terminado (DoD)

### Criterios Técnicos:
- [ ] Sistema de cuentas por cobrar funcional
- [ ] Generación de comprobantes operativa
- [ ] Facturación automática implementada
- [ ] Frontend de cobranza funcionando
- [ ] Notificaciones automáticas activas
- [ ] API endpoints documentados

### Criterios de Calidad:
- [ ] Tests unitarios > 85% cobertura
- [ ] Tests de integración pasando
- [ ] Performance validada (< 1s generación PDF)
- [ ] Integridad de datos garantizada
- [ ] UX validada con usuarios finales

### Criterios de Negocio:
- [ ] Cuentas por cobrar se crean automáticamente
- [ ] Pagos se registran correctamente
- [ ] Comprobantes se generan automáticamente
- [ ] Notificaciones se envían oportunamente
- [ ] Reportes de cobranza son precisos

## Riesgos Identificados

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| Complejidad de facturación | Media | Alto | Simplificar, iterar, validar con usuarios |
| Performance generación PDFs | Alta | Medio | Cache, optimización, generación asíncrona |
| Integridad pagos | Alta | Crítico | Transacciones BD, auditoría, validaciones |
| Notificaciones spam | Media | Medio | Rate limiting, configuración, opt-out |

## Métricas de Éxito

- **PDF generation time**: < 1s por comprobante
- **Payment processing**: < 300ms
- **Collection rate**: Mejorar 15% vs manual
- **User satisfaction**: > 4.2/5 en módulo financiero
- **Data accuracy**: 100% integridad en pagos

## Entregables

1. **Sistema de Cuentas por Cobrar** - Gestión completa
2. **Generador de Comprobantes** - PDFs automáticos
3. **Sistema de Facturación** - Facturación automática
4. **Frontend de Cobranza** - Interfaz completa
5. **Notificaciones Automáticas** - Sistema de recordatorios
6. **Reportes de Cobranza** - Dashboard y reportes

## Configuración de Entorno

### Variables Adicionales Financial Service
```env
PDF_GENERATOR=dompdf
VOUCHER_STORAGE_PATH=/app/storage/vouchers
INVOICE_STORAGE_PATH=/app/storage/invoices
NOTIFICATION_SERVICE_URL=http://notification-service:8000
AUTO_INVOICE_GENERATION=true
PAYMENT_REMINDER_DAYS=3
```

## Datos de Prueba

### Cuentas por Cobrar de Ejemplo
```json
{
  "accounts_receivable": [
    {
      "student_id": 1,
      "concept_id": 1,
      "amount": 150000,
      "due_date": "2024-02-15",
      "status": "pending"
    },
    {
      "student_id": 2,
      "concept_id": 1,
      "amount": 150000,
      "due_date": "2024-02-10",
      "status": "overdue"
    }
  ]
}
```

## Retrospectiva

### Preguntas para la retrospectiva:
1. ¿El sistema de cuentas por cobrar cubre todos los casos de uso?
2. ¿Los comprobantes generados son profesionales y completos?
3. ¿La facturación automática reduce el trabajo manual?
4. ¿Las notificaciones mejoran la cobranza?
5. ¿Qué funcionalidades financieras adicionales necesitamos?

---

**Sprint Anterior:** Sprint 3 - Financial Service (Parte 1)  
**Próximo Sprint:** Sprint 5 - Sports Service (Parte 1) - Gestión Básica