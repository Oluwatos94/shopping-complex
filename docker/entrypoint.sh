#!/bin/sh
set -e

# Explicit var list so envsubst leaves nginx's own $host/$uri alone.
export PORT="${PORT:-8000}"
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

cd /app

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link || true

# The platform can restart this container while its database is still booting.
echo "Waiting for database..."
i=0
until php -r '
    $host = getenv("DB_HOST") ?: "127.0.0.1";
    $port = getenv("DB_PORT") ?: "3306";
    $name = getenv("DB_DATABASE");
    try {
        new PDO("mysql:host={$host};port={$port};dbname={$name}", getenv("DB_USERNAME"), getenv("DB_PASSWORD"), [PDO::ATTR_TIMEOUT => 3]);
        exit(0);
    } catch (Throwable $e) {
        exit(1);
    }
' 2>/dev/null; do
    i=$((i + 1))
    if [ "$i" -ge 60 ]; then
        echo "Database did not become reachable after 120s; aborting." >&2
        exit 1
    fi
    sleep 2
done
echo "Database is up."

# --isolated locks via cache_locks, which doesn't exist until this migration runs.
php artisan migrate --force --path=database/migrations/0001_01_01_000001_create_cache_table.php
php artisan migrate --force --isolated

php artisan db:seed --class=CategorySeeder --force
php artisan db:seed --class=SubscriptionPlanSeeder --force
php artisan db:seed --class=AdminSeeder --force

# The steps above run as root; anything they wrote (notably storage/logs) would
# otherwise be unwritable by the www-data workers.
chown -R www-data:www-data /app/storage /app/bootstrap/cache

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
