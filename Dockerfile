# === Étape 1 : Image de base PHP 8.2 avec Apache ===
FROM php:8.2-apache AS base

# === Étape 2 : Installer les dépendances système et GD ===
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
    && a2enmod rewrite

# === Étape 3 : Installer Composer ===
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# === Étape 4 : Définir le répertoire de travail ===
WORKDIR /var/www/html

# === Étape 5 : Copier les fichiers composer et package pour cache ===
COPY composer.json composer.lock ./
COPY package.json package-lock.json ./

# === Étape 6 : Installer les dépendances PHP et Node ===
RUN composer install --no-dev --optimize-autoloader \
    && npm ci \
    && npm prune --production

# === Étape 7 : Copier tout le projet ===
COPY . .

# === Étape 8 : Créer les répertoires de stockage et définir les permissions ===
RUN mkdir -p storage/framework/{sessions,views,cache,testing} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# === Étape 9 : Build front-end si nécessaire (Vite, Webpack, etc.) ===
RUN npm run build

# === Étape 10 : Cacher les fichiers Laravel pour production ===
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan event:cache

# === Étape 11 : Exposer le port 80 pour Apache ===
EXPOSE 80

# === Étape 12 : Démarrer Apache en premier plan ===
CMD ["apache2-foreground"]
