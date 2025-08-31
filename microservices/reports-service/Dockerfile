FROM php:8.3-fpm

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
    cron \
    nodejs \
    npm \
    && rm -rf /var/lib/apt/lists/*

# Install wkhtmltopdf from official source
RUN curl -L https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-3/wkhtmltox_0.12.6.1-3.bookworm_amd64.deb -o wkhtmltox.deb \
    && apt-get update \
    && apt-get install -y ./wkhtmltox.deb \
    && rm wkhtmltox.deb \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

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

# Install Node.js dependencies for chart generation
COPY package*.json ./
RUN npm install

# Set permissions
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www/storage
RUN chmod -R 755 /var/www/bootstrap/cache

# Create directories for reports
RUN mkdir -p /var/www/storage/app/reports/generated
RUN mkdir -p /var/www/storage/app/reports/templates
RUN mkdir -p /var/www/storage/app/reports/cache
RUN mkdir -p /var/www/storage/app/charts

# Configure supervisor
RUN mkdir -p /var/log/supervisor
COPY docker/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/supervisor/laravel-worker.conf /etc/supervisor/conf.d/
COPY docker/supervisor/horizon.conf /etc/supervisor/conf.d/

# Configure cron for scheduled reports
COPY docker/cron/reports-cron /etc/cron.d/reports-cron
RUN chmod 0644 /etc/cron.d/reports-cron
RUN crontab /etc/cron.d/reports-cron

EXPOSE 9000
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]