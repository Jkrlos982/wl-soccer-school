#!/bin/bash
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Starting WL School Auth Service...${NC}"

# Function to wait for database
wait_for_db() {
    echo -e "${YELLOW}⏳ Waiting for database connection...${NC}"
    
    until php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; do
        echo -e "${YELLOW}⏳ Database is unavailable - sleeping${NC}"
        sleep 2
    done
    
    echo -e "${GREEN}✅ Database is ready!${NC}"
}

# Function to wait for Redis
wait_for_redis() {
    echo -e "${YELLOW}⏳ Waiting for Redis connection...${NC}"
    
    until php artisan tinker --execute="Redis::ping();" > /dev/null 2>&1; do
        echo -e "${YELLOW}⏳ Redis is unavailable - sleeping${NC}"
        sleep 2
    done
    
    echo -e "${GREEN}✅ Redis is ready!${NC}"
}

# Create necessary directories
echo -e "${BLUE}📁 Creating necessary directories...${NC}"
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/log/supervisor

# Set proper permissions
echo -e "${BLUE}🔐 Setting permissions...${NC}"
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Wait for dependencies if not in testing mode
if [ "$APP_ENV" != "testing" ]; then
    wait_for_db
    wait_for_redis
fi

# Clear and cache configuration
echo -e "${BLUE}⚙️  Optimizing application...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Cache configuration for production
if [ "$APP_ENV" = "production" ]; then
    echo -e "${BLUE}🏭 Production optimizations...${NC}"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Run database migrations
if [ "$APP_ENV" != "testing" ]; then
    echo -e "${BLUE}🗄️  Running database migrations...${NC}"
    php artisan migrate --force
    
    # Seed database if in development
    if [ "$APP_ENV" = "local" ] || [ "$APP_ENV" = "development" ]; then
        echo -e "${BLUE}🌱 Seeding database...${NC}"
        php artisan db:seed --force
    fi
fi

# Create storage link
echo -e "${BLUE}🔗 Creating storage link...${NC}"
php artisan storage:link || true

# Generate JWT secret if not exists
if [ -z "$JWT_SECRET" ]; then
    echo -e "${YELLOW}🔑 Generating JWT secret...${NC}"
    php artisan jwt:secret
fi

# Install/update Passport keys if needed
if [ "$APP_ENV" != "testing" ]; then
    echo -e "${BLUE}🔐 Setting up authentication keys...${NC}"
    # php artisan passport:keys --force || true
fi

# Clear any existing caches one more time
php artisan optimize:clear

echo -e "${GREEN}✅ WL School Auth Service is ready!${NC}"
echo -e "${BLUE}🌐 Service will be available at: $APP_URL${NC}"
echo -e "${BLUE}📊 Environment: $APP_ENV${NC}"
echo -e "${BLUE}🐛 Debug mode: $APP_DEBUG${NC}"

# Execute the main command
exec "$@"