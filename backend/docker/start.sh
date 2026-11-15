#!/bin/sh
set -eu

php artisan storage:link || true
php artisan migrate --force

# Seed the demo catalog. The seeders use updateOrCreate and are safe on restart.
php artisan db:seed --force

exec apache2-foreground
