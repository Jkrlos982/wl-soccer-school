<?php

return [

    'default_auth_profile' => env('GOOGLE_CALENDAR_AUTH_PROFILE', 'oauth'),

    'auth_profiles' => [

        /*
         * Authenticate using a service account.
         */
        'service_account' => [
            /*
             * Path to the json file containing the credentials.
             */
            'credentials_json' => storage_path('app/google-calendar/service-account-credentials.json'),
        ],

        /*
         * Authenticate with actual google user account.
         */
        'oauth' => [
            /*
             * Path to the json file containing the oauth2 credentials.
             */
            'credentials_json' => storage_path('app/google-calendar/oauth-credentials.json'),

            /*
             * Path to the json file containing the oauth2 token.
             */
            'token_json' => storage_path('app/google-calendar/oauth-token.json'),
        ],
    ],

    /*
     *  The id of the Google Calendar that will be used by default.
     */
    'calendar_id' => env('GOOGLE_CALENDAR_ID'),

     /*
     *  The email address of the user account to impersonate.
     */
    'user_to_impersonate' => env('GOOGLE_CALENDAR_IMPERSONATE'),

    /*
    |--------------------------------------------------------------------------
    | Custom Google Calendar API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for custom Google Calendar API integration
    |
    */

    'client_id' => env('GOOGLE_CALENDAR_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CALENDAR_CLIENT_SECRET'),
    'redirect_uri' => env('GOOGLE_CALENDAR_REDIRECT_URI', 'http://localhost:8000/auth/google/callback'),
    'application_name' => env('GOOGLE_CALENDAR_APPLICATION_NAME', 'WL School Calendar Service'),
    'scopes' => explode(',', env('GOOGLE_CALENDAR_SCOPES', 'https://www.googleapis.com/auth/calendar')),

    /*
    |--------------------------------------------------------------------------
    | Sync Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for calendar synchronization
    |
    */

    'sync' => [
        'enabled' => env('CALENDAR_SYNC_ENABLED', true),
        'interval' => env('CALENDAR_SYNC_INTERVAL', 15), // minutes
        'max_events' => env('CALENDAR_MAX_SYNC_EVENTS', 1000),
        'batch_size' => 50,
        'timeout' => 30, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Color Mapping
    |--------------------------------------------------------------------------
    |
    | Mapping between hex colors and Google Calendar color IDs
    |
    */

    'color_mapping' => [
        '#a4bdfc' => '1',  // Lavender
        '#7ae7bf' => '2',  // Sage
        '#dbadff' => '3',  // Grape
        '#ff887c' => '4',  // Flamingo
        '#fbd75b' => '5',  // Banana
        '#ffb878' => '6',  // Tangerine
        '#46d6db' => '7',  // Peacock
        '#e1e1e1' => '8',  // Graphite
        '#5484ed' => '9',  // Blueberry
        '#51b749' => '10', // Basil
        '#dc2127' => '11', // Tomato
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for calendar integration
    |
    */

    'defaults' => [
        'timezone' => 'America/Bogota',
        'reminder_minutes' => [10, 30],
        'visibility' => 'default',
        'send_notifications' => true,
    ],
];
