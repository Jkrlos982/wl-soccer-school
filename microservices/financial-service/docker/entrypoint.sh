#!/bin/bash
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Starting WL School Financial Service...${NC}"

# Function to wait for database
wait_for_db() {
    echo -e "${YELLOW}⏳ Waiting for database connection...${NC}"
    
    until php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; do
        echo -e "${YELLOW}⏳ Database is unavailable - sleeping${NC}"
        sleep 2
    done
    
    echo -e "${GREEN}✅ Database is ready!${NC}"
}

# Create necessary directories
echo -e "${BLUE}📁 Creating necessary directories...${NC}"
mkdir -p /var/www/storage/logs
mkdir -p /var/www/storage/framework/cache
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/bootstrap/cache

# Set proper permissions
echo -e "${BLUE}🔐 Setting permissions...${NC}"
chown -R www-data:www-data /var/www/storage
chown -R www-data:www-data /var/www/bootstrap/cache
chmod -R 775 /var/www/storage
chmod -R 775 /var/www/bootstrap/cache

# Wait for dependencies if not in testing mode
if [ "$APP_ENV" != "testing" ]; then
    wait_for_db
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
    
    # Check if migrations are needed
    if php artisan migrate:status | grep -q "No migrations found"; then
        echo -e "${YELLOW}⚠️  No migrations found, skipping...${NC}"
    else
        # Run migrations with error handling
        if php artisan migrate --force; then
            echo -e "${GREEN}✅ Database migrations completed successfully${NC}"
        else
            echo -e "${RED}❌ Database migrations failed${NC}"
            exit 1
        fi
    fi
    
    # Seed database if in development
    if [ "$APP_ENV" = "local" ] || [ "$APP_ENV" = "development" ]; then
        echo -e "${BLUE}🌱 Seeding database...${NC}"
        if php artisan db:seed --force; then
            echo -e "${GREEN}✅ Database seeding completed successfully${NC}"
        else
            echo -e "${YELLOW}⚠️  Database seeding failed or no seeders found${NC}"
        fi
    fi
fi

# Create storage link
echo -e "${BLUE}🔗 Creating storage link...${NC}"
php artisan storage:link || true

# Clear any existing caches one more time
php artisan optimize:clear

echo -e "${GREEN}✅ WL School Financial Service is ready!${NC}"
echo -e "${BLUE}🌐 Service will be available at: $APP_URL${NC}"
echo -e "${BLUE}📊 Environment: $APP_ENV${NC}"
echo -e "${BLUE}🐛 Debug mode: $APP_DEBUG${NC}"

# Execute the main command
exec "$@"