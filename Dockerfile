FROM php:8.2-apache

# Active mod_rewrite pour le routage des boutiques (/ma-boutique)
RUN a2enmod rewrite

# Installe les extensions PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Copie l'application PhoenixKA Shop
COPY . /var/www/html/

# Configurer les permissions
WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
