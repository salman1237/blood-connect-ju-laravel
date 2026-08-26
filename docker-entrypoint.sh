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

# One-time production data import: runs unless the dump has already been
# imported, so this is safe to leave in place across restarts/redeploys. This
# MUST run before `artisan migrate` -- the dump is a full mysqldump that DROPs
# and recreates every table it touches (including `migrations`), so running
# migrate first (which creates its own fresh schema) and importing after
# causes the import to clobber tables migrate just created/recorded, and on
# any later boot before the import has fully landed, migrate and the import
# can race and leave the schema in a half-created, unrecoverable state.
#
# Detection is keyed on the `donor_profiles` table specifically (a business
# table only ever created by the dump import, never by a fresh `artisan
# migrate`) rather than "any tables exist" -- a raw table-count check gets
# fooled by stray Laravel-default tables left behind from an
# interrupted/raced boot, permanently skipping the import. Once the import
# actually runs, its own `DROP TABLE IF EXISTS` statements clean up any such
# stray tables anyway, so nothing needs to be pre-emptively wiped here.
if [ -f /var/www/html/import.sql ] && [ -n "$DB_HOST" ]; then
    mysql -h "$DB_HOST" -P "${DB_PORT:-3306}" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT GET_LOCK('blood_connect_ju_boot', 60);" >/dev/null 2>&1 || true

    DONOR_PROFILES_TABLE_EXISTS=$(mysql -N -h "$DB_HOST" -P "${DB_PORT:-3306}" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e \
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_DATABASE' AND table_name='donor_profiles';" 2>/dev/null | tail -1)
    if [ "$DONOR_PROFILES_TABLE_EXISTS" = "0" ]; then
        echo "Production data not yet imported, importing from import.sql..."
        mysql -h "$DB_HOST" -P "${DB_PORT:-3306}" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < /var/www/html/import.sql
        echo "Import complete."
    else
        echo "Production data already imported, skipping import."
    fi

    mysql -h "$DB_HOST" -P "${DB_PORT:-3306}" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT RELEASE_LOCK('blood_connect_ju_boot');" >/dev/null 2>&1 || true
fi

php artisan migrate --force

php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
