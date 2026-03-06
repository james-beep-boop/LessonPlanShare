#!/bin/bash
set -e

cd /var/www/html

# Write .env from Docker environment variables
cat > .env <<EOF
APP_NAME="${APP_NAME:-Lesson Plan Exchange}"
APP_ENV=${APP_ENV:-local}
APP_KEY=
APP_DEBUG=${APP_DEBUG:-true}
APP_URL=${APP_URL:-http://localhost:8080}

DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST:-db}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-lessonplanshare}
DB_USERNAME=${DB_USERNAME:-laravel}
DB_PASSWORD=${DB_PASSWORD:-secret}

MAIL_MAILER=${MAIL_MAILER:-log}
MAIL_FROM_ADDRESS="noreply@localhost"
MAIL_FROM_NAME="${APP_NAME:-Lesson Plan Exchange}"

SESSION_DRIVER=${SESSION_DRIVER:-file}
CACHE_STORE=${CACHE_STORE:-file}
FILESYSTEM_DISK=${FILESYSTEM_DISK:-public}
EOF

# Generate APP_KEY (safe to run every start — only sets it if empty)
php artisan key:generate --force

# Wait for MySQL and run migrations
php artisan config:clear
php artisan migrate --force

# Create the storage symlink (public/storage -> storage/app/public)
php artisan storage:link --force

# Build config/route/view caches for faster responses
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Hand off to Apache
exec "$@"
