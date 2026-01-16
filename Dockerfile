# Utilise l'image officielle PHP avec Apache
FROM php:8.2-apache

# Copie tous les fichiers dans le dossier web d'Apache
COPY . /var/www/html/

# Active le module rewrite si tu utilises des routes
RUN a2enmod rewrite

# Expose le port 80
EXPOSE 80