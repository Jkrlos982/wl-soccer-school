# Sprint 7: Notification Service (Sistema de Notificaciones)

**Duración:** 2 semanas  
**Fase:** 3 - Sistema de Notificaciones y Calendario  
**Objetivo:** Implementar sistema completo de notificaciones multicanal con plantillas personalizables

## Resumen del Sprint

Este sprint implementa el microservicio de notificaciones con soporte para WhatsApp, Email, SMS y notificaciones push, incluyendo plantillas personalizables, programación automática y seguimiento de entrega.

## Objetivos Específicos

- ✅ Implementar microservicio de notificaciones
- ✅ Integrar WhatsApp Business API
- ✅ Configurar sistema de emails transaccionales
- ✅ Implementar notificaciones push (PWA)
- ✅ Crear sistema de plantillas personalizables
- ✅ Desarrollar programación automática
- ✅ Implementar seguimiento y métricas

## Tareas Detalladas

### 1. Configuración Base del Microservicio

**Responsable:** Backend Developer Senior  
**Estimación:** 1 día  
**Prioridad:** Alta

#### Subtareas:

1. **Crear estructura del microservicio:**
   ```bash
   # Crear directorio del servicio
   mkdir wl-school-notification-service
   cd wl-school-notification-service
   
   # Inicializar Laravel
   composer create-project laravel/laravel . "10.*"
   
   # Instalar dependencias específicas
   composer require:
     - guzzlehttp/guzzle (HTTP client)
     - pusher/pusher-php-server (Push notifications)
     - twilio/sdk (SMS/WhatsApp)
     - league/flysystem-aws-s3-v3 (File storage)
     - spatie/laravel-queue-monitor (Queue monitoring)
   ```

2. **Configurar variables de entorno:**
   ```env
   # .env
   APP_NAME="WL School Notification Service"
   APP_URL=http://localhost:8003
   
   # Database
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=wl_school_notifications
   DB_USERNAME=root
   DB_PASSWORD=password
   
   # Queue
   QUEUE_CONNECTION=redis
   REDIS_HOST=redis
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   
   # WhatsApp Business API
   WHATSAPP_API_URL=https://graph.facebook.com/v18.0
   WHATSAPP_ACCESS_TOKEN=your_access_token
   WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
   WHATSAPP_WEBHOOK_VERIFY_TOKEN=your_webhook_token
   
   # Email
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=notifications@wlschool.com
   MAIL_PASSWORD=your_app_password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=notifications@wlschool.com
   MAIL_FROM_NAME="WL School"
   
   # SMS (Twilio)
   TWILIO_SID=your_twilio_sid
   TWILIO_AUTH_TOKEN=your_twilio_token
   TWILIO_PHONE_NUMBER=your_twilio_number
   
   # Push Notifications
   PUSHER_APP_ID=your_pusher_app_id
   PUSHER_APP_KEY=your_pusher_key
   PUSHER_APP_SECRET=your_pusher_secret
   PUSHER_APP_CLUSTER=us2
   
   # Firebase (for mobile push)
   FIREBASE_SERVER_KEY=your_firebase_server_key
   FIREBASE_SENDER_ID=your_firebase_sender_id
   
   # File Storage
   AWS_ACCESS_KEY_ID=your_aws_key
   AWS_SECRET_ACCESS_KEY=your_aws_secret
   AWS_DEFAULT_REGION=us-east-1
   AWS_BUCKET=wl-school-notifications
   
   # External Services
   AUTH_SERVICE_URL=http://auth-service:8001
   FINANCIAL_SERVICE_URL=http://financial-service:8002
   SPORTS_SERVICE_URL=http://sports-service:8004
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
   
   # Configure supervisor for queues
   COPY docker/supervisor/laravel-worker.conf /etc/supervisor/conf.d/
   
   EXPOSE 9000
   CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
   ```

#### Criterios de Aceptación:
- [ ] Microservicio configurado y funcionando
- [ ] Docker container operativo
- [ ] Variables de entorno configuradas
- [ ] Conexiones a servicios externos establecidas

---

### 2. Modelos y Migraciones Base

**Responsable:** Backend Developer  
**Estimación:** 2 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear migración NotificationTemplates:**
   ```php
   // Migration: create_notification_templates_table
   Schema::create('notification_templates', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('school_id');
       $table->string('name'); // Nombre descriptivo
       $table->string('code')->unique(); // Código único del template
       $table->enum('type', ['whatsapp', 'email', 'sms', 'push', 'system']);
       $table->enum('category', [
           'payment_reminder', 'payment_confirmation', 'training_reminder',
           'attendance_alert', 'evaluation_ready', 'general_announcement',
           'birthday_greeting', 'welcome_message', 'account_created',
           'password_reset', 'invoice_generated', 'custom'
       ]);
       $table->string('subject')->nullable(); // Para emails
       $table->text('content'); // Contenido con variables {{variable}}
       $table->json('variables')->nullable(); // Variables disponibles
       $table->json('media_urls')->nullable(); // URLs de imágenes/archivos
       $table->boolean('is_active')->default(true);
       $table->boolean('is_default')->default(false); // Template por defecto
       $table->json('settings')->nullable(); // Configuraciones específicas
       $table->unsignedBigInteger('created_by');
       $table->timestamps();
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('created_by')->references('id')->on('users');
       $table->index(['school_id', 'type']);
       $table->index(['school_id', 'category']);
       $table->index(['code', 'is_active']);
   });
   ```

2. **Crear migración Notifications:**
   ```php
   // Migration: create_notifications_table
   Schema::create('notifications', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('school_id');
       $table->unsignedBigInteger('template_id')->nullable();
       $table->enum('type', ['whatsapp', 'email', 'sms', 'push', 'system']);
       $table->enum('category', [
           'payment_reminder', 'payment_confirmation', 'training_reminder',
           'attendance_alert', 'evaluation_ready', 'general_announcement',
           'birthday_greeting', 'welcome_message', 'account_created',
           'password_reset', 'invoice_generated', 'custom'
       ]);
       
       // Destinatario
       $table->string('recipient_type'); // User, Player, Parent, Coach, etc.
       $table->unsignedBigInteger('recipient_id');
       $table->string('recipient_phone')->nullable();
       $table->string('recipient_email')->nullable();
       $table->string('recipient_name');
       
       // Contenido
       $table->string('subject')->nullable();
       $table->text('content');
       $table->json('variables')->nullable(); // Variables utilizadas
       $table->json('media_urls')->nullable();
       
       // Estado y seguimiento
       $table->enum('status', [
           'pending', 'queued', 'sending', 'sent', 'delivered', 
           'read', 'failed', 'cancelled'
       ])->default('pending');
       $table->timestamp('scheduled_at')->nullable();
       $table->timestamp('sent_at')->nullable();
       $table->timestamp('delivered_at')->nullable();
       $table->timestamp('read_at')->nullable();
       $table->timestamp('failed_at')->nullable();
       
       // Información del proveedor
       $table->string('provider')->nullable(); // whatsapp, twilio, pusher, etc.
       $table->string('provider_message_id')->nullable();
       $table->json('provider_response')->nullable();
       $table->text('error_message')->nullable();
       $table->integer('retry_count')->default(0);
       $table->timestamp('next_retry_at')->nullable();
       
       // Metadata
       $table->string('reference_type')->nullable(); // Payment, Training, etc.
       $table->unsignedBigInteger('reference_id')->nullable();
       $table->json('metadata')->nullable();
       $table->unsignedBigInteger('created_by')->nullable();
       $table->timestamps();
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('template_id')->references('id')->on('notification_templates');
       $table->index(['school_id', 'status']);
       $table->index(['school_id', 'type']);
       $table->index(['recipient_type', 'recipient_id']);
       $table->index(['scheduled_at', 'status']);
       $table->index(['reference_type', 'reference_id']);
   });
   ```

3. **Crear migración NotificationLogs:**
   ```php
   // Migration: create_notification_logs_table
   Schema::create('notification_logs', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('notification_id');
       $table->enum('event', [
           'created', 'queued', 'sending', 'sent', 'delivered', 
           'read', 'failed', 'retry', 'cancelled'
       ]);
       $table->text('description')->nullable();
       $table->json('data')->nullable(); // Datos adicionales del evento
       $table->timestamp('occurred_at');
       $table->timestamps();
       
       $table->foreign('notification_id')->references('id')->on('notifications')->onDelete('cascade');
       $table->index(['notification_id', 'event']);
       $table->index(['notification_id', 'occurred_at']);
   });
   ```

4. **Crear migración NotificationPreferences:**
   ```php
   // Migration: create_notification_preferences_table
   Schema::create('notification_preferences', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('school_id');
       $table->string('user_type'); // User, Player, Parent
       $table->unsignedBigInteger('user_id');
       $table->string('category'); // payment_reminder, training_reminder, etc.
       $table->boolean('whatsapp_enabled')->default(true);
       $table->boolean('email_enabled')->default(true);
       $table->boolean('sms_enabled')->default(false);
       $table->boolean('push_enabled')->default(true);
       $table->json('schedule_preferences')->nullable(); // Horarios preferidos
       $table->timestamps();
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->unique(['school_id', 'user_type', 'user_id', 'category'], 'unique_user_category_preference');
       $table->index(['school_id', 'user_type', 'user_id']);
   });
   ```

5. **Implementar modelos:**
   ```php
   class NotificationTemplate extends Model
   {
       protected $fillable = [
           'school_id', 'name', 'code', 'type', 'category', 'subject',
           'content', 'variables', 'media_urls', 'is_active', 'is_default',
           'settings', 'created_by'
       ];
       
       protected $casts = [
           'variables' => 'array',
           'media_urls' => 'array',
           'settings' => 'array',
           'is_active' => 'boolean',
           'is_default' => 'boolean'
       ];
       
       // Relaciones
       public function school() {
           return $this->belongsTo(School::class);
       }
       
       public function creator() {
           return $this->belongsTo(User::class, 'created_by');
       }
       
       public function notifications() {
           return $this->hasMany(Notification::class, 'template_id');
       }
       
       // Métodos auxiliares
       public function renderContent(array $variables = []) {
           $content = $this->content;
           
           foreach ($variables as $key => $value) {
               $content = str_replace('{{' . $key . '}}', $value, $content);
           }
           
           return $content;
       }
       
       public function getAvailableVariables() {
           return $this->variables ?? [];
       }
       
       // Scopes
       public function scopeActive($query) {
           return $query->where('is_active', true);
       }
       
       public function scopeByType($query, $type) {
           return $query->where('type', $type);
       }
       
       public function scopeByCategory($query, $category) {
           return $query->where('category', $category);
       }
   }
   
   class Notification extends Model
   {
       protected $fillable = [
           'school_id', 'template_id', 'type', 'category', 'recipient_type',
           'recipient_id', 'recipient_phone', 'recipient_email', 'recipient_name',
           'subject', 'content', 'variables', 'media_urls', 'status',
           'scheduled_at', 'provider', 'reference_type', 'reference_id',
           'metadata', 'created_by'
       ];
       
       protected $casts = [
           'variables' => 'array',
           'media_urls' => 'array',
           'metadata' => 'array',
           'scheduled_at' => 'datetime',
           'sent_at' => 'datetime',
           'delivered_at' => 'datetime',
           'read_at' => 'datetime',
           'failed_at' => 'datetime',
           'next_retry_at' => 'datetime'
       ];
       
       // Relaciones
       public function school() {
           return $this->belongsTo(School::class);
       }
       
       public function template() {
           return $this->belongsTo(NotificationTemplate::class, 'template_id');
       }
       
       public function logs() {
           return $this->hasMany(NotificationLog::class);
       }
       
       public function creator() {
           return $this->belongsTo(User::class, 'created_by');
       }
       
       // Métodos auxiliares
       public function markAsSent($providerMessageId = null, $providerResponse = null) {
           $this->update([
               'status' => 'sent',
               'sent_at' => now(),
               'provider_message_id' => $providerMessageId,
               'provider_response' => $providerResponse
           ]);
           
           $this->logEvent('sent', 'Notification sent successfully');
       }
       
       public function markAsDelivered() {
           $this->update([
               'status' => 'delivered',
               'delivered_at' => now()
           ]);
           
           $this->logEvent('delivered', 'Notification delivered');
       }
       
       public function markAsRead() {
           $this->update([
               'status' => 'read',
               'read_at' => now()
           ]);
           
           $this->logEvent('read', 'Notification read by recipient');
       }
       
       public function markAsFailed($errorMessage, $scheduleRetry = true) {
           $retryCount = $this->retry_count + 1;
           $nextRetry = $scheduleRetry && $retryCount <= 3 
               ? now()->addMinutes(pow(2, $retryCount) * 5) // Exponential backoff
               : null;
               
           $this->update([
               'status' => 'failed',
               'failed_at' => now(),
               'error_message' => $errorMessage,
               'retry_count' => $retryCount,
               'next_retry_at' => $nextRetry
           ]);
           
           $this->logEvent('failed', $errorMessage);
       }
       
       public function logEvent($event, $description = null, $data = null) {
           $this->logs()->create([
               'event' => $event,
               'description' => $description,
               'data' => $data,
               'occurred_at' => now()
           ]);
       }
       
       // Scopes
       public function scopePending($query) {
           return $query->where('status', 'pending');
       }
       
       public function scopeScheduled($query) {
           return $query->where('scheduled_at', '<=', now())
                       ->whereIn('status', ['pending', 'queued']);
       }
       
       public function scopeForRetry($query) {
           return $query->where('status', 'failed')
                       ->where('next_retry_at', '<=', now())
                       ->where('retry_count', '<', 3);
       }
   }
   ```

#### Criterios de Aceptación:
- [ ] Migraciones ejecutadas correctamente
- [ ] Modelos implementados con relaciones
- [ ] Métodos auxiliares funcionando
- [ ] Índices de base de datos optimizados

---

### 3. Integración WhatsApp Business API

**Responsable:** Backend Developer Senior  
**Estimación:** 2 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear WhatsAppService:**
   ```php
   class WhatsAppService
   {
       private $httpClient;
       private $apiUrl;
       private $accessToken;
       private $phoneNumberId;
       
       public function __construct()
       {
           $this->httpClient = new \GuzzleHttp\Client();
           $this->apiUrl = config('services.whatsapp.api_url');
           $this->accessToken = config('services.whatsapp.access_token');
           $this->phoneNumberId = config('services.whatsapp.phone_number_id');
       }
       
       public function sendTextMessage($to, $message, $replyToMessageId = null)
       {
           $payload = [
               'messaging_product' => 'whatsapp',
               'to' => $this->formatPhoneNumber($to),
               'type' => 'text',
               'text' => [
                   'body' => $message
               ]
           ];
           
           if ($replyToMessageId) {
               $payload['context'] = [
                   'message_id' => $replyToMessageId
               ];
           }
           
           return $this->sendRequest($payload);
       }
       
       public function sendTemplateMessage($to, $templateName, $languageCode = 'es', $parameters = [])
       {
           $payload = [
               'messaging_product' => 'whatsapp',
               'to' => $this->formatPhoneNumber($to),
               'type' => 'template',
               'template' => [
                   'name' => $templateName,
                   'language' => [
                       'code' => $languageCode
                   ]
               ]
           ];
           
           if (!empty($parameters)) {
               $payload['template']['components'] = [
                   [
                       'type' => 'body',
                       'parameters' => array_map(function($param) {
                           return ['type' => 'text', 'text' => $param];
                       }, $parameters)
                   ]
               ];
           }
           
           return $this->sendRequest($payload);
       }
       
       public function sendMediaMessage($to, $mediaType, $mediaUrl, $caption = null)
       {
           $payload = [
               'messaging_product' => 'whatsapp',
               'to' => $this->formatPhoneNumber($to),
               'type' => $mediaType, // image, document, audio, video
               $mediaType => [
                   'link' => $mediaUrl
               ]
           ];
           
           if ($caption && in_array($mediaType, ['image', 'document', 'video'])) {
               $payload[$mediaType]['caption'] = $caption;
           }
           
           return $this->sendRequest($payload);
       }
       
       public function sendInteractiveMessage($to, $type, $content)
       {
           $payload = [
               'messaging_product' => 'whatsapp',
               'to' => $this->formatPhoneNumber($to),
               'type' => 'interactive',
               'interactive' => [
                   'type' => $type, // button, list
                   ...$content
               ]
           ];
           
           return $this->sendRequest($payload);
       }
       
       public function markAsRead($messageId)
       {
           $payload = [
               'messaging_product' => 'whatsapp',
               'status' => 'read',
               'message_id' => $messageId
           ];
           
           return $this->sendRequest($payload);
       }
       
       private function sendRequest($payload)
       {
           try {
               $response = $this->httpClient->post(
                   "{$this->apiUrl}/{$this->phoneNumberId}/messages",
                   [
                       'headers' => [
                           'Authorization' => 'Bearer ' . $this->accessToken,
                           'Content-Type' => 'application/json'
                       ],
                       'json' => $payload
                   ]
               );
               
               $body = json_decode($response->getBody()->getContents(), true);
               
               return [
                   'success' => true,
                   'message_id' => $body['messages'][0]['id'] ?? null,
                   'response' => $body
               ];
           } catch (\Exception $e) {
               \Log::error('WhatsApp API Error: ' . $e->getMessage(), [
                   'payload' => $payload,
                   'exception' => $e
               ]);
               
               return [
                   'success' => false,
                   'error' => $e->getMessage(),
                   'response' => null
               ];
           }
       }
       
       private function formatPhoneNumber($phone)
       {
           // Remover caracteres no numéricos
           $phone = preg_replace('/[^0-9]/', '', $phone);
           
           // Si no tiene código de país, agregar Colombia (+57)
           if (strlen($phone) === 10 && substr($phone, 0, 1) === '3') {
               $phone = '57' . $phone;
           }
           
           return $phone;
       }
       
       public function validateWebhook($mode, $token, $challenge)
       {
           $verifyToken = config('services.whatsapp.webhook_verify_token');
           
           if ($mode === 'subscribe' && $token === $verifyToken) {
               return $challenge;
           }
           
           return false;
       }
       
       public function processWebhook($payload)
       {
           foreach ($payload['entry'] ?? [] as $entry) {
               foreach ($entry['changes'] ?? [] as $change) {
                   if ($change['field'] === 'messages') {
                       $this->processMessageStatus($change['value']);
                   }
               }
           }
       }
       
       private function processMessageStatus($value)
       {
           // Procesar estados de mensajes (delivered, read, failed)
           foreach ($value['statuses'] ?? [] as $status) {
               $messageId = $status['id'];
               $statusType = $status['status'];
               
               $notification = Notification::where('provider_message_id', $messageId)->first();
               
               if ($notification) {
                   switch ($statusType) {
                       case 'delivered':
                           $notification->markAsDelivered();
                           break;
                       case 'read':
                           $notification->markAsRead();
                           break;
                       case 'failed':
                           $errorMessage = $status['errors'][0]['title'] ?? 'Unknown error';
                           $notification->markAsFailed($errorMessage);
                           break;
                   }
               }
           }
           
           // Procesar mensajes entrantes
           foreach ($value['messages'] ?? [] as $message) {
               $this->processIncomingMessage($message);
           }
       }
       
       private function processIncomingMessage($message)
       {
           // Procesar mensajes entrantes (respuestas, comandos, etc.)
           $from = $message['from'];
           $messageId = $message['id'];
           $timestamp = $message['timestamp'];
           
           // Marcar como leído
           $this->markAsRead($messageId);
           
           // Procesar contenido del mensaje
           if (isset($message['text'])) {
               $text = $message['text']['body'];
               $this->processTextMessage($from, $text, $messageId);
           }
           
           // Log del mensaje entrante
           \Log::info('WhatsApp incoming message', [
               'from' => $from,
               'message_id' => $messageId,
               'timestamp' => $timestamp,
               'message' => $message
           ]);
       }
       
       private function processTextMessage($from, $text, $messageId)
       {
           // Procesar comandos básicos
           $text = strtolower(trim($text));
           
           switch ($text) {
               case 'stop':
               case 'baja':
               case 'cancelar':
                   $this->handleUnsubscribe($from);
                   break;
               case 'help':
               case 'ayuda':
                   $this->sendHelpMessage($from);
                   break;
               default:
                   // Procesar otros comandos o respuestas
                   break;
           }
       }
       
       private function handleUnsubscribe($phone)
       {
           // Implementar lógica de desuscripción
           // Actualizar preferencias del usuario
           $this->sendTextMessage($phone, 'Has sido desuscrito de las notificaciones de WhatsApp.');
       }
       
       private function sendHelpMessage($phone)
       {
           $helpText = "🏫 *WL School - Ayuda*\n\n" .
                      "Comandos disponibles:\n" .
                      "• *STOP* - Desuscribirse\n" .
                      "• *AYUDA* - Ver este mensaje\n\n" .
                      "Para más información, contacta con tu escuela.";
                      
           $this->sendTextMessage($phone, $helpText);
       }
   }
   ```

2. **Crear WhatsAppController:**
   ```php
   class WhatsAppController extends Controller
   {
       private $whatsappService;
       
       public function __construct(WhatsAppService $whatsappService)
       {
           $this->whatsappService = $whatsappService;
       }
       
       public function webhook(Request $request)
       {
           // Verificación del webhook
           if ($request->has(['hub_mode', 'hub_verify_token', 'hub_challenge'])) {
               $challenge = $this->whatsappService->validateWebhook(
                   $request->hub_mode,
                   $request->hub_verify_token,
                   $request->hub_challenge
               );
               
               if ($challenge) {
                   return response($challenge, 200);
               }
               
               return response('Forbidden', 403);
           }
           
           // Procesar webhook
           $payload = $request->all();
           $this->whatsappService->processWebhook($payload);
           
           return response('OK', 200);
       }
       
       public function sendTest(Request $request)
       {
           $request->validate([
               'phone' => 'required|string',
               'message' => 'required|string'
           ]);
           
           $result = $this->whatsappService->sendTextMessage(
               $request->phone,
               $request->message
           );
           
           return response()->json($result);
       }
   }
   ```

#### Criterios de Aceptación:
- [ ] WhatsApp Business API integrada
- [ ] Envío de mensajes funcionando
- [ ] Webhook configurado y procesando estados
- [ ] Manejo de errores implementado

---

### 4. Sistema de Email Transaccional

**Responsable:** Backend Developer  
**Estimación:** 1.5 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear EmailService:**
   ```php
   class EmailService
   {
       public function sendTransactionalEmail($to, $subject, $content, $attachments = [])
       {
           try {
               Mail::send([], [], function ($message) use ($to, $subject, $content, $attachments) {
                   $message->to($to)
                          ->subject($subject)
                          ->html($content);
                          
                   foreach ($attachments as $attachment) {
                       if (isset($attachment['path'])) {
                           $message->attach($attachment['path'], [
                               'as' => $attachment['name'] ?? basename($attachment['path']),
                               'mime' => $attachment['mime'] ?? null
                           ]);
                       }
                   }
               });
               
               return [
                   'success' => true,
                   'message_id' => null, // Laravel Mail no retorna ID
                   'response' => 'Email sent successfully'
               ];
           } catch (\Exception $e) {
               \Log::error('Email sending failed: ' . $e->getMessage(), [
                   'to' => $to,
                   'subject' => $subject,
                   'exception' => $e
               ]);
               
               return [
                   'success' => false,
                   'error' => $e->getMessage(),
                   'response' => null
               ];
           }
       }
       
       public function sendTemplatedEmail($to, $template, $variables = [], $attachments = [])
       {
           try {
               $renderedContent = $this->renderEmailTemplate($template, $variables);
               
               return $this->sendTransactionalEmail(
                   $to,
                   $template->subject,
                   $renderedContent,
                   $attachments
               );
           } catch (\Exception $e) {
               return [
                   'success' => false,
                   'error' => $e->getMessage(),
                   'response' => null
               ];
           }
       }
       
       private function renderEmailTemplate($template, $variables = [])
       {
           $content = $template->content;
           
           // Reemplazar variables
           foreach ($variables as $key => $value) {
               $content = str_replace('{{' . $key . '}}', $value, $content);
           }
           
           // Aplicar layout base si no está presente
           if (!str_contains($content, '<html>')) {
               $content = $this->wrapInEmailLayout($content, $template->school);
           }
           
           return $content;
       }
       
       private function wrapInEmailLayout($content, $school)
       {
           $schoolName = $school->name ?? 'WL School';
           $schoolLogo = $school->logo_url ?? asset('images/default-logo.png');
           $schoolColors = $school->brand_colors ?? ['primary' => '#007bff', 'secondary' => '#6c757d'];
           
           return view('emails.layout', [
               'content' => $content,
               'school_name' => $schoolName,
               'school_logo' => $schoolLogo,
               'primary_color' => $schoolColors['primary'],
               'secondary_color' => $schoolColors['secondary']
           ])->render();
       }
   }
   ```

2. **Crear plantilla de email base:**
   ```blade
   {{-- resources/views/emails/layout.blade.php --}}
   <!DOCTYPE html>
   <html lang="es">
   <head>
       <meta charset="UTF-8">
       <meta name="viewport" content="width=device-width, initial-scale=1.0">
       <title>{{ $school_name }}</title>
       <style>
           body {
               font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
               line-height: 1.6;
               color: #333;
               max-width: 600px;
               margin: 0 auto;
               padding: 20px;
               background-color: #f8f9fa;
           }
           .email-container {
               background-color: white;
               border-radius: 8px;
               overflow: hidden;
               box-shadow: 0 2px 10px rgba(0,0,0,0.1);
           }
           .email-header {
               background-color: {{ $primary_color }};
               color: white;
               padding: 20px;
               text-align: center;
           }
           .email-header img {
               max-height: 60px;
               margin-bottom: 10px;
           }
           .email-body {
               padding: 30px;
           }
           .email-footer {
               background-color: #f8f9fa;
               padding: 20px;
               text-align: center;
               font-size: 12px;
               color: #6c757d;
           }
           .btn {
               display: inline-block;
               padding: 12px 24px;
               background-color: {{ $primary_color }};
               color: white;
               text-decoration: none;
               border-radius: 5px;
               margin: 10px 0;
           }
           .btn:hover {
               background-color: {{ $secondary_color }};
           }
           .alert {
               padding: 15px;
               margin: 15px 0;
               border-radius: 5px;
           }
           .alert-info {
               background-color: #d1ecf1;
               border-left: 4px solid #bee5eb;
               color: #0c5460;
           }
           .alert-warning {
               background-color: #fff3cd;
               border-left: 4px solid #ffeaa7;
               color: #856404;
           }
           .alert-success {
               background-color: #d4edda;
               border-left: 4px solid #c3e6cb;
               color: #155724;
           }
       </style>
   </head>
   <body>
       <div class="email-container">
           <div class="email-header">
               <img src="{{ $school_logo }}" alt="{{ $school_name }}">
               <h1>{{ $school_name }}</h1>
           </div>
           
           <div class="email-body">
               {!! $content !!}
           </div>
           
           <div class="email-footer">
               <p>Este es un mensaje automático de {{ $school_name }}.</p>
               <p>Si tienes alguna pregunta, contacta con nosotros.</p>
               <p>&copy; {{ date('Y') }} {{ $school_name }}. Todos los derechos reservados.</p>
           </div>
       </div>
   </body>
   </html>
   ```

#### Criterios de Aceptación:
- [ ] Sistema de emails transaccionales funcionando
- [ ] Plantillas de email renderizándose correctamente
- [ ] Layout base aplicándose automáticamente
- [ ] Manejo de adjuntos implementado

---

### 5. Jobs y Colas de Procesamiento

**Responsable:** Backend Developer  
**Estimación:** 2 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear SendNotificationJob:**
   ```php
   class SendNotificationJob implements ShouldQueue
   {
       use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
       
       public $notification;
       public $tries = 3;
       public $backoff = [60, 300, 900]; // 1min, 5min, 15min
       
       public function __construct(Notification $notification)
       {
           $this->notification = $notification;
       }
       
       public function handle()
       {
           if ($this->notification->status !== 'pending' && $this->notification->status !== 'failed') {
               return;
           }
           
           $this->notification->update(['status' => 'sending']);
           $this->notification->logEvent('sending', 'Starting to send notification');
           
           try {
               $result = $this->sendNotification();
               
               if ($result['success']) {
                   $this->notification->markAsSent(
                       $result['message_id'],
                       $result['response']
                   );
               } else {
                   $this->notification->markAsFailed($result['error']);
                   $this->fail(new \Exception($result['error']));
               }
           } catch (\Exception $e) {
               $this->notification->markAsFailed($e->getMessage());
               throw $e;
           }
       }
       
       private function sendNotification()
       {
           switch ($this->notification->type) {
               case 'whatsapp':
                   return $this->sendWhatsApp();
               case 'email':
                   return $this->sendEmail();
               case 'sms':
                   return $this->sendSMS();
               case 'push':
                   return $this->sendPush();
               default:
                   throw new \Exception('Unsupported notification type: ' . $this->notification->type);
           }
       }
       
       private function sendWhatsApp()
       {
           $whatsappService = app(WhatsAppService::class);
           
           // Verificar si tiene media
           if (!empty($this->notification->media_urls)) {
               $mediaUrl = $this->notification->media_urls[0];
               $mediaType = $this->detectMediaType($mediaUrl);
               
               return $whatsappService->sendMediaMessage(
                   $this->notification->recipient_phone,
                   $mediaType,
                   $mediaUrl,
                   $this->notification->content
               );
           }
           
           return $whatsappService->sendTextMessage(
               $this->notification->recipient_phone,
               $this->notification->content
           );
       }
       
       private function sendEmail()
       {
           $emailService = app(EmailService::class);
           
           $attachments = [];
           if (!empty($this->notification->media_urls)) {
               foreach ($this->notification->media_urls as $url) {
                   $attachments[] = ['path' => $url];
               }
           }
           
           return $emailService->sendTransactionalEmail(
               $this->notification->recipient_email,
               $this->notification->subject,
               $this->notification->content,
               $attachments
           );
       }
       
       private function sendSMS()
       {
           $smsService = app(SMSService::class);
           
           return $smsService->sendSMS(
               $this->notification->recipient_phone,
               $this->notification->content
           );
       }
       
       private function sendPush()
       {
           $pushService = app(PushNotificationService::class);
           
           return $pushService->sendPushNotification(
               $this->notification->recipient_id,
               $this->notification->subject,
               $this->notification->content,
               $this->notification->metadata
           );
       }
       
       private function detectMediaType($url)
       {
           $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
           
           return match($extension) {
               'jpg', 'jpeg', 'png', 'gif', 'webp' => 'image',
               'pdf', 'doc', 'docx', 'xls', 'xlsx' => 'document',
               'mp4', 'avi', 'mov' => 'video',
               'mp3', 'wav', 'ogg' => 'audio',
               default => 'document'
           };
       }
       
       public function failed(\Throwable $exception)
       {
           $this->notification->markAsFailed($exception->getMessage(), false);
       }
   }
   ```

2. **Crear ProcessScheduledNotificationsJob:**
   ```php
   class ProcessScheduledNotificationsJob implements ShouldQueue
   {
       use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
       
       public function handle()
       {
           // Procesar notificaciones programadas
           $scheduledNotifications = Notification::scheduled()
               ->limit(100)
               ->get();
               
           foreach ($scheduledNotifications as $notification) {
               $notification->update(['status' => 'queued']);
               $notification->logEvent('queued', 'Notification queued for sending');
               
               SendNotificationJob::dispatch($notification)
                   ->delay(now()->addSeconds(rand(1, 30))); // Distribuir carga
           }
           
           // Procesar reintentos
           $retryNotifications = Notification::forRetry()
               ->limit(50)
               ->get();
               
           foreach ($retryNotifications as $notification) {
               $notification->logEvent('retry', 'Retrying failed notification');
               
               SendNotificationJob::dispatch($notification)
                   ->delay(now()->addMinutes(1));
           }
       }
   }
   ```

3. **Configurar colas en config/queue.php:**
   ```php
   'connections' => [
       'redis' => [
           'driver' => 'redis',
           'connection' => 'default',
           'queue' => env('REDIS_QUEUE', 'default'),
           'retry_after' => 90,
           'block_for' => null,
       ],
   ],
   
   // Configurar colas específicas
   'queues' => [
       'notifications' => [
           'high' => 'notifications-high',
           'default' => 'notifications-default',
           'low' => 'notifications-low'
       ]
   ]
   ```

4. **Crear comando para procesar colas:**
   ```php
   class ProcessNotificationQueuesCommand extends Command
   {
       protected $signature = 'notifications:process';
       protected $description = 'Process scheduled notifications and retries';
       
       public function handle()
       {
           $this->info('Processing scheduled notifications...');
           
           ProcessScheduledNotificationsJob::dispatch();
           
           $this->info('Scheduled notifications processing job dispatched.');
       }
   }
   ```

#### Criterios de Aceptación:
- [ ] Jobs de envío implementados
- [ ] Sistema de colas configurado
- [ ] Reintentos automáticos funcionando
- [ ] Procesamiento de notificaciones programadas operativo

---

## API Endpoints Implementados

### Templates
```
GET    /api/v1/notifications/templates
POST   /api/v1/notifications/templates
GET    /api/v1/notifications/templates/{id}
PUT    /api/v1/notifications/templates/{id}
DELETE /api/v1/notifications/templates/{id}
```

### Notifications
```
GET    /api/v1/notifications
POST   /api/v1/notifications
GET    /api/v1/notifications/{id}
PUT    /api/v1/notifications/{id}
DELETE /api/v1/notifications/{id}
POST   /api/v1/notifications/bulk
GET    /api/v1/notifications/stats
```

### WhatsApp
```
POST   /api/v1/whatsapp/webhook
POST   /api/v1/whatsapp/send-test
```

### Preferences
```
GET    /api/v1/notifications/preferences/{userId}
PUT    /api/v1/notifications/preferences/{userId}
```

## Definición de Terminado (DoD)

### Criterios Técnicos:
- [ ] Microservicio de notificaciones funcionando
- [ ] WhatsApp Business API integrada
- [ ] Sistema de emails transaccionales operativo
- [ ] Colas de procesamiento configuradas
- [ ] Webhooks procesando estados correctamente

### Criterios de Calidad:
- [ ] Tests unitarios > 85% cobertura
- [ ] Tests de integración con APIs externas
- [ ] Performance validada (< 2s envío)
- [ ] Manejo de errores robusto
- [ ] Logs detallados implementados

### Criterios de Negocio:
- [ ] Notificaciones llegando correctamente
- [ ] Estados de entrega rastreándose
- [ ] Plantillas personalizables funcionando
- [ ] Preferencias de usuario respetándose

## Riesgos Identificados

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| Límites API WhatsApp | Alta | Alto | Rate limiting, colas, monitoreo |
| Deliverability emails | Media | Alto | Configuración SPF/DKIM, reputación |
| Performance colas | Media | Medio | Optimización Redis, workers múltiples |
| Costos SMS/WhatsApp | Baja | Alto | Monitoreo costos, límites por escuela |

## Métricas de Éxito

- **Delivery rate**: > 95% notificaciones entregadas
- **Processing time**: < 30s desde creación hasta envío
- **Error rate**: < 2% fallos en envío
- **Queue processing**: < 1min tiempo promedio en cola
- **Webhook processing**: < 500ms tiempo respuesta

## Entregables

1. **Microservicio Notificaciones** - Servicio completo funcionando
2. **Integración WhatsApp** - API Business integrada
3. **Sistema Email** - Emails transaccionales operativos
4. **Sistema Colas** - Procesamiento asíncrono
5. **Webhooks** - Seguimiento estados en tiempo real
6. **Documentación** - Guías integración y uso

## Variables de Entorno

```env
# WhatsApp Business API
WHATSAPP_API_URL=https://graph.facebook.com/v18.0
WHATSAPP_ACCESS_TOKEN=your_access_token
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_WEBHOOK_VERIFY_TOKEN=your_webhook_token

# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=notifications@wlschool.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls

# SMS (Twilio)
TWILIO_SID=your_twilio_sid
TWILIO_AUTH_TOKEN=your_twilio_token
TWILIO_PHONE_NUMBER=your_twilio_number

# Push Notifications
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_key
PUSHER_APP_SECRET=your_pusher_secret
FIREBASE_SERVER_KEY=your_firebase_server_key
```

## Datos de Prueba

```php
// Templates de ejemplo
$templates = [
    [
        'name' => 'Recordatorio de Pago',
        'code' => 'payment_reminder',
        'type' => 'whatsapp',
        'category' => 'payment_reminder',
        'content' => 'Hola {{student_name}}, tienes un pago pendiente de ${{amount}} con vencimiento {{due_date}}. Puedes pagar en: {{payment_link}}'
    ],
    [
        'name' => 'Confirmación de Entrenamiento',
        'code' => 'training_confirmation',
        'type' => 'whatsapp',
        'category' => 'training_reminder',
        'content' => '⚽ Recordatorio: Entrenamiento de {{category_name}} mañana {{date}} a las {{time}}. ¡Te esperamos!'
    ]
];
```

## Preguntas para Retrospectiva

1. **¿Las integraciones con APIs externas son estables?**
2. **¿El sistema de colas maneja bien la carga?**
3. **¿Los webhooks procesan estados correctamente?**
4. **¿Las plantillas son suficientemente flexibles?**
5. **¿El seguimiento de entrega es preciso?**
6. **¿Qué mejoras podemos hacer en deliverability?**
7. **¿Los costos de envío están controlados?**