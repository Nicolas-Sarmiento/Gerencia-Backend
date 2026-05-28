#!/bin/sh
set -e

# Ensure SQLite database exists if DB_CONNECTION is sqlite
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    echo "Setting up SQLite database..."
    if [ -z "$DB_DATABASE" ]; then
        export DB_DATABASE="/var/www/html/database/database.sqlite"
    fi
    DB_DIR=$(dirname "$DB_DATABASE")
    mkdir -p "$DB_DIR"
    touch "$DB_DATABASE"
    chmod 777 "$DB_DIR" "$DB_DATABASE"
fi

# Clear caches and cache configuration for production speed
echo "Caching Laravel configuration and routes..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
echo "Running migrations..."
php artisan migrate --force

# Create public storage symlink
echo "Creating storage link..."
php artisan storage:link --force

# Execute the container's main process (Apache)
exec "$@"
