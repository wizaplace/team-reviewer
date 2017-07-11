FROM php:7.1-apache

RUN apt-get update && apt-get -y install \
        libzip-dev \
    --no-install-recommends && rm -r /var/lib/apt/lists/* \
    && docker-php-ext-install -j$(nproc) zip

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('SHA384', 'composer-setup.php') === '669656bab3166a7aff8a7506b8cb2d1c292f042046c5a994c43155c0be6190fa0355160742ab2e1c88d40d5be660b410') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');" \
    && chmod +x /usr/local/bin/composer

COPY composer.json /var/www/html/
COPY composer.lock /var/www/html/

RUN composer install --no-dev --no-progress --no-interaction --prefer-dist --optimize-autoloader

COPY . /var/www/html
RUN a2enmod rewrite
COPY vhost.conf /etc/apache2/sites-available/000-default.conf

VOLUME ["/var/www/html/config.php", "/var/www/html/repos.dat"]
