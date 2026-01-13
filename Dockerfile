FROM php:8.2-apache

# Install Extension yang dibutuhkan
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Mod Rewrite untuk clean URL (jika nanti dibutuhkan)
RUN a2enmod rewrite

# Ubah Document Root Apache ke /var/www/html/public
# Ini adalah standar keamanan industri agar folder 'src' tidak bisa diakses browser
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Set Working Directory
WORKDIR /var/www/html

# Copy semua file project
COPY . /var/www/html

# Set Permission untuk folder uploads agar bisa diisi file
# Pastikan folder 'uploads' ada sebelum chown
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads