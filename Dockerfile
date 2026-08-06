FROM php:8.2-apache

RUN a2enmod rewrite headers expires

RUN apt-get update && apt-get install -y --no-install-recommends \
        default-mysql-client \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql mbstring gd zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader \
    && mkdir -p /var/www/html/logs /var/www/html/imagenes /var/www/html/uploads /var/www/html/backups \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
