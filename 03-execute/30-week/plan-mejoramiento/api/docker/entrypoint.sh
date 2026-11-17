#!/bin/sh
set -e

php artisan migrate --force
php artisan app:bootstrap-admin

exec php artisan serve --host=0.0.0.0 --port=8000
