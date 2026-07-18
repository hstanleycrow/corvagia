#!/bin/sh
set -e

# On a fresh clone the bind-mounted vendor/ is empty; install dependencies once
# so the container is usable straight from `docker compose up`.
if [ ! -f /var/www/html/vendor/autoload.php ]; then
    echo "[entrypoint] vendor/ missing — running composer install..."
    composer install --no-interaction --prefer-dist
fi

exec "$@"
