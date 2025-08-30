#!/bin/bash
set -e

# Function to wait for database
wait_for_db() {
    echo "Waiting for database connection..."
    while ! mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent; do
        echo "Database is unavailable - sleeping"
        sleep 2
    done
    echo "Database is up - executing command"
}

# Function to run migrations
run_migrations() {
    echo "Running database migrations..."
    php artisan migrate --force
    echo "Migrations completed"
}

# Function to clear and cache config
setup_laravel() {
    echo "Setting up Laravel application..."
    
    # Clear all caches
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear
    
    # Cache configurations for production
    if [ "$APP_ENV" = "production" ]; then
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
    fi
    
    echo "Laravel setup completed"
}

# Function to seed permissions
seed_permissions() {
    echo "Seeding permissions..."
    php artisan db:seed --class=PermissionSeeder --force 2>/dev/null || echo "Permission seeder not found or already run"
    echo "Permissions seeding completed"
}

# Main execution
if [ "$1" = 'php-fpm' ] || [ "$1" = 'php' ]; then
    # Wait for database to be ready
    wait_for_db
    
    # Run migrations
    run_migrations
    
    # Setup Laravel
    setup_laravel
    
    # Seed permissions
    seed_permissions
    
    echo "Calendar Service is ready!"
fi

# Execute the main command
exec "$@"