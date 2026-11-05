FROM php:8.2-apache

# Install PDO MySQL extension (SQLite is included by default in php:8.2)
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite headers

WORKDIR /var/www/html

# Copy all application files
COPY . /var/www/html/

# Prepare directories and permissions
RUN mkdir -p /var/www/html/.sessions \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html \
    && chmod 777 /var/www/html/.sessions \
    && if [ -f /var/www/html/smart_hostel.sqlite ]; then chmod 666 /var/www/html/smart_hostel.sqlite; fi

# Install entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80 8080 10000

ENTRYPOINT ["docker-entrypoint.sh"]
