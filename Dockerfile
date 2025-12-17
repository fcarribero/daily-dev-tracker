FROM php:8.3-apache

# Instalar dependencias del sistema y extensiones PHP
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       git unzip libzip-dev libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar el código del proyecto (será sobrescrito por el volumen en desarrollo)
COPY . /var/www/html

# Asegurar permisos de Laravel
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Entrypoint para preparar SQLite y ejecutar migraciones
COPY scripts/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
