# =====================================================================
# Warehouse Management System (WMS) - Dockerfile
# =====================================================================
# Multi-stage production build: PHP 8.2 + Apache
# MySQL database driver
# =====================================================================

FROM php:8.2-apache AS base

LABEL maintainer="andhikasepta"
LABEL description="Warehouse Management System - Lintasarta"

# ── Install system dependencies & PHP extensions ─────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        unzip \
        curl \
    && docker-php-ext-install \
        pdo_mysql \
        zip \
        opcache \
    && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false \
    && rm -rf /var/lib/apt/lists/*

# ── Enable Apache modules ───────────────────────────────────────────
RUN a2enmod rewrite headers expires

# ── Configure Apache VirtualHost ────────────────────────────────────
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html\n\
    <Directory /var/www/html>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# ── PHP production configuration ────────────────────────────────────
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY <<EOF $PHP_INI_DIR/conf.d/wms-custom.ini
; WMS Custom PHP Configuration
upload_max_filesize = 64M
post_max_size = 64M
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
session.gc_maxlifetime = 7200

; OPcache settings for production
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 0
opcache.revalidate_freq = 0

; Timezone
date.timezone = Asia/Jakarta
EOF

# ── Set working directory ───────────────────────────────────────────
WORKDIR /var/www/html

# ── Copy application source ────────────────────────────────────────
COPY . .

# ── Ensure uploads directory exists with proper permissions ─────────
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads

# ── Copy and prepare entrypoint ─────────────────────────────────────
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# ── Expose port & set entrypoint ────────────────────────────────────
EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
