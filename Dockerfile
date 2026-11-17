FROM php:8.2-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring gd opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

COPY composer.json ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY . .
RUN composer dump-autoload --optimize --no-interaction \
    && mkdir -p storage/logs storage/cache storage/sessions storage/tmp public/uploads \
    && chown -R www-data:www-data storage public/uploads \
    && chmod -R ug+rwX storage public/uploads

# Coolify injecte les variables (DATABASE_URL / DB_*) — pas de .env dans l’image
EXPOSE 80
