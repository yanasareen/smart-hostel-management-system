#!/bin/sh
set -e

# Default PORT to 80 if not provided by host environment (e.g. Render, Railway)
TARGET_PORT="${PORT:-80}"

# Update Apache port listening configuration
sed -i "s/Listen [0-9]*/Listen ${TARGET_PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${TARGET_PORT}>/" /etc/apache2/sites-available/000-default.conf

# Ensure permissions on SQLite file and sessions directory
if [ -f /var/www/html/smart_hostel.sqlite ]; then
    chmod 666 /var/www/html/smart_hostel.sqlite
fi
if [ -d /var/www/html/.sessions ]; then
    chmod 777 /var/www/html/.sessions
fi

echo "Starting Apache on port ${TARGET_PORT}..."
exec apache2-foreground
