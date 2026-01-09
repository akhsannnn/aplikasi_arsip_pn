FROM php:8.2-apache

# Install extension MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy source code
COPY ./app /var/www/html
