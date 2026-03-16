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

RUN rm -f bootstrap/cache/config.php bootstrap/cache/routes*.php bootstrap/cache/services.php

RUN echo "post_max_size=50M" > /usr/local/etc/php/conf.d/uploads.ini && \
    echo "upload_max_filesize=50M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_input_vars=10000" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/uploads.ini

ENTRYPOINT ["/bin/sh", "-c"]

CMD ["chmod -R 777 storage bootstrap/cache && php artisan config:clear && php artisan cache:clear && php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && php -S 0.0.0.0:${PORT:-8080} -t public"]
