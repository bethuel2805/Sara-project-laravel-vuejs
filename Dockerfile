FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    libonig-dev \
    libsqlite3-dev \
    zip \
    curl \
    unzip

RUN docker-php-ext-install pdo_sqlite mbstring exif pcntl bcmath gd zip

RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY laravel-back/ /var/www/html/

# Copier explicitement le fichier .env si nécessaire
# COPY laravel-back/.env /var/www/html/.env

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN composer install --no-interaction --optimize-autoloader --no-dev

RUN php artisan config:cache && php artisan route:cache

RUN mkdir -p database/data && \
    touch database/data/database.sqlite && \
    chown -R www-data:www-data storage bootstrap/cache database/data && \
    chmod -R 775 storage bootstrap/cache database/data

EXPOSE 80

# Script d'entrée pour lancer les migrations puis Apache
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
