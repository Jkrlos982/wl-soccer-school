#!/bin/bash

# WL School Notification Service Startup Script
# This script handles the initialization and startup of the notification service

set -e

echo "Starting WL School Notification Service..."

# Wait for database to be ready
echo "Waiting for database connection..."
until php artisan migrate:status > /dev/null 2>&1; do
    echo "Database not ready, waiting..."
    sleep 2
done

echo "Database connection established!"

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Clear and cache configuration
echo "Optimizing application..."
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage directories if they don't exist
echo "Setting up storage directories..."
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views

# Set proper permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Start PHP-FPM in background
echo "Starting PHP-FPM..."
php-fpm -D

# Start Supervisor (for queue workers)
echo "Starting Supervisor..."
supervisord -c /etc/supervisor/conf.d/supervisord.conf -n &

# Start Nginx
echo "Starting Nginx..."
nginx -g "daemon off;"