#!/bin/sh
set -e

echo "Deploying application ..."

# Enter maintenance mode
php artisan down
    # Install dependencies based on lock file
    # composer update --no-interaction

    # Migrate database
    php artisan migrate --force 

    # Note: If you're using queue workers, this is the place to restart them.

    # sudo chmod -R 777 lang

    # php artisan db:seed PermissionSeeder --force

    # Clear cache
    # php artisan route:clear
    # php artisan config:cache

    # Compile assets
    # npm run build

# Exit maintenance mode
php artisan up

echo "Application deployed!"
