#!/bin/sh
set -e

cd /var/www/html

# Asegurar que el archivo de base de datos SQLite existe
if [ ! -f "database/database.sqlite" ]; then
  mkdir -p database
  touch database/database.sqlite
  chown www-data:www-data database/database.sqlite
fi

# Instalar dependencias si no existe vendor
if [ ! -d "vendor" ]; then
  composer install --no-interaction --prefer-dist
fi

# Generar APP_KEY si está vacío
if grep -q '^APP_KEY=$' .env 2>/dev/null; then
  php artisan key:generate --force
fi

# Migraciones (crea tablas si faltan)
php artisan migrate --force || true

exec "$@"
