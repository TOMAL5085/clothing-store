#!/bin/sh
set -eu

php artisan storage:link || true
php artisan migrate --force

# Seed a new database once. Existing client data is never reset.
product_count="$(php artisan tinker --execute="echo \\App\\Models\\Product::count();" 2>/dev/null | tail -n 1 | tr -d '\r')"
if [ "${product_count}" = "0" ] || [ -z "${product_count}" ]; then
    php artisan db:seed --force
fi

exec apache2-foreground
