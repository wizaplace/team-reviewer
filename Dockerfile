FROM php:7.1-apache

RUN apt-get update && apt-get -y install \
        libzip-dev \
    --no-install-recommends && rm -r /var/lib/apt/lists/* \
    && docker-php-ext-install -j$(nproc) zip

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY composer.* /var/www/html/

RUN composer install --no-dev --no-progress --no-interaction --prefer-dist --optimize-autoloader

COPY . /var/www/html
RUN a2enmod rewrite
COPY vhost.conf /etc/apache2/sites-available/000-default.conf

VOLUME ["/var/www/html/config.php", "/var/www/html/repos.dat"]
