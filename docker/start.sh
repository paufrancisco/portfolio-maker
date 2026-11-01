#!/bin/bash
set -e
cd /var/www/html

php artisan package:discover --ansi
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan db:seed --class=TemplateSeeder --force

chown -R www-data:www-data storage bootstrap/cache

exec supervisord -c /etc/supervisord.conf
