FROM php:8.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY BookManager/ /var/www/html/
COPY database/ /opt/book-library/database/
COPY tests/ /opt/book-library/tests/

EXPOSE 80