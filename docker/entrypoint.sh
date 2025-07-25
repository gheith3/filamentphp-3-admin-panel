#!/bin/sh

# Laravel Docker Entrypoint Script
echo "🚀 Starting Smart Meter Challenge Backend..."



if [ "$DB_CONNECTION" != "sqlite" ] && [ -n "$DB_HOST" ]; then
    echo "⏳ Waiting for database connection..."
    if [ "$DB_CONNECTION" = "pgsql" ]; then
        until pg_isready -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE"; do
            echo "PostgreSQL not ready - sleeping..."
            sleep 2
        done
    elif [ "$DB_CONNECTION" = "mysql" ] || [ "$DB_CONNECTION" = "mariadb" ]; then
        until mysqladmin ping -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent; do
            echo "MySQL/MariaDB not ready - sleeping..."
            sleep 2
        done
    elif [ "$DB_CONNECTION" = "sqlsrv" ]; then
        # Try to connect to SQL Server using sqlcmd if available
        until sqlcmd -S "$DB_HOST,$DB_PORT" -U "$DB_USERNAME" -P "$DB_PASSWORD" -Q "SELECT 1" >/dev/null 2>&1; do
            echo "SQL Server not ready - sleeping..."
            sleep 2
        done
    fi
    echo "✅ Database connection established"
fi

# Ensure .env exists
if [ ! -f /var/www/html/.env ]; then
    echo "📝 Creating .env file from template..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Generate application key if not set
if ! grep -q "APP_KEY=" /var/www/html/.env || grep -q "APP_KEY=$" /var/www/html/.env; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
fi

# Create storage directories if they don't exist
echo "📁 Setting up storage directories..."
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/bootstrap/cache

# Set proper permissions
echo "🔒 Setting permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache



# Run database migrations if requested
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "🗄️ Running database migrations..."
    php artisan migrate --force
    php artisan db:seed
fi

# Clear and cache configuration (only if not already cached)
echo "⚡ Optimizing application..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache

echo "✅ Laravel initialization complete!"

# Ensure Supervisor log directory exists
echo "🎯 Ensuring supervisor log directory exists..."
mkdir -p /var/log/supervisor
chown -R www-data:www-data /var/log/supervisor

# Ensure Nginx log directory exists
echo "📁 Ensuring nginx log directory exists..."
mkdir -p /var/log/nginx
chown -R www-data:www-data /var/log/nginx

# Start supervisor to manage all processes
echo "🎯 Starting services with supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf