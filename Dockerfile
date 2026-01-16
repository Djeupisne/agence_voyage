FROM php:8.2-apache

# Installe les dépendances système et les extensions PHP
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libxml2-dev \
    libonig-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    mysqli \
    pdo \
    pdo_mysql \
    mbstring \
    xml \
    zip \
    exif \
    pcntl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Active le module Apache rewrite
RUN a2enmod rewrite

# Configure le fuseau horaire PHP (optionnel)
RUN echo "date.timezone = Europe/Paris" > /usr/local/etc/php/conf.d/timezone.ini

# Copie les fichiers de l'application
COPY . /var/www/html/

# Définit les permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80