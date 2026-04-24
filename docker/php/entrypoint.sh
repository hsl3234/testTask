#!/bin/sh
set -e

cd /var/www/html

mkdir -p var/cache/volt public/assets/components

if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] Installing composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

chmod -R 0777 var/cache || true

if [ -f vendor/bin/phalcon-migrations ]; then
    echo "[entrypoint] Running Phalcon migrations (CLI)..."
    php vendor/bin/phalcon-migrations run --directory=.
fi

exec "$@"
