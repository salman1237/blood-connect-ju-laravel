#!/bin/sh
set -e

# Wait briefly for the database to be reachable (Dokploy starts containers
# roughly together, so the DB service may not accept connections yet on the
# very first boot).
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database at $DB_HOST:${DB_PORT:-3306}..."
    for i in $(seq 1 30); do
        if php -r "new PDO('mysql:host=$DB_HOST;port=${DB_PORT:-3306}', '$DB_USERNAME', '$DB_PASSWORD');" 2>/dev/null; then
            echo "Database is up."
            break
        fi
        sleep 2
    done
fi

php artisan migrate --force

php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
