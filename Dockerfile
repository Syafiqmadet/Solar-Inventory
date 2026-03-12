FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libzip-dev libxml2-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring xml ctype fileinfo zip gd \
    && apt-get clean

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

RUN chmod -R 777 storage bootstrap/cache

CMD echo "Starting..." && \
    echo "PORT is: $PORT" && \
    echo "DB_HOST is: $DB_HOST" && \
    php artisan config:clear && \
    echo "Config cleared" && \
    php artisan migrate --force && \
    echo "Migrated" && \
    php -S 0.0.0.0:${PORT:-8080} -t public && \
    echo "Server started"
