# Sprint 10: Payroll Service (Servicio de Nómina)

**Duración:** 2 semanas  
**Fase:** 4 - Módulos Médicos y Nómina  
**Objetivo:** Implementar sistema completo de gestión de nómina con cálculos automáticos, deducciones, bonificaciones y reportes fiscales

## Resumen del Sprint

Este sprint implementa el microservicio de nómina con gestión de empleados, cálculos salariales automáticos, manejo de deducciones y bonificaciones, generación de recibos de pago, reportes fiscales y integración con el sistema financiero.

## Objetivos Específicos

- ✅ Implementar microservicio de nómina
- ✅ Crear sistema de gestión de empleados
- ✅ Implementar cálculos salariales automáticos
- ✅ Gestionar deducciones y bonificaciones
- ✅ Generar recibos de pago automáticos
- ✅ Crear reportes fiscales y contables
- ✅ Integrar con servicio financiero

## Tareas Detalladas

### 1. Configuración Base del Microservicio

**Responsable:** Backend Developer Senior  
**Estimación:** 1 día  
**Prioridad:** Alta

#### Subtareas:

1. **Crear estructura del microservicio:**
   ```bash
   # Crear directorio del servicio
   mkdir wl-school-payroll-service
   cd wl-school-payroll-service
   
   # Inicializar Laravel
   composer create-project laravel/laravel . "10.*"
   
   # Instalar dependencias específicas
   composer require:
     - barryvdh/laravel-dompdf (PDF generation)
     - spatie/laravel-permission (Permissions)
     - spatie/laravel-activitylog (Activity logging)
     - league/csv (CSV export/import)
     - maatwebsite/excel (Excel export/import)
     - nesbot/carbon (Date manipulation)
     - brick/money (Money calculations)
     - spatie/laravel-backup (Database backup)
   ```

2. **Configurar variables de entorno:**
   ```env
   # .env
   APP_NAME="WL School Payroll Service"
   APP_URL=http://localhost:8007
   
   # Database
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=wl_school_payroll
   DB_USERNAME=root
   DB_PASSWORD=password
   
   # Queue
   QUEUE_CONNECTION=redis
   REDIS_HOST=redis
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   
   # File Storage
   FILESYSTEM_DISK=local
   AWS_ACCESS_KEY_ID=your_aws_key
   AWS_SECRET_ACCESS_KEY=your_aws_secret
   AWS_DEFAULT_REGION=us-east-1
   AWS_BUCKET=wl-school-payroll
   
   # Payroll Configuration
   DEFAULT_CURRENCY=COP
   MINIMUM_WAGE=1300000
   TRANSPORT_ALLOWANCE=140606
   WORKING_HOURS_PER_DAY=8
   WORKING_DAYS_PER_MONTH=30
   
   # Tax Configuration
   INCOME_TAX_RATE=0.19
   HEALTH_CONTRIBUTION_RATE=0.04
   PENSION_CONTRIBUTION_RATE=0.04
   UNEMPLOYMENT_FUND_RATE=0.0833
   SEVERANCE_RATE=0.0833
   VACATION_RATE=0.0417
   
   # External Services
   AUTH_SERVICE_URL=http://auth-service:8001
   FINANCIAL_SERVICE_URL=http://financial-service:8002
   NOTIFICATION_SERVICE_URL=http://notification-service:8003
   
   # Banking Integration
   BANK_API_URL=https://api.bank.com
   BANK_API_KEY=your_bank_api_key
   BANK_ACCOUNT_NUMBER=1234567890
   
   # Government Integration
   DIAN_API_URL=https://api.dian.gov.co
   DIAN_NIT=900123456
   DIAN_API_KEY=your_dian_key
   
   # Security
   PAYROLL_DATA_ENCRYPTION=true
   SALARY_AUDIT_ENABLED=true
   BACKUP_FREQUENCY=daily
   
   # PDF Configuration
   PDF_PAPER_SIZE=A4
   PDF_ORIENTATION=portrait
   PDF_MARGIN_TOP=15
   PDF_MARGIN_BOTTOM=15
   PDF_MARGIN_LEFT=15
   PDF_MARGIN_RIGHT=15
   ```

3. **Configurar Docker:**
   ```dockerfile
   # Dockerfile
   FROM php:8.2-fpm
   
   # Install dependencies
   RUN apt-get update && apt-get install -y \
       git \
       curl \
       libpng-dev \
       libonig-dev \
       libxml2-dev \
       libzip-dev \
       zip \
       unzip \
       wkhtmltopdf \
       supervisor \
       cron
   
   # Install PHP extensions
   RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip
   
   # Install Composer
   COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
   
   # Set working directory
   WORKDIR /var/www
   
   # Copy application
   COPY . /var/www
   
   # Install dependencies
   RUN composer install --no-dev --optimize-autoloader
   
   # Set permissions
   RUN chown -R www-data:www-data /var/www
   RUN chmod -R 755 /var/www/storage
   RUN chmod -R 755 /var/www/bootstrap/cache
   
   # Create directories for payroll files
   RUN mkdir -p /var/www/storage/app/payroll/receipts
   RUN mkdir -p /var/www/storage/app/payroll/reports
   RUN mkdir -p /var/www/storage/app/payroll/backups
   
   # Configure supervisor
   COPY docker/supervisor/laravel-worker.conf /etc/supervisor/conf.d/
   
   # Configure cron for payroll automation
   COPY docker/cron/payroll-cron /etc/cron.d/payroll-cron
   RUN chmod 0644 /etc/cron.d/payroll-cron
   RUN crontab /etc/cron.d/payroll-cron
   
   EXPOSE 9000
   CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
   ```

#### Criterios de Aceptación:
- [ ] Microservicio configurado y funcionando
- [ ] Docker container operativo
- [ ] Variables de entorno configuradas
- [ ] Dependencias de nómina instaladas

---

### 2. Modelos y Migraciones Base

**Responsable:** Backend Developer  
**Estimación:** 2 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear migración Employees:**
   ```php
   // Migration: create_employees_table
   Schema::create('employees', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('school_id');
       $table->unsignedBigInteger('user_id')->nullable(); // Si tiene cuenta de usuario
       
       // Información personal
       $table->string('employee_code')->unique();
       $table->string('identification_type'); // CC, CE, PP, etc.
       $table->string('identification_number')->unique();
       $table->string('first_name');
       $table->string('last_name');
       $table->string('email')->unique();
       $table->string('phone')->nullable();
       $table->date('birth_date');
       $table->enum('gender', ['male', 'female', 'other']);
       $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed']);
       
       // Dirección
       $table->text('address');
       $table->string('city');
       $table->string('state');
       $table->string('postal_code')->nullable();
       $table->string('country')->default('Colombia');
       
       // Información laboral
       $table->string('position'); // Cargo
       $table->string('department'); // Departamento
       $table->enum('employment_type', ['full_time', 'part_time', 'contractor', 'intern']);
       $table->enum('contract_type', ['indefinite', 'fixed_term', 'apprenticeship', 'services']);
       $table->date('hire_date');
       $table->date('contract_start_date');
       $table->date('contract_end_date')->nullable();
       $table->date('termination_date')->nullable();
       $table->string('termination_reason')->nullable();
       
       // Información salarial
       $table->decimal('base_salary', 12, 2); // Salario base
       $table->enum('salary_type', ['monthly', 'hourly', 'daily']);
       $table->decimal('hourly_rate', 8, 2)->nullable();
       $table->integer('weekly_hours')->default(48);
       $table->boolean('receives_transport_allowance')->default(true);
       $table->decimal('additional_transport', 10, 2)->default(0);
       
       // Información bancaria
       $table->string('bank_name')->nullable();
       $table->string('bank_account_type')->nullable(); // savings, checking
       $table->string('bank_account_number')->nullable();
       
       // Seguridad social
       $table->string('eps_name')->nullable(); // EPS (Salud)
       $table->string('pension_fund')->nullable(); // Fondo de pensiones
       $table->string('arl_name')->nullable(); // ARL (Riesgos laborales)
       $table->string('compensation_fund')->nullable(); // Caja de compensación
       
       // Estado del empleado
       $table->enum('status', ['active', 'inactive', 'suspended', 'terminated'])->default('active');
       $table->boolean('is_payroll_active')->default(true);
       $table->text('notes')->nullable();
       
       // Información fiscal
       $table->boolean('tax_exempt')->default(false);
       $table->decimal('tax_exemption_amount', 10, 2)->default(0);
       $table->json('tax_dependents')->nullable(); // Personas a cargo
       
       // Archivos adjuntos
       $table->json('documents')->nullable(); // Documentos del empleado
       $table->string('photo_path')->nullable();
       
       $table->unsignedBigInteger('created_by');
       $table->unsignedBigInteger('updated_by')->nullable();
       $table->timestamps();
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('user_id')->references('id')->on('users');
       $table->foreign('created_by')->references('id')->on('users');
       $table->foreign('updated_by')->references('id')->on('users');
       
       $table->index(['school_id', 'status']);
       $table->index(['employee_code']);
       $table->index(['identification_number']);
       $table->index(['hire_date']);
       $table->index(['is_payroll_active']);
   });
   ```

2. **Crear migración PayrollPeriods:**
   ```php
   // Migration: create_payroll_periods_table
   Schema::create('payroll_periods', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('school_id');
       
       // Información del período
       $table->string('period_code')->unique(); // 2024-01, 2024-02, etc.
       $table->string('period_name'); // Enero 2024, Febrero 2024, etc.
       $table->enum('period_type', ['monthly', 'biweekly', 'weekly']);
       $table->date('start_date');
       $table->date('end_date');
       $table->date('payment_date');
       
       // Estado del período
       $table->enum('status', ['draft', 'calculated', 'approved', 'paid', 'closed'])->default('draft');
       $table->boolean('is_extraordinary')->default(false); // Nómina extraordinaria
       $table->text('description')->nullable();
       
       // Totales del período
       $table->integer('total_employees')->default(0);
       $table->decimal('total_gross_salary', 15, 2)->default(0);
       $table->decimal('total_deductions', 15, 2)->default(0);
       $table->decimal('total_net_salary', 15, 2)->default(0);
       $table->decimal('total_employer_contributions', 15, 2)->default(0);
       
       // Fechas importantes
       $table->timestamp('calculated_at')->nullable();
       $table->timestamp('approved_at')->nullable();
       $table->timestamp('paid_at')->nullable();
       $table->timestamp('closed_at')->nullable();
       
       // Usuarios responsables
       $table->unsignedBigInteger('calculated_by')->nullable();
       $table->unsignedBigInteger('approved_by')->nullable();
       $table->unsignedBigInteger('paid_by')->nullable();
       
       $table->unsignedBigInteger('created_by');
       $table->unsignedBigInteger('updated_by')->nullable();
       $table->timestamps();
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('calculated_by')->references('id')->on('users');
       $table->foreign('approved_by')->references('id')->on('users');
       $table->foreign('paid_by')->references('id')->on('users');
       $table->foreign('created_by')->references('id')->on('users');
       $table->foreign('updated_by')->references('id')->on('users');
       
       $table->index(['school_id', 'period_code']);
       $table->index(['status', 'payment_date']);
       $table->index(['start_date', 'end_date']);
   });
   ```

3. **Crear migración PayrollEntries:**
   ```php
   // Migration: create_payroll_entries_table
   Schema::create('payroll_entries', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('payroll_period_id');
       $table->unsignedBigInteger('employee_id');
       $table->unsignedBigInteger('school_id');
       
       // Información básica
       $table->string('entry_number')->unique(); // Número de recibo
       $table->date('calculation_date');
       $table->integer('worked_days');
       $table->decimal('worked_hours', 8, 2)->default(0);
       $table->integer('overtime_hours')->default(0);
       $table->integer('night_hours')->default(0);
       $table->integer('holiday_hours')->default(0);
       
       // Salarios y devengos
       $table->decimal('base_salary', 12, 2);
       $table->decimal('transport_allowance', 10, 2)->default(0);
       $table->decimal('overtime_pay', 10, 2)->default(0);
       $table->decimal('night_pay', 10, 2)->default(0);
       $table->decimal('holiday_pay', 10, 2)->default(0);
       $table->decimal('commission', 10, 2)->default(0);
       $table->decimal('bonuses', 10, 2)->default(0);
       $table->decimal('other_earnings', 10, 2)->default(0);
       $table->decimal('total_earnings', 12, 2);
       
       // Deducciones
       $table->decimal('health_contribution', 10, 2)->default(0);
       $table->decimal('pension_contribution', 10, 2)->default(0);
       $table->decimal('income_tax', 10, 2)->default(0);
       $table->decimal('loan_deductions', 10, 2)->default(0);
       $table->decimal('advance_deductions', 10, 2)->default(0);
       $table->decimal('other_deductions', 10, 2)->default(0);
       $table->decimal('total_deductions', 12, 2);
       
       // Aportes patronales
       $table->decimal('employer_health', 10, 2)->default(0);
       $table->decimal('employer_pension', 10, 2)->default(0);
       $table->decimal('employer_arl', 10, 2)->default(0);
       $table->decimal('employer_compensation_fund', 10, 2)->default(0);
       $table->decimal('employer_icbf', 10, 2)->default(0);
       $table->decimal('employer_sena', 10, 2)->default(0);
       $table->decimal('total_employer_contributions', 12, 2);
       
       // Prestaciones sociales
       $table->decimal('severance', 10, 2)->default(0);
       $table->decimal('severance_interest', 10, 2)->default(0);
       $table->decimal('vacation', 10, 2)->default(0);
       $table->decimal('christmas_bonus', 10, 2)->default(0);
       $table->decimal('total_benefits', 12, 2);
       
       // Totales
       $table->decimal('gross_salary', 12, 2);
       $table->decimal('net_salary', 12, 2);
       $table->decimal('total_cost', 12, 2); // Costo total para la empresa
       
       // Estado y archivos
       $table->enum('status', ['draft', 'calculated', 'approved', 'paid'])->default('draft');
       $table->string('receipt_path')->nullable(); // Ruta del recibo PDF
       $table->json('calculation_details')->nullable(); // Detalles del cálculo
       $table->text('notes')->nullable();
       
       // Fechas de pago
       $table->date('payment_date')->nullable();
       $table->string('payment_method')->nullable(); // transfer, cash, check
       $table->string('payment_reference')->nullable();
       $table->boolean('payment_confirmed')->default(false);
       
       $table->unsignedBigInteger('calculated_by')->nullable();
       $table->unsignedBigInteger('approved_by')->nullable();
       $table->timestamps();
       
       $table->foreign('payroll_period_id')->references('id')->on('payroll_periods')->onDelete('cascade');
       $table->foreign('employee_id')->references('id')->on('employees');
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('calculated_by')->references('id')->on('users');
       $table->foreign('approved_by')->references('id')->on('users');
       
       $table->index(['payroll_period_id', 'employee_id']);
       $table->index(['school_id', 'calculation_date']);
       $table->index(['status', 'payment_date']);
       $table->index(['employee_id', 'calculation_date']);
   });
   ```

4. **Crear migración PayrollAdjustments:**
   ```php
   // Migration: create_payroll_adjustments_table
   Schema::create('payroll_adjustments', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('employee_id');
       $table->unsignedBigInteger('payroll_period_id')->nullable();
       $table->unsignedBigInteger('school_id');
       
       // Información del ajuste
       $table->string('adjustment_code')->unique();
       $table->enum('adjustment_type', ['bonus', 'deduction', 'correction', 'advance', 'loan']);
       $table->enum('frequency', ['one_time', 'recurring', 'until_date']);
       $table->string('concept'); // Concepto del ajuste
       $table->text('description');
       
       // Valores
       $table->decimal('amount', 12, 2);
       $table->boolean('affects_base_salary')->default(false);
       $table->boolean('affects_benefits')->default(false);
       $table->boolean('taxable')->default(true);
       
       // Fechas de aplicación
       $table->date('start_date');
       $table->date('end_date')->nullable();
       $table->integer('installments')->default(1);
       $table->integer('installments_paid')->default(0);
       
       // Estado
       $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
       $table->text('cancellation_reason')->nullable();
       
       // Autorización
       $table->boolean('requires_approval')->default(true);
       $table->boolean('approved')->default(false);
       $table->date('approved_date')->nullable();
       $table->unsignedBigInteger('approved_by')->nullable();
       
       $table->unsignedBigInteger('created_by');
       $table->unsignedBigInteger('updated_by')->nullable();
       $table->timestamps();
       
       $table->foreign('employee_id')->references('id')->on('employees');
       $table->foreign('payroll_period_id')->references('id')->on('payroll_periods');
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('approved_by')->references('id')->on('users');
       $table->foreign('created_by')->references('id')->on('users');
       $table->foreign('updated_by')->references('id')->on('users');
       
       $table->index(['employee_id', 'status']);
       $table->index(['school_id', 'adjustment_type']);
       $table->index(['start_date', 'end_date']);
       $table->index(['frequency', 'status']);
   });
   ```

5. **Crear migración PayrollReports:**
   ```php
   // Migration: create_payroll_reports_table
   Schema::create('payroll_reports', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('school_id');
       $table->unsignedBigInteger('payroll_period_id')->nullable();
       
       // Información del reporte
       $table->string('report_code')->unique();
       $table->enum('report_type', [
           'payroll_summary', 'tax_report', 'social_security',
           'bank_transfer', 'cost_center', 'employee_summary'
       ]);
       $table->string('report_name');
       $table->text('description')->nullable();
       
       // Período del reporte
       $table->date('period_start');
       $table->date('period_end');
       $table->integer('total_employees');
       
       // Totales del reporte
       $table->json('report_totals'); // Totales específicos del reporte
       $table->json('report_data'); // Datos del reporte
       
       // Archivos generados
       $table->string('pdf_path')->nullable();
       $table->string('excel_path')->nullable();
       $table->string('csv_path')->nullable();
       
       // Estado del reporte
       $table->enum('status', ['generating', 'completed', 'failed'])->default('generating');
       $table->timestamp('generated_at')->nullable();
       $table->text('error_message')->nullable();
       
       // Envío y distribución
       $table->boolean('auto_send')->default(false);
       $table->json('recipients')->nullable();
       $table->timestamp('sent_at')->nullable();
       
       $table->unsignedBigInteger('generated_by');
       $table->timestamps();
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('payroll_period_id')->references('id')->on('payroll_periods');
       $table->foreign('generated_by')->references('id')->on('users');
       
       $table->index(['school_id', 'report_type']);
       $table->index(['period_start', 'period_end']);
       $table->index(['status', 'generated_at']);
   });
   ```

#### Criterios de Aceptación:
- [ ] Migraciones ejecutadas correctamente
- [ ] Modelos implementados con relaciones
- [ ] Índices de base de datos optimizados
- [ ] Estructura de datos de nómina completa

---

### 3. Implementación de Modelos y Servicios

**Responsable:** Backend Developer Senior  
**Estimación:** 2 días  
**Prioridad:** Alta

#### Subtareas:

1. **Implementar modelo Employee:**
   ```php
   class Employee extends Model
   {
       use HasFactory, LogsActivity;
       
       protected $fillable = [
           'school_id', 'user_id', 'employee_code', 'identification_type',
           'identification_number', 'first_name', 'last_name', 'email',
           'phone', 'birth_date', 'gender', 'marital_status', 'address',
           'city', 'state', 'postal_code', 'country', 'position',
           'department', 'employment_type', 'contract_type', 'hire_date',
           'contract_start_date', 'contract_end_date', 'termination_date',
           'termination_reason', 'base_salary', 'salary_type', 'hourly_rate',
           'weekly_hours', 'receives_transport_allowance', 'additional_transport',
           'bank_name', 'bank_account_type', 'bank_account_number',
           'eps_name', 'pension_fund', 'arl_name', 'compensation_fund',
           'status', 'is_payroll_active', 'notes', 'tax_exempt',
           'tax_exemption_amount', 'tax_dependents', 'documents',
           'photo_path', 'created_by', 'updated_by'
       ];
       
       protected $casts = [
           'birth_date' => 'date',
           'hire_date' => 'date',
           'contract_start_date' => 'date',
           'contract_end_date' => 'date',
           'termination_date' => 'date',
           'base_salary' => 'decimal:2',
           'hourly_rate' => 'decimal:2',
           'additional_transport' => 'decimal:2',
           'tax_exemption_amount' => 'decimal:2',
           'receives_transport_allowance' => 'boolean',
           'is_payroll_active' => 'boolean',
           'tax_exempt' => 'boolean',
           'tax_dependents' => 'array',
           'documents' => 'array'
       ];
       
       // Relaciones
       public function school() {
           return $this->belongsTo(School::class);
       }
       
       public function user() {
           return $this->belongsTo(User::class);
       }
       
       public function payrollEntries() {
           return $this->hasMany(PayrollEntry::class);
       }
       
       public function adjustments() {
           return $this->hasMany(PayrollAdjustment::class);
       }
       
       public function creator() {
           return $this->belongsTo(User::class, 'created_by');
       }
       
       // Métodos auxiliares
       public function generateEmployeeCode() {
           $year = now()->year;
           $schoolCode = str_pad($this->school_id, 3, '0', STR_PAD_LEFT);
           $sequence = str_pad(
               Employee::where('school_id', $this->school_id)
                   ->whereYear('created_at', $year)
                   ->count() + 1,
               4, '0', STR_PAD_LEFT
           );
           
           return "EMP-{$year}-{$schoolCode}-{$sequence}";
       }
       
       public function getFullNameAttribute() {
           return "{$this->first_name} {$this->last_name}";
       }
       
       public function getAgeAttribute() {
           return $this->birth_date ? $this->birth_date->age : null;
       }
       
       public function getYearsOfServiceAttribute() {
           return $this->hire_date ? $this->hire_date->diffInYears(now()) : 0;
       }
       
       public function getMonthlyTransportAllowance() {
           if (!$this->receives_transport_allowance) {
               return 0;
           }
           
           $minimumWage = config('payroll.minimum_wage');
           $transportAllowance = config('payroll.transport_allowance');
           
           // Si gana más de 2 salarios mínimos, no recibe auxilio
           if ($this->base_salary > ($minimumWage * 2)) {
               return $this->additional_transport;
           }
           
           return $transportAllowance + $this->additional_transport;
       }
       
       public function calculateDailyWage() {
           return $this->base_salary / 30;
       }
       
       public function calculateHourlyWage() {
           if ($this->salary_type === 'hourly') {
               return $this->hourly_rate;
           }
           
           return $this->base_salary / (30 * 8); // 30 días, 8 horas por día
       }
       
       public function isActive() {
           return $this->status === 'active' && $this->is_payroll_active;
       }
       
       public function hasValidBankAccount() {
           return !empty($this->bank_name) && !empty($this->bank_account_number);
       }
       
       public function getActiveAdjustments($periodId = null) {
           $query = $this->adjustments()->where('status', 'active');
           
           if ($periodId) {
               $query->where(function($q) use ($periodId) {
                   $q->whereNull('payroll_period_id')
                     ->orWhere('payroll_period_id', $periodId);
               });
           }
           
           return $query->get();
       }
       
       // Scopes
       public function scopeActive($query) {
           return $query->where('status', 'active')
                       ->where('is_payroll_active', true);
       }
       
       public function scopeForSchool($query, $schoolId) {
           return $query->where('school_id', $schoolId);
       }
       
       public function scopeByDepartment($query, $department) {
           return $query->where('department', $department);
       }
       
       public function scopeByPosition($query, $position) {
           return $query->where('position', $position);
       }
   }
   ```

2. **Implementar PayrollCalculationService:**
   ```php
   class PayrollCalculationService
   {
       private $minimumWage;
       private $transportAllowance;
       private $taxRates;
       
       public function __construct()
       {
           $this->minimumWage = config('payroll.minimum_wage');
           $this->transportAllowance = config('payroll.transport_allowance');
           $this->taxRates = [
               'health' => config('payroll.health_contribution_rate'),
               'pension' => config('payroll.pension_contribution_rate'),
               'income_tax' => config('payroll.income_tax_rate'),
               'unemployment' => config('payroll.unemployment_fund_rate'),
               'severance' => config('payroll.severance_rate'),
               'vacation' => config('payroll.vacation_rate')
           ];
       }
       
       public function calculatePayrollEntry(Employee $employee, PayrollPeriod $period, array $workData = [])
       {
           DB::beginTransaction();
           
           try {
               // Datos de trabajo del período
               $workedDays = $workData['worked_days'] ?? $this->calculateWorkedDays($period);
               $workedHours = $workData['worked_hours'] ?? ($workedDays * 8);
               $overtimeHours = $workData['overtime_hours'] ?? 0;
               $nightHours = $workData['night_hours'] ?? 0;
               $holidayHours = $workData['holiday_hours'] ?? 0;
               
               // Calcular devengos
               $earnings = $this->calculateEarnings($employee, $workedDays, $workedHours, $overtimeHours, $nightHours, $holidayHours);
               
               // Calcular deducciones
               $deductions = $this->calculateDeductions($employee, $earnings['total_earnings']);
               
               // Calcular aportes patronales
               $employerContributions = $this->calculateEmployerContributions($employee, $earnings['base_salary']);
               
               // Calcular prestaciones sociales
               $benefits = $this->calculateBenefits($employee, $earnings['base_salary']);
               
               // Aplicar ajustes del empleado
               $adjustments = $this->applyAdjustments($employee, $period);
               
               // Crear entrada de nómina
               $payrollEntry = PayrollEntry::create([
                   'payroll_period_id' => $period->id,
                   'employee_id' => $employee->id,
                   'school_id' => $employee->school_id,
                   'entry_number' => $this->generateEntryNumber($period, $employee),
                   'calculation_date' => now()->toDateString(),
                   'worked_days' => $workedDays,
                   'worked_hours' => $workedHours,
                   'overtime_hours' => $overtimeHours,
                   'night_hours' => $nightHours,
                   'holiday_hours' => $holidayHours,
                   
                   // Devengos
                   'base_salary' => $earnings['base_salary'],
                   'transport_allowance' => $earnings['transport_allowance'],
                   'overtime_pay' => $earnings['overtime_pay'],
                   'night_pay' => $earnings['night_pay'],
                   'holiday_pay' => $earnings['holiday_pay'],
                   'commission' => $earnings['commission'],
                   'bonuses' => $earnings['bonuses'] + $adjustments['bonuses'],
                   'other_earnings' => $earnings['other_earnings'],
                   'total_earnings' => $earnings['total_earnings'] + $adjustments['bonuses'],
                   
                   // Deducciones
                   'health_contribution' => $deductions['health'],
                   'pension_contribution' => $deductions['pension'],
                   'income_tax' => $deductions['income_tax'],
                   'loan_deductions' => $adjustments['loan_deductions'],
                   'advance_deductions' => $adjustments['advance_deductions'],
                   'other_deductions' => $deductions['other'] + $adjustments['other_deductions'],
                   'total_deductions' => $deductions['total'] + $adjustments['total_deductions'],
                   
                   // Aportes patronales
                   'employer_health' => $employerContributions['health'],
                   'employer_pension' => $employerContributions['pension'],
                   'employer_arl' => $employerContributions['arl'],
                   'employer_compensation_fund' => $employerContributions['compensation_fund'],
                   'employer_icbf' => $employerContributions['icbf'],
                   'employer_sena' => $employerContributions['sena'],
                   'total_employer_contributions' => $employerContributions['total'],
                   
                   // Prestaciones sociales
                   'severance' => $benefits['severance'],
                   'severance_interest' => $benefits['severance_interest'],
                   'vacation' => $benefits['vacation'],
                   'christmas_bonus' => $benefits['christmas_bonus'],
                   'total_benefits' => $benefits['total'],
                   
                   // Totales
                   'gross_salary' => $earnings['total_earnings'] + $adjustments['bonuses'],
                   'net_salary' => ($earnings['total_earnings'] + $adjustments['bonuses']) - ($deductions['total'] + $adjustments['total_deductions']),
                   'total_cost' => ($earnings['total_earnings'] + $adjustments['bonuses']) + $employerContributions['total'] + $benefits['total'],
                   
                   'status' => 'calculated',
                   'calculation_details' => [
                       'earnings_breakdown' => $earnings,
                       'deductions_breakdown' => $deductions,
                       'employer_contributions_breakdown' => $employerContributions,
                       'benefits_breakdown' => $benefits,
                       'adjustments_applied' => $adjustments
                   ]
               ]);
               
               DB::commit();
               
               return $payrollEntry;
           } catch (\Exception $e) {
               DB::rollback();
               throw $e;
           }
       }
       
       private function calculateEarnings(Employee $employee, $workedDays, $workedHours, $overtimeHours, $nightHours, $holidayHours)
       {
           $baseSalary = ($employee->base_salary / 30) * $workedDays;
           $transportAllowance = $employee->getMonthlyTransportAllowance() * ($workedDays / 30);
           
           $hourlyRate = $employee->calculateHourlyWage();
           $overtimePay = $overtimeHours * ($hourlyRate * 1.25); // 25% recargo
           $nightPay = $nightHours * ($hourlyRate * 1.35); // 35% recargo nocturno
           $holidayPay = $holidayHours * ($hourlyRate * 1.75); // 75% recargo festivo
           
           $totalEarnings = $baseSalary + $transportAllowance + $overtimePay + $nightPay + $holidayPay;
           
           return [
               'base_salary' => $baseSalary,
               'transport_allowance' => $transportAllowance,
               'overtime_pay' => $overtimePay,
               'night_pay' => $nightPay,
               'holiday_pay' => $holidayPay,
               'commission' => 0, // Se puede calcular según reglas específicas
               'bonuses' => 0, // Se aplicará desde ajustes
               'other_earnings' => 0,
               'total_earnings' => $totalEarnings
           ];
       }
       
       private function calculateDeductions(Employee $employee, $totalEarnings)
       {
           // Base para cálculo de aportes (sin auxilio de transporte si es menor a 2 SMMLV)
           $contributionBase = $totalEarnings;
           if ($employee->base_salary <= ($this->minimumWage * 2)) {
               $contributionBase -= $employee->getMonthlyTransportAllowance();
           }
           
           $healthContribution = $contributionBase * $this->taxRates['health'];
           $pensionContribution = $contributionBase * $this->taxRates['pension'];
           
           // Cálculo de retención en la fuente
           $incomeTax = $this->calculateIncomeTax($employee, $contributionBase);
           
           $totalDeductions = $healthContribution + $pensionContribution + $incomeTax;
           
           return [
               'health' => $healthContribution,
               'pension' => $pensionContribution,
               'income_tax' => $incomeTax,
               'other' => 0,
               'total' => $totalDeductions
           ];
       }
       
       private function calculateEmployerContributions(Employee $employee, $baseSalary)
       {
           $health = $baseSalary * 0.085; // 8.5% salud
           $pension = $baseSalary * 0.12; // 12% pensión
           $arl = $baseSalary * 0.00522; // 0.522% ARL (promedio)
           $compensationFund = $baseSalary * 0.04; // 4% caja de compensación
           $icbf = $baseSalary * 0.03; // 3% ICBF
           $sena = $baseSalary * 0.02; // 2% SENA
           
           return [
               'health' => $health,
               'pension' => $pension,
               'arl' => $arl,
               'compensation_fund' => $compensationFund,
               'icbf' => $icbf,
               'sena' => $sena,
               'total' => $health + $pension + $arl + $compensationFund + $icbf + $sena
           ];
       }
       
       private function calculateBenefits(Employee $employee, $baseSalary)
       {
           $severance = $baseSalary * $this->taxRates['severance']; // 8.33%
           $severanceInterest = $severance * 0.12; // 12% sobre cesantías
           $vacation = $baseSalary * $this->taxRates['vacation']; // 4.17%
           $christmasBonus = $baseSalary * $this->taxRates['severance']; // 8.33%
           
           return [
               'severance' => $severance,
               'severance_interest' => $severanceInterest,
               'vacation' => $vacation,
               'christmas_bonus' => $christmasBonus,
               'total' => $severance + $severanceInterest + $vacation + $christmasBonus
           ];
       }
       
       private function calculateIncomeTax(Employee $employee, $contributionBase)
       {
           // Tabla de retención en la fuente simplificada
           // En implementación real, usar tabla oficial de la DIAN
           
           $monthlyIncome = $contributionBase;
           $annualIncome = $monthlyIncome * 12;
           
           // Exenciones
           $exemptions = $employee->tax_exemption_amount;
           $dependents = count($employee->tax_dependents ?? []);
           $dependentExemption = $dependents * ($this->minimumWage * 2.5); // Ejemplo
           
           $taxableIncome = max(0, $annualIncome - $exemptions - $dependentExemption);
           
           // Cálculo simplificado de retención
           if ($taxableIncome <= ($this->minimumWage * 12 * 4)) {
               return 0; // No retención para ingresos bajos
           }
           
           $annualTax = $taxableIncome * 0.19; // 19% ejemplo
           return $annualTax / 12; // Retención mensual
       }
       
       private function applyAdjustments(Employee $employee, PayrollPeriod $period)
       {
           $adjustments = $employee->getActiveAdjustments($period->id);
           
           $bonuses = 0;
           $loanDeductions = 0;
           $advanceDeductions = 0;
           $otherDeductions = 0;
           
           foreach ($adjustments as $adjustment) {
               switch ($adjustment->adjustment_type) {
                   case 'bonus':
                       $bonuses += $adjustment->amount;
                       break;
                   case 'loan':
                       $loanDeductions += $adjustment->amount;
                       break;
                   case 'advance':
                       $advanceDeductions += $adjustment->amount;
                       break;
                   case 'deduction':
                       $otherDeductions += $adjustment->amount;
                       break;
               }
               
               // Actualizar cuotas pagadas si es recurrente
               if ($adjustment->frequency === 'recurring') {
                   $adjustment->increment('installments_paid');
                   
                   if ($adjustment->installments_paid >= $adjustment->installments) {
                       $adjustment->update(['status' => 'completed']);
                   }
               }
           }
           
           return [
               'bonuses' => $bonuses,
               'loan_deductions' => $loanDeductions,
               'advance_deductions' => $advanceDeductions,
               'other_deductions' => $otherDeductions,
               'total_deductions' => $loanDeductions + $advanceDeductions + $otherDeductions
           ];
       }
       
       private function calculateWorkedDays(PayrollPeriod $period)
       {
           $startDate = Carbon::parse($period->start_date);
           $endDate = Carbon::parse($period->end_date);
           
           $totalDays = 0;
           $currentDate = $startDate->copy();
           
           while ($currentDate->lte($endDate)) {
               // Contar solo días laborables (lunes a sábado)
               if ($currentDate->dayOfWeek !== Carbon::SUNDAY) {
                   $totalDays++;
               }
               $currentDate->addDay();
           }
           
           return min($totalDays, 30); // Máximo 30 días para cálculo
       }
       
       private function generateEntryNumber(PayrollPeriod $period, Employee $employee)
       {
           $periodCode = $period->period_code;
           $employeeCode = str_pad($employee->id, 4, '0', STR_PAD_LEFT);
           
           return "PE-{$periodCode}-{$employeeCode}";
       }
   }
   ```

#### Criterios de Aceptación:
- [ ] Modelos implementados con todas las relaciones
- [ ] PayrollCalculationService funcionando correctamente
- [ ] Cálculos salariales precisos
- [ ] Aplicación de ajustes automática

---

## API Endpoints Implementados

### Employees
```
GET    /api/v1/employees
POST   /api/v1/employees
GET    /api/v1/employees/{id}
PUT    /api/v1/employees/{id}
DELETE /api/v1/employees/{id}
GET    /api/v1/employees/{id}/payroll-history
POST   /api/v1/employees/{id}/adjustments
```

### Payroll Periods
```
GET    /api/v1/payroll-periods
POST   /api/v1/payroll-periods
GET    /api/v1/payroll-periods/{id}
PUT    /api/v1/payroll-periods/{id}
POST   /api/v1/payroll-periods/{id}/calculate
POST   /api/v1/payroll-periods/{id}/approve
POST   /api/v1/payroll-periods/{id}/pay
GET    /api/v1/payroll-periods/{id}/summary
```

### Payroll Entries
```
GET    /api/v1/payroll-entries
GET    /api/v1/payroll-entries/{id}
PUT    /api/v1/payroll-entries/{id}
GET    /api/v1/payroll-entries/{id}/receipt
POST   /api/v1/payroll-entries/{id}/approve
POST   /api/v1/payroll-entries/bulk-approve
```

### Payroll Reports
```
GET    /api/v1/payroll-reports
POST   /api/v1/payroll-reports
GET    /api/v1/payroll-reports/{id}
GET    /api/v1/payroll-reports/{id}/download
GET    /api/v1/payroll-reports/tax-summary
GET    /api/v1/payroll-reports/bank-transfer
```

## Definición de Terminado (DoD)

### Criterios Técnicos:
- [ ] Microservicio de nómina funcionando
- [ ] Gestión de empleados completa
- [ ] Cálculos salariales automáticos
- [ ] Sistema de ajustes operativo
- [ ] Generación de recibos automática
- [ ] API REST completa y documentada

### Criterios de Calidad:
- [ ] Tests unitarios > 90% cobertura
- [ ] Tests de cálculos salariales
- [ ] Validación de datos fiscales
- [ ] Auditoría de cambios salariales
- [ ] Backup automático de datos

### Criterios de Negocio:
- [ ] Empleados registrándose correctamente
- [ ] Nóminas calculándose automáticamente
- [ ] Recibos generándose correctamente
- [ ] Reportes fiscales precisos
- [ ] Integración financiera funcionando

## Riesgos Identificados

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| Errores en cálculos | Media | Crítico | Tests exhaustivos, validaciones |
| Cumplimiento fiscal | Alta | Crítico | Consulta expertos, actualizaciones |
| Performance cálculos | Media | Alto | Optimización, cache, índices |
| Seguridad datos | Alta | Crítico | Encriptación, auditoría, backup |

## Métricas de Éxito

- **Calculation accuracy**: > 99.9% cálculos correctos
- **Processing time**: < 2 min por período completo
- **Report generation**: < 30 seg reportes
- **Data security**: 100% datos encriptados
- **Fiscal compliance**: 100% cumplimiento normativo

## Entregables

1. **Microservicio Payroll** - Servicio completo funcionando
2. **Gestión Empleados** - CRUD completo de empleados
3. **Motor Cálculos** - Sistema de cálculos automáticos
4. **Recibos de Pago** - Generación automática PDF
5. **Reportes Fiscales** - Reportes para gobierno
6. **Integración Financiera** - Conexión con servicio financiero

## Variables de Entorno

```env
# Payroll Configuration
DEFAULT_CURRENCY=COP
MINIMUM_WAGE=1300000
TRANSPORT_ALLOWANCE=140606

# Tax Configuration
INCOME_TAX_RATE=0.19
HEALTH_CONTRIBUTION_RATE=0.04
PENSION_CONTRIBUTION_RATE=0.04

# Security
PAYROLL_DATA_ENCRYPTION=true
SALARY_AUDIT_ENABLED=true
BACKUP_FREQUENCY=daily
```

## Datos de Prueba

### Empleados de Ejemplo:
```json
{
  "employees": [
    {
      "employee_code": "EMP-2024-001-0001",
      "identification_number": "12345678",
      "first_name": "Juan",
      "last_name": "Pérez",
      "position": "Entrenador Principal",
      "department": "Deportes",
      "base_salary": 2500000,
      "hire_date": "2024-01-15"
    },
    {
      "employee_code": "EMP-2024-001-0002",
      "identification_number": "87654321",
      "first_name": "María",
      "last_name": "González",
      "position": "Coordinadora Académica",
      "department": "Académico",
      "base_salary": 3000000,
      "hire_date": "2024-02-01"
    }
  ]
}
```

### Períodos de Nómina:
```json
{
  "payroll_periods": [
    {
      "period_code": "2024-03",
      "period_name": "Marzo 2024",
      "start_date": "2024-03-01",
      "end_date": "2024-03-31",
      "payment_date": "2024-04-05"
    }
  ]
}
```

## Preguntas para Retrospectiva

1. **¿Qué funcionó bien en este sprint?**
   - ¿Los cálculos salariales son precisos?
   - ¿La generación de recibos es eficiente?

2. **¿Qué obstáculos encontramos?**
   - ¿La complejidad fiscal fue manejable?
   - ¿Los requisitos legales fueron claros?

3. **¿Qué podemos mejorar?**
   - ¿Cómo optimizar los cálculos de nómina?
   - ¿El flujo de aprobación es eficiente?

4. **¿Qué aprendimos?**
   - ¿Qué mejores prácticas para datos salariales?
   - ¿Cómo mejorar la experiencia del usuario?

5. **¿Estamos listos para el siguiente sprint?**
   - ¿Todos los cálculos funcionan correctamente?
   - ¿Los reportes fiscales son precisos?
   - ¿La integración financiera está completa?