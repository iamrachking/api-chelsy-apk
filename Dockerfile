# === Étape 1 : Image de base PHP 8.2 avec Apache ===
FROM php:8.2-apache

# === Étape 2 : Installer les extensions PHP et outils ===
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip opcache \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# === Étape 3 : Installer Composer globalement ===
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# === Étape 4 : Définir le répertoire de travail ===
WORKDIR /var/www/html

# === Étape 5 : Copier tout le projet AVANT l'installation ===
COPY . .

# === Étape 6 : Installer les dépendances PHP et Node correctement ===
RUN composer install --no-dev --ignore-platform-reqs --optimize-autoloader \
    && composer dump-autoload \
    && php artisan package:discover --ansi || true \
    && npm ci \
    && npm prune --production

# === Étape 7 : Créer les dossiers nécessaires et donner les permissions ===
RUN mkdir -p storage/framework/{sessions,views,cache,testing} storage/logs bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache

# === Étape 8 : Configurer le port pour Railway ===
EXPOSE 8080

# === Étape 9 : Commande de démarrage pour Apache ===
CMD ["apache2-foreground"]
