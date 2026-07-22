FROM php:8.3-apache AS base

# System deps + PHP extensions. mbstring is required (helpers use mb_* and it is
# not bundled in the official image); pdo_mysql for MariaDB/MySQL. gd and zip are
# not used by the skeleton itself but are what report libraries (e.g.
# hstanleycrow/easyphpreports) need for PDF/spreadsheet output.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libonig-dev libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install pdo pdo_mysql mbstring opcache gd zip \
    && rm -rf /var/lib/apt/lists/*
RUN a2enmod rewrite

# DocumentRoot -> public/, and allow the .htaccess RewriteRules to take effect.
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf \
    && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

WORKDIR /var/www/html
EXPOSE 80

# ----- Development: Composer + Xdebug, installs vendor on first boot -----
FROM base AS development
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN pecl install xdebug && docker-php-ext-enable xdebug
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]

# ----- Production: self-contained image with vendor baked in -----
FROM base AS production
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY composer.json composer.lock /var/www/html/
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --no-interaction \
    --prefer-dist
COPY . /var/www/html
RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data /var/www/html
