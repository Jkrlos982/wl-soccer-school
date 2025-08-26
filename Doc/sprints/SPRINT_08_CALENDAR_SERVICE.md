# Sprint 8: Calendar Service (Sistema de Calendario)

**Duración:** 2 semanas  
**Fase:** 3 - Sistema de Notificaciones y Calendario  
**Objetivo:** Implementar sistema completo de calendario con gestión de eventos, entrenamientos y recordatorios automáticos

## Resumen del Sprint

Este sprint implementa el microservicio de calendario con gestión de eventos deportivos, entrenamientos, reuniones, y sincronización con calendarios externos (Google Calendar, Outlook), incluyendo recordatorios automáticos y disponibilidad de recursos.

## Objetivos Específicos

- ✅ Implementar microservicio de calendario
- ✅ Crear sistema de gestión de eventos
- ✅ Integrar con calendarios externos
- ✅ Implementar gestión de recursos y disponibilidad
- ✅ Desarrollar recordatorios automáticos
- ✅ Crear vistas de calendario personalizables
- ✅ Implementar sincronización bidireccional

## Tareas Detalladas

### 1. Configuración Base del Microservicio

**Responsable:** Backend Developer Senior  
**Estimación:** 1 día  
**Prioridad:** Alta

#### Subtareas:

1. **Crear estructura del microservicio:**
   ```bash
   # Crear directorio del servicio
   mkdir wl-school-calendar-service
   cd wl-school-calendar-service
   
   # Inicializar Laravel
   composer create-project laravel/laravel . "10.*"
   
   # Instalar dependencias específicas
   composer require:
     - google/apiclient (Google Calendar API)
     - microsoft/microsoft-graph (Outlook Calendar)
     - spatie/laravel-google-calendar (Helper Google Calendar)
     - nesbot/carbon (Date manipulation)
     - spatie/laravel-permission (Permissions)
     - spatie/laravel-activitylog (Activity logging)
   ```

2. **Configurar variables de entorno:**
   ```env
   # .env
   APP_NAME="WL School Calendar Service"
   APP_URL=http://localhost:8005
   
   # Database
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=wl_school_calendar
   DB_USERNAME=root
   DB_PASSWORD=password
   
   # Queue
   QUEUE_CONNECTION=redis
   REDIS_HOST=redis
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   
   # Google Calendar API
   GOOGLE_CALENDAR_ID=your_calendar_id
   GOOGLE_CLIENT_ID=your_google_client_id
   GOOGLE_CLIENT_SECRET=your_google_client_secret
   GOOGLE_REDIRECT_URI=http://localhost:8005/auth/google/callback
   
   # Microsoft Graph API (Outlook)
   MICROSOFT_CLIENT_ID=your_microsoft_client_id
   MICROSOFT_CLIENT_SECRET=your_microsoft_client_secret
   MICROSOFT_REDIRECT_URI=http://localhost:8005/auth/microsoft/callback
   MICROSOFT_TENANT_ID=common
   
   # Timezone Configuration
   APP_TIMEZONE=America/Bogota
   DEFAULT_CALENDAR_TIMEZONE=America/Bogota
   
   # External Services
   AUTH_SERVICE_URL=http://auth-service:8001
   NOTIFICATION_SERVICE_URL=http://notification-service:8003
   SPORTS_SERVICE_URL=http://sports-service:8004
   
   # Calendar Settings
   MAX_EVENTS_PER_DAY=20
   MAX_RECURRING_INSTANCES=365
   SYNC_INTERVAL_MINUTES=15
   
   # File Storage
   FILESYSTEM_DISK=local
   AWS_ACCESS_KEY_ID=your_aws_key
   AWS_SECRET_ACCESS_KEY=your_aws_secret
   AWS_DEFAULT_REGION=us-east-1
   AWS_BUCKET=wl-school-calendar
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
       zip \
       unzip \
       cron \
       supervisor
   
   # Install PHP extensions
   RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd
   
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
   
   # Configure cron for sync
   COPY docker/cron/calendar-sync /etc/cron.d/calendar-sync
   RUN chmod 0644 /etc/cron.d/calendar-sync
   RUN crontab /etc/cron.d/calendar-sync
   
   # Configure supervisor
   COPY docker/supervisor/laravel-worker.conf /etc/supervisor/conf.d/
   COPY docker/supervisor/cron.conf /etc/supervisor/conf.d/
   
   EXPOSE 9000
   CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
   ```

#### Criterios de Aceptación:
- [ ] Microservicio configurado y funcionando
- [ ] Docker container operativo
- [ ] Variables de entorno configuradas
- [ ] Conexiones a APIs externas establecidas

---

### 2. Modelos y Migraciones Base

**Responsable:** Backend Developer  
**Estimación:** 2 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear migración Calendars:**
   ```php
   // Migration: create_calendars_table
   Schema::create('calendars', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('school_id');
       $table->string('name'); // Nombre del calendario
       $table->string('slug')->unique(); // URL-friendly name
       $table->text('description')->nullable();
       $table->string('color', 7)->default('#007bff'); // Color hex
       $table->string('timezone')->default('America/Bogota');
       
       // Configuración
       $table->boolean('is_public')->default(false); // Visible públicamente
       $table->boolean('is_default')->default(false); // Calendario por defecto
       $table->boolean('allow_external_sync')->default(true);
       $table->json('permissions')->nullable(); // Permisos específicos
       $table->json('settings')->nullable(); // Configuraciones adicionales
       
       // Sincronización externa
       $table->string('external_calendar_id')->nullable(); // ID en servicio externo
       $table->enum('external_provider', ['google', 'microsoft', 'apple'])->nullable();
       $table->json('external_credentials')->nullable(); // Tokens de acceso
       $table->timestamp('last_sync_at')->nullable();
       $table->enum('sync_status', ['active', 'paused', 'error'])->default('active');
       $table->text('sync_error')->nullable();
       
       $table->unsignedBigInteger('created_by');
       $table->timestamps();
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('created_by')->references('id')->on('users');
       $table->index(['school_id', 'is_public']);
       $table->index(['school_id', 'is_default']);
       $table->index(['external_provider', 'external_calendar_id']);
   });
   ```

2. **Crear migración Events:**
   ```php
   // Migration: create_events_table
   Schema::create('events', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('calendar_id');
       $table->unsignedBigInteger('school_id');
       
       // Información básica
       $table->string('title');
       $table->text('description')->nullable();
       $table->string('location')->nullable();
       $table->string('color', 7)->nullable(); // Override calendar color
       
       // Fechas y horarios
       $table->datetime('start_datetime');
       $table->datetime('end_datetime');
       $table->string('timezone')->default('America/Bogota');
       $table->boolean('is_all_day')->default(false);
       
       // Tipo y categoría
       $table->enum('type', [
           'training', 'match', 'tournament', 'meeting', 'evaluation',
           'payment_due', 'birthday', 'holiday', 'custom'
       ]);
       $table->string('category')->nullable(); // Subcategoría específica
       
       // Recurrencia
       $table->boolean('is_recurring')->default(false);
       $table->json('recurrence_rule')->nullable(); // RRULE format
       $table->unsignedBigInteger('parent_event_id')->nullable(); // Para eventos recurrentes
       $table->date('recurrence_end_date')->nullable();
       $table->integer('max_occurrences')->nullable();
       
       // Estado y visibilidad
       $table->enum('status', ['confirmed', 'tentative', 'cancelled'])->default('confirmed');
       $table->enum('visibility', ['public', 'private', 'confidential'])->default('public');
       $table->integer('priority')->default(0); // 0=normal, 1=high, -1=low
       
       // Participantes y recursos
       $table->json('attendees')->nullable(); // Lista de participantes
       $table->json('resources')->nullable(); // Recursos necesarios (canchas, equipos)
       $table->integer('max_attendees')->nullable();
       $table->boolean('requires_confirmation')->default(false);
       
       // Recordatorios
       $table->json('reminders')->nullable(); // Configuración de recordatorios
       $table->boolean('send_notifications')->default(true);
       
       // Sincronización externa
       $table->string('external_event_id')->nullable();
       $table->json('external_data')->nullable();
       $table->timestamp('last_sync_at')->nullable();
       
       // Referencias
       $table->string('reference_type')->nullable(); // Training, Match, Payment, etc.
       $table->unsignedBigInteger('reference_id')->nullable();
       $table->json('metadata')->nullable();
       
       $table->unsignedBigInteger('created_by');
       $table->unsignedBigInteger('updated_by')->nullable();
       $table->timestamps();
       
       $table->foreign('calendar_id')->references('id')->on('calendars')->onDelete('cascade');
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('parent_event_id')->references('id')->on('events')->onDelete('cascade');
       $table->foreign('created_by')->references('id')->on('users');
       $table->foreign('updated_by')->references('id')->on('users');
       
       $table->index(['calendar_id', 'start_datetime']);
       $table->index(['school_id', 'type']);
       $table->index(['start_datetime', 'end_datetime']);
       $table->index(['is_recurring', 'parent_event_id']);
       $table->index(['reference_type', 'reference_id']);
       $table->index(['external_event_id']);
   });
   ```

3. **Crear migración EventAttendees:**
   ```php
   // Migration: create_event_attendees_table
   Schema::create('event_attendees', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('event_id');
       $table->string('attendee_type'); // User, Player, Coach, Parent
       $table->unsignedBigInteger('attendee_id');
       $table->string('attendee_name');
       $table->string('attendee_email')->nullable();
       $table->string('attendee_phone')->nullable();
       
       // Estado de participación
       $table->enum('status', ['pending', 'accepted', 'declined', 'tentative'])->default('pending');
       $table->enum('role', ['organizer', 'required', 'optional', 'resource'])->default('required');
       $table->boolean('is_organizer')->default(false);
       
       // Confirmación y asistencia
       $table->timestamp('responded_at')->nullable();
       $table->timestamp('checked_in_at')->nullable();
       $table->timestamp('checked_out_at')->nullable();
       $table->text('response_comment')->nullable();
       
       // Notificaciones
       $table->boolean('send_reminders')->default(true);
       $table->timestamp('last_reminder_sent')->nullable();
       
       $table->timestamps();
       
       $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
       $table->unique(['event_id', 'attendee_type', 'attendee_id'], 'unique_event_attendee');
       $table->index(['event_id', 'status']);
       $table->index(['attendee_type', 'attendee_id']);
   });
   ```

4. **Crear migración Resources:**
   ```php
   // Migration: create_resources_table
   Schema::create('resources', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('school_id');
       $table->string('name'); // Nombre del recurso
       $table->string('type'); // field, equipment, room, vehicle
       $table->text('description')->nullable();
       $table->string('location')->nullable();
       $table->integer('capacity')->nullable(); // Capacidad máxima
       
       // Disponibilidad
       $table->json('availability_schedule')->nullable(); // Horarios disponibles
       $table->boolean('requires_approval')->default(false);
       $table->decimal('hourly_rate', 10, 2)->nullable(); // Costo por hora
       
       // Estado
       $table->boolean('is_active')->default(true);
       $table->enum('status', ['available', 'maintenance', 'reserved', 'unavailable'])->default('available');
       
       // Configuración
       $table->json('equipment_included')->nullable(); // Equipos incluidos
       $table->json('booking_rules')->nullable(); // Reglas de reserva
       $table->json('metadata')->nullable();
       
       $table->unsignedBigInteger('created_by');
       $table->timestamps();
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('created_by')->references('id')->on('users');
       $table->index(['school_id', 'type']);
       $table->index(['school_id', 'is_active']);
   });
   ```

5. **Crear migración EventResources:**
   ```php
   // Migration: create_event_resources_table
   Schema::create('event_resources', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('event_id');
       $table->unsignedBigInteger('resource_id');
       $table->integer('quantity')->default(1);
       $table->enum('status', ['requested', 'confirmed', 'cancelled'])->default('requested');
       $table->text('notes')->nullable();
       $table->decimal('cost', 10, 2)->nullable();
       $table->timestamps();
       
       $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
       $table->foreign('resource_id')->references('id')->on('resources');
       $table->unique(['event_id', 'resource_id']);
   });
   ```

6. **Implementar modelos:**
   ```php
   class Calendar extends Model
   {
       protected $fillable = [
           'school_id', 'name', 'slug', 'description', 'color', 'timezone',
           'is_public', 'is_default', 'allow_external_sync', 'permissions',
           'settings', 'external_calendar_id', 'external_provider',
           'external_credentials', 'sync_status', 'created_by'
       ];
       
       protected $casts = [
           'permissions' => 'array',
           'settings' => 'array',
           'external_credentials' => 'array',
           'is_public' => 'boolean',
           'is_default' => 'boolean',
           'allow_external_sync' => 'boolean',
           'last_sync_at' => 'datetime'
       ];
       
       // Relaciones
       public function school() {
           return $this->belongsTo(School::class);
       }
       
       public function events() {
           return $this->hasMany(Event::class);
       }
       
       public function creator() {
           return $this->belongsTo(User::class, 'created_by');
       }
       
       // Métodos auxiliares
       public function getEventsForPeriod($startDate, $endDate) {
           return $this->events()
               ->where(function($query) use ($startDate, $endDate) {
                   $query->whereBetween('start_datetime', [$startDate, $endDate])
                         ->orWhereBetween('end_datetime', [$startDate, $endDate])
                         ->orWhere(function($q) use ($startDate, $endDate) {
                             $q->where('start_datetime', '<=', $startDate)
                               ->where('end_datetime', '>=', $endDate);
                         });
               })
               ->orderBy('start_datetime')
               ->get();
       }
       
       public function canUserAccess($userId, $permission = 'view') {
           $permissions = $this->permissions ?? [];
           
           if ($this->is_public && $permission === 'view') {
               return true;
           }
           
           return isset($permissions[$userId]) && 
                  in_array($permission, $permissions[$userId]);
       }
       
       // Scopes
       public function scopePublic($query) {
           return $query->where('is_public', true);
       }
       
       public function scopeForSchool($query, $schoolId) {
           return $query->where('school_id', $schoolId);
       }
   }
   
   class Event extends Model
   {
       protected $fillable = [
           'calendar_id', 'school_id', 'title', 'description', 'location',
           'color', 'start_datetime', 'end_datetime', 'timezone', 'is_all_day',
           'type', 'category', 'is_recurring', 'recurrence_rule', 'parent_event_id',
           'recurrence_end_date', 'max_occurrences', 'status', 'visibility',
           'priority', 'attendees', 'resources', 'max_attendees',
           'requires_confirmation', 'reminders', 'send_notifications',
           'external_event_id', 'external_data', 'reference_type',
           'reference_id', 'metadata', 'created_by', 'updated_by'
       ];
       
       protected $casts = [
           'start_datetime' => 'datetime',
           'end_datetime' => 'datetime',
           'recurrence_end_date' => 'date',
           'attendees' => 'array',
           'resources' => 'array',
           'reminders' => 'array',
           'external_data' => 'array',
           'metadata' => 'array',
           'is_all_day' => 'boolean',
           'is_recurring' => 'boolean',
           'requires_confirmation' => 'boolean',
           'send_notifications' => 'boolean',
           'last_sync_at' => 'datetime'
       ];
       
       // Relaciones
       public function calendar() {
           return $this->belongsTo(Calendar::class);
       }
       
       public function school() {
           return $this->belongsTo(School::class);
       }
       
       public function parentEvent() {
           return $this->belongsTo(Event::class, 'parent_event_id');
       }
       
       public function childEvents() {
           return $this->hasMany(Event::class, 'parent_event_id');
       }
       
       public function eventAttendees() {
           return $this->hasMany(EventAttendee::class);
       }
       
       public function eventResources() {
           return $this->hasMany(EventResource::class);
       }
       
       public function creator() {
           return $this->belongsTo(User::class, 'created_by');
       }
       
       // Métodos auxiliares
       public function getDurationInMinutes() {
           return $this->start_datetime->diffInMinutes($this->end_datetime);
       }
       
       public function isUpcoming() {
           return $this->start_datetime->isFuture();
       }
       
       public function isToday() {
           return $this->start_datetime->isToday();
       }
       
       public function hasConflictWith(Event $otherEvent) {
           return $this->start_datetime < $otherEvent->end_datetime &&
                  $this->end_datetime > $otherEvent->start_datetime;
       }
       
       public function generateRecurringInstances($limit = 100) {
           if (!$this->is_recurring || !$this->recurrence_rule) {
               return collect();
           }
           
           // Implementar lógica de recurrencia basada en RRULE
           // Esta es una implementación simplificada
           $instances = collect();
           $rule = $this->recurrence_rule;
           
           // Ejemplo para recurrencia semanal
           if ($rule['freq'] === 'WEEKLY') {
               $current = $this->start_datetime->copy();
               $interval = $rule['interval'] ?? 1;
               
               for ($i = 0; $i < $limit; $i++) {
                   if ($this->recurrence_end_date && $current->toDateString() > $this->recurrence_end_date) {
                       break;
                   }
                   
                   if ($this->max_occurrences && $i >= $this->max_occurrences) {
                       break;
                   }
                   
                   $instances->push([
                       'start_datetime' => $current->copy(),
                       'end_datetime' => $current->copy()->addMinutes($this->getDurationInMinutes()),
                       'occurrence_number' => $i + 1
                   ]);
                   
                   $current->addWeeks($interval);
               }
           }
           
           return $instances;
       }
       
       // Scopes
       public function scopeUpcoming($query) {
           return $query->where('start_datetime', '>', now());
       }
       
       public function scopeToday($query) {
           return $query->whereDate('start_datetime', today());
       }
       
       public function scopeByType($query, $type) {
           return $query->where('type', $type);
       }
       
       public function scopeInPeriod($query, $startDate, $endDate) {
           return $query->where(function($q) use ($startDate, $endDate) {
               $q->whereBetween('start_datetime', [$startDate, $endDate])
                 ->orWhereBetween('end_datetime', [$startDate, $endDate])
                 ->orWhere(function($subQ) use ($startDate, $endDate) {
                     $subQ->where('start_datetime', '<=', $startDate)
                          ->where('end_datetime', '>=', $endDate);
                 });
           });
       }
   }
   ```

#### Criterios de Aceptación:
- [ ] Migraciones ejecutadas correctamente
- [ ] Modelos implementados con relaciones
- [ ] Métodos auxiliares funcionando
- [ ] Índices de base de datos optimizados

---

### 3. Integración con Google Calendar

**Responsable:** Backend Developer Senior  
**Estimación:** 2 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear GoogleCalendarService:**
   ```php
   class GoogleCalendarService
   {
       private $client;
       private $service;
       
       public function __construct()
       {
           $this->client = new \Google_Client();
           $this->client->setClientId(config('services.google.client_id'));
           $this->client->setClientSecret(config('services.google.client_secret'));
           $this->client->setRedirectUri(config('services.google.redirect_uri'));
           $this->client->addScope(\Google_Service_Calendar::CALENDAR);
           $this->client->setAccessType('offline');
           $this->client->setPrompt('consent');
           
           $this->service = new \Google_Service_Calendar($this->client);
       }
       
       public function getAuthUrl()
       {
           return $this->client->createAuthUrl();
       }
       
       public function handleCallback($code)
       {
           try {
               $token = $this->client->fetchAccessTokenWithAuthCode($code);
               
               if (isset($token['error'])) {
                   throw new \Exception('Error getting access token: ' . $token['error']);
               }
               
               return $token;
           } catch (\Exception $e) {
               \Log::error('Google Calendar auth error: ' . $e->getMessage());
               throw $e;
           }
       }
       
       public function setAccessToken($token)
       {
           $this->client->setAccessToken($token);
           
           // Refresh token if needed
           if ($this->client->isAccessTokenExpired()) {
               if ($this->client->getRefreshToken()) {
                   $newToken = $this->client->fetchAccessTokenWithRefreshToken();
                   return $newToken;
               }
           }
           
           return $token;
       }
       
       public function createEvent($calendarId, Event $event)
       {
           try {
               $googleEvent = new \Google_Service_Calendar_Event([
                   'summary' => $event->title,
                   'description' => $event->description,
                   'location' => $event->location,
                   'start' => [
                       'dateTime' => $event->start_datetime->toRfc3339String(),
                       'timeZone' => $event->timezone,
                   ],
                   'end' => [
                       'dateTime' => $event->end_datetime->toRfc3339String(),
                       'timeZone' => $event->timezone,
                   ],
                   'attendees' => $this->formatAttendees($event->attendees ?? []),
                   'reminders' => [
                       'useDefault' => false,
                       'overrides' => $this->formatReminders($event->reminders ?? [])
                   ],
                   'colorId' => $this->getGoogleColorId($event->color),
                   'visibility' => $event->visibility,
                   'status' => $this->mapStatus($event->status)
               ]);
               
               if ($event->is_recurring && $event->recurrence_rule) {
                   $googleEvent->setRecurrence([$this->formatRecurrenceRule($event->recurrence_rule)]);
               }
               
               $createdEvent = $this->service->events->insert($calendarId, $googleEvent);
               
               return [
                   'success' => true,
                   'external_id' => $createdEvent->getId(),
                   'response' => $createdEvent
               ];
           } catch (\Exception $e) {
               \Log::error('Google Calendar create event error: ' . $e->getMessage(), [
                   'event_id' => $event->id,
                   'calendar_id' => $calendarId
               ]);
               
               return [
                   'success' => false,
                   'error' => $e->getMessage()
               ];
           }
       }
       
       public function updateEvent($calendarId, $eventId, Event $event)
       {
           try {
               $googleEvent = $this->service->events->get($calendarId, $eventId);
               
               $googleEvent->setSummary($event->title);
               $googleEvent->setDescription($event->description);
               $googleEvent->setLocation($event->location);
               
               $start = new \Google_Service_Calendar_EventDateTime();
               $start->setDateTime($event->start_datetime->toRfc3339String());
               $start->setTimeZone($event->timezone);
               $googleEvent->setStart($start);
               
               $end = new \Google_Service_Calendar_EventDateTime();
               $end->setDateTime($event->end_datetime->toRfc3339String());
               $end->setTimeZone($event->timezone);
               $googleEvent->setEnd($end);
               
               $updatedEvent = $this->service->events->update($calendarId, $eventId, $googleEvent);
               
               return [
                   'success' => true,
                   'response' => $updatedEvent
               ];
           } catch (\Exception $e) {
               \Log::error('Google Calendar update event error: ' . $e->getMessage());
               
               return [
                   'success' => false,
                   'error' => $e->getMessage()
               ];
           }
       }
       
       public function deleteEvent($calendarId, $eventId)
       {
           try {
               $this->service->events->delete($calendarId, $eventId);
               
               return ['success' => true];
           } catch (\Exception $e) {
               \Log::error('Google Calendar delete event error: ' . $e->getMessage());
               
               return [
                   'success' => false,
                   'error' => $e->getMessage()
               ];
           }
       }
       
       public function syncEvents($calendarId, $syncToken = null)
       {
           try {
               $optParams = [
                   'maxResults' => 250,
                   'orderBy' => 'startTime',
                   'singleEvents' => true,
                   'timeMin' => now()->subMonths(1)->toRfc3339String(),
                   'timeMax' => now()->addMonths(6)->toRfc3339String()
               ];
               
               if ($syncToken) {
                   $optParams['syncToken'] = $syncToken;
               }
               
               $events = $this->service->events->listEvents($calendarId, $optParams);
               
               return [
                   'success' => true,
                   'events' => $events->getItems(),
                   'next_sync_token' => $events->getNextSyncToken()
               ];
           } catch (\Exception $e) {
               \Log::error('Google Calendar sync error: ' . $e->getMessage());
               
               return [
                   'success' => false,
                   'error' => $e->getMessage()
               ];
           }
       }
       
       private function formatAttendees($attendees)
       {
           return array_map(function($attendee) {
               return [
                   'email' => $attendee['email'] ?? '',
                   'displayName' => $attendee['name'] ?? '',
                   'responseStatus' => $attendee['status'] ?? 'needsAction'
               ];
           }, $attendees);
       }
       
       private function formatReminders($reminders)
       {
           return array_map(function($reminder) {
               return [
                   'method' => $reminder['method'] ?? 'email',
                   'minutes' => $reminder['minutes'] ?? 15
               ];
           }, $reminders);
       }
       
       private function formatRecurrenceRule($rule)
       {
           // Convertir regla interna a formato RRULE de Google
           $rrule = 'RRULE:';
           
           if (isset($rule['freq'])) {
               $rrule .= 'FREQ=' . $rule['freq'];
           }
           
           if (isset($rule['interval'])) {
               $rrule .= ';INTERVAL=' . $rule['interval'];
           }
           
           if (isset($rule['count'])) {
               $rrule .= ';COUNT=' . $rule['count'];
           }
           
           if (isset($rule['until'])) {
               $rrule .= ';UNTIL=' . $rule['until'];
           }
           
           if (isset($rule['byday'])) {
               $rrule .= ';BYDAY=' . implode(',', $rule['byday']);
           }
           
           return $rrule;
       }
       
       private function getGoogleColorId($hexColor)
       {
           // Mapear colores hex a IDs de color de Google Calendar
           $colorMap = [
               '#a4bdfc' => '1', // Lavender
               '#7ae7bf' => '2', // Sage
               '#dbadff' => '3', // Grape
               '#ff887c' => '4', // Flamingo
               '#fbd75b' => '5', // Banana
               '#ffb878' => '6', // Tangerine
               '#46d6db' => '7', // Peacock
               '#e1e1e1' => '8', // Graphite
               '#5484ed' => '9', // Blueberry
               '#51b749' => '10', // Basil
               '#dc2127' => '11'  // Tomato
           ];
           
           return $colorMap[$hexColor] ?? '9'; // Default to blueberry
       }
       
       private function mapStatus($status)
       {
           return match($status) {
               'confirmed' => 'confirmed',
               'tentative' => 'tentative',
               'cancelled' => 'cancelled',
               default => 'confirmed'
           };
       }
   }
   ```

2. **Crear CalendarSyncService:**
   ```php
   class CalendarSyncService
   {
       private $googleService;
       
       public function __construct(GoogleCalendarService $googleService)
       {
           $this->googleService = $googleService;
       }
       
       public function syncCalendar(Calendar $calendar)
       {
           if (!$calendar->allow_external_sync || !$calendar->external_calendar_id) {
               return false;
           }
           
           try {
               // Set access token
               $token = $this->googleService->setAccessToken($calendar->external_credentials);
               
               if (isset($token['access_token'])) {
                   // Update stored credentials if refreshed
                   $calendar->update(['external_credentials' => $token]);
               }
               
               // Sync events from Google to local
               $this->syncFromExternal($calendar);
               
               // Sync local events to Google
               $this->syncToExternal($calendar);
               
               $calendar->update([
                   'last_sync_at' => now(),
                   'sync_status' => 'active',
                   'sync_error' => null
               ]);
               
               return true;
           } catch (\Exception $e) {
               \Log::error('Calendar sync failed: ' . $e->getMessage(), [
                   'calendar_id' => $calendar->id
               ]);
               
               $calendar->update([
                   'sync_status' => 'error',
                   'sync_error' => $e->getMessage()
               ]);
               
               return false;
           }
       }
       
       private function syncFromExternal(Calendar $calendar)
       {
           $result = $this->googleService->syncEvents($calendar->external_calendar_id);
           
           if (!$result['success']) {
               throw new \Exception($result['error']);
           }
           
           foreach ($result['events'] as $googleEvent) {
               $this->processExternalEvent($calendar, $googleEvent);
           }
       }
       
       private function syncToExternal(Calendar $calendar)
       {
           // Sync local events that haven't been synced
           $localEvents = $calendar->events()
               ->whereNull('external_event_id')
               ->where('updated_at', '>', $calendar->last_sync_at ?? now()->subDays(7))
               ->get();
               
           foreach ($localEvents as $event) {
               $result = $this->googleService->createEvent($calendar->external_calendar_id, $event);
               
               if ($result['success']) {
                   $event->update([
                       'external_event_id' => $result['external_id'],
                       'last_sync_at' => now()
                   ]);
               }
           }
           
           // Update modified local events
           $modifiedEvents = $calendar->events()
               ->whereNotNull('external_event_id')
               ->where('updated_at', '>', $calendar->last_sync_at ?? now()->subDays(7))
               ->get();
               
           foreach ($modifiedEvents as $event) {
               $result = $this->googleService->updateEvent(
                   $calendar->external_calendar_id,
                   $event->external_event_id,
                   $event
               );
               
               if ($result['success']) {
                   $event->update(['last_sync_at' => now()]);
               }
           }
       }
       
       private function processExternalEvent(Calendar $calendar, $googleEvent)
       {
           // Check if event already exists
           $existingEvent = Event::where('external_event_id', $googleEvent->getId())->first();
           
           $eventData = [
               'calendar_id' => $calendar->id,
               'school_id' => $calendar->school_id,
               'title' => $googleEvent->getSummary() ?? 'Sin título',
               'description' => $googleEvent->getDescription(),
               'location' => $googleEvent->getLocation(),
               'start_datetime' => $this->parseGoogleDateTime($googleEvent->getStart()),
               'end_datetime' => $this->parseGoogleDateTime($googleEvent->getEnd()),
               'timezone' => $googleEvent->getStart()->getTimeZone() ?? $calendar->timezone,
               'status' => $this->mapGoogleStatus($googleEvent->getStatus()),
               'external_event_id' => $googleEvent->getId(),
               'external_data' => $googleEvent->toSimpleObject(),
               'last_sync_at' => now(),
               'type' => 'custom', // Default type for external events
               'created_by' => 1 // System user
           ];
           
           if ($existingEvent) {
               $existingEvent->update($eventData);
           } else {
               Event::create($eventData);
           }
       }
       
       private function parseGoogleDateTime($dateTime)
       {
           if ($dateTime->getDateTime()) {
               return \Carbon\Carbon::parse($dateTime->getDateTime());
           } elseif ($dateTime->getDate()) {
               return \Carbon\Carbon::parse($dateTime->getDate())->startOfDay();
           }
           
           return now();
       }
       
       private function mapGoogleStatus($status)
       {
           return match($status) {
               'confirmed' => 'confirmed',
               'tentative' => 'tentative',
               'cancelled' => 'cancelled',
               default => 'confirmed'
           };
       }
   }
   ```

#### Criterios de Aceptación:
- [ ] Google Calendar API integrada
- [ ] Sincronización bidireccional funcionando
- [ ] Autenticación OAuth implementada
- [ ] Manejo de tokens de acceso y refresh

---

### 4. Sistema de Recordatorios Automáticos

**Responsable:** Backend Developer  
**Estimación:** 1.5 días  
**Prioridad:** Media

#### Subtareas:

1. **Crear ReminderService:**
   ```php
   class ReminderService
   {
       private $notificationService;
       
       public function __construct()
       {
           $this->notificationService = app('NotificationService');
       }
       
       public function processEventReminders()
       {
           $upcomingEvents = Event::with(['calendar', 'eventAttendees'])
               ->where('send_notifications', true)
               ->where('start_datetime', '>', now())
               ->where('start_datetime', '<=', now()->addDays(7))
               ->get();
               
           foreach ($upcomingEvents as $event) {
               $this->processEventReminder($event);
           }
       }
       
       private function processEventReminder(Event $event)
       {
           $reminders = $event->reminders ?? $this->getDefaultReminders($event->type);
           
           foreach ($reminders as $reminder) {
               $reminderTime = $event->start_datetime->subMinutes($reminder['minutes']);
               
               // Check if it's time to send this reminder
               if (now()->between($reminderTime->subMinutes(5), $reminderTime->addMinutes(5))) {
                   $this->sendEventReminder($event, $reminder);
               }
           }
       }
       
       private function sendEventReminder(Event $event, $reminder)
       {
           // Get attendees who should receive reminders
           $attendees = $event->eventAttendees()
               ->where('send_reminders', true)
               ->where(function($query) use ($reminder) {
                   $query->whereNull('last_reminder_sent')
                         ->orWhere('last_reminder_sent', '<', now()->subHours(1));
               })
               ->get();
               
           foreach ($attendees as $attendee) {
               $this->sendReminderToAttendee($event, $attendee, $reminder);
           }
       }
       
       private function sendReminderToAttendee(Event $event, EventAttendee $attendee, $reminder)
       {
           $variables = [
               'event_title' => $event->title,
               'event_date' => $event->start_datetime->format('d/m/Y'),
               'event_time' => $event->start_datetime->format('H:i'),
               'event_location' => $event->location ?? 'Por definir',
               'attendee_name' => $attendee->attendee_name,
               'reminder_time' => $this->formatReminderTime($reminder['minutes']),
               'calendar_name' => $event->calendar->name
           ];
           
           // Determine notification type based on reminder method
           $notificationType = $reminder['method'] ?? 'whatsapp';
           
           // Get appropriate template
           $template = $this->getEventReminderTemplate($event->type, $notificationType);
           
           if ($template) {
               $this->notificationService->sendNotification([
                   'template_id' => $template->id,
                   'recipient_type' => $attendee->attendee_type,
                   'recipient_id' => $attendee->attendee_id,
                   'recipient_phone' => $attendee->attendee_phone,
                   'recipient_email' => $attendee->attendee_email,
                   'recipient_name' => $attendee->attendee_name,
                   'variables' => $variables,
                   'reference_type' => 'Event',
                   'reference_id' => $event->id,
                   'metadata' => [
                       'reminder_type' => $reminder['type'] ?? 'general',
                       'event_type' => $event->type,
                       'minutes_before' => $reminder['minutes']
                   ]
               ]);
               
               // Update last reminder sent
               $attendee->update(['last_reminder_sent' => now()]);
           }
       }
       
       private function getDefaultReminders($eventType)
       {
           return match($eventType) {
               'training' => [
                   ['method' => 'whatsapp', 'minutes' => 1440, 'type' => 'day_before'], // 1 day
                   ['method' => 'whatsapp', 'minutes' => 60, 'type' => 'hour_before']   // 1 hour
               ],
               'match' => [
                   ['method' => 'whatsapp', 'minutes' => 2880, 'type' => 'two_days_before'], // 2 days
                   ['method' => 'whatsapp', 'minutes' => 1440, 'type' => 'day_before'],     // 1 day
                   ['method' => 'whatsapp', 'minutes' => 120, 'type' => 'two_hours_before'] // 2 hours
               ],
               'tournament' => [
                   ['method' => 'whatsapp', 'minutes' => 10080, 'type' => 'week_before'],    // 1 week
                   ['method' => 'whatsapp', 'minutes' => 2880, 'type' => 'two_days_before'], // 2 days
                   ['method' => 'whatsapp', 'minutes' => 1440, 'type' => 'day_before']       // 1 day
               ],
               'meeting' => [
                   ['method' => 'email', 'minutes' => 1440, 'type' => 'day_before'],    // 1 day
                   ['method' => 'whatsapp', 'minutes' => 30, 'type' => 'thirty_minutes'] // 30 min
               ],
               'payment_due' => [
                   ['method' => 'whatsapp', 'minutes' => 4320, 'type' => 'three_days_before'], // 3 days
                   ['method' => 'whatsapp', 'minutes' => 1440, 'type' => 'day_before'],        // 1 day
                   ['method' => 'whatsapp', 'minutes' => 0, 'type' => 'due_date']              // Due date
               ],
               default => [
                   ['method' => 'whatsapp', 'minutes' => 1440, 'type' => 'day_before'], // 1 day
                   ['method' => 'whatsapp', 'minutes' => 60, 'type' => 'hour_before']   // 1 hour
               ]
           };
       }
       
       private function getEventReminderTemplate($eventType, $notificationType)
       {
           // Get template based on event type and notification method
           $templateCode = match($eventType) {
               'training' => 'training_reminder',
               'match' => 'match_reminder',
               'tournament' => 'tournament_reminder',
               'meeting' => 'meeting_reminder',
               'payment_due' => 'payment_reminder',
               'birthday' => 'birthday_reminder',
               default => 'general_event_reminder'
           };
           
           return NotificationTemplate::where('code', $templateCode)
               ->where('type', $notificationType)
               ->where('is_active', true)
               ->first();
       }
       
       private function formatReminderTime($minutes)
       {
           if ($minutes < 60) {
               return $minutes . ' minutos';
           } elseif ($minutes < 1440) {
               $hours = intval($minutes / 60);
               return $hours . ($hours === 1 ? ' hora' : ' horas');
           } else {
               $days = intval($minutes / 1440);
               return $days . ($days === 1 ? ' día' : ' días');
           }
       }
       
       public function createBirthdayReminders()
       {
           // Get users with birthdays in the next 7 days
           $upcomingBirthdays = $this->getUsersWithUpcomingBirthdays();
           
           foreach ($upcomingBirthdays as $user) {
               $this->createBirthdayEvent($user);
           }
       }
       
       private function getUsersWithUpcomingBirthdays()
       {
           // This would query the user service for upcoming birthdays
           // Implementation depends on user service structure
           return [];
       }
       
       private function createBirthdayEvent($user)
       {
           $birthdayDate = Carbon::parse($user['birthday'])->setYear(now()->year);
           
           // Check if birthday event already exists
           $existingEvent = Event::where('reference_type', 'Birthday')
               ->where('reference_id', $user['id'])
               ->whereDate('start_datetime', $birthdayDate)
               ->first();
               
           if (!$existingEvent) {
               Event::create([
                   'calendar_id' => $this->getDefaultCalendar($user['school_id'])->id,
                   'school_id' => $user['school_id'],
                   'title' => '🎂 Cumpleaños de ' . $user['name'],
                   'description' => 'Cumpleaños de ' . $user['name'],
                   'start_datetime' => $birthdayDate->startOfDay(),
                   'end_datetime' => $birthdayDate->endOfDay(),
                   'is_all_day' => true,
                   'type' => 'birthday',
                   'status' => 'confirmed',
                   'visibility' => 'public',
                   'reference_type' => 'Birthday',
                   'reference_id' => $user['id'],
                   'reminders' => [
                       ['method' => 'whatsapp', 'minutes' => 0, 'type' => 'birthday']
                   ],
                   'created_by' => 1 // System user
               ]);
           }
       }
       
       private function getDefaultCalendar($schoolId)
       {
           return Calendar::where('school_id', $schoolId)
               ->where('is_default', true)
               ->first();
       }
   }
   ```

2. **Crear comando para procesar recordatorios:**
   ```php
   class ProcessEventRemindersCommand extends Command
   {
       protected $signature = 'calendar:process-reminders';
       protected $description = 'Process and send event reminders';
       
       public function handle(ReminderService $reminderService)
       {
           $this->info('Processing event reminders...');
           
           $reminderService->processEventReminders();
           
           $this->info('Event reminders processed successfully.');
           
           $this->info('Creating birthday reminders...');
           
           $reminderService->createBirthdayReminders();
           
           $this->info('Birthday reminders created successfully.');
       }
   }
   ```

#### Criterios de Aceptación:
- [ ] Sistema de recordatorios automáticos funcionando
- [ ] Recordatorios enviándose en horarios correctos
- [ ] Diferentes tipos de recordatorios por tipo de evento
- [ ] Recordatorios de cumpleaños automáticos

---

## API Endpoints Implementados

### Calendars
```
GET    /api/v1/calendars
POST   /api/v1/calendars
GET    /api/v1/calendars/{id}
PUT    /api/v1/calendars/{id}
DELETE /api/v1/calendars/{id}
POST   /api/v1/calendars/{id}/sync
GET    /api/v1/calendars/{id}/events
```

### Events
```
GET    /api/v1/events
POST   /api/v1/events
GET    /api/v1/events/{id}
PUT    /api/v1/events/{id}
DELETE /api/v1/events/{id}
GET    /api/v1/events/upcoming
GET    /api/v1/events/today
POST   /api/v1/events/{id}/attendees
PUT    /api/v1/events/{id}/attendees/{attendeeId}
DELETE /api/v1/events/{id}/attendees/{attendeeId}
```

### Resources
```
GET    /api/v1/resources
POST   /api/v1/resources
GET    /api/v1/resources/{id}
PUT    /api/v1/resources/{id}
DELETE /api/v1/resources/{id}
GET    /api/v1/resources/{id}/availability
POST   /api/v1/resources/{id}/book
```

### External Integration
```
GET    /api/v1/calendar/auth/google
GET    /api/v1/calendar/auth/google/callback
GET    /api/v1/calendar/auth/microsoft
GET    /api/v1/calendar/auth/microsoft/callback
POST   /api/v1/calendar/sync/all
```

## Definición de Terminado (DoD)

### Criterios Técnicos:
- [ ] Microservicio de calendario funcionando
- [ ] Google Calendar integrado y sincronizando
- [ ] Sistema de recordatorios automáticos operativo
- [ ] Gestión de recursos implementada
- [ ] API REST completa y documentada

### Criterios de Calidad:
- [ ] Tests unitarios > 85% cobertura
- [ ] Tests de integración con APIs externas
- [ ] Performance validada (< 1s consultas)
- [ ] Manejo de errores robusto
- [ ] Logs detallados implementados

### Criterios de Negocio:
- [ ] Eventos creándose y sincronizándose correctamente
- [ ] Recordatorios enviándose en tiempo correcto
- [ ] Recursos reservándose sin conflictos
- [ ] Calendarios externos sincronizando bidireccional

## Riesgos Identificados

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| Límites API Google | Media | Alto | Rate limiting, cache, tokens múltiples |
| Conflictos sincronización | Alta | Medio | Timestamps, conflict resolution |
| Performance consultas | Media | Medio | Índices, cache, paginación |
| Timezone issues | Alta | Alto | Validación timezone, UTC storage |

## Métricas de Éxito

- **Sync success rate**: > 98% sincronizaciones exitosas
- **Reminder delivery**: > 95% recordatorios entregados
- **API response time**: < 500ms promedio
- **Event conflicts**: < 1% eventos con conflictos
- **User adoption**: > 80% usuarios usando calendario

## Entregables

1. **Microservicio Calendar** - Servicio completo funcionando
2. **Integración Google Calendar** - Sincronización bidireccional
3. **Sistema Recordatorios** - Notificaciones automáticas
4. **Gestión Recursos** - Reserva y disponibilidad
5. **API REST** - Endpoints completos documentados
6. **Documentación** - Guías integración y uso

## Variables de Entorno

```env
# Google Calendar API
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8005/auth/google/callback

# Microsoft Graph API
MICROSOFT_CLIENT_ID=your_microsoft_client_id
MICROSOFT_CLIENT_SECRET=your_microsoft_client_secret
MICROSOFT_REDIRECT_URI=http://localhost:8005/auth/microsoft/callback

# Calendar Configuration
APP_TIMEZONE=America/Bogota
DEFAULT_CALENDAR_TIMEZONE=America/Bogota
MAX_EVENTS_PER_DAY=20
SYNC_INTERVAL_MINUTES=15
```

## Datos de Prueba

```php
// Calendarios de ejemplo
$calendars = [
    [
        'name' => 'Entrenamientos',
        'slug' => 'entrenamientos',
        'description' => 'Calendario de entrenamientos de todas las categorías',
        'color' => '#28a745',
        'is_public' => true,
        'is_default' => true
    ],
    [
        'name' => 'Partidos',
        'slug' => 'partidos',
        'description' => 'Calendario de partidos y torneos',
        'color' => '#dc3545',
        'is_public' => true
    ]
];

// Eventos de ejemplo
$events = [
    [
        'title' => 'Entrenamiento Sub-12',
        'description' => 'Entrenamiento semanal categoría Sub-12',
        'location' => 'Cancha Principal',
        'start_datetime' => now()->addDays(1)->setTime(16, 0),
        'end_datetime' => now()->addDays(1)->setTime(17, 30),
        'type' => 'training',
        'is_recurring' => true,
        'recurrence_rule' => [
            'freq' => 'WEEKLY',
            'interval' => 1,
            'byday' => ['TU', 'TH']
        ]
    ],
    [
        'title' => 'Partido vs Academia Rival',
        'description' => 'Partido amistoso categoría Sub-15',
        'location' => 'Estadio Municipal',
        'start_datetime' => now()->addDays(5)->setTime(10, 0),
        'end_datetime' => now()->addDays(5)->setTime(12, 0),
        'type' => 'match',
        'reminders' => [
            ['method' => 'whatsapp', 'minutes' => 2880],
            ['method' => 'whatsapp', 'minutes' => 1440],
            ['method' => 'whatsapp', 'minutes' => 120]
        ]
    ]
];

// Recursos de ejemplo
$resources = [
    [
        'name' => 'Cancha Principal',
        'type' => 'field',
        'description' => 'Cancha de fútbol principal con césped natural',
        'location' => 'Sector A',
        'capacity' => 22,
        'availability_schedule' => [
            'monday' => ['06:00-22:00'],
            'tuesday' => ['06:00-22:00'],
            'wednesday' => ['06:00-22:00'],
            'thursday' => ['06:00-22:00'],
            'friday' => ['06:00-22:00'],
            'saturday' => ['08:00-20:00'],
            'sunday' => ['08:00-18:00']
        ],
        'hourly_rate' => 50000
    ],
    [
        'name' => 'Salón de Reuniones',
        'type' => 'room',
        'description' => 'Salón para reuniones y charlas técnicas',
        'location' => 'Edificio Administrativo',
        'capacity' => 30,
        'equipment_included' => ['proyector', 'sonido', 'aire_acondicionado']
    ]
];
```

## Preguntas para Retrospectiva

1. **¿Qué funcionó bien en este sprint?**
   - ¿La integración con Google Calendar fue más fácil o difícil de lo esperado?
   - ¿El sistema de recordatorios está enviando notificaciones correctamente?

2. **¿Qué obstáculos encontramos?**
   - ¿Hubo problemas con los límites de la API de Google?
   - ¿La sincronización bidireccional presentó conflictos?
   - ¿Los timezone causaron problemas?

3. **¿Qué podemos mejorar?**
   - ¿Cómo podemos optimizar la performance de las consultas?
   - ¿El manejo de errores es suficientemente robusto?
   - ¿La experiencia de usuario es intuitiva?

4. **¿Qué aprendimos?**
   - ¿Qué mejores prácticas identificamos para integraciones externas?
   - ¿Cómo podemos mejorar el sistema de recordatorios?

5. **¿Estamos listos para el siguiente sprint?**
   - ¿Todos los endpoints están funcionando correctamente?
   - ¿La documentación está completa?
   - ¿Los tests cubren los casos críticos?