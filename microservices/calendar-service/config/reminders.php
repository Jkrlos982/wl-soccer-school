<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reminder System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the automatic reminder
    | system including default reminder times, notification settings,
    | and processing limits.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Reminder Times (in minutes before event)
    |--------------------------------------------------------------------------
    */
    'default_reminders' => [
        'training' => [1440, 60], // 24 hours and 1 hour before
        'match' => [2880, 1440, 120], // 48 hours, 24 hours, and 2 hours before
        'tournament' => [10080, 2880, 1440], // 1 week, 48 hours, and 24 hours before
        'meeting' => [1440, 30], // 24 hours and 30 minutes before
        'payment' => [4320, 1440, 60], // 3 days, 24 hours, and 1 hour before
        'general' => [1440], // 24 hours before
    ],

    /*
    |--------------------------------------------------------------------------
    | Birthday Reminder Settings
    |--------------------------------------------------------------------------
    */
    'birthdays' => [
        'enabled' => env('BIRTHDAY_REMINDERS_ENABLED', true),
        'reminder_time' => '09:00', // Time to send birthday reminders
        'days_ahead' => 0, // Send on the actual birthday (0) or days before
        'create_events' => true, // Create calendar events for birthdays
        'event_duration' => 60, // Duration in minutes for birthday events
    ],

    /*
    |--------------------------------------------------------------------------
    | Processing Limits
    |--------------------------------------------------------------------------
    */
    'processing' => [
        'batch_size' => env('REMINDER_BATCH_SIZE', 100),
        'max_retries' => env('REMINDER_MAX_RETRIES', 3),
        'retry_delay' => env('REMINDER_RETRY_DELAY', 300), // 5 minutes
        'timeout' => env('REMINDER_TIMEOUT', 300), // 5 minutes
        'memory_limit' => env('REMINDER_MEMORY_LIMIT', '256M'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'connection' => env('REMINDER_QUEUE_CONNECTION', 'redis'),
        'name' => env('REMINDER_QUEUE_NAME', 'reminders'),
        'priority' => [
            'immediate' => 10,
            'urgent' => 5,
            'normal' => 1,
            'low' => 0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'channels' => ['mail', 'database'], // Available: mail, sms, push, database
        'fallback_channel' => 'database',
        'rate_limiting' => [
            'enabled' => true,
            'max_per_user_per_hour' => 10,
            'max_per_user_per_day' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Monitoring
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'failure_threshold' => env('REMINDER_FAILURE_THRESHOLD', 10), // Percentage
        'alert_email' => env('REMINDER_ALERT_EMAIL', env('MAIL_FROM_ADDRESS')),
        'consecutive_failures_limit' => 5,
        'health_check_interval' => 30, // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'stats_ttl' => 3600, // 1 hour
        'failure_rate_ttl' => 1800, // 30 minutes
        'user_limits_ttl' => 3600, // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('REMINDER_LOGGING_ENABLED', true),
        'level' => env('REMINDER_LOG_LEVEL', 'info'),
        'channels' => ['daily', 'slack'], // Log channels to use
        'retention_days' => env('REMINDER_LOG_RETENTION', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Settings
    |--------------------------------------------------------------------------
    */
    'templates' => [
        'default_language' => 'es',
        'fallback_template' => 'general_event_reminder',
        'variable_validation' => true,
        'cache_compiled' => env('TEMPLATE_CACHE_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    */
    'integrations' => [
        'google_calendar' => [
            'sync_reminders' => true,
            'create_google_reminders' => false, // Use our system instead
        ],
        'authentication_service' => [
            'timeout' => 30,
            'retries' => 3,
            'cache_user_data' => true,
            'cache_ttl' => 1800, // 30 minutes
        ],
    ],
];