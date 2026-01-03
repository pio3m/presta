FROM prestashop/prestashop:8.1.5-php8.2-apache

RUN apt-get update && apt-get install -y git curl unzip nano  && rm -rf /var/lib/apt/lists/*

RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN a2enmod rewrite headers
WORKDIR /var/www/html