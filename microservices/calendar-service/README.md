# WL School - Calendar Service

A Laravel-based microservice for managing school calendars with Google Calendar integration.

## Features

- **Calendar Management**: Create, update, and manage school calendars
- **Google Calendar Integration**: Bidirectional synchronization with Google Calendar
- **Event Management**: Full CRUD operations for calendar events
- **Real-time Sync**: Automatic synchronization with external calendars
- **Multi-tenant Support**: Support for multiple schools and users
- **Queue-based Processing**: Asynchronous calendar synchronization
- **Event Broadcasting**: Real-time notifications for sync completion

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Installation & Setup

### 1. Clone and Install Dependencies

```bash
git clone <repository-url>
cd calendar-service
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Database Setup

```bash
php artisan migrate
php artisan db:seed
```

### 3. Google Calendar API Setup

#### Step 1: Create Google Cloud Project
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the Google Calendar API

#### Step 2: Create OAuth 2.0 Credentials
1. Go to "Credentials" in the Google Cloud Console
2. Click "Create Credentials" > "OAuth 2.0 Client IDs"
3. Set application type to "Web application"
4. Add authorized redirect URIs:
   - `http://localhost:8000/auth/google/callback` (development)
   - `https://yourdomain.com/auth/google/callback` (production)

#### Step 3: Configure Environment Variables

Update your `.env` file with the Google Calendar credentials:

```env
GOOGLE_CALENDAR_CLIENT_ID=your_client_id_here
GOOGLE_CALENDAR_CLIENT_SECRET=your_client_secret_here
GOOGLE_CALENDAR_REDIRECT_URI=http://localhost:8000/auth/google/callback
GOOGLE_CALENDAR_APPLICATION_NAME="WL School Calendar Service"
GOOGLE_CALENDAR_SCOPES="https://www.googleapis.com/auth/calendar"

# Calendar Sync Configuration
CALENDAR_SYNC_ENABLED=true
CALENDAR_SYNC_INTERVAL=15
CALENDAR_MAX_SYNC_EVENTS=1000
```

### 4. Queue Configuration

For asynchronous calendar synchronization, configure your queue driver:

```env
QUEUE_CONNECTION=database
```

Then run the queue worker:

```bash
php artisan queue:work
```

### 5. Broadcasting Setup (Optional)

For real-time sync notifications, configure broadcasting:

```env
BROADCAST_CONNECTION=pusher
# Add your Pusher credentials
```

## API Endpoints

### Authentication
- `GET /api/auth/google` - Get Google OAuth authorization URL
- `GET /api/auth/google/callback` - Handle OAuth callback

### Calendar Management
- `POST /api/calendars/{calendar}/sync` - Sync calendar with Google
- `GET /api/calendars/{calendar}/status` - Get sync status
- `DELETE /api/calendars/{calendar}/disconnect` - Disconnect from Google
- `GET /api/calendars/{calendar}/google-calendars` - List Google calendars

### Events
- `GET /api/calendars/{calendar}/events` - List events
- `POST /api/calendars/{calendar}/events` - Create event
- `PUT /api/events/{event}` - Update event
- `DELETE /api/events/{event}` - Delete event

## Artisan Commands

### Calendar Synchronization

```bash
# Sync all calendars
php artisan calendar:sync

# Sync specific calendar
php artisan calendar:sync --calendar=1

# Force sync (ignore last sync time)
php artisan calendar:sync --force

# Dry run (preview changes)
php artisan calendar:sync --dry-run
```

## Queue Jobs

- `SyncCalendarJob` - Handles asynchronous calendar synchronization
- Automatically retries failed syncs with exponential backoff
- Sends notifications on completion/failure

## Events & Listeners

- `CalendarSyncCompleted` - Fired when sync completes (success or failure)
- `SendCalendarSyncNotification` - Handles sync completion notifications

## Configuration

All configuration is in `config/google-calendar.php`:

- OAuth credentials and scopes
- Sync settings (interval, batch size, timeout)
- Color mapping for Google Calendar
- Default timezone and reminder settings

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
