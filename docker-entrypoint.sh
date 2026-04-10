#!/bin/bash
set -e

# Wait for database to be ready (supports both PostgreSQL and MySQL)
if [ ! -z "$DB_HOST" ]; then
    DB_PORT=${DB_PORT:-5432}
    echo "Waiting for database at $DB_HOST:$DB_PORT..."
    for i in {1..30}; do
        if nc -z $DB_HOST $DB_PORT 2>/dev/null; then
            echo "✓ Database is ready!"
            break
        fi
        if [ $i -eq 30 ]; then
            echo "✗ Database startup failed after 30 attempts"
            exit 1
        fi
        echo "Database is unavailable - sleeping (attempt $i/30)"
        sleep 1
    done
fi

# Create .env file if it doesn't exist (for production, env vars should be set by Docker/Render)
if [ ! -f /var/www/html/.env ]; then
    echo "Creating .env from environment variables..."
    cat > /var/www/html/.env <<EOF
DB_HOST=${DB_HOST:-localhost}
DB_PORT=${DB_PORT:-5432}
DB_NAME=${DB_NAME:-cdshipping_hub}
DB_USER=${DB_USER:-postgres}
DB_PASS=${DB_PASS:-}
APP_ENV=${APP_ENV:-production}
SITE_PROTOCOL=${SITE_PROTOCOL:-https}
SITE_DOMAIN=${SITE_DOMAIN:-localhost}
SITE_PATH=${SITE_PATH:-/}
SESSION_SECURE_COOKIE=${SESSION_SECURE_COOKIE:-true}
SESSION_COOKIE_SAMESITE=${SESSION_COOKIE_SAMESITE:-Strict}
EOF
    chown www-data:www-data /var/www/html/.env
    chmod 600 /var/www/html/.env
fi

# Run setup script if database hasn't been initialized yet
if [ "$INIT_DB" = "true" ] || [ "$INIT_DB" = "1" ]; then
    echo "Initializing database..."
    cd /var/www/html
    php setup.php
fi

# Start the application
exec "$@"
