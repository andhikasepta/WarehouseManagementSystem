# =====================================================================
# Warehouse Management System (WMS) - Dockerfile
# =====================================================================
# Base: PHP 8.2 with Apache (Debian)
# =====================================================================

FROM php:8.2-apache

# ── System dependencies ──────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    libzip-dev \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# ── PHP extensions ───────────────────────────────────────────────────
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_pgsql \
        gd \
        zip \
        opcache

# ── Apache modules ──────────────────────────────────────────────────
RUN a2enmod rewrite headers expires deflate

# ── Apache virtual-host ─────────────────────────────────────────────
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Update DocumentRoot in the copied config to match container path
RUN sed -i 's|/var/www/html/WarehouseManagementSystem|/var/www/html|g' \
    /etc/apache2/sites-available/000-default.conf

# ── PHP production settings ─────────────────────────────────────────
RUN { \
    echo "display_errors = Off"; \
    echo "log_errors = On"; \
    echo "error_log = /var/log/php_errors.log"; \
    echo "upload_max_filesize = 64M"; \
    echo "post_max_size = 64M"; \
    echo "memory_limit = 256M"; \
    echo "max_execution_time = 120"; \
    echo "session.gc_maxlifetime = 7200"; \
    echo "date.timezone = Asia/Jakarta"; \
    echo "opcache.enable = 1"; \
    echo "opcache.memory_consumption = 128"; \
    echo "opcache.max_accelerated_files = 10000"; \
    echo "opcache.validate_timestamps = 0"; \
} > /usr/local/etc/php/conf.d/wms-production.ini

# ── Copy application source ─────────────────────────────────────────
COPY --chown=www-data:www-data . /var/www/html/

# ── Set correct permissions ─────────────────────────────────────────
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# ── Healthcheck ─────────────────────────────────────────────────────
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

EXPOSE 80

CMD ["apache2-foreground"]
