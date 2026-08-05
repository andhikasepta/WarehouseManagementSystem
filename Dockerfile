FROM php:8.2-apache

# 1. Instal ekstensi PHP dan MySQL Server
RUN apt-get update && apt-get install -y \
    mariadb-server \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd \
    && a2enmod rewrite

# 2. Salin source code aplikasi ke folder Apache
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

# 3. Buat file .env otomatis di dalam kontainer berdasarkan konfigurasi Anda
RUN echo 'APP_ENV=production' > /var/www/html/.env && \
    echo 'APP_NAME="Warehouse Management System - Lintasarta"' >> /var/www/html/.env && \
    echo 'APP_URL=http://localhost:7860' >> /var/www/html/.env && \
    echo 'APP_TIMEZONE=Asia/Jakarta' >> /var/www/html/.env && \
    echo 'DB_DRIVER=mysql' >> /var/www/html/.env && \
    echo 'DB_HOST=127.0.0.1' >> /var/www/html/.env && \
    echo 'DB_USER=wms_user' >> /var/www/html/.env && \
    echo 'DB_PASSWORD=wms_secure_password' >> /var/www/html/.env && \
    echo 'DB_NAME=dashboard_db' >> /var/www/html/.env && \
    echo 'SESSION_LIFETIME=7200' >> /var/www/html/.env && \
    echo 'SESSION_SECURE=false' >> /var/www/html/.env

# 4. Konfigurasi Port wajib Hugging Face (7860)
RUN sed -i 's/Listen 80/Listen 7860/g' /etc/apache2/ports.conf && \
    sed -i 's/<VirtualHost \*:80>/<VirtualHost \*:7860>/g' /etc/apache2/sites-available/000-default.conf

EXPOSE 7860

# 5. Script untuk menyalakan MySQL, membuat database, dan menjalankan Apache
CMD service mariadb start && \
    mysql -e "CREATE DATABASE IF NOT EXISTS dashboard_db;" && \
    mysql -e "CREATE USER IF NOT EXISTS 'wms_user'@'localhost' IDENTIFIED BY 'wms_secure_password';" && \
    mysql -e "GRANT ALL PRIVILEGES ON dashboard_db.* TO 'wms_user'@'localhost';" && \
    mysql -e "FLUSH PRIVILEGES;" && \
    (mysql dashboard_db < /var/www/html/database.sql || mysql dashboard_db < /var/www/html/db.sql || echo "No SQL dump found") && \
    apache2-foreground
