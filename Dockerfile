# Utilise PHP 8.2 avec Apache
FROM php:8.2-apache

# Installer les dépendances système nécessaires
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    libonig-dev \
    libsqlite3-dev \
    zip \
    curl \
    unzip

# Installer les extensions PHP indispensables
RUN docker-php-ext-install pdo_sqlite mbstring exif pcntl bcmath gd zip

# Activer le module de réécriture Apache (pour les URLs Laravel)
RUN a2enmod rewrite

# Pointer Apache vers le dossier /public de Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copier les fichiers du projet dans le conteneur
COPY . /var/www/html

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Installer les dépendances Laravel sans les outils de dev
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Créer le dossier pour la base SQLite et donner les droits d'écriture
RUN mkdir -p /var/www/html/database/data && \
    touch /var/www/html/database/data/database.sqlite && \
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database/data

# Exposer le port 80
EXPOSE 80

# Lancer les migrations et démarrer Apache
CMD php artisan migrate --force && apache2-foreground
