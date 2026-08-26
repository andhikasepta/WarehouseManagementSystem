# =====================================================================
# Warehouse Management System (WMS) - Dockerfile
# =====================================================================
# Production build: PHP 8.2 + Apache
# Supports both MySQL and PostgreSQL database drivers
# Target Host: 103.123.100.12
# =====================================================================

FROM php:8.2-apache

LABEL maintainer="andhikasepta"
LABEL description="Warehouse Management System - Lintasarta"

# ── Install system dependencies & PHP extensions ─────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        unzip \
        curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false \
    && rm -rf /var/lib/apt/lists/*

# ── Enable Apache modules ───────────────────────────────────────────
RUN a2enmod rewrite headers expires

# ── Configure Apache VirtualHost ────────────────────────────────────
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# ── PHP production configuration ────────────────────────────────────
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/custom.ini $PHP_INI_DIR/conf.d/wms-custom.ini

# ── Set working directory ───────────────────────────────────────────
WORKDIR /var/www/html

# ── Copy application source code ────────────────────────────────────
COPY . /var/www/html/

# ── Ensure uploads directory exists with proper permissions ─────────
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads

# ── Copy and prepare entrypoint ─────────────────────────────────────
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

# ── Expose container HTTP port ──────────────────────────────────────
EXPOSE 80

# ── Set Entrypoint & Default Command ────────────────────────────────
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
