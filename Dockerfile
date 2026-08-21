# =====================================================================
# Warehouse Management System (WMS) - Dockerfile
# =====================================================================
# Base: PHP 8.2 + Apache (Debian Bookworm)
# Features:
#   - MySQL & PostgreSQL PDO drivers (dual-driver support)
#   - Apache mod_rewrite, mod_headers, mod_expires enabled
#   - GD & ZIP extensions for file/image handling
#   - Auto-migration on container startup
#   - Non-root runtime with www-data
# =====================================================================

FROM php:8.2-apache AS base

# ── System dependencies & PHP extensions ─────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
        libpq-dev \
        unzip \
        curl \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_pgsql \
        gd \
        zip \
        opcache \
    && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false \
    && rm -rf /var/lib/apt/lists/*

# ── Apache configuration ────────────────────────────────────────────
RUN a2enmod rewrite headers expires

# Set ServerName to suppress warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Configure Apache VirtualHost for /var/www/html
RUN sed -i 's|/var/www/html|/var/www/html|g' /etc/apache2/sites-available/000-default.conf \
    && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Ensure AllowOverride All for /var/www/html so .htaccess works
RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/wms-override.conf \
    && a2enconf wms-override

# ── PHP configuration ───────────────────────────────────────────────
RUN { \
    echo "upload_max_filesize = 64M"; \
    echo "post_max_size = 64M"; \
    echo "max_execution_time = 300"; \
    echo "max_input_time = 300"; \
    echo "memory_limit = 256M"; \
    echo "date.timezone = Asia/Jakarta"; \
    echo "session.gc_maxlifetime = 7200"; \
    echo "session.cookie_httponly = 1"; \
    echo "expose_php = Off"; \
} > /usr/local/etc/php/conf.d/wms.ini

# ── OPcache for production performance ──────────────────────────────
RUN { \
    echo "opcache.enable=1"; \
    echo "opcache.memory_consumption=128"; \
    echo "opcache.interned_strings_buffer=16"; \
    echo "opcache.max_accelerated_files=10000"; \
    echo "opcache.revalidate_freq=0"; \
    echo "opcache.validate_timestamps=0"; \
    echo "opcache.save_comments=1"; \
    echo "opcache.fast_shutdown=1"; \
} > /usr/local/etc/php/conf.d/opcache.ini

# ── Application code ────────────────────────────────────────────────
WORKDIR /var/www/html

# Copy application files
COPY . .

# ── Directory permissions ───────────────────────────────────────────
RUN mkdir -p /var/www/html/uploads/repository \
    && chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && chmod -R 775 /var/www/html/uploads

# ── Entrypoint script ───────────────────────────────────────────────
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
