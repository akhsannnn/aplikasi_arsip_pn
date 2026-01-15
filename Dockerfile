FROM php:8.2-apache

# Install Extension yang dibutuhkan
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Mod Rewrite
RUN a2enmod rewrite

# Setup Document Root ke folder public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Set Working Directory
WORKDIR /var/www/html

# Copy semua file project
COPY . /var/www/html

# --- PERBAIKAN 1: LOAD SETTINGAN MAX SIZE ---
# Copy file uploads.ini ke folder konfigurasi PHP agar limit 2GB aktif
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# --- PERBAIKAN 2: IZIN FOLDER YANG BENAR ---
# Buat folder 'public/uploads' (bukan uploads di root) dan beri akses tulis
RUN mkdir -p /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html/public/uploads \
    && chmod -R 775 /var/www/html/public/uploads

# Expose port
EXPOSE 80