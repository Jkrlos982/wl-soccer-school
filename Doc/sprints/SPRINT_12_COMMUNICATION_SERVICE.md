# Sprint 12: Communication Service (Servicio de Comunicación Avanzada)

**Duración:** 2 semanas  
**Fase:** 5 - Comunicación y Notificaciones Avanzadas  
**Objetivo:** Implementar sistema completo de comunicación multi-canal con chat en tiempo real, videollamadas, mensajería masiva y centro de comunicaciones unificado

## Resumen del Sprint

Este sprint implementa el microservicio de comunicación avanzada que incluye chat en tiempo real, sistema de videollamadas, mensajería masiva, centro de comunicaciones unificado, y integración con múltiples canales de comunicación.

## Objetivos Específicos

- ✅ Implementar chat en tiempo real
- ✅ Desarrollar sistema de videollamadas
- ✅ Crear mensajería masiva inteligente
- ✅ Implementar centro de comunicaciones
- ✅ Integrar múltiples canales de comunicación
- ✅ Desarrollar sistema de moderación automática

## Tareas Detalladas

### 1. Configuración Base del Microservicio

**Responsable:** Backend Developer Senior  
**Estimación:** 1 día  
**Prioridad:** Alta

#### Subtareas:

1. **Crear estructura del microservicio:**
   ```bash
   # Crear directorio del servicio
   mkdir wl-school-communication-service
   cd wl-school-communication-service
   
   # Inicializar Laravel
   composer create-project laravel/laravel . "10.*"
   
   # Instalar dependencias específicas
   composer require:
     - pusher/pusher-php-server (Real-time messaging)
     - laravel/reverb (WebSocket server)
     - spatie/laravel-permission (Permissions)
     - spatie/laravel-activitylog (Activity logging)
     - intervention/image (Image processing)
     - league/flysystem-aws-s3-v3 (File storage)
     - predis/predis (Redis cache)
     - laravel/horizon (Queue monitoring)
     - spatie/laravel-medialibrary (Media management)
     - barryvdh/laravel-dompdf (PDF generation)
     - maatwebsite/excel (Excel export)
     - twilio/sdk (SMS and video calls)
     - vonage/client (Alternative SMS/Voice)
     - agora/rtc-sdk (Video calling)
     - firebase/php-jwt (JWT tokens)
     - ratchet/pawl (WebSocket client)
     - react/socket (Async sockets)
   ```

2. **Configurar variables de entorno:**
   ```env
   # .env
   APP_NAME="WL School Communication Service"
   APP_URL=http://localhost:8009
   
   # Database
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=wl_school_communication
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
   BROADCAST_DRIVER=reverb
   REVERB_APP_ID=your_reverb_app_id
   REVERB_APP_KEY=your_reverb_key
   REVERB_APP_SECRET=your_reverb_secret
   REVERB_HOST=localhost
   REVERB_PORT=8080
   REVERB_SCHEME=http
   
   # Pusher (Fallback)
   PUSHER_APP_ID=your_pusher_app_id
   PUSHER_APP_KEY=your_pusher_key
   PUSHER_APP_SECRET=your_pusher_secret
   PUSHER_APP_CLUSTER=us2
   
   # File Storage
   FILESYSTEM_DISK=s3
   AWS_ACCESS_KEY_ID=your_aws_key
   AWS_SECRET_ACCESS_KEY=your_aws_secret
   AWS_DEFAULT_REGION=us-east-1
   AWS_BUCKET=wl-school-communication
   
   # External Services
   AUTH_SERVICE_URL=http://auth-service:8001
   NOTIFICATION_SERVICE_URL=http://notification-service:8003
   CALENDAR_SERVICE_URL=http://calendar-service:8005
   
   # Twilio Configuration
   TWILIO_SID=your_twilio_sid
   TWILIO_TOKEN=your_twilio_token
   TWILIO_FROM=+1234567890
   TWILIO_VIDEO_API_KEY=your_video_api_key
   TWILIO_VIDEO_API_SECRET=your_video_api_secret
   
   # Vonage Configuration
   VONAGE_API_KEY=your_vonage_key
   VONAGE_API_SECRET=your_vonage_secret
   VONAGE_FROM=WL_SCHOOL
   
   # Agora Configuration
   AGORA_APP_ID=your_agora_app_id
   AGORA_APP_CERTIFICATE=your_agora_certificate
   
   # Chat Configuration
   CHAT_MESSAGE_RETENTION_DAYS=365
   CHAT_FILE_MAX_SIZE=10MB
   CHAT_ALLOWED_FILE_TYPES=jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx
   CHAT_MAX_PARTICIPANTS=100
   CHAT_TYPING_TIMEOUT=5
   
   # Video Call Configuration
   VIDEO_MAX_PARTICIPANTS=50
   VIDEO_RECORDING_ENABLED=true
   VIDEO_RECORDING_STORAGE=s3
   VIDEO_QUALITY_DEFAULT=HD
   VIDEO_BANDWIDTH_LIMIT=2000
   
   # Messaging Configuration
   BULK_MESSAGE_BATCH_SIZE=100
   BULK_MESSAGE_RATE_LIMIT=1000
   MESSAGE_TEMPLATE_CACHE_TTL=3600
   AUTO_MODERATION_ENABLED=true
   
   # Communication Center
   COMMUNICATION_HISTORY_RETENTION=730
   ANALYTICS_ENABLED=true
   CONVERSATION_ARCHIVING=true
   
   # Security
   MESSAGE_ENCRYPTION=true
   FILE_VIRUS_SCANNING=true
   CONTENT_MODERATION=true
   SPAM_DETECTION=true
   
   # Performance
   WEBSOCKET_MAX_CONNECTIONS=10000
   MESSAGE_CACHE_TTL=300
   PRESENCE_CACHE_TTL=60
   TYPING_INDICATOR_TTL=5
   
   # Moderation
   PROFANITY_FILTER=true
   AUTO_MODERATION_THRESHOLD=0.8
   MANUAL_REVIEW_QUEUE=true
   MODERATION_LOG_RETENTION=90
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
       supervisor \
       nodejs \
       npm \
       ffmpeg \
       imagemagick \
       clamav \
       clamav-daemon
   
   # Install PHP extensions
   RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
       && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip sockets
   
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
   
   # Install Node.js dependencies for real-time features
   COPY package*.json ./
   RUN npm install
   
   # Set permissions
   RUN chown -R www-data:www-data /var/www
   RUN chmod -R 755 /var/www/storage
   RUN chmod -R 755 /var/www/bootstrap/cache
   
   # Create directories for communication
   RUN mkdir -p /var/www/storage/app/chat/files
   RUN mkdir -p /var/www/storage/app/chat/images
   RUN mkdir -p /var/www/storage/app/video/recordings
   RUN mkdir -p /var/www/storage/app/voice/recordings
   RUN mkdir -p /var/www/storage/app/temp
   
   # Configure supervisor
   COPY docker/supervisor/laravel-worker.conf /etc/supervisor/conf.d/
   COPY docker/supervisor/horizon.conf /etc/supervisor/conf.d/
   COPY docker/supervisor/reverb.conf /etc/supervisor/conf.d/
   
   # Configure ClamAV
   RUN freshclam
   
   EXPOSE 9000 8080
   CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
   ```

#### Criterios de Aceptación:
- [ ] Microservicio configurado y funcionando
- [ ] Docker container operativo
- [ ] WebSocket server funcionando
- [ ] Integración con servicios externos configurada

---

### 2. Modelos y Migraciones Base

**Responsable:** Backend Developer  
**Estimación:** 2 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear migración ChatRooms:**
   ```php
   // Migration: create_chat_rooms_table
   Schema::create('chat_rooms', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('school_id');
       
       // Información básica del chat
       $table->string('room_code')->unique();
       $table->string('name');
       $table->text('description')->nullable();
       $table->enum('room_type', [
           'direct', 'group', 'channel', 'announcement',
           'class', 'department', 'emergency', 'support'
       ]);
       
       // Configuración del chat
       $table->boolean('is_private')->default(false);
       $table->boolean('is_archived')->default(false);
       $table->boolean('allow_file_sharing')->default(true);
       $table->boolean('allow_voice_messages')->default(true);
       $table->boolean('allow_video_calls')->default(true);
       $table->integer('max_participants')->default(100);
       
       // Configuración de moderación
       $table->boolean('moderation_enabled')->default(false);
       $table->boolean('auto_moderation')->default(true);
       $table->json('moderation_rules')->nullable();
       $table->json('allowed_file_types')->nullable();
       $table->integer('file_size_limit')->default(10); // MB
       
       // Configuración de notificaciones
       $table->enum('notification_level', ['all', 'mentions', 'none'])->default('all');
       $table->boolean('push_notifications')->default(true);
       $table->boolean('email_notifications')->default(false);
       
       // Configuración de retención
       $table->integer('message_retention_days')->nullable();
       $table->boolean('auto_delete_files')->default(false);
       $table->integer('file_retention_days')->nullable();
       
       // Metadatos
       $table->json('room_settings')->nullable();
       $table->string('room_avatar')->nullable();
       $table->string('room_color')->default('#007bff');
       $table->json('custom_fields')->nullable();
       
       // Estadísticas
       $table->integer('total_messages')->default(0);
       $table->integer('total_participants')->default(0);
       $table->timestamp('last_activity_at')->nullable();
       $table->timestamp('last_message_at')->nullable();
       
       // Fechas importantes
       $table->timestamp('archived_at')->nullable();
       $table->unsignedBigInteger('archived_by')->nullable();
       
       $table->unsignedBigInteger('created_by');
       $table->unsignedBigInteger('updated_by')->nullable();
       $table->timestamps();
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('created_by')->references('id')->on('users');
       $table->foreign('updated_by')->references('id')->on('users');
       $table->foreign('archived_by')->references('id')->on('users');
       
       $table->index(['school_id', 'room_type']);
       $table->index(['is_private', 'is_archived']);
       $table->index(['last_activity_at']);
       $table->index(['room_code']);
   });
   ```

2. **Crear migración ChatParticipants:**
   ```php
   // Migration: create_chat_participants_table
   Schema::create('chat_participants', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('chat_room_id');
       $table->unsignedBigInteger('user_id');
       
       // Rol en el chat
       $table->enum('role', ['owner', 'admin', 'moderator', 'member', 'guest'])->default('member');
       $table->json('permissions')->nullable(); // Permisos específicos
       
       // Estado del participante
       $table->enum('status', ['active', 'muted', 'banned', 'left'])->default('active');
       $table->timestamp('joined_at');
       $table->timestamp('left_at')->nullable();
       $table->timestamp('last_seen_at')->nullable();
       $table->timestamp('last_read_at')->nullable();
       
       // Configuración personal
       $table->boolean('notifications_enabled')->default(true);
       $table->enum('notification_level', ['all', 'mentions', 'none'])->default('all');
       $table->boolean('sound_enabled')->default(true);
       $table->string('custom_nickname')->nullable();
       
       // Moderación
       $table->timestamp('muted_until')->nullable();
       $table->unsignedBigInteger('muted_by')->nullable();
       $table->text('mute_reason')->nullable();
       $table->timestamp('banned_until')->nullable();
       $table->unsignedBigInteger('banned_by')->nullable();
       $table->text('ban_reason')->nullable();
       
       // Estadísticas
       $table->integer('messages_sent')->default(0);
       $table->integer('files_shared')->default(0);
       $table->integer('reactions_given')->default(0);
       $table->timestamp('first_message_at')->nullable();
       $table->timestamp('last_message_at')->nullable();
       
       $table->unsignedBigInteger('added_by')->nullable();
       $table->timestamps();
       
       $table->foreign('chat_room_id')->references('id')->on('chat_rooms')->onDelete('cascade');
       $table->foreign('user_id')->references('id')->on('users');
       $table->foreign('muted_by')->references('id')->on('users');
       $table->foreign('banned_by')->references('id')->on('users');
       $table->foreign('added_by')->references('id')->on('users');
       
       $table->unique(['chat_room_id', 'user_id']);
       $table->index(['user_id', 'status']);
       $table->index(['chat_room_id', 'role']);
       $table->index(['last_seen_at']);
   });
   ```

3. **Crear migración ChatMessages:**
   ```php
   // Migration: create_chat_messages_table
   Schema::create('chat_messages', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('chat_room_id');
       $table->unsignedBigInteger('sender_id');
       $table->unsignedBigInteger('reply_to_id')->nullable();
       $table->unsignedBigInteger('thread_id')->nullable();
       
       // Contenido del mensaje
       $table->enum('message_type', [
           'text', 'image', 'file', 'voice', 'video',
           'location', 'contact', 'poll', 'system',
           'call_start', 'call_end', 'call_missed'
       ]);
       $table->longText('content')->nullable();
       $table->text('formatted_content')->nullable(); // HTML formatted
       $table->json('metadata')->nullable(); // Metadata específico del tipo
       
       // Archivos adjuntos
       $table->json('attachments')->nullable();
       $table->string('file_path')->nullable();
       $table->string('file_name')->nullable();
       $table->string('file_type')->nullable();
       $table->integer('file_size')->nullable();
       $table->string('thumbnail_path')->nullable();
       
       // Mensaje de voz/video
       $table->integer('duration')->nullable(); // Segundos
       $table->string('transcription')->nullable();
       $table->boolean('auto_transcribed')->default(false);
       
       // Estado del mensaje
       $table->enum('status', ['sent', 'delivered', 'read', 'failed', 'deleted'])->default('sent');
       $table->boolean('is_edited')->default(false);
       $table->timestamp('edited_at')->nullable();
       $table->boolean('is_pinned')->default(false);
       $table->timestamp('pinned_at')->nullable();
       $table->unsignedBigInteger('pinned_by')->nullable();
       
       // Moderación
       $table->boolean('is_flagged')->default(false);
       $table->boolean('is_moderated')->default(false);
       $table->enum('moderation_status', ['pending', 'approved', 'rejected', 'auto_approved'])->nullable();
       $table->text('moderation_reason')->nullable();
       $table->unsignedBigInteger('moderated_by')->nullable();
       $table->timestamp('moderated_at')->nullable();
       
       // Reacciones y interacciones
       $table->json('reactions')->nullable(); // {"👍": [user_ids], "❤️": [user_ids]}
       $table->integer('reaction_count')->default(0);
       $table->boolean('allow_reactions')->default(true);
       
       // Menciones y hashtags
       $table->json('mentions')->nullable(); // User IDs mencionados
       $table->json('hashtags')->nullable();
       $table->json('links')->nullable(); // URLs detectadas
       
       // Entrega y lectura
       $table->timestamp('delivered_at')->nullable();
       $table->json('read_by')->nullable(); // {user_id: timestamp}
       $table->integer('read_count')->default(0);
       
       // Programación
       $table->timestamp('scheduled_at')->nullable();
       $table->boolean('is_scheduled')->default(false);
       
       // Encriptación
       $table->boolean('is_encrypted')->default(false);
       $table->string('encryption_key_id')->nullable();
       
       $table->timestamps();
       $table->softDeletes();
       
       $table->foreign('chat_room_id')->references('id')->on('chat_rooms');
       $table->foreign('sender_id')->references('id')->on('users');
       $table->foreign('reply_to_id')->references('id')->on('chat_messages');
       $table->foreign('thread_id')->references('id')->on('chat_messages');
       $table->foreign('pinned_by')->references('id')->on('users');
       $table->foreign('moderated_by')->references('id')->on('users');
       
       $table->index(['chat_room_id', 'created_at']);
       $table->index(['sender_id', 'created_at']);
       $table->index(['message_type', 'status']);
       $table->index(['is_flagged', 'moderation_status']);
       $table->index(['scheduled_at', 'is_scheduled']);
       $table->fullText(['content', 'formatted_content']);
   });
   ```

4. **Crear migración VideoCalls:**
   ```php
   // Migration: create_video_calls_table
   Schema::create('video_calls', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('school_id');
       $table->unsignedBigInteger('chat_room_id')->nullable();
       
       // Información básica de la llamada
       $table->string('call_id')->unique();
       $table->string('session_id')->nullable(); // ID de sesión del proveedor
       $table->string('room_name');
       $table->text('description')->nullable();
       
       // Tipo y configuración
       $table->enum('call_type', ['audio', 'video', 'screen_share', 'webinar']);
       $table->enum('call_mode', ['direct', 'group', 'broadcast', 'meeting']);
       $table->boolean('is_scheduled')->default(false);
       $table->timestamp('scheduled_start')->nullable();
       $table->timestamp('scheduled_end')->nullable();
       
       // Configuración de la llamada
       $table->integer('max_participants')->default(50);
       $table->boolean('recording_enabled')->default(false);
       $table->boolean('auto_record')->default(false);
       $table->boolean('screen_sharing_enabled')->default(true);
       $table->boolean('chat_enabled')->default(true);
       $table->boolean('mute_on_join')->default(false);
       $table->boolean('camera_on_join')->default(true);
       
       // Configuración de acceso
       $table->boolean('require_password')->default(false);
       $table->string('password')->nullable();
       $table->boolean('waiting_room_enabled')->default(false);
       $table->boolean('allow_guests')->default(false);
       $table->json('allowed_domains')->nullable();
       
       // Estado de la llamada
       $table->enum('status', [
           'scheduled', 'waiting', 'active', 'ended',
           'cancelled', 'failed', 'recording'
       ])->default('scheduled');
       $table->timestamp('started_at')->nullable();
       $table->timestamp('ended_at')->nullable();
       $table->integer('duration')->nullable(); // Segundos
       
       // Grabación
       $table->string('recording_url')->nullable();
       $table->string('recording_path')->nullable();
       $table->integer('recording_size')->nullable(); // Bytes
       $table->integer('recording_duration')->nullable(); // Segundos
       $table->enum('recording_status', [
           'not_recorded', 'recording', 'processing',
           'completed', 'failed', 'deleted'
       ])->default('not_recorded');
       
       // Estadísticas
       $table->integer('total_participants')->default(0);
       $table->integer('max_concurrent_participants')->default(0);
       $table->json('participant_stats')->nullable();
       $table->json('quality_stats')->nullable();
       $table->decimal('avg_connection_quality', 3, 2)->nullable();
       
       // Configuración técnica
       $table->string('provider')->default('agora'); // agora, twilio, jitsi
       $table->json('provider_config')->nullable();
       $table->string('server_region')->nullable();
       $table->enum('video_quality', ['SD', 'HD', 'FHD', '4K'])->default('HD');
       $table->integer('bandwidth_limit')->nullable(); // Kbps
       
       // Moderación
       $table->unsignedBigInteger('host_id');
       $table->json('moderators')->nullable(); // User IDs
       $table->boolean('host_controls_enabled')->default(true);
       $table->boolean('participant_controls_enabled')->default(true);
       
       // Notificaciones
       $table->boolean('send_reminders')->default(true);
       $table->json('reminder_times')->nullable(); // [15, 5] minutos antes
       $table->boolean('send_recording_notification')->default(true);
       
       $table->unsignedBigInteger('created_by');
       $table->timestamps();
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('chat_room_id')->references('id')->on('chat_rooms');
       $table->foreign('host_id')->references('id')->on('users');
       $table->foreign('created_by')->references('id')->on('users');
       
       $table->index(['school_id', 'status']);
       $table->index(['scheduled_start', 'scheduled_end']);
       $table->index(['call_type', 'call_mode']);
       $table->index(['host_id', 'created_at']);
   });
   ```

5. **Crear migración BulkMessages:**
   ```php
   // Migration: create_bulk_messages_table
   Schema::create('bulk_messages', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('school_id');
       
       // Información básica
       $table->string('campaign_code')->unique();
       $table->string('name');
       $table->text('description')->nullable();
       $table->enum('message_type', [
           'announcement', 'emergency', 'reminder',
           'marketing', 'survey', 'notification'
       ]);
       
       // Contenido del mensaje
       $table->string('subject')->nullable();
       $table->longText('content');
       $table->text('formatted_content')->nullable();
       $table->json('attachments')->nullable();
       $table->string('template_id')->nullable();
       
       // Canales de envío
       $table->json('channels'); // ['email', 'sms', 'push', 'whatsapp']
       $table->json('channel_config')->nullable(); // Configuración por canal
       
       // Audiencia objetivo
       $table->enum('audience_type', [
           'all_users', 'role_based', 'custom_list',
           'segment', 'class', 'department'
       ]);
       $table->json('audience_criteria')->nullable();
       $table->json('recipient_list')->nullable(); // IDs específicos
       $table->integer('estimated_recipients')->default(0);
       
       // Programación
       $table->boolean('is_scheduled')->default(false);
       $table->timestamp('scheduled_at')->nullable();
       $table->string('timezone')->default('America/Bogota');
       $table->boolean('respect_quiet_hours')->default(true);
       $table->time('quiet_hours_start')->default('22:00:00');
       $table->time('quiet_hours_end')->default('08:00:00');
       
       // Configuración de envío
       $table->integer('batch_size')->default(100);
       $table->integer('rate_limit')->default(1000); // Por hora
       $table->boolean('personalization_enabled')->default(false);
       $table->json('personalization_fields')->nullable();
       
       // Estado de la campaña
       $table->enum('status', [
           'draft', 'scheduled', 'sending', 'sent',
           'paused', 'cancelled', 'failed'
       ])->default('draft');
       $table->timestamp('started_at')->nullable();
       $table->timestamp('completed_at')->nullable();
       $table->text('failure_reason')->nullable();
       
       // Estadísticas de envío
       $table->integer('total_recipients')->default(0);
       $table->integer('sent_count')->default(0);
       $table->integer('delivered_count')->default(0);
       $table->integer('failed_count')->default(0);
       $table->integer('opened_count')->default(0);
       $table->integer('clicked_count')->default(0);
       $table->integer('unsubscribed_count')->default(0);
       
       // Métricas por canal
       $table->json('channel_stats')->nullable();
       $table->decimal('delivery_rate', 5, 2)->nullable();
       $table->decimal('open_rate', 5, 2)->nullable();
       $table->decimal('click_rate', 5, 2)->nullable();
       
       // Configuración de seguimiento
       $table->boolean('track_opens')->default(true);
       $table->boolean('track_clicks')->default(true);
       $table->boolean('track_unsubscribes')->default(true);
       $table->string('tracking_domain')->nullable();
       
       // A/B Testing
       $table->boolean('ab_test_enabled')->default(false);
       $table->json('ab_test_config')->nullable();
       $table->string('winning_variant')->nullable();
       
       // Aprobación
       $table->boolean('requires_approval')->default(false);
       $table->enum('approval_status', [
           'pending', 'approved', 'rejected'
       ])->nullable();
       $table->unsignedBigInteger('approved_by')->nullable();
       $table->timestamp('approved_at')->nullable();
       $table->text('approval_notes')->nullable();
       
       $table->unsignedBigInteger('created_by');
       $table->unsignedBigInteger('updated_by')->nullable();
       $table->timestamps();
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('created_by')->references('id')->on('users');
       $table->foreign('updated_by')->references('id')->on('users');
       $table->foreign('approved_by')->references('id')->on('users');
       
       $table->index(['school_id', 'status']);
       $table->index(['message_type', 'audience_type']);
       $table->index(['scheduled_at', 'is_scheduled']);
       $table->index(['approval_status', 'requires_approval']);
   });
   ```

#### Criterios de Aceptación:
- [ ] Migraciones ejecutadas correctamente
- [ ] Modelos implementados con relaciones
- [ ] Índices de base de datos optimizados
- [ ] Estructura de datos de comunicación completa

---

### 3. Implementación de Servicios Core

**Responsable:** Backend Developer Senior  
**Estimación:** 4 días  
**Prioridad:** Alta

#### Subtareas:

1. **Implementar ChatService:**
   ```php
   class ChatService
   {
       private $messageEncryptionService;
       private $moderationService;
       private $notificationService;
       
       public function __construct(
           MessageEncryptionService $messageEncryptionService,
           ModerationService $moderationService,
           NotificationService $notificationService
       ) {
           $this->messageEncryptionService = $messageEncryptionService;
           $this->moderationService = $moderationService;
           $this->notificationService = $notificationService;
       }
       
       public function sendMessage(array $data)
       {
           DB::beginTransaction();
           
           try {
               $chatRoom = ChatRoom::findOrFail($data['chat_room_id']);
               $sender = User::findOrFail($data['sender_id']);
               
               // Verificar permisos
               $this->validateSendPermissions($chatRoom, $sender);
               
               // Procesar contenido
               $processedContent = $this->processMessageContent(
                   $data['content'],
                   $data['message_type']
               );
               
               // Crear mensaje
               $message = ChatMessage::create([
                   'chat_room_id' => $chatRoom->id,
                   'sender_id' => $sender->id,
                   'reply_to_id' => $data['reply_to_id'] ?? null,
                   'message_type' => $data['message_type'],
                   'content' => $processedContent['content'],
                   'formatted_content' => $processedContent['formatted_content'],
                   'metadata' => $processedContent['metadata'],
                   'attachments' => $data['attachments'] ?? null,
                   'mentions' => $processedContent['mentions'],
                   'hashtags' => $processedContent['hashtags'],
                   'links' => $processedContent['links'],
                   'is_encrypted' => config('communication.message_encryption', false)
               ]);
               
               // Encriptar si está habilitado
               if ($message->is_encrypted) {
                   $message = $this->messageEncryptionService->encryptMessage($message);
               }
               
               // Moderar contenido
               if ($chatRoom->auto_moderation) {
                   $moderationResult = $this->moderationService->moderateMessage($message);
                   $message->update([
                       'moderation_status' => $moderationResult['status'],
                       'moderation_reason' => $moderationResult['reason'] ?? null
                   ]);
               }
               
               // Actualizar estadísticas del chat
               $chatRoom->increment('total_messages');
               $chatRoom->update(['last_message_at' => now()]);
               
               // Actualizar estadísticas del participante
               $participant = ChatParticipant::where('chat_room_id', $chatRoom->id)
                   ->where('user_id', $sender->id)
                   ->first();
               
               if ($participant) {
                   $participant->increment('messages_sent');
                   $participant->update(['last_message_at' => now()]);
               }
               
               DB::commit();
               
               // Enviar en tiempo real
               $this->broadcastMessage($message);
               
               // Enviar notificaciones
               $this->sendMessageNotifications($message);
               
               return $message->load(['sender', 'replyTo', 'attachments']);
               
           } catch (\Exception $e) {
               DB::rollback();
               throw $e;
           }
       }
       
       public function createChatRoom(array $data)
       {
           DB::beginTransaction();
           
           try {
               $chatRoom = ChatRoom::create([
                   'school_id' => $data['school_id'],
                   'room_code' => $this->generateRoomCode(),
                   'name' => $data['name'],
                   'description' => $data['description'] ?? null,
                   'room_type' => $data['room_type'],
                   'is_private' => $data['is_private'] ?? false,
                   'max_participants' => $data['max_participants'] ?? 100,
                   'allow_file_sharing' => $data['allow_file_sharing'] ?? true,
                   'allow_voice_messages' => $data['allow_voice_messages'] ?? true,
                   'allow_video_calls' => $data['allow_video_calls'] ?? true,
                   'moderation_enabled' => $data['moderation_enabled'] ?? false,
                   'auto_moderation' => $data['auto_moderation'] ?? true,
                   'room_settings' => $data['room_settings'] ?? null,
                   'created_by' => auth()->id()
               ]);
               
               // Agregar creador como owner
               ChatParticipant::create([
                   'chat_room_id' => $chatRoom->id,
                   'user_id' => auth()->id(),
                   'role' => 'owner',
                   'joined_at' => now()
               ]);
               
               // Agregar participantes iniciales
               if (isset($data['participants'])) {
                   foreach ($data['participants'] as $participantData) {
                       $this->addParticipant(
                           $chatRoom->id,
                           $participantData['user_id'],
                           $participantData['role'] ?? 'member'
                       );
                   }
               }
               
               DB::commit();
               
               // Enviar notificación de creación
               $this->notifyRoomCreation($chatRoom);
               
               return $chatRoom->load(['participants.user', 'creator']);
               
           } catch (\Exception $e) {
               DB::rollback();
               throw $e;
           }
       }
       
       public function addParticipant($chatRoomId, $userId, $role = 'member')
       {
           $chatRoom = ChatRoom::findOrFail($chatRoomId);
           $user = User::findOrFail($userId);
           
           // Verificar si ya es participante
           $existingParticipant = ChatParticipant::where('chat_room_id', $chatRoomId)
               ->where('user_id', $userId)
               ->first();
               
           if ($existingParticipant) {
               if ($existingParticipant->status === 'left') {
                   // Reactivar participante
                   $existingParticipant->update([
                       'status' => 'active',
                       'joined_at' => now(),
                       'left_at' => null
                   ]);
                   return $existingParticipant;
               }
               throw new \Exception('El usuario ya es participante del chat');
           }
           
           // Verificar límite de participantes
           $currentParticipants = ChatParticipant::where('chat_room_id', $chatRoomId)
               ->where('status', 'active')
               ->count();
               
           if ($currentParticipants >= $chatRoom->max_participants) {
               throw new \Exception('Se ha alcanzado el límite máximo de participantes');
           }
           
           $participant = ChatParticipant::create([
               'chat_room_id' => $chatRoomId,
               'user_id' => $userId,
               'role' => $role,
               'joined_at' => now(),
               'added_by' => auth()->id()
           ]);
           
           // Actualizar contador de participantes
           $chatRoom->increment('total_participants');
           
           // Enviar mensaje de sistema
           $this->sendSystemMessage(
               $chatRoomId,
               "{$user->name} se ha unido al chat",
               'user_joined'
           );
           
           // Notificar al usuario
           $this->notificationService->sendNotification([
               'user_id' => $userId,
               'type' => 'chat_invitation',
               'title' => 'Invitación a chat',
               'message' => "Has sido agregado al chat: {$chatRoom->name}",
               'data' => ['chat_room_id' => $chatRoomId]
           ]);
           
           return $participant->load('user');
       }
       
       public function markAsRead($chatRoomId, $userId, $messageId = null)
       {
           $participant = ChatParticipant::where('chat_room_id', $chatRoomId)
               ->where('user_id', $userId)
               ->firstOrFail();
               
           $readTimestamp = now();
           
           if ($messageId) {
               // Marcar mensaje específico como leído
               $message = ChatMessage::findOrFail($messageId);
               $readBy = $message->read_by ?? [];
               $readBy[$userId] = $readTimestamp->toISOString();
               
               $message->update([
                   'read_by' => $readBy,
                   'read_count' => count($readBy)
               ]);
           } else {
               // Marcar todos los mensajes como leídos
               $participant->update(['last_read_at' => $readTimestamp]);
           }
           
           // Broadcast read receipt
           broadcast(new MessageRead($chatRoomId, $userId, $messageId, $readTimestamp));
           
           return true;
       }
       
       public function getUnreadCount($userId)
       {
           $participations = ChatParticipant::where('user_id', $userId)
               ->where('status', 'active')
               ->with('chatRoom')
               ->get();
               
           $unreadCounts = [];
           
           foreach ($participations as $participation) {
               $lastReadAt = $participation->last_read_at ?? $participation->joined_at;
               
               $unreadCount = ChatMessage::where('chat_room_id', $participation->chat_room_id)
                   ->where('sender_id', '!=', $userId)
                   ->where('created_at', '>', $lastReadAt)
                   ->where('status', '!=', 'deleted')
                   ->count();
                   
               $unreadCounts[$participation->chat_room_id] = $unreadCount;
           }
           
           return $unreadCounts;
       }
       
       public function searchMessages($chatRoomId, $query, $filters = [])
       {
           $messagesQuery = ChatMessage::where('chat_room_id', $chatRoomId)
               ->where('status', '!=', 'deleted');
               
           // Búsqueda de texto completo
           if ($query) {
               $messagesQuery->whereFullText(['content', 'formatted_content'], $query);
           }
           
           // Filtros adicionales
           if (isset($filters['message_type'])) {
               $messagesQuery->where('message_type', $filters['message_type']);
           }
           
           if (isset($filters['sender_id'])) {
               $messagesQuery->where('sender_id', $filters['sender_id']);
           }
           
           if (isset($filters['date_from'])) {
               $messagesQuery->where('created_at', '>=', $filters['date_from']);
           }
           
           if (isset($filters['date_to'])) {
               $messagesQuery->where('created_at', '<=', $filters['date_to']);
           }
           
           if (isset($filters['has_attachments']) && $filters['has_attachments']) {
               $messagesQuery->whereNotNull('attachments');
           }
           
           return $messagesQuery->with(['sender', 'replyTo', 'attachments'])
               ->orderBy('created_at', 'desc')
               ->paginate(50);
       }
       
       private function processMessageContent($content, $messageType)
       {
           $processed = [
               'content' => $content,
               'formatted_content' => null,
               'metadata' => [],
               'mentions' => [],
               'hashtags' => [],
               'links' => []
           ];
           
           if ($messageType === 'text') {
               // Detectar menciones (@usuario)
               preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $mentions);
               if (!empty($mentions[1])) {
                   $usernames = $mentions[1];
                   $users = User::whereIn('username', $usernames)->get();
                   $processed['mentions'] = $users->pluck('id')->toArray();
                   
                   // Reemplazar menciones en contenido formateado
                   $formattedContent = $content;
                   foreach ($users as $user) {
                       $formattedContent = str_replace(
                           '@' . $user->username,
                           '<span class="mention" data-user-id="' . $user->id . '">@' . $user->username . '</span>',
                           $formattedContent
                       );
                   }
                   $processed['formatted_content'] = $formattedContent;
               }
               
               // Detectar hashtags
               preg_match_all('/#([a-zA-Z0-9_]+)/', $content, $hashtags);
               if (!empty($hashtags[1])) {
                   $processed['hashtags'] = $hashtags[1];
               }
               
               // Detectar URLs
               preg_match_all('/https?:\/\/[^\s]+/', $content, $links);
               if (!empty($links[0])) {
                   $processed['links'] = $links[0];
               }
           }
           
           return $processed;
       }
       
       private function broadcastMessage(ChatMessage $message)
       {
           $messageData = [
               'id' => $message->id,
               'chat_room_id' => $message->chat_room_id,
               'sender' => $message->sender->only(['id', 'name', 'avatar']),
               'message_type' => $message->message_type,
               'content' => $message->content,
               'formatted_content' => $message->formatted_content,
               'attachments' => $message->attachments,
               'mentions' => $message->mentions,
               'created_at' => $message->created_at,
               'reply_to' => $message->replyTo ? [
                   'id' => $message->replyTo->id,
                   'content' => Str::limit($message->replyTo->content, 100),
                   'sender_name' => $message->replyTo->sender->name
               ] : null
           ];
           
           broadcast(new MessageSent($message->chat_room_id, $messageData));
       }
       
       private function sendMessageNotifications(ChatMessage $message)
       {
           $chatRoom = $message->chatRoom;
           $sender = $message->sender;
           
           // Obtener participantes que deben recibir notificación
           $participants = ChatParticipant::where('chat_room_id', $chatRoom->id)
               ->where('user_id', '!=', $sender->id)
               ->where('status', 'active')
               ->where('notifications_enabled', true)
               ->with('user')
               ->get();
               
           foreach ($participants as $participant) {
               $shouldNotify = false;
               
               switch ($participant->notification_level) {
                   case 'all':
                       $shouldNotify = true;
                       break;
                   case 'mentions':
                       $shouldNotify = in_array($participant->user_id, $message->mentions ?? []);
                       break;
                   case 'none':
                       $shouldNotify = false;
                       break;
               }
               
               if ($shouldNotify) {
                   $this->notificationService->sendNotification([
                       'user_id' => $participant->user_id,
                       'type' => 'chat_message',
                       'title' => $chatRoom->name,
                       'message' => "{$sender->name}: " . Str::limit($message->content, 100),
                       'data' => [
                           'chat_room_id' => $chatRoom->id,
                           'message_id' => $message->id
                       ]
                   ]);
               }
           }
       }
       
       private function generateRoomCode()
       {
           do {
               $code = 'CHAT-' . strtoupper(Str::random(8));
           } while (ChatRoom::where('room_code', $code)->exists());
           
           return $code;
       }
   }
   ```

2. **Implementar VideoCallService:**
   ```php
   class VideoCallService
   {
       private $agoraService;
       private $twilioService;
       private $recordingService;
       
       public function __construct(
           AgoraService $agoraService,
           TwilioService $twilioService,
           RecordingService $recordingService
       ) {
           $this->agoraService = $agoraService;
           $this->twilioService = $twilioService;
           $this->recordingService = $recordingService;
       }
       
       public function createVideoCall(array $data)
       {
           DB::beginTransaction();
           
           try {
               $videoCall = VideoCall::create([
                   'school_id' => $data['school_id'],
                   'chat_room_id' => $data['chat_room_id'] ?? null,
                   'call_id' => $this->generateCallId(),
                   'room_name' => $data['room_name'],
                   'description' => $data['description'] ?? null,
                   'call_type' => $data['call_type'],
                   'call_mode' => $data['call_mode'],
                   'is_scheduled' => $data['is_scheduled'] ?? false,
                   'scheduled_start' => $data['scheduled_start'] ?? null,
                   'scheduled_end' => $data['scheduled_end'] ?? null,
                   'max_participants' => $data['max_participants'] ?? 50,
                   'recording_enabled' => $data['recording_enabled'] ?? false,
                   'auto_record' => $data['auto_record'] ?? false,
                   'screen_sharing_enabled' => $data['screen_sharing_enabled'] ?? true,
                   'chat_enabled' => $data['chat_enabled'] ?? true,
                   'mute_on_join' => $data['mute_on_join'] ?? false,
                   'camera_on_join' => $data['camera_on_join'] ?? true,
                   'require_password' => $data['require_password'] ?? false,
                   'password' => $data['password'] ?? null,
                   'waiting_room_enabled' => $data['waiting_room_enabled'] ?? false,
                   'allow_guests' => $data['allow_guests'] ?? false,
                   'provider' => config('communication.video_provider', 'agora'),
                   'video_quality' => $data['video_quality'] ?? 'HD',
                   'host_id' => $data['host_id'],
                   'moderators' => $data['moderators'] ?? null,
                   'send_reminders' => $data['send_reminders'] ?? true,
                   'reminder_times' => $data['reminder_times'] ?? [15, 5],
                   'created_by' => auth()->id()
               ]);
               
               // Crear sesión en el proveedor
               $sessionData = $this->createProviderSession($videoCall);
               $videoCall->update([
                   'session_id' => $sessionData['session_id'],
                   'provider_config' => $sessionData['config']
               ]);
               
               DB::commit();
               
               // Enviar invitaciones si está programado
               if ($videoCall->is_scheduled) {
                   $this->scheduleReminders($videoCall);
               }
               
               return $videoCall;
               
           } catch (\Exception $e) {
               DB::rollback();
               throw $e;
           }
       }
       
       public function joinVideoCall($callId, $userId, array $options = [])
       {
           $videoCall = VideoCall::where('call_id', $callId)->firstOrFail();
           $user = User::findOrFail($userId);
           
           // Verificar permisos de acceso
           $this->validateJoinPermissions($videoCall, $user);
           
           // Verificar límite de participantes
           $currentParticipants = $this->getCurrentParticipantCount($videoCall);
           if ($currentParticipants >= $videoCall->max_participants) {
               throw new \Exception('Se ha alcanzado el límite máximo de participantes');
           }
           
           // Generar token de acceso
           $accessToken = $this->generateAccessToken($videoCall, $user, $options);
           
           // Registrar participación
           $this->recordParticipation($videoCall, $user, 'joined');
           
           // Iniciar llamada si es la primera vez
           if ($videoCall->status === 'scheduled' || $videoCall->status === 'waiting') {
               $this->startVideoCall($videoCall);
           }
           
           return [
               'call_id' => $videoCall->call_id,
               'session_id' => $videoCall->session_id,
               'access_token' => $accessToken,
               'server_config' => $this->getServerConfig($videoCall),
               'user_config' => [
                   'user_id' => $user->id,
                   'display_name' => $user->name,
                   'avatar' => $user->avatar,
                   'role' => $this->getUserRole($videoCall, $user),
                   'permissions' => $this->getUserPermissions($videoCall, $user)
               ],
               'call_config' => [
                   'video_enabled' => $videoCall->camera_on_join,
                   'audio_enabled' => !$videoCall->mute_on_join,
                   'screen_sharing_enabled' => $videoCall->screen_sharing_enabled,
                   'chat_enabled' => $videoCall->chat_enabled,
                   'recording_enabled' => $videoCall->recording_enabled
               ]
           ];
       }
       
       public function leaveVideoCall($callId, $userId)
       {
           $videoCall = VideoCall::where('call_id', $callId)->firstOrFail();
           $user = User::findOrFail($userId);
           
           // Registrar salida
           $this->recordParticipation($videoCall, $user, 'left');
           
           // Verificar si debe terminar la llamada
           $remainingParticipants = $this->getCurrentParticipantCount($videoCall);
           
           if ($remainingParticipants === 0 || $user->id === $videoCall->host_id) {
               $this->endVideoCall($videoCall);
           }
           
           return true;
       }
       
       public function startRecording($callId, $userId)
       {
           $videoCall = VideoCall::where('call_id', $callId)->firstOrFail();
           $user = User::findOrFail($userId);
           
           // Verificar permisos
           if (!$this->canControlRecording($videoCall, $user)) {
               throw new \Exception('No tienes permisos para controlar la grabación');
           }
           
           if (!$videoCall->recording_enabled) {
               throw new \Exception('La grabación no está habilitada para esta llamada');
           }
           
           // Iniciar grabación en el proveedor
           $recordingData = $this->recordingService->startRecording(
               $videoCall->session_id,
               $videoCall->provider
           );
           
           $videoCall->update([
               'recording_status' => 'recording',
               'recording_url' => $recordingData['recording_url'] ?? null
           ]);
           
           // Notificar a participantes
           broadcast(new RecordingStarted($videoCall->call_id));
           
           return $recordingData;
       }
       
       public function stopRecording($callId, $userId)
       {
           $videoCall = VideoCall::where('call_id', $callId)->firstOrFail();
           $user = User::findOrFail($userId);
           
           // Verificar permisos
           if (!$this->canControlRecording($videoCall, $user)) {
               throw new \Exception('No tienes permisos para controlar la grabación');
           }
           
           // Detener grabación en el proveedor
           $recordingData = $this->recordingService->stopRecording(
               $videoCall->session_id,
               $videoCall->provider
           );
           
           $videoCall->update([
               'recording_status' => 'processing',
               'recording_path' => $recordingData['recording_path'] ?? null,
               'recording_size' => $recordingData['file_size'] ?? null,
               'recording_duration' => $recordingData['duration'] ?? null
           ]);
           
           // Notificar a participantes
           broadcast(new RecordingStopped($videoCall->call_id));
           
           // Procesar grabación de forma asíncrona
           ProcessVideoRecording::dispatch($videoCall->id);
           
           return $recordingData;
       }
       
       private function createProviderSession(VideoCall $videoCall)
       {
           switch ($videoCall->provider) {
               case 'agora':
                   return $this->agoraService->createSession([
                       'channel_name' => $videoCall->call_id,
                       'max_participants' => $videoCall->max_participants,
                       'recording_enabled' => $videoCall->recording_enabled
                   ]);
                   
               case 'twilio':
                   return $this->twilioService->createRoom([
                       'unique_name' => $videoCall->call_id,
                       'type' => $videoCall->call_mode === 'group' ? 'group' : 'peer-to-peer',
                       'max_participants' => $videoCall->max_participants,
                       'record_participants_on_connect' => $videoCall->auto_record
                   ]);
                   
               default:
                   throw new \Exception('Proveedor de video no soportado');
           }
       }
       
       private function generateAccessToken(VideoCall $videoCall, User $user, array $options)
       {
           $role = $this->getUserRole($videoCall, $user);
           $permissions = $this->getUserPermissions($videoCall, $user);
           
           switch ($videoCall->provider) {
               case 'agora':
                   return $this->agoraService->generateToken(
                       $videoCall->call_id,
                       $user->id,
                       $role,
                       3600 // 1 hora de validez
                   );
                   
               case 'twilio':
                   return $this->twilioService->generateAccessToken(
                       $videoCall->session_id,
                       $user->id,
                       $permissions
                   );
                   
               default:
                   throw new \Exception('Proveedor de video no soportado');
           }
       }
   }
   ```

3. **Implementar BulkMessageService:**
   ```php
   class BulkMessageService
   {
       private $notificationService;
       private $templateService;
       private $audienceService;
       
       public function createBulkMessage(array $data)
       {
           DB::beginTransaction();
           
           try {
               $bulkMessage = BulkMessage::create([
                   'school_id' => $data['school_id'],
                   'campaign_code' => $this->generateCampaignCode(),
                   'name' => $data['name'],
                   'description' => $data['description'] ?? null,
                   'message_type' => $data['message_type'],
                   'subject' => $data['subject'] ?? null,
                   'content' => $data['content'],
                   'formatted_content' => $this->formatContent($data['content']),
                   'attachments' => $data['attachments'] ?? null,
                   'channels' => $data['channels'],
                   'channel_config' => $data['channel_config'] ?? null,
                   'audience_type' => $data['audience_type'],
                   'audience_criteria' => $data['audience_criteria'] ?? null,
                   'recipient_list' => $data['recipient_list'] ?? null,
                   'is_scheduled' => $data['is_scheduled'] ?? false,
                   'scheduled_at' => $data['scheduled_at'] ?? null,
                   'batch_size' => $data['batch_size'] ?? 100,
                   'rate_limit' => $data['rate_limit'] ?? 1000,
                   'personalization_enabled' => $data['personalization_enabled'] ?? false,
                   'personalization_fields' => $data['personalization_fields'] ?? null,
                   'requires_approval' => $data['requires_approval'] ?? false,
                   'created_by' => auth()->id()
               ]);
               
               // Calcular audiencia estimada
               $estimatedRecipients = $this->calculateAudienceSize($bulkMessage);
               $bulkMessage->update(['estimated_recipients' => $estimatedRecipients]);
               
               DB::commit();
               
               return $bulkMessage;
               
           } catch (\Exception $e) {
               DB::rollback();
               throw $e;
           }
       }
       
       public function sendBulkMessage($bulkMessageId)
       {
           $bulkMessage = BulkMessage::findOrFail($bulkMessageId);
           
           // Verificar estado
           if ($bulkMessage->status !== 'draft' && $bulkMessage->status !== 'scheduled') {
               throw new \Exception('El mensaje no puede ser enviado en su estado actual');
           }
           
           // Verificar aprobación si es requerida
           if ($bulkMessage->requires_approval && $bulkMessage->approval_status !== 'approved') {
               throw new \Exception('El mensaje requiere aprobación antes de ser enviado');
           }
           
           // Obtener lista de destinatarios
           $recipients = $this->getRecipientList($bulkMessage);
           
           $bulkMessage->update([
               'status' => 'sending',
               'started_at' => now(),
               'total_recipients' => count($recipients)
           ]);
           
           // Enviar en lotes
           $batches = array_chunk($recipients, $bulkMessage->batch_size);
           
           foreach ($batches as $batch) {
               SendBulkMessageBatch::dispatch($bulkMessage->id, $batch)
                   ->delay(now()->addSeconds(count($batches) * 2)); // Rate limiting
           }
           
           return true;
       }
   }
   ```

#### Criterios de Aceptación:
- [ ] ChatService implementado con funcionalidades completas
- [ ] VideoCallService operativo con múltiples proveedores
- [ ] BulkMessageService funcionando correctamente
- [ ] Integración con servicios externos exitosa

---

### 4. API Endpoints

**Responsable:** Backend Developer  
**Estimación:** 2 días  
**Prioridad:** Alta

#### Endpoints de Chat:

```php
// Chat Rooms
POST   /api/chat/rooms                    // Crear sala de chat
GET    /api/chat/rooms                    // Listar salas de chat
GET    /api/chat/rooms/{id}               // Obtener sala específica
PUT    /api/chat/rooms/{id}               // Actualizar sala
DELETE /api/chat/rooms/{id}               // Eliminar sala
POST   /api/chat/rooms/{id}/archive       // Archivar sala
POST   /api/chat/rooms/{id}/unarchive     // Desarchivar sala

// Participants
POST   /api/chat/rooms/{id}/participants  // Agregar participante
DELETE /api/chat/rooms/{id}/participants/{userId} // Remover participante
PUT    /api/chat/rooms/{id}/participants/{userId}  // Actualizar rol
POST   /api/chat/rooms/{id}/participants/{userId}/mute   // Silenciar
POST   /api/chat/rooms/{id}/participants/{userId}/unmute // Desilenciar

// Messages
POST   /api/chat/rooms/{id}/messages      // Enviar mensaje
GET    /api/chat/rooms/{id}/messages      // Obtener mensajes
PUT    /api/chat/messages/{id}            // Editar mensaje
DELETE /api/chat/messages/{id}            // Eliminar mensaje
POST   /api/chat/messages/{id}/react      // Reaccionar a mensaje
POST   /api/chat/messages/{id}/pin        // Anclar mensaje
POST   /api/chat/messages/{id}/flag       // Reportar mensaje
POST   /api/chat/rooms/{id}/messages/read // Marcar como leído
GET    /api/chat/search                   // Buscar mensajes

// File Sharing
POST   /api/chat/upload                   // Subir archivo
GET    /api/chat/files/{id}               // Descargar archivo
DELETE /api/chat/files/{id}               // Eliminar archivo
```

#### Endpoints de Video Llamadas:

```php
// Video Calls
POST   /api/video/calls                   // Crear llamada
GET    /api/video/calls                   // Listar llamadas
GET    /api/video/calls/{id}              // Obtener llamada
PUT    /api/video/calls/{id}              // Actualizar llamada
DELETE /api/video/calls/{id}              // Cancelar llamada
POST   /api/video/calls/{id}/join         // Unirse a llamada
POST   /api/video/calls/{id}/leave        // Salir de llamada
POST   /api/video/calls/{id}/start        // Iniciar llamada
POST   /api/video/calls/{id}/end          // Terminar llamada

// Recording
POST   /api/video/calls/{id}/recording/start  // Iniciar grabación
POST   /api/video/calls/{id}/recording/stop   // Detener grabación
GET    /api/video/calls/{id}/recording        // Obtener grabación
DELETE /api/video/calls/{id}/recording       // Eliminar grabación

// Participants
GET    /api/video/calls/{id}/participants     // Listar participantes
POST   /api/video/calls/{id}/participants/{userId}/mute    // Silenciar
POST   /api/video/calls/{id}/participants/{userId}/unmute  // Desilenciar
POST   /api/video/calls/{id}/participants/{userId}/kick    // Expulsar
```

#### Endpoints de Mensajería Masiva:

```php
// Bulk Messages
POST   /api/bulk-messages                 // Crear campaña
GET    /api/bulk-messages                 // Listar campañas
GET    /api/bulk-messages/{id}            // Obtener campaña
PUT    /api/bulk-messages/{id}            // Actualizar campaña
DELETE /api/bulk-messages/{id}            // Eliminar campaña
POST   /api/bulk-messages/{id}/send       // Enviar campaña
POST   /api/bulk-messages/{id}/pause      // Pausar envío
POST   /api/bulk-messages/{id}/resume     // Reanudar envío
POST   /api/bulk-messages/{id}/cancel     // Cancelar envío
GET    /api/bulk-messages/{id}/stats      // Estadísticas
GET    /api/bulk-messages/{id}/recipients // Lista de destinatarios

// Approval
POST   /api/bulk-messages/{id}/approve    // Aprobar campaña
POST   /api/bulk-messages/{id}/reject     // Rechazar campaña

// Templates
GET    /api/message-templates             // Listar plantillas
POST   /api/message-templates             // Crear plantilla
GET    /api/message-templates/{id}        // Obtener plantilla
PUT    /api/message-templates/{id}        // Actualizar plantilla
DELETE /api/message-templates/{id}        // Eliminar plantilla
```

---

### 5. Frontend Components

**Responsable:** Frontend Developer  
**Estimación:** 3 días  
**Prioridad:** Media

#### Componentes de Chat:

1. **ChatRoomList** - Lista de salas de chat
2. **ChatRoom** - Interfaz principal del chat
3. **MessageList** - Lista de mensajes
4. **MessageInput** - Input para enviar mensajes
5. **FileUpload** - Componente para subir archivos
6. **EmojiPicker** - Selector de emojis
7. **ParticipantList** - Lista de participantes
8. **ChatSettings** - Configuración del chat

#### Componentes de Video Llamadas:

1. **VideoCallInterface** - Interfaz principal de video
2. **ParticipantGrid** - Grilla de participantes
3. **ControlPanel** - Controles de la llamada
4. **ScreenShare** - Compartir pantalla
5. **RecordingControls** - Controles de grabación
6. **ChatPanel** - Chat durante la llamada
7. **WaitingRoom** - Sala de espera

---

## Criterios de Aceptación

### Técnicos:
- [ ] Chat en tiempo real funcionando con WebSockets
- [ ] Video llamadas operativas con múltiples proveedores
- [ ] Mensajería masiva con segmentación de audiencia
- [ ] Sistema de moderación automática implementado
- [ ] Encriptación de mensajes habilitada
- [ ] Grabación de llamadas funcionando
- [ ] Notificaciones push integradas
- [ ] API REST completa documentada
- [ ] Interfaz de usuario responsive
- [ ] Pruebas unitarias > 80% cobertura

### Calidad:
- [ ] Latencia de mensajes < 500ms
- [ ] Calidad de video HD estable
- [ ] Soporte para 100+ participantes simultáneos
- [ ] Tiempo de conexión < 3 segundos
- [ ] Disponibilidad > 99.5%
- [ ] Seguridad de datos garantizada

### Negocio:
- [ ] Comunicación fluida entre usuarios
- [ ] Reducción del 60% en comunicación por email
- [ ] Mejora en colaboración escolar
- [ ] Facilidad de uso para todos los roles
- [ ] Cumplimiento de regulaciones de privacidad

## Riesgos Identificados

1. **Escalabilidad de WebSockets** - Riesgo Alto
   - Mitigación: Implementar clustering y load balancing

2. **Calidad de video en conexiones lentas** - Riesgo Medio
   - Mitigación: Adaptación automática de calidad

3. **Costos de ancho de banda** - Riesgo Medio
   - Mitigación: Optimización de codecs y compresión

4. **Moderación de contenido** - Riesgo Medio
   - Mitigación: IA para detección automática

5. **Privacidad y seguridad** - Riesgo Alto
   - Mitigación: Encriptación end-to-end

## Métricas de Éxito

- **Adopción:** > 80% de usuarios activos
- **Engagement:** > 50 mensajes por usuario/día
- **Satisfacción:** NPS > 8.0
- **Performance:** Latencia < 500ms
- **Disponibilidad:** > 99.5% uptime
- **Calidad:** < 2% llamadas con problemas

## Entregables

1. **Código fuente completo**
2. **Base de datos configurada**
3. **API documentada**
4. **Interfaz de usuario**
5. **Pruebas automatizadas**
6. **Documentación técnica**
7. **Manual de usuario**
8. **Configuración de despliegue**

## Variables de Entorno

```env
# Comunicación
COMMUNICATION_SERVICE_URL=http://localhost:8009
WEBSOCKET_SERVER_URL=ws://localhost:8080
REVERB_APP_ID=your_reverb_app_id
REVERB_APP_KEY=your_reverb_key
REVERB_APP_SECRET=your_reverb_secret

# Proveedores de Video
AGORA_APP_ID=your_agora_app_id
AGORA_APP_CERTIFICATE=your_agora_certificate
TWILIO_SID=your_twilio_sid
TWILIO_TOKEN=your_twilio_token
TWILIO_VIDEO_API_KEY=your_video_api_key

# Almacenamiento
AWS_BUCKET=wl-school-communication
FILE_MAX_SIZE=10MB
RECORDING_STORAGE=s3

# Configuración
CHAT_MESSAGE_RETENTION_DAYS=365
VIDEO_MAX_PARTICIPANTS=50
BULK_MESSAGE_BATCH_SIZE=100
MESSAGE_ENCRYPTION=true
AUTO_MODERATION=true
```

## Datos de Ejemplo

### Chat Room:
```json
{
  "id": 1,
  "school_id": 1,
  "room_code": "CHAT-ABC12345",
  "name": "Profesores - Matemáticas",
  "description": "Chat para coordinación del departamento de matemáticas",
  "room_type": "department",
  "is_private": true,
  "max_participants": 50,
  "allow_file_sharing": true,
  "allow_voice_messages": true,
  "allow_video_calls": true,
  "moderation_enabled": true,
  "auto_moderation": true,
  "notification_level": "all",
  "total_messages": 1247,
  "total_participants": 12,
  "last_activity_at": "2024-01-15T14:30:00Z",
  "created_by": 1
}
```

### Video Call:
```json
{
  "id": 1,
  "school_id": 1,
  "call_id": "CALL-XYZ98765",
  "room_name": "Reunión Padres de Familia - Grado 5A",
  "description": "Reunión trimestral con padres de familia",
  "call_type": "video",
  "call_mode": "meeting",
  "is_scheduled": true,
  "scheduled_start": "2024-01-20T15:00:00Z",
  "scheduled_end": "2024-01-20T16:30:00Z",
  "max_participants": 30,
  "recording_enabled": true,
  "auto_record": true,
  "require_password": true,
  "password": "reunion2024",
  "waiting_room_enabled": true,
  "status": "scheduled",
  "provider": "agora",
  "video_quality": "HD",
  "host_id": 5,
  "send_reminders": true,
  "reminder_times": [15, 5]
}
```

### Bulk Message:
```json
{
  "id": 1,
  "school_id": 1,
  "campaign_code": "BULK-DEF45678",
  "name": "Recordatorio Reunión de Padres",
  "description": "Recordatorio para la reunión de padres del próximo viernes",
  "message_type": "reminder",
  "subject": "Recordatorio: Reunión de Padres - Viernes 20 de Enero",
  "content": "Estimados padres de familia, les recordamos que el viernes 20 de enero a las 3:00 PM tendremos nuestra reunión trimestral...",
  "channels": ["email", "sms", "push", "whatsapp"],
  "audience_type": "role_based",
  "audience_criteria": {
    "roles": ["parent"],
    "grades": ["5A", "5B"]
  },
  "is_scheduled": true,
  "scheduled_at": "2024-01-18T09:00:00Z",
  "batch_size": 50,
  "rate_limit": 500,
  "estimated_recipients": 85,
  "status": "scheduled",
  "requires_approval": true,
  "approval_status": "approved"
}
```

## Preguntas para Retrospectiva

1. **Técnicas:**
   - ¿El sistema de chat en tiempo real cumple con los requisitos de latencia?
   - ¿Las video llamadas mantienen calidad estable con múltiples participantes?
   - ¿El sistema de moderación automática es efectivo?
   - ¿La integración con múltiples proveedores funciona correctamente?

2. **Funcionales:**
   - ¿Los usuarios pueden comunicarse efectivamente a través de todos los canales?
   - ¿La mensajería masiva llega a la audiencia correcta?
   - ¿Las grabaciones de llamadas se procesan correctamente?
   - ¿El sistema de notificaciones es apropiado?

3. **Rendimiento:**
   - ¿El sistema soporta la carga esperada de usuarios concurrentes?
   - ¿Los tiempos de respuesta son aceptables?
   - ¿El consumo de ancho de banda es optimizado?
   - ¿La escalabilidad horizontal funciona correctamente?

4. **Seguridad:**
   - ¿La encriptación de mensajes protege la privacidad?
   - ¿El sistema de moderación previene contenido inapropiado?
   - ¿Los controles de acceso funcionan correctamente?
   - ¿Se cumplen las regulaciones de protección de datos?

5. **Experiencia de Usuario:**
   - ¿La interfaz es intuitiva para todos los tipos de usuarios?
   - ¿La calidad de audio y video es satisfactoria?
   - ¿Las notificaciones son útiles sin ser intrusivas?
   - ¿Los usuarios adoptan fácilmente las nuevas funcionalidades?

6. **Integración:**
   - ¿La integración con otros microservicios es fluida?
   - ¿Los datos se sincronizan correctamente entre servicios?
   - ¿Las APIs externas responden de manera confiable?
   - ¿El sistema de colas maneja correctamente la carga?

7. **Mantenimiento:**
   - ¿El código es mantenible y bien documentado?
   - ¿Las pruebas automatizadas cubren los casos críticos?
   - ¿El monitoreo y logging son adecuados?
   - ¿Los procesos de despliegue son confiables?

8. **Negocio:**
   - ¿El sistema mejora la comunicación en la institución educativa?
   - ¿Se reduce la dependencia de herramientas externas?
   - ¿Los costos operativos están dentro del presupuesto?
   - ¿El ROI justifica la inversión en desarrollo?