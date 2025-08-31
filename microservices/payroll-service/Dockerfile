FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    wkhtmltopdf \
    supervisor \
    cron \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

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
RUN chmod -R 755 /var/www/bootstrap/cache

# Create directories for payroll files
RUN mkdir -p /var/www/storage/app/payroll/receipts
RUN mkdir -p /var/www/storage/app/payroll/reports
RUN mkdir -p /var/www/storage/app/payroll/backups

# Configure supervisor
COPY docker/supervisor/laravel-worker.conf /etc/supervisor/conf.d/

# Configure cron for payroll automation
COPY docker/cron/payroll-cron /etc/cron.d/payroll-cron
RUN chmod 0644 /etc/cron.d/payroll-cron
RUN crontab /etc/cron.d/payroll-cron

EXPOSE 9000
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]