FROM php:8.4-apache-trixie

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/* \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

COPY BookManager/ /var/www/html/
COPY database/ /opt/book-library/database/
COPY tests/api_smoke.py /opt/book-library/tests/api_smoke.py

EXPOSE 80
