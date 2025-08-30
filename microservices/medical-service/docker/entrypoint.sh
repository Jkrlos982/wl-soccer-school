#!/bin/bash

# Exit on any error
set -e

# Wait for database to be ready
echo "Waiting for database connection..."
while ! nc -z $DB_HOST $DB_PORT; do
  sleep 1
done
echo "Database is ready!"

# Create log directories
mkdir -p /var/log/supervisor
mkdir -p /var/log/nginx

# Set proper permissions
chown -R www-data:www-data /var/www/storage
chown -R www-data:www-data /var/www/bootstrap/cache
chmod -R 775 /var/www/storage
chmod -R 775 /var/www/bootstrap/cache

# Clear and cache Laravel configurations
echo "Optimizing Laravel..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Publish vendor assets if needed
php artisan vendor:publish --tag=laravel-medialibrary-migrations --force
php artisan vendor:publish --tag=spatie-permission-migrations --force
php artisan vendor:publish --tag=activitylog-migrations --force

# Cache configurations for production
if [ "$APP_ENV" = "production" ]; then
    echo "Caching configurations for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Create medical directories if they don't exist
mkdir -p /var/www/storage/app/medical/records
mkdir -p /var/www/storage/app/medical/certificates
mkdir -p /var/www/storage/app/medical/exams
chown -R www-data:www-data /var/www/storage/app/medical

echo "Starting services..."

# Start supervisor (which will start nginx and php-fpm)
exec supervisord -c /etc/supervisor/supervisord.conf