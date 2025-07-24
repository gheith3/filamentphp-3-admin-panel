#!/bin/sh

# Laravel Docker Entrypoint Script
echo "🚀 Starting Smart Meter Challenge Backend..."

# Wait for database if using external database
if [ "$DB_CONNECTION" = "mysql" ] && [ -n "$DB_HOST" ]; then
    echo "⏳ Waiting for database connection..."
    until nc -z "$DB_HOST" "${DB_PORT:-3306}"; do
        echo "Database not ready - sleeping..."
        sleep 2
    done
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