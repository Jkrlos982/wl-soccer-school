# Sprint 11: Reports & Dashboard Service (Servicio de Reportes y Dashboard)

**Duración:** 2 semanas  
**Fase:** 5 - Reportes y Dashboard  
**Objetivo:** Implementar sistema completo de reportes ejecutivos, dashboard interactivo y análisis de datos con visualizaciones avanzadas

## Resumen del Sprint

Este sprint implementa el microservicio de reportes y dashboard con generación automática de reportes ejecutivos, dashboard interactivo en tiempo real, análisis de datos avanzados, visualizaciones dinámicas y sistema de alertas inteligentes.

## Objetivos Específicos

- ✅ Implementar microservicio de reportes
- ✅ Crear dashboard ejecutivo interactivo
- ✅ Desarrollar sistema de análisis de datos
- ✅ Implementar visualizaciones avanzadas
- ✅ Crear sistema de alertas inteligentes
- ✅ Generar reportes automáticos programados

## Tareas Detalladas

### 1. Configuración Base del Microservicio

**Responsable:** Backend Developer Senior  
**Estimación:** 1 día  
**Prioridad:** Alta

#### Subtareas:

1. **Crear estructura del microservicio:**
   ```bash
   # Crear directorio del servicio
   mkdir wl-school-reports-service
   cd wl-school-reports-service
   
   # Inicializar Laravel
   composer create-project laravel/laravel . "10.*"
   
   # Instalar dependencias específicas
   composer require:
     - barryvdh/laravel-dompdf (PDF generation)
     - maatwebsite/excel (Excel export)
     - league/csv (CSV export)
     - spatie/laravel-permission (Permissions)
     - spatie/laravel-activitylog (Activity logging)
     - pusher/pusher-php-server (Real-time updates)
     - predis/predis (Redis cache)
     - laravel/horizon (Queue monitoring)
     - spatie/laravel-backup (Database backup)
     - intervention/image (Image processing)
     - nesbot/carbon (Date manipulation)
     - phpoffice/phpspreadsheet (Advanced Excel)
   ```

2. **Configurar variables de entorno:**
   ```env
   # .env
   APP_NAME="WL School Reports Service"
   APP_URL=http://localhost:8008
   
   # Database
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=wl_school_reports
   DB_USERNAME=root
   DB_PASSWORD=password
   
   # Queue
   QUEUE_CONNECTION=redis
   REDIS_HOST=redis
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   
   # Cache
   CACHE_DRIVER=redis
   SESSION_DRIVER=redis
   
   # Broadcasting (Real-time)
   BROADCAST_DRIVER=pusher
   PUSHER_APP_ID=your_pusher_app_id
   PUSHER_APP_KEY=your_pusher_key
   PUSHER_APP_SECRET=your_pusher_secret
   PUSHER_APP_CLUSTER=us2
   
   # File Storage
   FILESYSTEM_DISK=s3
   AWS_ACCESS_KEY_ID=your_aws_key
   AWS_SECRET_ACCESS_KEY=your_aws_secret
   AWS_DEFAULT_REGION=us-east-1
   AWS_BUCKET=wl-school-reports
   
   # External Services
   AUTH_SERVICE_URL=http://auth-service:8001
   FINANCIAL_SERVICE_URL=http://financial-service:8002
   SPORTS_SERVICE_URL=http://sports-service:8004
   MEDICAL_SERVICE_URL=http://medical-service:8006
   PAYROLL_SERVICE_URL=http://payroll-service:8007
   NOTIFICATION_SERVICE_URL=http://notification-service:8003
   CALENDAR_SERVICE_URL=http://calendar-service:8005
   
   # Report Configuration
   REPORTS_CACHE_TTL=3600
   DASHBOARD_REFRESH_INTERVAL=30
   MAX_REPORT_SIZE=50MB
   REPORT_RETENTION_DAYS=365
   
   # Analytics Configuration
   ANALYTICS_ENABLED=true
   ANALYTICS_SAMPLING_RATE=0.1
   PERFORMANCE_MONITORING=true
   
   # Chart Configuration
   CHART_DEFAULT_WIDTH=800
   CHART_DEFAULT_HEIGHT=400
   CHART_MAX_DATA_POINTS=1000
   
   # Alert Configuration
   ALERTS_ENABLED=true
   ALERT_CHECK_INTERVAL=300
   ALERT_RETENTION_DAYS=90
   
   # PDF Configuration
   PDF_PAPER_SIZE=A4
   PDF_ORIENTATION=portrait
   PDF_DPI=150
   PDF_COMPRESSION=true
   
   # Excel Configuration
   EXCEL_DEFAULT_FORMAT=xlsx
   EXCEL_MEMORY_LIMIT=512M
   EXCEL_CALCULATION_ENGINE=true
   
   # Security
   REPORT_DATA_ENCRYPTION=true
   SENSITIVE_DATA_MASKING=true
   AUDIT_REPORT_ACCESS=true
   
   # Performance
   QUERY_CACHE_ENABLED=true
   QUERY_TIMEOUT=30
   PARALLEL_PROCESSING=true
   MAX_CONCURRENT_REPORTS=5
   ```

3. **Configurar Docker:**
   ```dockerfile
   # Dockerfile
   FROM php:8.2-fpm
   
   # Install system dependencies
   RUN apt-get update && apt-get install -y \
       git \
       curl \
       libpng-dev \
       libonig-dev \
       libxml2-dev \
       libzip-dev \
       libfreetype6-dev \
       libjpeg62-turbo-dev \
       zip \
       unzip \
       wkhtmltopdf \
       supervisor \
       cron \
       nodejs \
       npm
   
   # Install PHP extensions
   RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
       && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip
   
   # Install Redis extension
   RUN pecl install redis && docker-php-ext-enable redis
   
   # Install Composer
   COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
   
   # Set working directory
   WORKDIR /var/www
   
   # Copy application
   COPY . /var/www
   
   # Install PHP dependencies
   RUN composer install --no-dev --optimize-autoloader
   
   # Install Node.js dependencies for chart generation
   COPY package*.json ./
   RUN npm install
   
   # Set permissions
   RUN chown -R www-data:www-data /var/www
   RUN chmod -R 755 /var/www/storage
   RUN chmod -R 755 /var/www/bootstrap/cache
   
   # Create directories for reports
   RUN mkdir -p /var/www/storage/app/reports/generated
   RUN mkdir -p /var/www/storage/app/reports/templates
   RUN mkdir -p /var/www/storage/app/reports/cache
   RUN mkdir -p /var/www/storage/app/charts
   
   # Configure supervisor
   COPY docker/supervisor/laravel-worker.conf /etc/supervisor/conf.d/
   COPY docker/supervisor/horizon.conf /etc/supervisor/conf.d/
   
   # Configure cron for scheduled reports
   COPY docker/cron/reports-cron /etc/cron.d/reports-cron
   RUN chmod 0644 /etc/cron.d/reports-cron
   RUN crontab /etc/cron.d/reports-cron
   
   EXPOSE 9000
   CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
   ```

#### Criterios de Aceptación:
- [ ] Microservicio configurado y funcionando
- [ ] Docker container operativo
- [ ] Variables de entorno configuradas
- [ ] Dependencias de reportes instaladas

---

### 2. Modelos y Migraciones Base

**Responsable:** Backend Developer  
**Estimación:** 2 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear migración ReportTemplates:**
   ```php
   // Migration: create_report_templates_table
   Schema::create('report_templates', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('school_id');
       
       // Información básica del template
       $table->string('template_code')->unique();
       $table->string('name');
       $table->text('description')->nullable();
       $table->enum('category', [
           'financial', 'sports', 'medical', 'payroll', 
           'academic', 'administrative', 'executive'
       ]);
       $table->enum('type', [
           'summary', 'detailed', 'analytical', 'comparative',
           'trend', 'dashboard', 'kpi', 'custom'
       ]);
       
       // Configuración del reporte
       $table->json('data_sources'); // Servicios y tablas de datos
       $table->json('fields_config'); // Campos a incluir
       $table->json('filters_config'); // Filtros disponibles
       $table->json('grouping_config')->nullable(); // Agrupaciones
       $table->json('sorting_config')->nullable(); // Ordenamiento
       $table->json('calculations_config')->nullable(); // Cálculos y fórmulas
       
       // Configuración visual
       $table->json('chart_config')->nullable(); // Configuración de gráficos
       $table->json('layout_config'); // Layout del reporte
       $table->json('styling_config')->nullable(); // Estilos personalizados
       
       // Configuración de exportación
       $table->json('export_formats'); // PDF, Excel, CSV, etc.
       $table->json('pdf_config')->nullable(); // Configuración PDF
       $table->json('excel_config')->nullable(); // Configuración Excel
       
       // Programación automática
       $table->boolean('is_scheduled')->default(false);
       $table->string('schedule_frequency')->nullable(); // daily, weekly, monthly
       $table->json('schedule_config')->nullable(); // Configuración de programación
       $table->json('recipients')->nullable(); // Destinatarios automáticos
       
       // Permisos y acceso
       $table->enum('visibility', ['public', 'private', 'role_based']);
       $table->json('allowed_roles')->nullable();
       $table->json('allowed_users')->nullable();
       
       // Estado y versioning
       $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
       $table->string('version')->default('1.0');
       $table->boolean('is_default')->default(false);
       $table->text('changelog')->nullable();
       
       // Métricas de uso
       $table->integer('usage_count')->default(0);
       $table->timestamp('last_used_at')->nullable();
       $table->decimal('avg_generation_time', 8, 2)->nullable();
       
       $table->unsignedBigInteger('created_by');
       $table->unsignedBigInteger('updated_by')->nullable();
       $table->timestamps();
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('created_by')->references('id')->on('users');
       $table->foreign('updated_by')->references('id')->on('users');
       
       $table->index(['school_id', 'category']);
       $table->index(['status', 'is_scheduled']);
       $table->index(['template_code']);
       $table->index(['visibility', 'status']);
   });
   ```

2. **Crear migración GeneratedReports:**
   ```php
   // Migration: create_generated_reports_table
   Schema::create('generated_reports', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('report_template_id');
       $table->unsignedBigInteger('school_id');
       
       // Información básica
       $table->string('report_code')->unique();
       $table->string('name');
       $table->text('description')->nullable();
       
       // Parámetros de generación
       $table->json('generation_params'); // Filtros, fechas, etc.
       $table->date('period_start')->nullable();
       $table->date('period_end')->nullable();
       $table->json('applied_filters')->nullable();
       
       // Datos del reporte
       $table->longText('report_data'); // Datos JSON del reporte
       $table->json('summary_data')->nullable(); // Resumen ejecutivo
       $table->json('chart_data')->nullable(); // Datos para gráficos
       $table->json('kpi_data')->nullable(); // KPIs calculados
       
       // Archivos generados
       $table->string('pdf_path')->nullable();
       $table->string('excel_path')->nullable();
       $table->string('csv_path')->nullable();
       $table->json('chart_images')->nullable(); // Rutas de imágenes de gráficos
       
       // Métricas de generación
       $table->decimal('generation_time', 8, 2); // Tiempo en segundos
       $table->integer('data_rows_count')->default(0);
       $table->decimal('file_size_mb', 8, 2)->nullable();
       $table->json('performance_metrics')->nullable();
       
       // Estado y distribución
       $table->enum('status', ['generating', 'completed', 'failed', 'expired'])->default('generating');
       $table->text('error_message')->nullable();
       $table->boolean('is_scheduled')->default(false);
       $table->boolean('auto_distributed')->default(false);
       
       // Fechas importantes
       $table->timestamp('generated_at')->nullable();
       $table->timestamp('expires_at')->nullable();
       $table->timestamp('last_accessed_at')->nullable();
       
       // Distribución y notificaciones
       $table->json('distributed_to')->nullable(); // Usuarios notificados
       $table->timestamp('distributed_at')->nullable();
       $table->integer('download_count')->default(0);
       $table->integer('view_count')->default(0);
       
       $table->unsignedBigInteger('generated_by');
       $table->timestamps();
       
       $table->foreign('report_template_id')->references('id')->on('report_templates');
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('generated_by')->references('id')->on('users');
       
       $table->index(['school_id', 'status']);
       $table->index(['report_template_id', 'generated_at']);
       $table->index(['period_start', 'period_end']);
       $table->index(['is_scheduled', 'status']);
       $table->index(['expires_at']);
   });
   ```

3. **Crear migración DashboardWidgets:**
   ```php
   // Migration: create_dashboard_widgets_table
   Schema::create('dashboard_widgets', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('school_id');
       $table->unsignedBigInteger('dashboard_id')->nullable();
       
       // Información básica del widget
       $table->string('widget_code')->unique();
       $table->string('title');
       $table->text('description')->nullable();
       $table->enum('widget_type', [
           'kpi', 'chart', 'table', 'metric', 'progress',
           'gauge', 'map', 'calendar', 'list', 'custom'
       ]);
       
       // Configuración de datos
       $table->string('data_source'); // Servicio o endpoint
       $table->json('data_config'); // Configuración de datos
       $table->json('query_params')->nullable(); // Parámetros de consulta
       $table->integer('refresh_interval')->default(300); // Segundos
       $table->boolean('real_time')->default(false);
       
       // Configuración visual
       $table->json('chart_config')->nullable(); // Configuración de gráfico
       $table->json('styling_config')->nullable(); // Estilos
       $table->json('layout_config'); // Posición y tamaño
       $table->string('color_scheme')->default('default');
       
       // Configuración de interacción
       $table->boolean('is_interactive')->default(true);
       $table->json('drill_down_config')->nullable(); // Configuración drill-down
       $table->json('filter_config')->nullable(); // Filtros del widget
       $table->boolean('exportable')->default(true);
       
       // Alertas y notificaciones
       $table->boolean('has_alerts')->default(false);
       $table->json('alert_rules')->nullable(); // Reglas de alerta
       $table->json('alert_recipients')->nullable();
       
       // Permisos
       $table->enum('visibility', ['public', 'private', 'role_based']);
       $table->json('allowed_roles')->nullable();
       $table->json('allowed_users')->nullable();
       
       // Estado y orden
       $table->boolean('is_active')->default(true);
       $table->integer('sort_order')->default(0);
       $table->json('cache_config')->nullable();
       
       // Métricas de uso
       $table->integer('view_count')->default(0);
       $table->timestamp('last_viewed_at')->nullable();
       $table->decimal('avg_load_time', 8, 2)->nullable();
       
       $table->unsignedBigInteger('created_by');
       $table->unsignedBigInteger('updated_by')->nullable();
       $table->timestamps();
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('created_by')->references('id')->on('users');
       $table->foreign('updated_by')->references('id')->on('users');
       
       $table->index(['school_id', 'widget_type']);
       $table->index(['dashboard_id', 'sort_order']);
       $table->index(['is_active', 'visibility']);
       $table->index(['real_time', 'refresh_interval']);
   });
   ```

4. **Crear migración ReportAlerts:**
   ```php
   // Migration: create_report_alerts_table
   Schema::create('report_alerts', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('school_id');
       $table->unsignedBigInteger('widget_id')->nullable();
       $table->unsignedBigInteger('report_template_id')->nullable();
       
       // Información básica
       $table->string('alert_code')->unique();
       $table->string('name');
       $table->text('description');
       $table->enum('alert_type', [
           'threshold', 'trend', 'anomaly', 'comparison',
           'schedule', 'data_quality', 'performance'
       ]);
       
       // Configuración de la regla
       $table->string('metric_field'); // Campo a monitorear
       $table->enum('condition', ['>', '<', '>=', '<=', '=', '!=', 'between', 'not_between']);
       $table->json('threshold_values'); // Valores de umbral
       $table->enum('severity', ['low', 'medium', 'high', 'critical']);
       
       // Configuración de evaluación
       $table->integer('evaluation_interval')->default(300); // Segundos
       $table->integer('evaluation_window')->default(3600); // Ventana de tiempo
       $table->boolean('require_consecutive')->default(false);
       $table->integer('consecutive_count')->default(1);
       
       // Configuración de notificación
       $table->json('notification_channels'); // email, sms, push, webhook
       $table->json('recipients'); // Destinatarios
       $table->boolean('escalation_enabled')->default(false);
       $table->json('escalation_rules')->nullable();
       
       // Estado y control
       $table->boolean('is_active')->default(true);
       $table->timestamp('last_evaluated_at')->nullable();
       $table->timestamp('last_triggered_at')->nullable();
       $table->integer('trigger_count')->default(0);
       $table->boolean('is_suppressed')->default(false);
       $table->timestamp('suppressed_until')->nullable();
       
       // Datos de la última evaluación
       $table->json('last_evaluation_data')->nullable();
       $table->decimal('last_metric_value', 15, 4)->nullable();
       $table->enum('last_status', ['ok', 'warning', 'critical'])->default('ok');
       
       $table->unsignedBigInteger('created_by');
       $table->unsignedBigInteger('updated_by')->nullable();
       $table->timestamps();
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('widget_id')->references('id')->on('dashboard_widgets');
       $table->foreign('report_template_id')->references('id')->on('report_templates');
       $table->foreign('created_by')->references('id')->on('users');
       $table->foreign('updated_by')->references('id')->on('users');
       
       $table->index(['school_id', 'alert_type']);
       $table->index(['is_active', 'last_evaluated_at']);
       $table->index(['severity', 'last_status']);
       $table->index(['last_triggered_at']);
   });
   ```

5. **Crear migración ReportSchedules:**
   ```php
   // Migration: create_report_schedules_table
   Schema::create('report_schedules', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('report_template_id');
       $table->unsignedBigInteger('school_id');
       
       // Información básica
       $table->string('schedule_code')->unique();
       $table->string('name');
       $table->text('description')->nullable();
       
       // Configuración de programación
       $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom']);
       $table->string('cron_expression')->nullable(); // Para frecuencias custom
       $table->time('execution_time')->default('08:00:00');
       $table->json('execution_days')->nullable(); // Días específicos
       $table->string('timezone')->default('America/Bogota');
       
       // Parámetros de generación
       $table->json('default_params'); // Parámetros por defecto
       $table->enum('period_type', ['fixed', 'relative', 'custom']);
       $table->json('period_config'); // Configuración del período
       
       // Distribución automática
       $table->boolean('auto_distribute')->default(true);
       $table->json('distribution_list'); // Lista de destinatarios
       $table->json('distribution_channels'); // email, notification, etc.
       $table->string('email_subject_template')->nullable();
       $table->text('email_body_template')->nullable();
       
       // Control de ejecución
       $table->boolean('is_active')->default(true);
       $table->timestamp('next_execution')->nullable();
       $table->timestamp('last_execution')->nullable();
       $table->integer('execution_count')->default(0);
       $table->integer('success_count')->default(0);
       $table->integer('failure_count')->default(0);
       
       // Configuración de retención
       $table->integer('retention_days')->default(90);
       $table->boolean('auto_cleanup')->default(true);
       
       // Estado de la última ejecución
       $table->enum('last_status', ['success', 'failed', 'partial'])->nullable();
       $table->text('last_error_message')->nullable();
       $table->decimal('last_execution_time', 8, 2)->nullable();
       
       $table->unsignedBigInteger('created_by');
       $table->unsignedBigInteger('updated_by')->nullable();
       $table->timestamps();
       
       $table->foreign('report_template_id')->references('id')->on('report_templates');
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('created_by')->references('id')->on('users');
       $table->foreign('updated_by')->references('id')->on('users');
       
       $table->index(['school_id', 'frequency']);
       $table->index(['is_active', 'next_execution']);
       $table->index(['report_template_id', 'is_active']);
   });
   ```

#### Criterios de Aceptación:
- [ ] Migraciones ejecutadas correctamente
- [ ] Modelos implementados con relaciones
- [ ] Índices de base de datos optimizados
- [ ] Estructura de datos de reportes completa

---

### 3. Implementación de Servicios Core

**Responsable:** Backend Developer Senior  
**Estimación:** 3 días  
**Prioridad:** Alta

#### Subtareas:

1. **Implementar ReportGenerationService:**
   ```php
   class ReportGenerationService
   {
       private $dataAggregationService;
       private $chartGenerationService;
       private $pdfGenerationService;
       private $excelGenerationService;
       
       public function __construct(
           DataAggregationService $dataAggregationService,
           ChartGenerationService $chartGenerationService,
           PdfGenerationService $pdfGenerationService,
           ExcelGenerationService $excelGenerationService
       ) {
           $this->dataAggregationService = $dataAggregationService;
           $this->chartGenerationService = $chartGenerationService;
           $this->pdfGenerationService = $pdfGenerationService;
           $this->excelGenerationService = $excelGenerationService;
       }
       
       public function generateReport(ReportTemplate $template, array $params = [])
       {
           $startTime = microtime(true);
           
           DB::beginTransaction();
           
           try {
               // Crear registro del reporte
               $generatedReport = GeneratedReport::create([
                   'report_template_id' => $template->id,
                   'school_id' => $template->school_id,
                   'report_code' => $this->generateReportCode($template),
                   'name' => $this->buildReportName($template, $params),
                   'description' => $template->description,
                   'generation_params' => $params,
                   'period_start' => $params['period_start'] ?? null,
                   'period_end' => $params['period_end'] ?? null,
                   'applied_filters' => $params['filters'] ?? null,
                   'status' => 'generating',
                   'generated_by' => auth()->id()
               ]);
               
               // Agregar datos
               $reportData = $this->dataAggregationService->aggregateData(
                   $template->data_sources,
                   $params,
                   $template->fields_config,
                   $template->filters_config
               );
               
               // Calcular KPIs
               $kpiData = $this->calculateKPIs($reportData, $template->calculations_config);
               
               // Generar datos de resumen
               $summaryData = $this->generateSummaryData($reportData, $kpiData);
               
               // Generar gráficos si están configurados
               $chartData = null;
               $chartImages = [];
               if ($template->chart_config) {
                   $chartData = $this->chartGenerationService->prepareChartData(
                       $reportData, 
                       $template->chart_config
                   );
                   
                   $chartImages = $this->chartGenerationService->generateChartImages(
                       $chartData,
                       $generatedReport->id
                   );
               }
               
               // Actualizar reporte con datos
               $generatedReport->update([
                   'report_data' => json_encode($reportData),
                   'summary_data' => $summaryData,
                   'chart_data' => $chartData,
                   'kpi_data' => $kpiData,
                   'chart_images' => $chartImages,
                   'data_rows_count' => count($reportData['rows'] ?? []),
                   'status' => 'completed',
                   'generated_at' => now()
               ]);
               
               // Generar archivos de exportación
               $this->generateExportFiles($generatedReport, $template);
               
               // Calcular métricas de rendimiento
               $generationTime = microtime(true) - $startTime;
               $generatedReport->update([
                   'generation_time' => $generationTime,
                   'performance_metrics' => [
                       'data_aggregation_time' => $this->dataAggregationService->getLastExecutionTime(),
                       'chart_generation_time' => $this->chartGenerationService->getLastExecutionTime(),
                       'total_generation_time' => $generationTime,
                       'memory_usage' => memory_get_peak_usage(true),
                       'query_count' => DB::getQueryLog() ? count(DB::getQueryLog()) : 0
                   ]
               ]);
               
               // Actualizar métricas del template
               $template->increment('usage_count');
               $template->update([
                   'last_used_at' => now(),
                   'avg_generation_time' => $template->generated_reports()
                       ->avg('generation_time')
               ]);
               
               DB::commit();
               
               // Enviar notificaciones si está configurado
               if ($template->is_scheduled) {
                   $this->notifyReportCompletion($generatedReport);
               }
               
               return $generatedReport;
               
           } catch (\Exception $e) {
               DB::rollback();
               
               if (isset($generatedReport)) {
                   $generatedReport->update([
                       'status' => 'failed',
                       'error_message' => $e->getMessage()
                   ]);
               }
               
               throw $e;
           }
       }
       
       public function generateScheduledReports()
       {
           $schedules = ReportSchedule::where('is_active', true)
               ->where('next_execution', '<=', now())
               ->get();
               
           foreach ($schedules as $schedule) {
               try {
                   $this->executeScheduledReport($schedule);
               } catch (\Exception $e) {
                   Log::error('Scheduled report failed', [
                       'schedule_id' => $schedule->id,
                       'error' => $e->getMessage()
                   ]);
                   
                   $schedule->update([
                       'last_status' => 'failed',
                       'last_error_message' => $e->getMessage(),
                       'failure_count' => $schedule->failure_count + 1
                   ]);
               }
           }
       }
       
       private function executeScheduledReport(ReportSchedule $schedule)
       {
           $template = $schedule->reportTemplate;
           
           // Preparar parámetros basados en la configuración del schedule
           $params = $this->buildScheduleParams($schedule);
           
           // Generar reporte
           $generatedReport = $this->generateReport($template, $params);
           
           // Marcar como programado
           $generatedReport->update(['is_scheduled' => true]);
           
           // Distribuir automáticamente si está configurado
           if ($schedule->auto_distribute) {
               $this->distributeReport($generatedReport, $schedule);
           }
           
           // Actualizar schedule
           $schedule->update([
               'last_execution' => now(),
               'next_execution' => $this->calculateNextExecution($schedule),
               'execution_count' => $schedule->execution_count + 1,
               'success_count' => $schedule->success_count + 1,
               'last_status' => 'success',
               'last_execution_time' => $generatedReport->generation_time
           ]);
           
           return $generatedReport;
       }
       
       private function calculateKPIs(array $reportData, ?array $calculationsConfig)
       {
           if (!$calculationsConfig) {
               return null;
           }
           
           $kpis = [];
           
           foreach ($calculationsConfig as $kpiConfig) {
               $kpiValue = $this->calculateKPI($reportData, $kpiConfig);
               
               $kpis[$kpiConfig['name']] = [
                   'value' => $kpiValue,
                   'formatted_value' => $this->formatKPIValue($kpiValue, $kpiConfig),
                   'trend' => $this->calculateKPITrend($kpiConfig, $kpiValue),
                   'target' => $kpiConfig['target'] ?? null,
                   'variance' => $this->calculateKPIVariance($kpiValue, $kpiConfig['target'] ?? null)
               ];
           }
           
           return $kpis;
       }
       
       private function calculateKPI(array $reportData, array $kpiConfig)
       {
           $rows = $reportData['rows'] ?? [];
           $field = $kpiConfig['field'];
           $operation = $kpiConfig['operation']; // sum, avg, count, min, max
           
           switch ($operation) {
               case 'sum':
                   return array_sum(array_column($rows, $field));
               case 'avg':
                   $values = array_column($rows, $field);
                   return count($values) > 0 ? array_sum($values) / count($values) : 0;
               case 'count':
                   return count($rows);
               case 'min':
                   return min(array_column($rows, $field));
               case 'max':
                   return max(array_column($rows, $field));
               case 'custom':
                   return $this->executeCustomCalculation($rows, $kpiConfig['formula']);
               default:
                   return 0;
           }
       }
       
       private function generateExportFiles(GeneratedReport $report, ReportTemplate $template)
       {
           $exportFormats = $template->export_formats;
           $filePaths = [];
           
           if (in_array('pdf', $exportFormats)) {
               $pdfPath = $this->pdfGenerationService->generatePDF($report, $template);
               $report->update(['pdf_path' => $pdfPath]);
           }
           
           if (in_array('excel', $exportFormats)) {
               $excelPath = $this->excelGenerationService->generateExcel($report, $template);
               $report->update(['excel_path' => $excelPath]);
           }
           
           if (in_array('csv', $exportFormats)) {
               $csvPath = $this->generateCSV($report);
               $report->update(['csv_path' => $csvPath]);
           }
           
           // Calcular tamaño total de archivos
           $totalSize = 0;
           foreach ([$report->pdf_path, $report->excel_path, $report->csv_path] as $path) {
               if ($path && Storage::exists($path)) {
                   $totalSize += Storage::size($path);
               }
           }
           
           $report->update(['file_size_mb' => $totalSize / 1024 / 1024]);
       }
       
       private function generateReportCode(ReportTemplate $template)
       {
           $date = now()->format('Ymd');
           $templateCode = $template->template_code;
           $sequence = str_pad(
               GeneratedReport::whereDate('created_at', now())
                   ->where('report_template_id', $template->id)
                   ->count() + 1,
               4, '0', STR_PAD_LEFT
           );
           
           return "RPT-{$templateCode}-{$date}-{$sequence}";
       }
       
       private function buildReportName(ReportTemplate $template, array $params)
       {
           $name = $template->name;
           
           if (isset($params['period_start']) && isset($params['period_end'])) {
               $startDate = Carbon::parse($params['period_start'])->format('d/m/Y');
               $endDate = Carbon::parse($params['period_end'])->format('d/m/Y');
               $name .= " - {$startDate} a {$endDate}";
           }
           
           return $name;
       }
   }
   ```

2. **Implementar DashboardService:**
   ```php
   class DashboardService
   {
       private $widgetDataService;
       private $cacheService;
       
       public function __construct(
           WidgetDataService $widgetDataService,
           CacheService $cacheService
       ) {
           $this->widgetDataService = $widgetDataService;
           $this->cacheService = $cacheService;
       }
       
       public function getDashboardData($schoolId, $userId = null)
       {
           $cacheKey = "dashboard_data_{$schoolId}_{$userId}";
           
           return $this->cacheService->remember($cacheKey, 300, function() use ($schoolId, $userId) {
               // Obtener widgets visibles para el usuario
               $widgets = $this->getVisibleWidgets($schoolId, $userId);
               
               $dashboardData = [
                   'widgets' => [],
                   'layout' => $this->getDashboardLayout($schoolId, $userId),
                   'last_updated' => now(),
                   'refresh_interval' => config('reports.dashboard_refresh_interval', 30)
               ];
               
               foreach ($widgets as $widget) {
                   try {
                       $widgetData = $this->getWidgetData($widget);
                       $dashboardData['widgets'][] = [
                           'id' => $widget->id,
                           'code' => $widget->widget_code,
                           'title' => $widget->title,
                           'type' => $widget->widget_type,
                           'data' => $widgetData,
                           'config' => [
                               'chart' => $widget->chart_config,
                               'layout' => $widget->layout_config,
                               'styling' => $widget->styling_config
                           ],
                           'interactive' => $widget->is_interactive,
                           'real_time' => $widget->real_time,
                           'refresh_interval' => $widget->refresh_interval
                       ];
                   } catch (\Exception $e) {
                       Log::error('Widget data loading failed', [
                           'widget_id' => $widget->id,
                           'error' => $e->getMessage()
                       ]);
                       
                       // Incluir widget con error para mostrar en dashboard
                       $dashboardData['widgets'][] = [
                           'id' => $widget->id,
                           'code' => $widget->widget_code,
                           'title' => $widget->title,
                           'type' => $widget->widget_type,
                           'error' => 'Error al cargar datos del widget',
                           'config' => [
                               'layout' => $widget->layout_config
                           ]
                       ];
                   }
               }
               
               return $dashboardData;
           });
       }
       
       public function getWidgetData(DashboardWidget $widget)
       {
           $cacheKey = "widget_data_{$widget->id}";
           $cacheTTL = $widget->real_time ? 30 : $widget->refresh_interval;
           
           return $this->cacheService->remember($cacheKey, $cacheTTL, function() use ($widget) {
               $startTime = microtime(true);
               
               // Obtener datos del widget
               $data = $this->widgetDataService->fetchWidgetData(
                   $widget->data_source,
                   $widget->data_config,
                   $widget->query_params
               );
               
               // Procesar datos según el tipo de widget
               $processedData = $this->processWidgetData($data, $widget);
               
               // Calcular tiempo de carga
               $loadTime = microtime(true) - $startTime;
               
               // Actualizar métricas del widget
               $widget->increment('view_count');
               $widget->update([
                   'last_viewed_at' => now(),
                   'avg_load_time' => $widget->view_count > 1 
                       ? (($widget->avg_load_time * ($widget->view_count - 1)) + $loadTime) / $widget->view_count
                       : $loadTime
               ]);
               
               // Verificar alertas si están configuradas
               if ($widget->has_alerts) {
                   $this->checkWidgetAlerts($widget, $processedData);
               }
               
               return [
                   'data' => $processedData,
                   'metadata' => [
                       'last_updated' => now(),
                       'load_time' => $loadTime,
                       'data_points' => is_array($processedData) ? count($processedData) : 1,
                       'cache_ttl' => $cacheTTL
                   ]
               ];
           });
       }
       
       public function getRealtimeWidgetData($widgetId)
       {
           $widget = DashboardWidget::findOrFail($widgetId);
           
           if (!$widget->real_time) {
               throw new \Exception('Widget no está configurado para tiempo real');
           }
           
           // No usar cache para datos en tiempo real
           $data = $this->widgetDataService->fetchWidgetData(
               $widget->data_source,
               $widget->data_config,
               $widget->query_params
           );
           
           $processedData = $this->processWidgetData($data, $widget);
           
           // Broadcast a usuarios conectados
           broadcast(new WidgetDataUpdated($widget->id, $processedData));
           
           return $processedData;
       }
       
       private function processWidgetData($data, DashboardWidget $widget)
       {
           switch ($widget->widget_type) {
               case 'kpi':
                   return $this->processKPIData($data, $widget);
               case 'chart':
                   return $this->processChartData($data, $widget);
               case 'table':
                   return $this->processTableData($data, $widget);
               case 'metric':
                   return $this->processMetricData($data, $widget);
               case 'progress':
                   return $this->processProgressData($data, $widget);
               case 'gauge':
                   return $this->processGaugeData($data, $widget);
               default:
                   return $data;
           }
       }
       
       private function processKPIData($data, DashboardWidget $widget)
       {
           $config = $widget->data_config;
           
           $kpi = [
               'value' => $data[$config['value_field']] ?? 0,
               'label' => $config['label'] ?? $widget->title,
               'format' => $config['format'] ?? 'number',
               'prefix' => $config['prefix'] ?? '',
               'suffix' => $config['suffix'] ?? ''
           ];
           
           // Calcular tendencia si hay datos históricos
           if (isset($config['trend_field']) && isset($data[$config['trend_field']])) {
               $previousValue = $data[$config['trend_field']];
               $currentValue = $kpi['value'];
               
               if ($previousValue > 0) {
                   $changePercent = (($currentValue - $previousValue) / $previousValue) * 100;
                   $kpi['trend'] = [
                       'direction' => $changePercent > 0 ? 'up' : ($changePercent < 0 ? 'down' : 'stable'),
                       'percentage' => abs($changePercent),
                       'previous_value' => $previousValue
                   ];
               }
           }
           
           // Agregar target si está configurado
           if (isset($config['target'])) {
               $kpi['target'] = $config['target'];
               $kpi['achievement'] = $kpi['target'] > 0 ? ($kpi['value'] / $kpi['target']) * 100 : 0;
           }
           
           return $kpi;
       }
       
       private function processChartData($data, DashboardWidget $widget)
       {
           $chartConfig = $widget->chart_config;
           $chartType = $chartConfig['type'] ?? 'line';
           
           switch ($chartType) {
               case 'line':
               case 'area':
                   return $this->processTimeSeriesData($data, $chartConfig);
               case 'bar':
               case 'column':
                   return $this->processCategoryData($data, $chartConfig);
               case 'pie':
               case 'donut':
                   return $this->processPieData($data, $chartConfig);
               default:
                   return $data;
           }
       }
       
       private function checkWidgetAlerts(DashboardWidget $widget, $data)
       {
           $alerts = ReportAlert::where('widget_id', $widget->id)
               ->where('is_active', true)
               ->where('last_evaluated_at', '<', now()->subSeconds(300)) // Evaluar cada 5 min
               ->get();
               
           foreach ($alerts as $alert) {
               $this->evaluateAlert($alert, $data);
           }
       }
       
       private function evaluateAlert(ReportAlert $alert, $data)
       {
           $metricValue = $this->extractMetricValue($data, $alert->metric_field);
           
           if ($metricValue === null) {
               return;
           }
           
           $isTriggered = $this->evaluateAlertCondition(
               $metricValue, 
               $alert->condition, 
               $alert->threshold_values
           );
           
           $alert->update([
               'last_evaluated_at' => now(),
               'last_evaluation_data' => $data,
               'last_metric_value' => $metricValue
           ]);
           
           if ($isTriggered && !$alert->is_suppressed) {
               $this->triggerAlert($alert, $metricValue);
           } elseif (!$isTriggered) {
               $alert->update(['last_status' => 'ok']);
           }
       }
       
       private function triggerAlert(ReportAlert $alert, $metricValue)
       {
           $alert->update([
               'last_triggered_at' => now(),
               'trigger_count' => $alert->trigger_count + 1,
               'last_status' => $alert->severity === 'critical' ? 'critical' : 'warning'
           ]);
           
           // Enviar notificaciones
           foreach ($alert->notification_channels as $channel) {
               $this->sendAlertNotification($alert, $channel, $metricValue);
           }
           
           // Broadcast en tiempo real
           broadcast(new AlertTriggered($alert, $metricValue));
       }
   }
   ```

#### Criterios de Aceptación:
- [ ] ReportGenerationService funcionando
- [ ] DashboardService operativo
- [ ] Generación de reportes automática
- [ ] Dashboard en tiempo real funcionando

---

## API Endpoints Implementados

### Report Templates
```
GET    /api/v1/report-templates
POST   /api/v1/report-templates
GET    /api/v1/report-templates/{id}
PUT    /api/v1/report-templates/{id}
DELETE /api/v1/report-templates/{id}
POST   /api/v1/report-templates/{id}/generate
GET    /api/v1/report-templates/{id}/preview
```

### Generated Reports
```
GET    /api/v1/reports
GET    /api/v1/reports/{id}
GET    /api/v1/reports/{id}/download/{format}
POST   /api/v1/reports/{id}/share
DELETE /api/v1/reports/{id}
GET    /api/v1/reports/scheduled
```

### Dashboard
```
GET    /api/v1/dashboard
GET    /api/v1/dashboard/widgets
POST   /api/v1/dashboard/widgets
PUT    /api/v1/dashboard/widgets/{id}
DELETE /api/v1/dashboard/widgets/{id}
GET    /api/v1/dashboard/widgets/{id}/data
GET    /api/v1/dashboard/realtime/{widgetId}
```

### Alerts
```
GET    /api/v1/alerts
POST   /api/v1/alerts
PUT    /api/v1/alerts/{id}
DELETE /api/v1/alerts/{id}
POST   /api/v1/alerts/{id}/suppress
GET    /api/v1/alerts/history
```

## Definición de Terminado (DoD)

### Criterios Técnicos:
- [ ] Microservicio de reportes funcionando
- [ ] Dashboard interactivo operativo
- [ ] Generación automática de reportes
- [ ] Sistema de alertas funcionando
- [ ] Visualizaciones avanzadas implementadas
- [ ] API REST completa y documentada

### Criterios de Calidad:
- [ ] Tests unitarios > 90% cobertura
- [ ] Tests de generación de reportes
- [ ] Validación de datos de dashboard
- [ ] Performance de consultas optimizado
- [ ] Cache implementado correctamente

### Criterios de Negocio:
- [ ] Reportes generándose correctamente
- [ ] Dashboard cargando en < 3 segundos
- [ ] Alertas funcionando en tiempo real
- [ ] Exportación a PDF/Excel operativa
- [ ] Programación automática funcionando

## Riesgos Identificados

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| Performance consultas | Alta | Alto | Índices, cache, optimización |
| Complejidad visualizaciones | Media | Medio | Librerías probadas, tests |
| Volumen de datos | Alta | Alto | Paginación, límites, cache |
| Tiempo real | Media | Medio | WebSockets, broadcasting |

## Métricas de Éxito

- **Dashboard load time**: < 3 segundos
- **Report generation**: < 2 min reportes complejos
- **Real-time updates**: < 1 segundo latencia
- **Cache hit rate**: > 80%
- **Alert accuracy**: > 95%

## Entregables

1. **Microservicio Reports** - Servicio completo funcionando
2. **Dashboard Interactivo** - Dashboard en tiempo real
3. **Generador Reportes** - Sistema automático de reportes
4. **Sistema Alertas** - Alertas inteligentes
5. **Visualizaciones** - Gráficos y charts avanzados
6. **Exportación** - PDF, Excel, CSV

## Variables de Entorno

```env
# Report Configuration
REPORTS_CACHE_TTL=3600
DASHBOARD_REFRESH_INTERVAL=30
MAX_REPORT_SIZE=50MB

# Analytics
ANALYTICS_ENABLED=true
PERFORMANCE_MONITORING=true

# Alerts
ALERTS_ENABLED=true
ALERT_CHECK_INTERVAL=300
```

## Datos de Prueba

### Templates de Reportes:
```json
{
  "report_templates": [
    {
      "template_code": "FINANCIAL_SUMMARY",
      "name": "Resumen Financiero",
      "category": "financial",
      "type": "summary",
      "data_sources": ["financial-service"]
    },
    {
      "template_code": "SPORTS_PERFORMANCE",
      "name": "Rendimiento Deportivo",
      "category": "sports",
      "type": "analytical",
      "data_sources": ["sports-service"]
    }
  ]
}
```

### Widgets de Dashboard:
```json
{
  "dashboard_widgets": [
    {
      "widget_code": "TOTAL_REVENUE",
      "title": "Ingresos Totales",
      "widget_type": "kpi",
      "data_source": "financial-service/dashboard/revenue"
    },
    {
      "widget_code": "ATTENDANCE_CHART",
      "title": "Asistencia por Mes",
      "widget_type": "chart",
      "data_source": "sports-service/dashboard/attendance"
    }
  ]
}
```

## Preguntas para Retrospectiva

1. **¿Qué funcionó bien en este sprint?**
   - ¿Los reportes se generan correctamente?
   - ¿El dashboard es intuitivo y rápido?

2. **¿Qué obstáculos encontramos?**
   - ¿La performance de las consultas fue adecuada?
   - ¿Las visualizaciones son claras?

3. **¿Qué podemos mejorar?**
   - ¿Cómo optimizar la carga del dashboard?
   - ¿El sistema de alertas es efectivo?

4. **¿Qué aprendimos?**
   - ¿Qué mejores prácticas para visualización de datos?
   - ¿Cómo mejorar la experiencia del usuario?

5. **¿Estamos listos para el siguiente sprint?**
   - ¿Todos los reportes funcionan correctamente?
   - ¿El dashboard es estable y rápido?
   - ¿Las alertas son precisas y útiles?