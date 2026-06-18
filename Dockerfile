# Simple production Dockerfile for Bionic Cleaning Tracker backend
# ------------------------------------------------------------
# Uses the official PHP‑Apache image (PHP 8.4) and installs Composer.
# This matches the original Dockerfile behaviour but avoids a multi‑stage
# build that caused long‑running apk installations.
# ------------------------------------------------------------
FROM php:8.4-apache-bookworm

# -----------------------------------------------------------------
# System dependencies – install only what the original Dockerfile needs
# -----------------------------------------------------------------
RUN apt-get update && apt-get install -y \
    libpq-dev libicu-dev libzip-dev \
    libpng-dev libfreetype6-dev libjpeg62-turbo-dev \
    libonig-dev libxml2-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql pgsql mysqli pdo_mysql gd intl mbstring zip opcache \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# -----------------------------------------------------------------
# PHP production configuration
# -----------------------------------------------------------------
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php-opcache.ini "$PHP_INI_DIR/conf.d/opcache.ini"

# -----------------------------------------------------------------
# Apache configuration
# -----------------------------------------------------------------
RUN a2enmod rewrite && \
    echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf && \
    a2enconf servername
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# -----------------------------------------------------------------
# Composer (copy from official composer image)
# -----------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# -----------------------------------------------------------------
# Install PHP dependencies (no dev packages)
# -----------------------------------------------------------------
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --no-scripts && rm -rf /root/.composer

# -----------------------------------------------------------------
# Copy application source (preserve permissions for writable dirs)
# -----------------------------------------------------------------
COPY --chown=www-data:www-data . .

# -----------------------------------------------------------------
# Create writable directories required by CodeIgniter
# -----------------------------------------------------------------
RUN mkdir -p writable/cache writable/logs writable/temp writable/uploads \
    && chown -R www-data:www-data writable

# -----------------------------------------------------------------
# Entrypoint script (same as original repo)
# -----------------------------------------------------------------
COPY docker/entrypoint.sh /entrypoint.sh
RUN sed -i 's/\r$//' /entrypoint.sh && chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["./entrypoint.sh"]
