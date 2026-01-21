# Gunakan PHP versi terbaru dengan Apache
FROM php:8.2-apache

# 1. Install Ekstensi PHP yang Wajib (MySQLi)
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# 2. Aktifkan Mod Rewrite (Agar URL cantik bisa jalan, jika nanti butuh)
RUN a2enmod rewrite

# 3. Setting Upload Limit (Agar bisa upload file besar)
# Kita copy file settingan khusus
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# 4. Set Work Directory
WORKDIR /var/www/html

# 5. Berikan Hak Akses ke Folder www-data (Agar PHP bisa tulis file upload)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80 (Port standar web server di dalam container)
EXPOSE 80