#!/bin/sh
set -e

# Start PHP-FPM in the background
php-fpm -D

# Wait briefly to ensure PHP-FPM is ready before Nginx starts accepting requests
sleep 1

# Start Nginx in the foreground (keeps the container alive)
exec nginx -g "daemon off;"
