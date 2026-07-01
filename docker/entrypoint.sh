#!/bin/sh
set -e

# Wait for MySQL to become available
until php -r "
    try {
        \$pdo = new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
        exit(0);
    } catch (PDOException \$e) {
        fwrite(STDERR, 'Database connection failed: ' . \$e->getMessage() . PHP_EOL);
        exit(1);
    }
" > /dev/null 2>&1; do
    echo "Waiting for database connection..."
    sleep 2
done

echo "Database is online!"

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Optimize Laravel configurations
echo "Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Execute the container's main command (php-fpm)
echo "Starting PHP-FPM..."
exec php-fpm
